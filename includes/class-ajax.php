<?php
namespace Babel\Directory;

/**
 * Lógica de Consultas AJAX Dinámicas y Renderizado Premium
 * v4.0.0 — Optimización MySQL e Integración con Divi 5 Library
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
class Ajax {

    /**
     * Constructor de la clase. Registra los hooks AJAX de WordPress.
     */
    public function __construct() {
        add_action( 'wp_ajax_bd_filter_listings', array( $this, 'filter_listings' ) );
        add_action( 'wp_ajax_nopriv_bd_filter_listings', array( $this, 'filter_listings' ) );
    }

    /**
     * Procesa la consulta AJAX de búsqueda y filtrado de negocios.
     */
    public function filter_listings() {
        // Verificación de nonce para seguridad
        check_ajax_referer('babel_search_nonce', 'nonce');
        
        global $wpdb;
        $table_index = $wpdb->prefix . 'bd_search_index';

        // Sanitización de parámetros recibidos
        $keyword = isset( $_POST['keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['keyword'] ) ) : '';
        $cat     = isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '';
        $region  = isset( $_POST['region'] ) ? sanitize_text_field( wp_unslash( $_POST['region'] ) ) : '';
        $sort    = isset( $_POST['sort'] ) ? sanitize_text_field( wp_unslash( $_POST['sort'] ) ) : 'featured';
        $paged   = isset( $_POST['paged'] ) ? absint( $_POST['paged'] ) : 1;
        
        $lat    = isset( $_POST['lat'] ) ? floatval( $_POST['lat'] ) : 0;
        $lng    = isset( $_POST['lng'] ) ? floatval( $_POST['lng'] ) : 0;
        $radius = isset( $_POST['radius'] ) ? absint( $_POST['radius'] ) : 50; // En kilómetros

        $posts_per_page = 12;
        $offset = ( $paged - 1 ) * $posts_per_page;

        // Construir consulta SQL optimizada sobre el índice
        $where = array( "p.post_status = 'publish'" );
        $join  = " INNER JOIN {$wpdb->posts} p ON idx.post_id = p.ID";
        
        // Filtro por Categoría (slug a ID)
        if ( ! empty( $cat ) ) {
            $term = get_term_by( 'slug', $cat, 'babel_category' );
            if ( $term ) {
                $where[] = $wpdb->prepare( "idx.category_id = %d", $term->term_id );
            }
        }

        // Filtro por Región/Comuna (slug a ID)
        if ( ! empty( $region ) ) {
            $term = get_term_by( 'slug', $region, 'babel_region' );
            if ( $term ) {
                $where[] = $wpdb->prepare( "idx.region_id = %d", $term->term_id );
            }
        }

        // Geolocalización mediante Haversine sobre índices compuestos
        $distance_select = "";
        if ( $lat && $lng ) {
            $haversine = $wpdb->prepare(
                "( 6371 * acos( cos( radians(%f) ) * cos( radians( idx.latitude ) ) * cos( radians( idx.longitude ) - radians(%f) ) + sin( radians(%f) ) * sin( radians( idx.latitude ) ) ) )",
                $lat, $lng, $lat
            );
            $distance_select = ", $haversine AS distance";
            if ( $radius > 0 ) {
                $where[] = "($haversine <= " . floatval( $radius ) . ")";
            }
        }

        // Prioridad e indexación de ordenamientos compuestos
        $orderby = "idx.is_featured DESC, idx.post_id DESC";
        switch ( $sort ) {
            case 'rating':
                $orderby = "idx.is_featured DESC, idx.rating_avg DESC, idx.post_id DESC";
                break;
            case 'az':
                $orderby = "idx.is_featured DESC, p.post_title ASC";
                break;
            case 'distance':
                if ( $lat && $lng ) {
                    $orderby = "idx.is_featured DESC, distance ASC";
                }
                break;
            case 'newest':
                $orderby = "idx.is_featured DESC, idx.post_id DESC";
                break;
            default:
                $orderby = "idx.is_featured DESC, idx.post_id DESC";
                break;
        }

        if ( ! empty( $keyword ) ) {
            $like_keyword = '%' . $wpdb->esc_like( $keyword ) . '%';
            $where[] = $wpdb->prepare( 
                "(p.post_title LIKE %s OR p.post_content LIKE %s OR EXISTS (
                    SELECT 1 FROM {$wpdb->term_relationships} tr 
                    INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id 
                    INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id 
                    WHERE tr.object_id = p.ID 
                    AND tt.taxonomy IN ('babel_category', 'babel_region') 
                    AND t.name LIKE %s
                ))", 
                $like_keyword, 
                $like_keyword, 
                $like_keyword
            );
        }

        $where_str = implode( " AND ", $where );
        
        // Consultas concurrentes optimizadas
        $sql = "SELECT idx.post_id $distance_select FROM $table_index idx $join WHERE $where_str ORDER BY $orderby LIMIT $offset, $posts_per_page";
        $total_sql = "SELECT COUNT(idx.post_id) FROM $table_index idx $join WHERE $where_str";

        $post_ids = $wpdb->get_col( $sql );
        $total_posts = $wpdb->get_var( $total_sql );
        $max_pages = ceil( $total_posts / $posts_per_page );

        // 1. CONFIGURACIÓN DEL LAYOUT ID
        $layout_id = intval( get_option( 'babel_divi_grid_layout_id', 0 ) );
        if ( ! $layout_id && defined( 'BABEL_DEFAULT_LAYOUT_ID' ) ) {
            $layout_id = intval( BABEL_DEFAULT_LAYOUT_ID );
        }

        // 4. BLINDAJE Y VALIDACIÓN PREVIA DEL LAYOUT
        $is_layout_valid = false;
        if ( $layout_id > 0 ) {
            $layout_post = get_post( $layout_id );
            if ( $layout_post && 'et_pb_layout' === $layout_post->post_type ) {
                $is_layout_valid = true;
            }
        }
        $is_layout_valid = $is_layout_valid && function_exists( 'do_shortcode' );

        ob_start();
        if ( ! empty( $post_ids ) ) {
            echo '<div class="babel-grid-container">';
            
            global $post;
            $original_post = $post; // Respaldar post global original
            
            foreach ( $post_ids as $pid ) {
                $current_post = get_post( $pid );
                if ( ! $current_post ) {
                    continue;
                }
                
                // 2. BUCLE AJAX E HIDRATACIÓN DE CONTEXTO
                // Forzar el reemplazo del objeto global $post para que Divi 5 (Dynamic Content) reconozca el negocio actual
                $post = $current_post;
                setup_postdata( $post );
                
                if ( $is_layout_valid ) {
                    // 3. RENDERIZADO EFICIENTE
                    echo '<div class="babel-divi-card-wrapper">';
                    echo do_shortcode( '[et_pb_layout id="' . $layout_id . '"]' );
                    echo '</div>';
                } else {
                    // 4. BLINDAJE Y FALLBACK (Marcado HTML semántico básico)
                    $this->render_fallback_card( $pid );
                }
                
                // 3. LIMPIAR EL CONTEXTO al finalizar cada iteración
                wp_reset_postdata();
            }
            
            // 3. LIMPIAR EL CONTEXTO al terminar el bucle por completo
            wp_reset_postdata();
            $post = $original_post;
            
            echo '</div>';
            
            // Paginación dinámicamente inyectada
            if ( $max_pages > 1 ) {
                echo '<div class="babel-pagination-wrapper">';
                echo paginate_links( array(
                    'total'     => $max_pages,
                    'current'   => $paged,
                    'type'      => 'plain',
                    'prev_text' => '<span class="babel-pagination-arrow">&larr;</span>',
                    'next_text' => '<span class="babel-pagination-arrow">&rarr;</span>',
                ) );
                echo '</div>';
            }
        } else {
            // Estado vacío estilizado
            $this->render_empty_state();
        }
        
        $html = ob_get_clean();
        wp_send_json_success( array( 
            'html'  => $html, 
            'count' => intval( $total_posts ), 
            'max'   => intval( $max_pages ) 
        ) );
    }

    /**
     * Renderiza una tarjeta de negocio de contingencia con una estética premium.
     *
     * @param int $post_id ID del negocio.
     */
    private function render_fallback_card( $post_id ) {
        $title       = get_the_title( $post_id );
        $permalink   = get_permalink( $post_id );
        $phone       = get_post_meta( $post_id, '_babel_phone', true );
        $whatsapp    = get_post_meta( $post_id, '_babel_whatsapp', true );
        $address     = get_post_meta( $post_id, '_babel_address', true );
        $gmaps       = get_post_meta( $post_id, '_babel_gmaps', true );
        // Variables actualizadas según UX_ROADMAP.md
        $is_verified  = get_post_meta( $post_id, '_babel_verified', true );
        $is_featured  = get_post_meta( $post_id, '_babel_featured', true );
        $rating_avg   = (float) get_post_meta( $post_id, '_babel_rating_avg', true );
        $rating_count = (int) get_post_meta( $post_id, '_babel_rating_count', true );
        $price_range  = get_post_meta( $post_id, '_babel_price_range', true );

        $categorias = get_the_terms( $post_id, 'babel_category' );
        $regiones   = get_the_terms( $post_id, 'babel_region' );

        $category_name = ( ! empty( $categorias ) && ! \is_wp_error( $categorias ) ) ? $categorias[0]->name : '';
        $region_name   = '';
        if ( ! empty( $regiones ) && ! \is_wp_error( $regiones ) ) {
            $region_name = preg_replace( '/^[IVX]+\s*-\s*REG\s*-\s*/i', '', $regiones[0]->name );
        }

        $thumb_id  = get_post_thumbnail_id( $post_id );
        $thumb_url = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'medium_large' ) : '';
        ?>
        <a href="<?php echo esc_url( $permalink ); ?>" class="babel-biz-card" aria-label="<?php echo esc_attr( $title ); ?>">

            <!-- Zona de imagen -->
            <div class="babel-biz-card__image-wrap">
                <?php if ( $thumb_url ) : ?>
                    <img
                        src="<?php echo esc_url( $thumb_url ); ?>"
                        alt="<?php echo esc_attr( $title ); ?>"
                        class="babel-biz-card__image"
                        loading="lazy"
                    />
                <?php else : ?>
                    <div class="babel-biz-card__placeholder">
                        <span class="material-symbols-outlined" style="font-size:56px;">store</span>
                    </div>
                <?php endif; ?>

                <!-- Badges flotantes -->
                <?php if ( $is_featured || $is_verified ) : ?>
                    <div class="babel-biz-card__badges">
                        <?php if ( $is_featured ) : ?>
                            <span class="babel-biz-card__badge babel-biz-card__badge--featured">
                                <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;">stars</span>
                                <?php esc_html_e( 'Destacado', 'babel-directory' ); ?>
                            </span>
                        <?php endif; ?>
                        <?php if ( $is_verified ) : ?>
                            <span class="babel-biz-card__badge babel-biz-card__badge--verified">
                                <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;">verified</span>
                                <?php esc_html_e( 'Verificado', 'babel-directory' ); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div><!-- /.babel-biz-card__image-wrap -->

            <!-- Cuerpo de la tarjeta -->
            <div class="babel-biz-card__body">

                <h3 class="babel-biz-card__title"><?php echo esc_html( $title ); ?></h3>

                <?php if ( $rating_count > 0 ) : ?>
                    <div class="babel-biz-card__rating" aria-label="<?php echo esc_attr( number_format( $rating_avg, 1 ) ); ?> de 5 estrellas">
                        <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;" aria-hidden="true">star</span>
                        <span class="babel-biz-card__rating-score"><?php echo esc_html( number_format( $rating_avg, 1 ) ); ?></span>
                        <span class="babel-biz-card__rating-count">(<?php echo esc_html( $rating_count ); ?>)</span>
                    </div>
                <?php endif; ?>

                <?php if ( $category_name || $region_name ) : ?>
                    <div class="babel-biz-card__meta">
                        <?php if ( $category_name ) : ?>
                            <span class="babel-biz-card__meta-item">
                                <span class="material-symbols-outlined" aria-hidden="true">category</span>
                                <?php echo esc_html( $category_name ); ?>
                            </span>
                        <?php endif; ?>
                        <?php if ( $category_name && $region_name ) : ?>
                            <span class="babel-biz-card__meta-sep" aria-hidden="true"></span>
                        <?php endif; ?>
                        <?php if ( $region_name ) : ?>
                            <span class="babel-biz-card__meta-item">
                                <span class="material-symbols-outlined" aria-hidden="true">location_on</span>
                                <?php echo esc_html( $region_name ); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="babel-biz-card__footer">
                    <?php if ( $price_range ) : ?>
                        <span class="babel-biz-card__price"><?php echo esc_html( $price_range ); ?></span>
                    <?php else : ?>
                        <span></span>
                    <?php endif; ?>
                    <span class="babel-biz-card__cta">
                        <?php esc_html_e( 'Ver perfil', 'babel-directory' ); ?>
                        <span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span>
                    </span>
                </div>

            </div><!-- /.babel-biz-card__body -->

        </a><!-- /.babel-biz-card -->
        <?php
    }

    /**
     * Renderiza el estado vacío cuando no se encuentran resultados de búsqueda.
     */
    private function render_empty_state() {
        ?>
        <div class="babel-empty-state">
            <div class="babel-empty-icon">🔍</div>
            <h3><?php esc_html_e( 'Sin resultados coincidentes', 'babel-directory' ); ?></h3>
            <p><?php esc_html_e( 'No encontramos ningún negocio que coincida con tus criterios de búsqueda. Intenta limpiando los filtros o ampliando el rango.', 'babel-directory' ); ?></p>
            <button type="button" class="babel-clear-filters-btn" onclick="if(window.babelResetFilters) window.babelResetFilters();">
                <?php esc_html_e( 'Limpiar Filtros', 'babel-directory' ); ?>
            </button>
        </div>
        <?php
    }
}