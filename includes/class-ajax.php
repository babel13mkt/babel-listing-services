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
        $is_verified = get_post_meta( $post_id, '_babel_is_verified', true );
        $is_featured = get_post_meta( $post_id, '_babel_is_featured', true );
        $rating_avg  = get_post_meta( $post_id, '_babel_rating_avg', true );
        $review_count = get_post_meta( $post_id, '_babel_review_count', true );

        $categories = wp_get_post_terms( $post_id, 'babel_category' );
        $regions    = wp_get_post_terms( $post_id, 'babel_region' );

        $category_name = ! empty( $categories ) ? $categories[0]->name : __( 'General', 'babel-directory' );
        $region_name   = ! empty( $regions ) ? $regions[0]->name : __( 'Chile', 'babel-directory' );

        // Obtener una descripción corta (extracto de hasta 15 palabras)
        $raw_content = get_post_field( 'post_content', $post_id );
        $excerpt     = get_post_field( 'post_excerpt', $post_id );
        $short_desc  = ! empty( $excerpt ) ? $excerpt : $raw_content;
        $trimmed_desc = wp_trim_words( $short_desc, 15, '...' );

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
                <div class="babel-card-image-wrap">
                    <?php if ( $logo_url ) : ?>
                        <img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" class="babel-card-logo" />
                    <?php else : ?>
                        <div class="babel-card-logo-placeholder" style="background: <?php echo esc_attr( $chosen_gradient ); ?>">
                            <?php echo esc_html( mb_substr( $title, 0, 1 ) ); ?>
                        </div>
                    <?php endif; ?>
                </div>

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
                        <svg class="babel-verified-svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16" title="<?php esc_attr_e( 'Negocio Verificado', 'babel-directory' ); ?>">
                            <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                    <?php endif; ?>
                </h3>

                <!-- Sección de Calificación (Estrellas) -->
                <?php if ( ! empty( $rating_avg ) && floatval( $rating_avg ) > 0 ) : 
                    $rating_val = floatval( $rating_avg );
                    $count_val  = intval( $review_count );
                    ?>
                    <div class="babel-card-rating" title="<?php echo esc_attr( sprintf( __( 'Calificación: %s de 5 estrellas', 'babel-directory' ), $rating_val ) ); ?>">
                        <div class="babel-stars">
                            <?php
                            for ( $i = 1; $i <= 5; $i++ ) {
                                if ( $i <= round( $rating_val ) ) {
                                    echo '<span class="babel-star babel-star-fill">★</span>';
                                } else {
                                    echo '<span class="babel-star babel-star-empty">☆</span>';
                                }
                            }
                            ?>
                        </div>
                        <span class="babel-rating-val">(<?php echo esc_html( number_format( $rating_val, 1 ) ); ?>)</span>
                        <?php if ( $count_val > 0 ) : ?>
                            <span class="babel-reviews-count">• <?php echo esc_html( sprintf( _n( '%d reseña', '%d reseñas', $count_val, 'babel-directory' ), $count_val ) ); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ( ! empty( $trimmed_desc ) ) : ?>
                    <p class="babel-card-excerpt"><?php echo esc_html( $trimmed_desc ); ?></p>
                <?php endif; ?>

                <div class="babel-card-location">
                    <svg class="babel-icon-svg babel-icon-pin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                        <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                    <span class="babel-location-text"><?php echo esc_html( $region_name ); ?><?php echo ! empty( $address ) ? ', ' . esc_html( $address ) : ''; ?></span>
                </div>
            </div>

            <!-- Botones de Acción y Contacto -->
            <div class="babel-card-footer">
                <?php if ( $gmaps ) : ?>
                    <a href="<?php echo esc_url( $gmaps ); ?>" target="_blank" rel="noopener" class="babel-card-action-btn babel-btn-gmaps" title="<?php esc_attr_e( 'Ver Mapa', 'babel-directory' ); ?>">
                        <svg class="babel-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                            <polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"></polygon>
                            <line x1="9" y1="3" x2="9" y2="18"></line>
                            <line x1="15" y1="6" x2="15" y2="21"></line>
                        </svg>
                    </a>
                <?php endif; ?>

                <?php if ( $phone ) : ?>
                    <a href="tel:<?php echo esc_attr( str_replace( ' ', '', $phone ) ); ?>" class="babel-card-action-btn babel-btn-phone">
                        <svg class="babel-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                        </svg>
                        <span><?php esc_html_e( 'Llamar', 'babel-directory' ); ?></span>
                    </a>
                <?php endif; ?>

                <?php if ( $whatsapp ) : ?>
                    <a href="https://wa.me/<?php echo esc_attr( preg_replace( '/[^0-9]/', '', $whatsapp ) ); ?>" target="_blank" rel="noopener" class="babel-card-action-btn babel-btn-whatsapp">
                        <svg class="babel-icon-svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.003 5.324 5.328 0 11.91 0c3.19.001 6.189 1.242 8.448 3.498c2.259 2.257 3.5 5.253 3.5 8.444c-.004 6.584-5.328 11.908-11.912 11.908c-2.008-.002-3.98-.51-5.732-1.474L0 24zm6.59-2.222c1.666.988 3.315 1.489 5.313 1.49c5.637 0 10.224-4.587 10.228-10.224C22.19 8.04 21.144 5.4 19.28 3.535C17.417 1.67 14.773.626 11.91.626c-5.633 0-10.223 4.587-10.227 10.224c0 2.083.548 4.12 1.587 5.922l-.993 3.622l3.725-.976c1.642.897 3.328 1.398 4.646 1.398zM17.487 14.4c-.3-.15-1.78-.88-2.057-.98c-.28-.1-.48-.15-.68.15c-.2.3-.77.98-.95 1.18c-.18.2-.36.22-.66.07c-.3-.15-1.27-.47-2.42-1.5c-.9-.8-1.5-1.8-1.68-2.1c-.18-.3-.02-.46.13-.6c.14-.14.3-.35.45-.53c.15-.17.2-.3.3-.5c.1-.2.05-.38-.025-.53C9.75 9.1 9.17 7.68 8.93 7.1c-.24-.58-.48-.5-.66-.51c-.17-.01-.37-.01-.57-.01c-.2 0-.52.08-.8.38c-.28.3-1.08 1.06-1.08 2.58c0 1.52 1.11 3 1.26 3.2c.15.2 2.19 3.34 5.31 4.69c.74.32 1.32.51 1.77.65c.75.24 1.43.2 1.97.12c.6-.09 1.78-.73 2.03-1.43c.25-.7.25-1.3.17-1.43c-.08-.13-.28-.21-.58-.36z"/>
                        </svg>
                        <span><?php esc_html_e( 'WhatsApp', 'babel-directory' ); ?></span>
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
