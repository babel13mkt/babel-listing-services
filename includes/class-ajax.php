<?php
/**
 * Lógica de Consultas AJAX Dinámicas y Renderizado Premium
 * v4.0.0 — Optimización MySQL e Integración con Divi 5 Library
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BD_AJAX {

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
        // Validación de seguridad mediante Nonce
        if ( ! isset( $_POST['nonce'] ) || ( ! wp_verify_nonce( $_POST['nonce'], 'bd_ajax_nonce' ) && ! wp_verify_nonce( $_POST['nonce'], 'babel_search_nonce' ) ) ) {
            wp_send_json_error( 'Acceso no autorizado por token de seguridad vencido.' );
        }

        global $wpdb;
        $table_index = $wpdb->prefix . 'bd_search_index';

        // Sanitización de parámetros recibidos
        $keyword = isset( $_POST['keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['keyword'] ) ) : '';
        $cat     = isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '';
        $region  = isset( $_POST['region'] ) ? sanitize_text_field( wp_unslash( $_POST['region'] ) ) : '';
        $sort    = isset( $_POST['sort'] ) ? sanitize_text_field( wp_unslash( $_POST['sort'] ) ) : 'featured';
        $paged   = isset( $_POST['paged'] ) ? intval( $_POST['paged'] ) : 1;
        
        $lat    = isset( $_POST['lat'] ) ? floatval( $_POST['lat'] ) : 0;
        $lng    = isset( $_POST['lng'] ) ? floatval( $_POST['lng'] ) : 0;
        $radius = isset( $_POST['radius'] ) ? intval( $_POST['radius'] ) : 50; // En kilómetros

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

        // Búsqueda textual sobre títulos y contenidos del post
        if ( ! empty( $keyword ) ) {
            $where[] = $wpdb->prepare( 
                "(p.post_title LIKE %s OR p.post_content LIKE %s)", 
                '%' . $wpdb->esc_like( $keyword ) . '%', 
                '%' . $wpdb->esc_like( $keyword ) . '%' 
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
        $is_verified = get_post_meta( $post_id, '_babel_is_verified', true );
        $is_featured = get_post_meta( $post_id, '_babel_is_featured', true );

        $categories = wp_get_post_terms( $post_id, 'babel_category' );
        $regions    = wp_get_post_terms( $post_id, 'babel_region' );

        $category_name = ! empty( $categories ) ? $categories[0]->name : __( 'General', 'babel-directory' );
        $region_name   = ! empty( $regions ) ? $regions[0]->name : __( 'Chile', 'babel-directory' );

        // Generar un gradiente moderno aleatorio basado en el título del post si no hay logo
        $logo_url = get_the_post_thumbnail_url( $post_id, 'medium' );
        $bg_gradients = array(
            'linear-gradient(135deg, #FF6B6B 0%, #4D96FF 100%)',
            'linear-gradient(135deg, #6BCB77 0%, #FFD93D 100%)',
            'linear-gradient(135deg, #4D96FF 0%, #9B5DE5 100%)',
            'linear-gradient(135deg, #F15BB5 0%, #F72585 100%)',
        );
        $gradient_index = abs( crc32( $title ) ) % count( $bg_gradients );
        $chosen_gradient = $bg_gradients[ $gradient_index ];
        ?>
        <div class="babel-premium-card <?php echo ( '1' === $is_featured ) ? 'babel-card-featured' : ''; ?>">
            
            <!-- Cabecera de la Tarjeta (Imagen / Logo) -->
            <div class="babel-card-header">
                <?php if ( $logo_url ) : ?>
                    <img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" class="babel-card-logo" />
                <?php else : ?>
                    <div class="babel-card-logo-placeholder" style="background: <?php echo esc_attr( $chosen_gradient ); ?>">
                        <?php echo esc_html( mb_substr( $title, 0, 1 ) ); ?>
                    </div>
                <?php endif; ?>

                <div class="babel-card-badges">
                    <?php if ( '1' === $is_featured ) : ?>
                        <span class="babel-badge babel-badge-featured"><?php esc_html_e( 'Destacado', 'babel-directory' ); ?></span>
                    <?php endif; ?>
                    <span class="babel-badge babel-badge-category"><?php echo esc_html( $category_name ); ?></span>
                </div>
            </div>

            <!-- Cuerpo de la Tarjeta -->
            <div class="babel-card-body">
                <h3 class="babel-card-title">
                    <a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
                    <?php if ( '1' === $is_verified ) : ?>
                        <span class="babel-verified-icon" title="<?php esc_attr_e( 'Negocio Verificado', 'babel-directory' ); ?>">✓</span>
                    <?php endif; ?>
                </h3>

                <div class="babel-card-location">
                    <span class="babel-location-icon">📍</span>
                    <span class="babel-location-text"><?php echo esc_html( $region_name ); ?><?php echo ! empty( $address ) ? ', ' . esc_html( $address ) : ''; ?></span>
                </div>
            </div>

            <!-- Botones de Acción y Contacto -->
            <div class="babel-card-footer">
                <?php if ( $gmaps ) : ?>
                    <a href="<?php echo esc_url( $gmaps ); ?>" target="_blank" rel="noopener" class="babel-card-action-btn babel-btn-gmaps" title="<?php esc_attr_e( 'Ver Mapa', 'babel-directory' ); ?>">
                        🗺️
                    </a>
                <?php endif; ?>

                <?php if ( $phone ) : ?>
                    <a href="tel:<?php echo esc_attr( str_replace( ' ', '', $phone ) ); ?>" class="babel-card-action-btn babel-btn-phone">
                        📞 <?php esc_html_e( 'Llamar', 'babel-directory' ); ?>
                    </a>
                <?php endif; ?>

                <?php if ( $whatsapp ) : ?>
                    <a href="https://wa.me/<?php echo esc_attr( preg_replace( '/[^0-9]/', '', $whatsapp ) ); ?>" target="_blank" rel="noopener" class="babel-card-action-btn babel-btn-whatsapp">
                        💬 <?php esc_html_e( 'WhatsApp', 'babel-directory' ); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
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
