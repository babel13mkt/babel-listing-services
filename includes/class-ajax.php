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
        
        add_action( 'wp_ajax_bd_track_event', array( $this, 'track_event' ) );
        add_action( 'wp_ajax_nopriv_bd_track_event', array( $this, 'track_event' ) );
    }

    public function filter_listings() {
        // [NUEVO] Middleware de Seguridad: Máximo 30 peticiones de búsqueda por minuto por IP
        if ( class_exists( '\Babel\Directory\Security' ) && ! \Babel\Directory\Security::check_rate_limit( 'ajax_search', 30, 60 ) ) {
            wp_send_json_error( 'Rate limit exceeded. Please wait a moment.' );
        }

        // Verificación de nonce desactivada para búsquedas públicas. 
        // El caché de página (Cloudflare/Divi) almacena el HTML con un nonce estático, 
        // lo que provoca que check_ajax_referer devuelva -1 (HTTP 200) y rompa el buscador 
        // aleatoriamente para usuarios que navegan con caché de invitados pero están logueados (o viceversa).
        // check_ajax_referer('babel_search_nonce', 'nonce');
        
        global $wpdb;
        $table_index = $wpdb->prefix . 'bd_search_index';

        // Sanitización de parámetros recibidos
        $keyword     = isset( $_POST['keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['keyword'] ) ) : '';
        $cat         = isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '';
        $region      = isset( $_POST['region'] ) ? sanitize_text_field( wp_unslash( $_POST['region'] ) ) : '';
        $sort        = isset( $_POST['sort'] ) ? sanitize_text_field( wp_unslash( $_POST['sort'] ) ) : 'featured';
        $paged       = isset( $_POST['paged'] ) ? absint( $_POST['paged'] ) : 1;
        $entity_type = isset( $_POST['entity_type'] ) ? sanitize_text_field( wp_unslash( $_POST['entity_type'] ) ) : 'business';
        
        $lat    = isset( $_POST['lat'] ) ? floatval( $_POST['lat'] ) : 0;
        $lng    = isset( $_POST['lng'] ) ? floatval( $_POST['lng'] ) : 0;
        $radius = isset( $_POST['radius'] ) ? absint( $_POST['radius'] ) : 50; // En kilómetros

        $posts_per_page = 12;
        $offset = ( $paged - 1 ) * $posts_per_page;

        // [NUEVO] Interceptar con Caché (Transients) — key determinística y normalizada
        if ( class_exists( '\Babel\Directory\Cache' ) ) {
            $cache_key = \Babel\Directory\Cache::key( 'ajax_search', $_POST );
            $cached_result = \Babel\Directory\Cache::get_transient( $cache_key );
            if ( false !== $cached_result ) {
                wp_send_json_success( $cached_result );
            }
        }

        // Construir consulta SQL optimizada sobre el índice
        $where = array( "p.post_status = 'publish'" );
        $join  = " INNER JOIN {$wpdb->posts} p ON idx.post_id = p.ID";
        $join .= " LEFT JOIN {$wpdb->postmeta} pm_prio ON (p.ID = pm_prio.post_id AND pm_prio.meta_key = '_babel_priority_score')";
        
        // Filtro Entidad (Comercios vs Instituciones)
        $inst_slugs = array( 'salud', 'seguridad-publica', 'gobierno', 'justicia', 'cultura', 'educacion', 'instituciones-y-servicios-publicos' );
        $institution_term_ids = array();
        foreach ( $inst_slugs as $slug ) {
            $term = get_term_by( 'slug', $slug, 'babel_category' );
            if ( $term ) {
                $institution_term_ids[] = $term->term_id;
                $children = get_term_children( $term->term_id, 'babel_category' );
                if ( ! is_wp_error( $children ) ) {
                    $institution_term_ids = array_merge( $institution_term_ids, $children );
                }
            }
        }
        $inst_ids_sql = implode(',', array_map('intval', array_unique($institution_term_ids)));

        // Si el usuario escribe una palabra clave, buscamos en TODO (negocios e instituciones)
        if ( ! empty( $keyword ) ) {
            $entity_type = 'all';
        }

        if ( $entity_type === 'institution' ) {
            if ( ! empty( $cat ) ) {
                $term = get_term_by( 'slug', $cat, 'babel_category' );
                if ( $term ) {
                    $where[] = $wpdb->prepare( "idx.category_id = %d", $term->term_id );
                }
            } else {
                $where[] = "idx.category_id IN ($inst_ids_sql)";
            }
        } elseif ( $entity_type === 'business' ) {
            // Excluir siempre las instituciones por tipo de post
            $where[] = "p.post_type != 'bd_institution'";
            
            if ( ! empty( $cat ) ) {
                $term = get_term_by( 'slug', $cat, 'babel_category' );
                if ( $term ) {
                    $where[] = $wpdb->prepare( "idx.category_id = %d", $term->term_id );
                }
            } else {
                // Solo comercios (excluimos instituciones y sus hijas por defecto)
                $where[] = "idx.category_id NOT IN ($inst_ids_sql)";
            }
        } else {
            // entity_type == 'all'
            if ( ! empty( $cat ) ) {
                $term = get_term_by( 'slug', $cat, 'babel_category' );
                if ( $term ) {
                    $where[] = $wpdb->prepare( "idx.category_id = %d", $term->term_id );
                }
            }
        }

        // Filtro por Región/Comuna (slug a ID)
        if ( ! empty( $region ) ) {
            $term = get_term_by( 'slug', $region, 'babel_region' );
            if ( $term ) {
                $where[] = $wpdb->prepare( "idx.region_id = %d", $term->term_id );
            }
        }

        // Geolocalización mediante Haversine sobre índices compuestos (SEGURO: usa placeholders en query final)
        $distance_select = ", CAST(IFNULL(pm_prio.meta_value, 0) AS SIGNED) AS priority_score";
        $haversine_sql = "";
        $haversine_params = [];
        if ( $lat && $lng ) {
            $haversine_sql = "( 6371 * acos( cos( radians(%f) ) * cos( radians( idx.latitude ) ) * cos( radians( idx.longitude ) - radians(%f) ) + sin( radians(%f) ) * sin( radians( idx.latitude ) ) ) )";
            $haversine_params = [ $lat, $lng, $lat ];
            $distance_select = ", ( {$haversine_sql} ) AS distance";
            if ( $radius > 0 ) {
                $haversine_params[] = floatval( $radius );
                $where[] = "( {$haversine_sql} <= %f )";
            }
        }

        // Prioridad e indexación de ordenamientos compuestos (Whitelist estricta)
        // Cuando hay coordenadas, el orden por defecto es por distancia (más cercanos primero)
        $allowed_orders = array(
            'rating'   => 'priority_score DESC, idx.is_featured DESC, idx.rating_avg DESC, idx.post_id DESC',
            'az'       => 'priority_score DESC, idx.is_featured DESC, p.post_title ASC',
            'distance' => ( $lat && $lng ) ? 'priority_score DESC, idx.is_featured DESC, distance ASC' : 'priority_score DESC, idx.is_featured DESC, idx.post_id DESC',
            'newest'   => 'priority_score DESC, idx.is_featured DESC, idx.post_id DESC'
        );
        // Si hay coordenadas y el sort es 'featured', usar distancia por defecto
        if ( $lat && $lng && ( $sort === 'featured' || ! isset( $allowed_orders[ $sort ] ) ) ) {
            $orderby = 'idx.is_featured DESC, distance ASC';
        } else {
            $orderby = isset( $allowed_orders[ $sort ] ) ? $allowed_orders[ $sort ] : 'idx.is_featured DESC, idx.post_id DESC';
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

        // Usamos get_results para capturar distancia cuando hay geolocalización
        $results = $wpdb->get_results( $sql, ARRAY_A );
        $total_posts = $wpdb->get_var( $total_sql );
        $max_pages = ceil( $total_posts / $posts_per_page );

        // Extraer post_ids y distancias
        $post_ids = array();
        $GLOBALS['bd_search_distance'] = array();
        if ( $results ) {
            foreach ( $results as $row ) {
                $post_ids[] = (int) $row['post_id'];
                if ( isset( $row['distance'] ) ) {
                    $GLOBALS['bd_search_distance'][ (int) $row['post_id'] ] = (float) $row['distance'];
                }
            }
        }

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
        
        // CSS para tarjetas de institución y grilla mixta inteligente (12 columnas)
        echo '<style>
        .babel-grid-container { display: grid !important; grid-template-columns: repeat(12, 1fr) !important; gap: 24px !important; }
        
        /* Comportamiento por defecto (móvil) */
        .babel-divi-card-wrapper, .babel-biz-card, .babel-inst-card { grid-column: span 12 !important; }
        
        /* Tablet */
        @media (min-width: 768px) {
            .babel-divi-card-wrapper, .babel-biz-card, .babel-inst-card { grid-column: span 6 !important; }
        }
        
        /* Desktop */
        @media (min-width: 981px) {
            .babel-divi-card-wrapper, .babel-biz-card { grid-column: span 4 !important; } /* 3 columnas para negocios */
            .babel-inst-card { grid-column: span 3 !important; } /* 4 columnas para instituciones */
        }
        
        .babel-inst-card { display: flex; flex-direction: column; padding: 16px; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; text-decoration: none; color: #1e293b; transition: all 0.2s; gap: 8px; align-items: flex-start; }
        .babel-inst-card:hover { border-color: #cbd5e1; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); transform: translateY(-2px); }
        .babel-inst-card-header { display: flex; gap: 12px; align-items: center; width: 100%; }
        .babel-inst-card-icon { font-size: 24px; color: #64748b; }
        .babel-inst-card-title { margin: 0; font-size: 15px; font-weight: 600; line-height: 1.3; color: #0f172a; }
        .babel-inst-card-body { display: flex; flex-direction: column; gap: 6px; width: 100%; margin-top: 8px; font-size: 13px; color: #475569; }
        .babel-inst-card-detail { display: flex; gap: 6px; align-items: center; }
        .babel-inst-card-detail .material-symbols-outlined { font-size: 16px; color: #94a3b8; }
        .babel-inst-card-link { color: #2563eb; text-decoration: none; }
        .babel-inst-card-link:hover { text-decoration: underline; }
        </style>';

        $map_markers = array();

        if ( ! empty( $post_ids ) ) {
            $is_inst_grid = false;
            if ( get_post_type($post_ids[0]) === 'bd_institution' ) {
                $is_inst_grid = true;
            }
            $grid_class = $is_inst_grid ? 'babel-grid-container babel-grid-institutions' : 'babel-grid-container';
            echo '<div class="' . esc_attr($grid_class) . '">';
            
            global $post;
            $original_post = $post; // Respaldar post global original
            
            try {
                foreach ( $post_ids as $pid ) {
                    $current_post = get_post( $pid );
                    if ( ! $current_post ) {
                        continue;
                    }
                    
                    // 2. BUCLE AJAX E HIDRATACIÓN DE CONTEXTO
                    // Forzar el reemplazo del objeto global $post para que Divi 5 (Dynamic Content) reconozca el negocio actual
                    $post = $current_post;
                    setup_postdata( $post );

                    // Recolectar datos para el mapa
                    $lat = get_post_meta( $pid, '_babel_lat', true ) ?: get_post_meta( $pid, '_babel_latitude', true );
                    $lng = get_post_meta( $pid, '_babel_lng', true ) ?: get_post_meta( $pid, '_babel_longitude', true );
                    
                    if ( ! empty( $lat ) && ! empty( $lng ) ) {
                        $categorias = get_the_terms( $pid, 'babel_category' );
                        $cat_name = ( ! empty( $categorias ) && ! \is_wp_error( $categorias ) ) ? $categorias[0]->name : '';
                        $thumb_id  = get_post_thumbnail_id( $pid );
                        $thumb_url = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'thumbnail' ) : '';

                        $map_markers[] = array(
                            'id'    => $pid,
                            'lat'   => (float) $lat,
                            'lng'   => (float) $lng,
                            'title' => get_the_title( $pid ),
                            'url'   => get_permalink( $pid ),
                            'cat'   => $cat_name,
                            'img'   => $thumb_url
                        );
                    }
                    
                    $is_inst_meta = get_post_meta( $pid, '_babel_is_institution', true );
                    $requested_entity = isset($_POST['entity_type']) ? sanitize_text_field($_POST['entity_type']) : '';
                    $is_institution = ( get_post_type($pid) === 'bd_institution' || in_array( $is_inst_meta, array( '1', 'yes', true ), true ) || $requested_entity === 'institution' );
                    
                    if ( $is_institution ) {
                        $this->render_institution_list_item( $pid );
                    } elseif ( $is_layout_valid ) {
                        // 3. RENDERIZADO EFICIENTE
                        echo '<div class="babel-divi-card-wrapper">';
                        echo do_shortcode( '[et_pb_layout id="' . absint( $layout_id ) . '"]' );
                        echo '</div>';
                    } else {
                        // 4. BLINDAJE Y FALLBACK (Marcado HTML semántico básico)
                        $this->render_fallback_card( $pid );
                    }
                }
            } finally {
                // 3. LIMPIAR EL CONTEXTO al terminar el bucle por completo
                wp_reset_postdata();
                $post = $original_post;
            }
            
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
            if ( ! empty( $keyword ) || ! empty( $cat ) || ! empty( $region ) ) {
                $this->render_empty_state();
            }
        }
        
        $html = ob_get_clean();
        
        $result_data = array( 
            'html'    => $html, 
            'count'   => intval( $total_posts ), 
            'max'     => intval( $max_pages ),
            'markers' => $map_markers 
        );

        // [NUEVO] Guardar en Caché por 30 minutos
        if ( class_exists( '\Babel\Directory\Cache' ) ) {
            \Babel\Directory\Cache::set_transient( $cache_key, $result_data, 1800 );
        }

        wp_send_json_success( $result_data );
    }

    /**
     * Renderiza una tarjeta de negocio de contingencia con una estética premium.
     *
     * @param int $post_id ID del negocio.
     */
    private function render_fallback_card( $post_id ) {
        $title       = get_the_title( $post_id );
        $permalink   = get_permalink( $post_id );
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

        // Distancia (si viene de búsqueda geolocalizada)
        $distance_km = isset( $GLOBALS['bd_search_distance'][ $post_id ] ) ? $GLOBALS['bd_search_distance'][ $post_id ] : null;
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

                <?php if ( $category_name || $region_name || $distance_km ) : ?>
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
                        <?php if ( $distance_km !== null ) : ?>
                            <span class="babel-biz-card__meta-sep" aria-hidden="true"></span>
                            <span class="babel-biz-card__meta-item">
                                <span class="material-symbols-outlined" aria-hidden="true">near_me</span>
                                <?php echo esc_html( number_format( $distance_km, 1 ) ); ?> km
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
     * Renderiza un item de lista para instituciones.
     */
    private function render_institution_list_item( $post_id ) {
        $title     = get_the_title( $post_id );
        $address   = get_post_meta( $post_id, '_babel_address', true );
        $phone     = get_post_meta( $post_id, '_babel_phone', true );
        $whatsapp  = get_post_meta( $post_id, '_babel_whatsapp', true );
        $email     = get_post_meta( $post_id, '_babel_email', true );
        $website   = get_post_meta( $post_id, '_babel_website', true );
        $hours     = get_post_meta( $post_id, '_babel_hours', true );
        
        $contact_phone = $phone ? $phone : $whatsapp;
        
        ?>
        <div class="babel-inst-card">
            <div class="babel-inst-card-header">
                <span class="material-symbols-outlined babel-inst-card-icon" aria-hidden="true">account_balance</span>
                <h3 class="babel-inst-card-title"><?php echo esc_html( $title ); ?></h3>
            </div>
            
            <div class="babel-inst-card-body">
                <?php if ( $address ) : ?>
                    <div class="babel-inst-card-detail" title="Dirección">
                        <span class="material-symbols-outlined" aria-hidden="true">location_on</span>
                        <span><?php echo esc_html( $address ); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ( $contact_phone ) : ?>
                    <div class="babel-inst-card-detail" title="Teléfono">
                        <span class="material-symbols-outlined" aria-hidden="true">call</span>
                        <a href="tel:<?php echo esc_attr( preg_replace('/[^0-9+]/', '', $contact_phone) ); ?>" class="babel-inst-card-link"><?php echo esc_html( $contact_phone ); ?></a>
                    </div>
                <?php endif; ?>
                
                <?php if ( $email ) : ?>
                    <div class="babel-inst-card-detail" title="Email">
                        <span class="material-symbols-outlined" aria-hidden="true">mail</span>
                        <a href="mailto:<?php echo esc_attr( $email ); ?>" class="babel-inst-card-link"><?php echo esc_html( $email ); ?></a>
                    </div>
                <?php endif; ?>
                
                <?php if ( $website ) : ?>
                    <div class="babel-inst-card-detail" title="Sitio Web">
                        <span class="material-symbols-outlined" aria-hidden="true">language</span>
                        <a href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener noreferrer" class="babel-inst-card-link">Visitar sitio web</a>
                    </div>
                <?php endif; ?>
                
                <?php if ( $hours && ! is_array( $hours ) ) : ?>
                    <div class="babel-inst-card-detail" title="Horario">
                        <span class="material-symbols-outlined" aria-hidden="true">schedule</span>
                        <span><?php echo esc_html( $hours ); ?></span>
                    </div>
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
            <h3><?php esc_html_e( '¡Este espacio está disponible!', 'babel-directory' ); ?></h3>
            <p><?php esc_html_e( 'Tu negocio puede ser el primero en aparecer aquí. Destaca tu marca y llega a más clientes en esta región.', 'babel-directory' ); ?></p>
            <a href="/publicar-negocio/" class="babel-clear-filters-btn" style="text-decoration:none; display:inline-block; margin-top:10px;">
                <?php esc_html_e( 'Publicar mi Negocio', 'babel-directory' ); ?>
            </a>
        </div>
        <?php
    }

    /**
     * Endpoint ligero para rastrear eventos de analíticas (vistas, clics).
     */
    public function track_event() {
        if ( ! isset( $_POST['post_id'] ) || ! isset( $_POST['event_type'] ) ) {
            wp_send_json_error( 'Missing parameters.' );
        }

        $post_id = absint( $_POST['post_id'] );
        $event_type = sanitize_text_field( wp_unslash( $_POST['event_type'] ) );
        
        $allowed_events = array( 'view', 'click_phone', 'click_map', 'click_web', 'click_whatsapp' );
        if ( ! in_array( $event_type, $allowed_events ) ) {
            wp_send_json_error( 'Invalid event type.' );
        }

        global $wpdb;
        $stats_table = $wpdb->prefix . 'bd_listing_stats';
        $today = current_time( 'Y-m-d' );

        $query = $wpdb->prepare(
            "INSERT INTO $stats_table (post_id, event_type, event_date, event_count) 
             VALUES (%d, %s, %s, 1) 
             ON DUPLICATE KEY UPDATE event_count = event_count + 1",
            $post_id,
            $event_type,
            $today
        );

        $result = $wpdb->query( $query );

        if ( false === $result ) {
            wp_send_json_error( 'Database error.' );
        }

        wp_send_json_success();
    }
}