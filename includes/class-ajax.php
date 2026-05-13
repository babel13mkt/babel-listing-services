<?php
/**
 * Lógica AJAX (BD_AJAX)
 * v3.7 — Agregado soporte para Reservas (Hito 7).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BD_AJAX {

    public function __construct() {
        add_action( 'wp_ajax_bd_filter_listings', array( $this, 'filter_listings' ) );
        add_action( 'wp_ajax_nopriv_bd_filter_listings', array( $this, 'filter_listings' ) );
        
        // Reservas
    }


    /**
     * Filtros de listados (Hito 6)
     */
    public function filter_listings() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bd_ajax_nonce' ) ) {
            wp_send_json_error( 'Seguridad fallida' );
        }

        global $wpdb;
        $table_index = $wpdb->prefix . 'bd_search_index';

        $keyword = isset( $_POST['keyword'] ) ? sanitize_text_field( $_POST['keyword'] ) : '';
        $cat     = isset( $_POST['category'] ) ? sanitize_text_field( $_POST['category'] ) : '';
        $region  = isset( $_POST['region'] ) ? sanitize_text_field( $_POST['region'] ) : '';
        $sort    = isset( $_POST['sort'] ) ? sanitize_text_field( $_POST['sort'] ) : 'newest';
        $paged   = isset( $_POST['paged'] ) ? intval( $_POST['paged'] ) : 1;
        
        $lat    = isset( $_POST['lat'] ) ? floatval( $_POST['lat'] ) : 0;
        $lng    = isset( $_POST['lng'] ) ? floatval( $_POST['lng'] ) : 0;
        $radius = isset( $_POST['radius'] ) ? intval( $_POST['radius'] ) : 50;

        $posts_per_page = 12;
        $offset = ( $paged - 1 ) * $posts_per_page;

        // Construir consulta SQL base sobre el índice
        $where = array( "1=1" );
        $join = "";
        
        // Filtro por Categoría
        if ( ! empty( $cat ) ) {
            $term = get_term_by( 'slug', $cat, 'directorio_categoria' );
            if ( $term ) {
                $where[] = $wpdb->prepare( "idx.category_id = %d", $term->term_id );
            }
        }

        // Filtro por Región
        if ( ! empty( $region ) ) {
            $term = get_term_by( 'slug', $region, 'directorio_region' );
            if ( $term ) {
                $where[] = $wpdb->prepare( "idx.region_id = %d", $term->term_id );
            }
        }

        // Geocalización Haversine
        $distance_select = "";
        if ( $lat && $lng ) {
            $haversine = $wpdb->prepare(
                "( 6371 * acos( cos( radians(%f) ) * cos( radians( idx.latitude ) ) * cos( radians( idx.longitude ) - radians(%f) ) + sin( radians(%f) ) * sin( radians( idx.latitude ) ) ) )",
                $lat, $lng, $lat
            );
            $distance_select = ", $haversine AS distance";
            if ( $radius > 0 ) {
                $where[] = "($haversine <= " . floatval($radius) . ")";
            }
        }

        // Ordenamiento
        $orderby = "idx.post_id DESC";
        switch ( $sort ) {
            case 'rating':
                $orderby = "idx.rating_avg DESC";
                break;
            case 'az':
                $join .= " INNER JOIN {$wpdb->posts} p ON idx.post_id = p.ID";
                $orderby = "p.post_title ASC";
                break;
            case 'distance':
                if ( $lat && $lng ) {
                    $orderby = "distance ASC";
                }
                break;
            default:
                $orderby = "idx.post_id DESC";
                break;
        }

        // Búsqueda por palabra clave
        if ( ! empty( $keyword ) ) {
            if ( empty( $join ) ) {
                $join .= " INNER JOIN {$wpdb->posts} p ON idx.post_id = p.ID";
            }
            $where[] = $wpdb->prepare( "(p.post_title LIKE %s OR p.post_content LIKE %s)", '%' . $wpdb->esc_like( $keyword ) . '%', '%' . $wpdb->esc_like( $keyword ) . '%' );
        }

        $where_str = implode( " AND ", $where );
        
        // Consulta final de IDs y conteo
        $sql = "SELECT idx.post_id $distance_select FROM $table_index idx $join WHERE $where_str ORDER BY $orderby LIMIT $offset, $posts_per_page";
        $total_sql = "SELECT COUNT(idx.post_id) FROM $table_index idx $join WHERE $where_str";

        $post_ids = $wpdb->get_col( $sql );
        $total_posts = $wpdb->get_var( $total_sql );
        $max_pages = ceil( $total_posts / $posts_per_page );

        ob_start();
        if ( ! empty( $post_ids ) ) {
            echo '<div class="bd-grid bd-cols-3">';
            foreach ( $post_ids as $pid ) {
                BD_Frontend::render_card( $pid );
            }
            echo '</div>';
            
            if ( $max_pages > 1 ) {
                echo '<div class="bd-pagination">';
                echo paginate_links( array(
                    'total'   => $max_pages,
                    'current' => $paged,
                    'type'    => 'plain',
                    'prev_text' => '<i class="fas fa-chevron-left"></i>',
                    'next_text' => '<i class="fas fa-chevron-right"></i>',
                ) );
                echo '</div>';
            }
        } else {
            echo '<div class="bd-no-results-premium">';
            echo '<i class="fas fa-search-minus"></i>';
            echo '<h3>No encontramos nada por aquí</h3>';
            echo '<p>Prueba ajustando los filtros o ampliando el radar de búsqueda.</p>';
            echo '</div>';
        }
        
        $html = ob_get_clean();
        wp_send_json_success( array( 
            'html'  => $html, 
            'count' => $total_posts, 
            'max'   => $max_pages 
        ) );
    }
}
