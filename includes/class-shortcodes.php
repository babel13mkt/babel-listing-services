<?php
namespace Babel\Directory;

/**
 * Shortcodes para UI del Frontend (BD_Shortcodes)
 * Provee componentes visuales para integrar en Divi 5.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
class Shortcodes {

    public function __construct() {
        add_shortcode( 'babel_radar_search', array( $this, 'render_radar_search' ) );
        add_shortcode( 'babel_region_grid', array( $this, 'render_region_grid' ) );
        add_shortcode( 'bd_footer_regions', array( $this, 'render_footer_regions' ) );
        add_shortcode( 'bd_footer_categories', array( $this, 'render_footer_categories' ) );
        add_shortcode( 'bd_archive_loop', array( $this, 'render_archive_loop' ) );
        add_shortcode( 'bd_region_template', array( $this, 'render_region_template' ) );
        add_shortcode( 'bd_business_profile', array( $this, 'render_business_profile' ) );
    }

    public function render_radar_search( $atts ) {
        wp_enqueue_style( 'babel-public-css' );
        wp_enqueue_script( 'babel-public-js' );
        ob_start();
        ?>
        <div class="babel-search-section">
            <form id="babel-search-form" class="babel-search-form-wrapper" action="/buscar/" method="GET" autocomplete="off">
                <!-- Entrada de Búsqueda por Palabra Clave -->
                <div class="babel-search-field">
                    <div class="babel-input-icon-wrapper">
                        <span class="babel-input-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                        </span>
                        <input type="text" id="babel-search-keyword" name="keyword" placeholder="¿Qué buscas? (ej. sushi, completos, barberías...)" />
                    </div>
                </div>

                <!-- Control GPS / Radar Integrado -->
                <div class="babel-search-radar-wrapper">
                    <div class="babel-radar-control-group">
                        <button type="button" id="babel-geo-btn" class="babel-radar-btn" title="Buscar cerca de mí (GPS)">
                            <svg class="radar-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20">
                                <circle cx="12" cy="12" r="10"></circle>
                                <circle cx="12" cy="12" r="3"></circle>
                                <line x1="12" y1="1" x2="12" y2="3"></line>
                                <line x1="12" y1="21" x2="12" y2="23"></line>
                                <line x1="1" y1="12" x2="3" y2="12"></line>
                                <line x1="21" y1="12" x2="23" y2="12"></line>
                            </svg>
                            <span class="radar-ripple"></span>
                        </button>
                        <input type="hidden" id="babel-search-lat" name="lat" value="" />
                        <input type="hidden" id="babel-search-lng" name="lng" value="" />
                        <input type="hidden" id="babel-search-radius" name="radius" value="25" />
                    </div>
                </div>

                <!-- Botón de Búsqueda Directa -->
                <div class="babel-search-submit-wrapper">
                    <button type="submit" class="babel-search-submit-btn">Buscar</button>
                </div>
            </form>

            <!-- Contenedor Dinámico para Carga Asíncrona (AJAX) -->
            <div id="babel-directory-results" class="babel-results-container"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_region_grid( $atts ) {
        wp_enqueue_style( 'babel-public-css' );
        $atts = shortcode_atts( array(
            'columns' => 4,
            'rows'    => 4,
        ), $atts, 'babel_region_grid' );

        $columns = intval( $atts['columns'] );
        $rows = intval( $atts['rows'] );
        $limit = $columns * $rows;

        $terms_args = array(
            'taxonomy'   => 'babel_region',
            'hide_empty' => false,
            'parent'     => 0,
        );

        if ( $limit > 0 ) {
            $terms_args['number'] = $limit;
        }

        $terms = get_terms( $terms_args );

        if ( \is_wp_error( $terms ) || empty( $terms ) ) {
            return '<p>No se encontraron regiones.</p>';
        }

        ob_start();
        echo '<div class="babel-region-grid" style="--babel-grid-cols: ' . esc_attr( $columns ) . ';">';
        
        foreach ( $terms as $term ) {
            $image_id = get_term_meta( $term->term_id, 'bd_term_image_id', true );
            $image_url = '';
            
            if ( $image_id ) {
                $image_url = wp_get_attachment_image_url( $image_id, 'large' );
            } else {
                // Fallback a un gradiente bonito si la imagen no cargó aún
                $image_url = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300"><rect width="400" height="300" fill="%232c3e50"/></svg>';
            }

            $term_link = get_term_link( $term );
            if ( \is_wp_error( $term_link ) ) {
                continue;
            }

            // Limpiar nombre para la clase PIP
            $clean_name = preg_replace('/^[IVX]+\s*-\s*REG\s*-\s*/i', '', $term->name);

            // Detectar si la región tiene efecto PIP en la imagen original para recortar bordes borrosos
            $has_pip = false;
            $pip_regions = array( 'atacama', 'valparaiso', 'valparaíso', 'magallanes', 'arica' );
            foreach ( $pip_regions as $pip_r ) {
                if ( stripos( $clean_name, $pip_r ) !== false ) {
                    $has_pip = true;
                    break;
                }
            }
            $pip_class = $has_pip ? ' babel-region-pip-fix' : '';

            // Obtener el conteo recursivo de negocios (región padre + comunas hijas)
            $child_ids = get_term_children( $term->term_id, 'babel_region' );
            $term_ids = array( $term->term_id );
            if ( ! \is_wp_error( $child_ids ) && ! empty( $child_ids ) ) {
                $term_ids = array_merge( $term_ids, $child_ids );
            }

            $business_query = new \WP_Query( array(
                'post_type'      => 'babel_business',
                'post_status'    => 'publish',
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'no_found_rows'  => false,
                'tax_query'      => array(
                    array(
                        'taxonomy' => 'babel_region',
                        'field'    => 'term_id',
                        'terms'    => $term_ids,
                        'operator' => 'IN',
                    ),
                ),
            ) );
            $business_count = $business_query->found_posts;

            ?>
            <div class="babel-region-wrapper">
                <a href="<?php echo esc_url( $term_link ); ?>" class="babel-region-card no-lightbox disable-lightbox" target="_self" data-et-has-event-already="true">
                    <div class="babel-region-bg<?php echo esc_attr( $pip_class ); ?>" style="background-image: url('<?php echo esc_url( $image_url ); ?>');"></div>
                    <div class="babel-region-overlay"></div>
                    <div class="babel-region-content">
                        <span class="babel-region-title"><?php echo esc_html( $term->name ); ?></span>
                    </div>
                </a>
            </div>
            <?php
        }
        
        echo '</div>';
        return ob_get_clean();
    }

    /**
     * Shortcode [bd_footer_regions] para renderizar un listado de regiones ordenadas alfabéticamente
     */
    public function render_footer_regions( $atts ) {
        wp_enqueue_style( 'babel-public-css' );

        $atts = shortcode_atts( array(
            'columns' => 2,
            'rows'    => 8,
            'orderby' => 'name',
            'order'   => 'ASC',
        ), $atts, 'bd_footer_regions' );

        $columns = intval( $atts['columns'] );
        $rows = intval( $atts['rows'] );

        $terms = get_terms( array(
            'taxonomy'   => 'babel_region',
            'parent'     => 0,
            'hide_empty' => false,
        ) );

        if ( \is_wp_error( $terms ) || empty( $terms ) ) {
            return '';
        }

        // Ordenar alfabéticamente por el nombre original (incluye números romanos)
        usort( $terms, function( $a, $b ) {
            return strcasecmp( $a->name, $b->name );
        } );

        // Limitar los elementos
        $limit = $columns * $rows;
        if ( $limit > 0 && count( $terms ) > $limit ) {
            $terms = array_slice( $terms, 0, $limit );
        }

        ob_start();
        ?>
        <div class="bd-regions-col">
            <ul style="column-count: <?php echo esc_attr( $columns ); ?>; column-gap: 20px;">
                <?php foreach ( $terms as $term ) : ?>
                    <?php
                    $term_link = get_term_link( $term );
                    if ( \is_wp_error( $term_link ) ) {
                        continue;
                    }
                    ?>
                    <li>
                        <a href="<?php echo esc_url( $term_link ); ?>">
                            <?php echo esc_html( $term->name ); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode [bd_footer_categories] para renderizar un listado de categorías principales
     */
    public function render_footer_categories( $atts ) {
        wp_enqueue_style( 'babel-public-css' );

        $atts = shortcode_atts( array(
            'columns' => 3,
            'rows'    => 8,
            'parent'  => 0,
            'orderby' => 'name',
            'order'   => 'ASC',
        ), $atts, 'bd_footer_categories' );

        $columns = intval( $atts['columns'] );
        $rows = intval( $atts['rows'] );
        $parent = $atts['parent'] === 'any' ? '' : intval( $atts['parent'] );

        $terms_args = array(
            'taxonomy'   => 'babel_category',
            'hide_empty' => false,
            'orderby'    => $atts['orderby'],
            'order'      => $atts['order'],
        );

        if ( $parent !== '' ) {
            $terms_args['parent'] = $parent;
        }

        $terms = get_terms( $terms_args );

        if ( \is_wp_error( $terms ) || empty( $terms ) ) {
            return '';
        }

        // Limitar los elementos
        $limit = $columns * $rows;
        if ( $limit > 0 && count( $terms ) > $limit ) {
            $terms = array_slice( $terms, 0, $limit );
        }

        ob_start();
        ?>
        <div class="bd-categories-col">
            <ul style="column-count: <?php echo esc_attr( $columns ); ?>; column-gap: 30px;">
                <?php foreach ( $terms as $term ) : ?>
                    <?php
                    $term_link = get_term_link( $term );
                    if ( \is_wp_error( $term_link ) ) {
                        continue;
                    }
                    ?>
                    <li>
                        <a href="<?php echo esc_url( $term_link ); ?>">
                            <?php echo esc_html( $term->name ); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode [bd_archive_loop] para inyectar el loop nativo de resultados
     * en plantillas visuales del Divi Theme Builder (ideal para CPT/Taxonomías)
     */
    public function render_archive_loop( $atts ) {
        wp_enqueue_style( 'babel-public-css' );
        
        ob_start();
        ?>
        <div class="sdc-grid-archive">
            <?php if ( have_posts() ) : ?>
                <?php while ( have_posts() ) : the_post(); 
                    
                    $categorias = get_the_terms( get_the_ID(), 'babel_category' );
                    $regiones = get_the_terms( get_the_ID(), 'babel_region' );
                    
                    $cat_name = ! empty( $categorias ) && ! \is_wp_error( $categorias ) ? $categorias[0]->name : 'Comercio';
                    $reg_name = '';
                    if ( ! empty( $regiones ) && ! \is_wp_error( $regiones ) ) {
                        // Si el término es una comuna (tiene padre), podemos mostrar su nombre.
                        $reg_name = preg_replace('/^[IVX]+\s*-\s*REG\s*-\s*/i', '', $regiones[0]->name);
                    }
                ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class('sdc-card-business'); ?>>
                        <a href="<?php the_permalink(); ?>">
                            <div class="sdc-card-img">
                                <?php if ( has_post_thumbnail() ) : ?>
                                    <?php the_post_thumbnail( 'medium' ); ?>
                                <?php else : ?>
                                    <img src="https://images.unsplash.com/photo-1473116763249-2faaef81ccda?auto=format&fit=crop&q=80&w=600" alt="<?php the_title_attribute(); ?>">
                                <?php endif; ?>
                                
                                <div class="sdc-card-badge"><?php echo esc_html( $cat_name ); ?></div>
                            </div>
                            <div class="sdc-card-content">
                                <h3 class="sdc-card-title"><?php the_title(); ?></h3>
                                <div class="sdc-card-excerpt">
                                    <?php echo wp_trim_words( get_the_excerpt(), 15, '...' ); ?>
                                </div>
                                <div class="sdc-card-footer">
                                    <div class="sdc-card-rating">
                                        <i class="material-symbols-outlined">star</i> 4.5
                                    </div>
                                    <?php if ( ! empty( $reg_name ) ) : ?>
                                        <div class="sdc-card-location">
                                            <i class="material-symbols-outlined">location_on</i> <?php echo esc_html( $reg_name ); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    </article>
                <?php endwhile; ?>
            <?php else : ?>
                <p class="sdc-no-results">No se encontraron negocios en esta región.</p>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <div class="sdc-pagination">
            <?php
            the_posts_pagination( array(
                'mid_size'  => 2,
                'prev_text' => '<i class="material-symbols-outlined">chevron_left</i>',
                'next_text' => '<i class="material-symbols-outlined">chevron_right</i>',
            ) );
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode [bd_region_template] para renderizar la página de región interactiva completa.
     */
    public function render_region_template( $atts ) {
        wp_enqueue_style( 'babel-public-css' );
        wp_enqueue_script( 'babel-public-js' );

        $atts = shortcode_atts( array(
            'region' => 'auto',
        ), $atts, 'bd_region_template' );

        $term = null;
        if ( 'auto' === $atts['region'] ) {
            $term = get_queried_object();
        } else {
            $term = get_term_by( 'slug', $atts['region'], 'babel_region' );
        }

        if ( ! $term || \is_wp_error( $term ) || ! is_a( $term, 'WP_Term' ) || 'babel_region' !== $term->taxonomy ) {
            // Fallback para cuando no estamos en una página de taxonomía (ej. previsualización en página normal)
            $terms = get_terms( array(
                'taxonomy'   => 'babel_region',
                'number'     => 1,
                'hide_empty' => false,
            ) );
            if ( ! \is_wp_error( $terms ) && ! empty( $terms ) ) {
                $term = $terms[0];
            }
        }

        if ( ! $term ) {
            return '<p>' . esc_html__( 'Región no encontrada.', 'babel-directory' ) . '</p>';
        }

        // Limpiar el nombre
        $full_name = $term->name;
        $clean_name = preg_replace('/^[IVX]+\s*-\s*REG\s*-\s*/i', '', $full_name);
        preg_match('/^([IVX]+)/i', $full_name, $matches);
        $eyebrow = ! empty( $matches[1] ) ? sprintf( __( 'Región %s', 'babel-directory' ), $matches[1] ) : __( 'Región de Chile', 'babel-directory' );

        // Obtener la imagen
        $image_id = get_term_meta( $term->term_id, 'bd_term_image_id', true );
        $image_url = '';
        if ( $image_id ) {
            $image_url = wp_get_attachment_image_url( $image_id, 'large' );
        } else {
            $image_url = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="400"><rect width="1200" height="400" fill="%23023047"/></svg>';
        }

        // Conteo de negocios
        $child_ids = get_term_children( $term->term_id, 'babel_region' );
        $term_ids = array( $term->term_id );
        if ( ! \is_wp_error( $child_ids ) && ! empty( $child_ids ) ) {
            $term_ids = array_merge( $term_ids, $child_ids );
        }
        $business_query = new \WP_Query( array(
            'post_type'      => 'babel_business',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => false,
            'tax_query'      => array(
                array(
                    'taxonomy' => 'babel_region',
                    'field'    => 'term_id',
                    'terms'    => $term_ids,
                    'operator' => 'IN',
                ),
            ),
        ) );
        $business_count = $business_query->found_posts;

        // Obtener categorías de nivel superior
        $categories = get_terms( array(
            'taxonomy'   => 'babel_category',
            'parent'     => 0,
            'hide_empty' => false,
        ) );

        // Emojis de fallback para categorías principales
        $cat_emojis = array(
            'restaurantes'  => '🍔',
            'comida'        => '🍔',
            'alojamiento'   => '🏨',
            'hoteles'       => '🏨',
            'turismo'       => '🗺️',
            'servicios'     => '💼',
            'compras'       => '🛍️',
            'tiendas'       => '🛍️',
            'salud'         => '🏥',
            'educación'     => '🎓',
            'entretenimiento'=> '🎉',
            'barberias'     => '💈',
            'barbería'      => '💈',
        );

        ob_start();
        ?>
        <div class="bd-region-container">
            <!-- Hero Section -->
            <div class="bd-region-hero">
                <div class="bd-region-hero-bg" style="background-image: url('<?php echo esc_url( $image_url ); ?>');"></div>
                <div class="bd-region-hero-overlay"></div>
                <div class="bd-region-hero-content">
                    <span class="bd-region-hero-eyebrow"><?php echo esc_html( $eyebrow ); ?></span>
                    <h1 class="bd-region-hero-title"><?php echo esc_html( $clean_name ); ?></h1>
                    <p class="bd-region-hero-count">
                        <?php 
                        printf( 
                            _n( '<strong>%d</strong> negocio registrado', '<strong>%d</strong> negocios registrados', $business_count, 'babel-directory' ), 
                            $business_count 
                        ); 
                        ?>
                    </p>
                </div>
            </div>

            <!-- Categories Pills Section -->
            <div class="bd-region-cats-section">
                <div class="bd-region-cats-label"><?php esc_html_e( 'Filtrar por Categoría', 'babel-directory' ); ?></div>
                <div class="bd-category-pills">
                    <a class="bd-category-pill active" data-category="">
                        <span class="bd-category-pill-icon">🌟</span>
                        <span class="bd-category-pill-name"><?php esc_html_e( 'Todos', 'babel-directory' ); ?></span>
                    </a>
                    <?php if ( ! \is_wp_error( $categories ) && ! empty( $categories ) ) : ?>
                        <?php foreach ( $categories as $cat ) : 
                            $emoji = '🏷️';
                            $slug_lower = strtolower( $cat->slug );
                            foreach ( $cat_emojis as $key => $emo ) {
                                if ( false !== strpos( $slug_lower, $key ) ) {
                                    $emoji = $emo;
                                    break;
                                }
                            }
                        ?>
                            <a class="bd-category-pill" data-category="<?php echo esc_attr( $cat->slug ); ?>">
                                <span class="bd-category-pill-icon"><?php echo esc_html( $emoji ); ?></span>
                                <span class="bd-category-pill-name"><?php echo esc_html( $cat->name ); ?></span>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Results Header Section -->
            <div class="bd-region-results-header">
                <h2 class="bd-region-results-title">
                    <?php 
                    printf( 
                        esc_html__( 'Todos los negocios en %s', 'babel-directory' ), 
                        esc_html( $clean_name ) 
                    ); 
                    ?>
                </h2>
            </div>

            <!-- Results Wrap -->
            <div class="bd-region-results-wrap">
                <div id="babel-directory-results" class="babel-results-container" data-region="<?php echo esc_attr( $term->slug ); ?>" data-category="">
                    <!-- Los resultados se cargan vía AJAX/REST al cargar la página -->
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode [bd_business_profile] para renderizar el perfil de negocio premium
     */
    public function render_business_profile( $atts ) {
        \wp_enqueue_style( 'babel-public-css' );

        $atts = shortcode_atts( array(
            'id' => \get_the_ID(),
        ), $atts, 'bd_business_profile' );

        $post_id = intval( $atts['id'] );
        if ( ! $post_id || 'babel_business' !== \get_post_type( $post_id ) ) {
            return '';
        }

        // Obtener metadatos
        $phone            = \get_post_meta( $post_id, '_babel_phone', true );
        $phone_alt        = \get_post_meta( $post_id, '_babel_phone_alt', true );
        $whatsapp         = \get_post_meta( $post_id, '_babel_whatsapp', true );
        $email            = \get_post_meta( $post_id, '_babel_email', true );
        $email_alt        = \get_post_meta( $post_id, '_babel_email_alt', true );
        $website          = \get_post_meta( $post_id, '_babel_website', true );
        $address          = \get_post_meta( $post_id, '_babel_address', true );
        $coverage_area    = \get_post_meta( $post_id, '_babel_coverage_area', true );
        $maps             = \get_post_meta( $post_id, '_babel_maps', true );
        $lat              = \get_post_meta( $post_id, '_babel_lat', true );
        $lng              = \get_post_meta( $post_id, '_babel_lng', true );
        $instagram        = \get_post_meta( $post_id, '_babel_instagram', true );
        $facebook         = \get_post_meta( $post_id, '_babel_facebook', true );
        $linkedin         = \get_post_meta( $post_id, '_babel_linkedin', true );
        $tiktok           = \get_post_meta( $post_id, '_babel_tiktok', true );
        $twitter          = \get_post_meta( $post_id, '_babel_twitter', true );
        $pinterest        = \get_post_meta( $post_id, '_babel_pinterest', true );
        $youtube_channel  = \get_post_meta( $post_id, '_babel_youtube_channel', true );
        $youtube_url      = \get_post_meta( $post_id, '_babel_youtube_url', true );
        $youtube_url_2    = \get_post_meta( $post_id, '_babel_youtube_url_2', true );
        $verified         = \get_post_meta( $post_id, '_babel_verified', true );
        $featured         = \get_post_meta( $post_id, '_babel_featured', true );
        $gallery_meta     = \get_post_meta( $post_id, '_babel_gallery', true );
        $hours_meta       = \get_post_meta( $post_id, '_babel_hours', true );
        $rut              = \get_post_meta( $post_id, '_babel_rut', true );
        $razon_social     = \get_post_meta( $post_id, '_babel_razon_social', true );
        $nombre_comercial = \get_post_meta( $post_id, '_babel_nombre_comercial', true );
        $giro             = \get_post_meta( $post_id, '_babel_giro', true );
        $patente          = \get_post_meta( $post_id, '_babel_patente', true );
        $rep_legal        = \get_post_meta( $post_id, '_babel_rep_legal', true );
        $founded_year     = \get_post_meta( $post_id, '_babel_founded_year', true );
        $parking          = \get_post_meta( $post_id, '_babel_parking', true );
        $pet_friendly     = \get_post_meta( $post_id, '_babel_pet_friendly', true );
        $payments_meta    = \get_post_meta( $post_id, '_babel_payments', true );
        $accessibility    = \get_post_meta( $post_id, '_babel_accessibility', true );
        $wifi             = \get_post_meta( $post_id, '_babel_wifi', true );
        $reservations     = \get_post_meta( $post_id, '_babel_reservations', true );
        $delivery         = \get_post_meta( $post_id, '_babel_delivery', true );
        $price_range      = \get_post_meta( $post_id, '_babel_price_range', true );
        $spaces_meta      = \get_post_meta( $post_id, '_babel_spaces', true );
        $biz_type         = \get_post_meta( $post_id, '_babel_biz_type', true );
        $languages_meta   = \get_post_meta( $post_id, '_babel_languages', true );
        $menu_url         = \get_post_meta( $post_id, '_babel_menu_url', true );
        $booking_url      = \get_post_meta( $post_id, '_babel_booking_url', true );

        // Decodificación de campos JSON
        $payments  = ! empty( $payments_meta ) ? \json_decode( $payments_meta, true ) : array();
        $spaces    = ! empty( $spaces_meta ) ? \json_decode( $spaces_meta, true ) : array();
        $languages = ! empty( $languages_meta ) ? \json_decode( $languages_meta, true ) : array();
        $hours     = ! empty( $hours_meta ) ? \json_decode( $hours_meta, true ) : array();

        if ( ! \is_array( $payments ) ) { $payments = array(); }
        if ( ! \is_array( $spaces ) ) { $spaces = array(); }
        if ( ! \is_array( $languages ) ) { $languages = array(); }
        if ( ! \is_array( $hours ) ) { $hours = array(); }

        // Iconos SVG Inline
        $svg_icons = array(
            'check' => '<svg class="bd-icon bd-icon-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
            'star' => '<svg class="bd-icon bd-icon-star" viewBox="0 0 24 24" fill="currentColor" width="14" height="14"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>',
            'parking' => '<svg class="bd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="17" x2="9" y2="7"></line><path d="M9 7h4a3 3 0 0 1 0 6H9"></path></svg>',
            'wifi' => '<svg class="bd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M5 12.55a11 11 0 0 1 14.08 0"></path><path d="M1.42 9a16 16 0 0 1 21.16 0"></path><path d="M8.53 16.11a6 6 0 0 1 6.95 0"></path><line x1="12" y1="20" x2="12.01" y2="20"></line></svg>',
            'pet' => '<svg class="bd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><circle cx="12" cy="5" r="2"></circle><circle cx="6" cy="9" r="2"></circle><circle cx="18" cy="9" r="2"></circle><circle cx="9" cy="14" r="2"></circle><circle cx="15" cy="14" r="2"></circle><path d="M12 21a5 5 0 0 0 5-5v-1a2 2 0 0 0-4 0v1h-2v-1a2 2 0 0 0-4 0v1a5 5 0 0 0 5 5z"></path></svg>',
            'delivery' => '<svg class="bd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>',
            'accessibility' => '<svg class="bd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><circle cx="12" cy="4" r="1.5"></circle><path d="M10 9.5a2.5 2.5 0 0 1 5 0v3.5l2.5 4"></path><path d="M9.5 13H13v4l-2 3"></path></svg>',
            'reservations' => '<svg class="bd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
            'terrace' => '<svg class="bd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M12 2v20"></path><path d="M2 10h20"></path><path d="M12 10a8 8 0 0 1 8-8"></path><path d="M12 10a8 8 0 0 0-8-8"></path></svg>',
            'air_conditioned' => '<svg class="bd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M12 2v20"></path><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H7"></path></svg>',
            'heating' => '<svg class="bd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"></path></svg>',
            'smoking_area' => '<svg class="bd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><line x1="18" y1="8" x2="22" y2="8"></line><line x1="18" y1="12" x2="22" y2="12"></line><line x1="18" y1="16" x2="22" y2="16"></line><path d="M2 18h11a4 4 0 0 0 4-4V6a4 4 0 0 0-4-4H2"></path></svg>',
            'private_room' => '<svg class="bd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>',
            'language' => '<svg class="bd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>',
            'payment' => '<svg class="bd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><rect x="2" y="5" width="20" height="14" rx="2" ry="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line></svg>',
            'phone' => '<svg class="bd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>',
            'whatsapp' => '<svg class="bd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>',
            'email' => '<svg class="bd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>',
            'website' => '<svg class="bd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>',
            'menu' => '<svg class="bd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>',
            'booking' => '<svg class="bd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line><path d="M8 14h.01"></path><path d="M12 14h.01"></path><path d="M16 14h.01"></path><path d="M8 18h.01"></path><path d="M12 18h.01"></path><path d="M16 18h.01"></path></svg>',
            'instagram' => '<svg class="bd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>',
            'facebook' => '<svg class="bd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>',
            'linkedin' => '<svg class="bd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path><rect x="2" y="9" width="4" height="12"></rect><circle cx="4" cy="4" r="2"></circle></svg>',
            'tiktok' => '<svg class="bd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"></path></svg>',
            'twitter' => '<svg class="bd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"></path></svg>',
            'pinterest' => '<svg class="bd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M8 22a9 9 0 0 1-1.91-5.17c0-3.32 1.83-6.4 5.3-6.4 3.03 0 4.7 1.87 4.7 4.3 0 3.63-1.63 6.13-4.13 6.13-1.33 0-2.33-.94-2.02-2.12.38-1.42 1.1-2.95 1.1-3.98 0-.9-.55-1.67-1.7-1.67-1.34 0-2.4 1.25-2.4 2.92 0 1.07.4 1.8.4 1.8l-1.38 5.17A9.01 9.01 0 0 1 8 22z"></path></svg>',
            'youtube' => '<svg class="bd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"></path><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"></polygon></svg>',
            'clock' => '<svg class="bd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>',
            'map-pin' => '<svg class="bd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M21 10c0 7-9 13-9 13s-9-6-9-10a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>',
            'shield' => '<svg class="bd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>',
            'tag' => '<svg class="bd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>',
            'dollar' => '<svg class="bd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>',
            'briefcase' => '<svg class="bd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>'
        );

        \ob_start();
        ?>
        <div class="bd-profile-wrapper <?php echo ( '1' === $featured ) ? 'bd-profile-featured' : ''; ?>">
            
            <!-- 1. HERO HEADER -->
            <div class="bd-profile-hero">
                <?php if ( \has_post_thumbnail( $post_id ) ) : ?>
                    <div class="bd-profile-logo-wrapper">
                        <?php echo \get_the_post_thumbnail( $post_id, 'medium', array( 'class' => 'bd-profile-logo' ) ); ?>
                    </div>
                <?php endif; ?>

                <div class="bd-profile-title-section">
                    <div class="bd-profile-badges-row">
                        <?php if ( '1' === $verified ) : ?>
                            <span class="bd-badge-pill bd-badge-verified">
                                <?php echo $svg_icons['check']; ?>
                                <?php esc_html_e( 'Verificado', 'babel-directory' ); ?>
                            </span>
                        <?php endif; ?>
                        
                        <?php if ( '1' === $featured ) : ?>
                            <span class="bd-badge-pill bd-badge-featured">
                                <?php echo $svg_icons['star']; ?>
                                <?php esc_html_e( 'Destacado', 'babel-directory' ); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <h1 class="bd-profile-name"><?php echo esc_html( \get_the_title( $post_id ) ); ?></h1>
                    
                    <div class="bd-profile-meta-row">
                        <?php if ( ! empty( $price_range ) ) : ?>
                            <span class="bd-meta-item bd-meta-price" title="<?php esc_attr_e( 'Rango de precios', 'babel-directory' ); ?>">
                                <?php echo $svg_icons['dollar']; ?>
                                <strong><?php echo esc_html( $price_range ); ?></strong>
                            </span>
                        <?php endif; ?>

                        <?php if ( ! empty( $biz_type ) ) : 
                            $biz_types = array(
                                'physical' => __( 'Local físico', 'babel-directory' ),
                                'online'   => __( 'Solo online', 'babel-directory' ),
                                'hybrid'   => __( 'Híbrido', 'babel-directory' ),
                                'mobile'   => __( 'Itinerante / Móvil', 'babel-directory' ),
                            );
                            if ( isset( $biz_types[ $biz_type ] ) ) :
                            ?>
                                <span class="bd-meta-item bd-meta-type">
                                    <?php echo $svg_icons['briefcase']; ?>
                                    <?php echo esc_html( $biz_types[ $biz_type ] ); ?>
                                </span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- 2. DESCRIPCIÓN -->
            <?php 
            $content = \get_post_field( 'post_content', $post_id );
            if ( ! empty( $content ) ) : 
            ?>
                <div class="bd-profile-section bd-profile-description">
                    <h2 class="bd-section-subtitle"><?php esc_html_e( 'Sobre nosotros', 'babel-directory' ); ?></h2>
                    <div class="bd-description-content">
                        <?php echo \wpautop( $content ); ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 3. AMENITIES -->
            <?php
            $amenities_list = array();
            
            if ( ! empty( $parking ) && 'none' !== $parking ) {
                $parking_labels = array(
                    'street'  => __( 'Estacionamiento en la calle', 'babel-directory' ),
                    'private' => __( 'Estacionamiento privado', 'babel-directory' ),
                    'valet'   => __( 'Valet Parking', 'babel-directory' ),
                );
                if ( isset( $parking_labels[ $parking ] ) ) {
                    $amenities_list[] = array( 'label' => $parking_labels[ $parking ], 'icon' => 'parking' );
                }
            }

            if ( ! empty( $wifi ) && 'none' !== $wifi ) {
                $wifi_labels = array(
                    'free'         => __( 'Wi-Fi gratis', 'babel-directory' ),
                    'clients_only' => __( 'Wi-Fi clientes', 'babel-directory' ),
                );
                if ( isset( $wifi_labels[ $wifi ] ) ) {
                    $amenities_list[] = array( 'label' => $wifi_labels[ $wifi ], 'icon' => 'wifi' );
                }
            }

            if ( ! empty( $pet_friendly ) && 'no' !== $pet_friendly ) {
                $pet_labels = array(
                    'terrace_only' => __( 'Pet Friendly (Terraza)', 'babel-directory' ),
                    'full_access'  => __( 'Pet Friendly (Permitido)', 'babel-directory' ),
                );
                if ( isset( $pet_labels[ $pet_friendly ] ) ) {
                    $amenities_list[] = array( 'label' => $pet_labels[ $pet_friendly ], 'icon' => 'pet' );
                }
            }

            if ( ! empty( $delivery ) && 'none' !== $delivery ) {
                $delivery_labels = array(
                    'own'         => __( 'Delivery propio', 'babel-directory' ),
                    'third_party' => __( 'Delivery aplicaciones', 'babel-directory' ),
                    'pickup_only' => __( 'Solo retiro en local', 'babel-directory' ),
                );
                if ( isset( $delivery_labels[ $delivery ] ) ) {
                    $amenities_list[] = array( 'label' => $delivery_labels[ $delivery ], 'icon' => 'delivery' );
                }
            }

            if ( ! empty( $accessibility ) && 'none' !== $accessibility ) {
                $access_labels = array(
                    'full'    => __( 'Accesibilidad total', 'babel-directory' ),
                    'partial' => __( 'Accesibilidad parcial', 'babel-directory' ),
                );
                if ( isset( $access_labels[ $accessibility ] ) ) {
                    $amenities_list[] = array( 'label' => $access_labels[ $accessibility ], 'icon' => 'accessibility' );
                }
            }

            if ( ! empty( $reservations ) && 'not_needed' !== $reservations ) {
                $res_labels = array(
                    'required'    => __( 'Reserva obligatoria', 'babel-directory' ),
                    'recommended' => __( 'Reserva recomendada', 'babel-directory' ),
                );
                if ( isset( $res_labels[ $reservations ] ) ) {
                    $amenities_list[] = array( 'label' => $res_labels[ $reservations ], 'icon' => 'reservations' );
                }
            }

            if ( ! empty( $spaces ) && \is_array( $spaces ) ) {
                $spaces_labels = array(
                    'terrace'         => __( 'Terraza exterior', 'babel-directory' ),
                    'air_conditioned' => __( 'Aire acondicionado', 'babel-directory' ),
                    'heating'         => __( 'Calefacción', 'babel-directory' ),
                    'smoking_area'    => __( 'Área de fumadores', 'babel-directory' ),
                    'private_room'    => __( 'Sala privada', 'babel-directory' ),
                );
                foreach ( $spaces as $space ) {
                    if ( isset( $spaces_labels[ $space ] ) ) {
                        $amenities_list[] = array( 'label' => $spaces_labels[ $space ], 'icon' => isset( $svg_icons[ $space ] ) ? $space : 'star' );
                    }
                }
            }

            if ( ! empty( $languages ) && \is_array( $languages ) ) {
                $languages_labels = array(
                    'es'    => __( 'Español', 'babel-directory' ),
                    'en'    => __( 'Inglés', 'babel-directory' ),
                    'pt'    => __( 'Portugués', 'babel-directory' ),
                    'other' => __( 'Otros idiomas', 'babel-directory' ),
                );
                foreach ( $languages as $lang ) {
                    if ( isset( $languages_labels[ $lang ] ) ) {
                        $amenities_list[] = array( 'label' => sprintf( __( 'Idioma: %s', 'babel-directory' ), $languages_labels[ $lang ] ), 'icon' => 'language' );
                    }
                }
            }

            if ( ! empty( $payments ) && \is_array( $payments ) ) {
                $payments_labels = array(
                    'cash'         => __( 'Efectivo', 'babel-directory' ),
                    'debit'        => __( 'Débito', 'babel-directory' ),
                    'credit'       => __( 'Crédito', 'babel-directory' ),
                    'transfer'     => __( 'Transferencia', 'babel-directory' ),
                    'webpay'       => __( 'Webpay', 'babel-directory' ),
                    'mercadopago'  => __( 'MercadoPago', 'babel-directory' ),
                );
                foreach ( $payments as $pay ) {
                    if ( isset( $payments_labels[ $pay ] ) ) {
                        $amenities_list[] = array( 'label' => sprintf( __( 'Pago: %s', 'babel-directory' ), $payments_labels[ $pay ] ), 'icon' => 'payment' );
                    }
                }
            }

            if ( ! empty( $amenities_list ) ) :
            ?>
                <div class="bd-profile-section bd-profile-amenities">
                    <h2 class="bd-section-subtitle"><?php esc_html_e( 'Servicios y Comodidades', 'babel-directory' ); ?></h2>
                    <div class="bd-amenities-grid">
                        <?php foreach ( $amenities_list as $amenity ) : ?>
                            <div class="bd-amenity-chip">
                                <?php 
                                $icon_key = $amenity['icon'];
                                echo isset( $svg_icons[ $icon_key ] ) ? $svg_icons[ $icon_key ] : $svg_icons['star']; 
                                ?>
                                <span class="bd-amenity-label"><?php echo esc_html( $amenity['label'] ); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 4. CONTACTO -->
            <?php
            $has_contact = ( ! empty( $phone ) || ! empty( $phone_alt ) || ! empty( $whatsapp ) || ! empty( $email ) || ! empty( $email_alt ) || ! empty( $website ) || ! empty( $menu_url ) || ! empty( $booking_url ) );
            if ( $has_contact ) :
            ?>
                <div class="bd-profile-section bd-profile-contact">
                    <h2 class="bd-section-subtitle"><?php esc_html_e( 'Contacto y Enlaces', 'babel-directory' ); ?></h2>
                    <div class="bd-contact-buttons">
                        <?php if ( ! empty( $phone ) ) : ?>
                            <a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>" class="bd-btn bd-btn-contact bd-btn-phone">
                                <?php echo $svg_icons['phone']; ?>
                                <span><?php echo esc_html( $phone ); ?></span>
                            </a>
                        <?php endif; ?>

                        <?php if ( ! empty( $phone_alt ) ) : ?>
                            <a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone_alt ) ); ?>" class="bd-btn bd-btn-contact bd-btn-phone-alt">
                                <?php echo $svg_icons['phone']; ?>
                                <span><?php echo esc_html( $phone_alt ); ?></span>
                            </a>
                        <?php endif; ?>

                        <?php if ( ! empty( $whatsapp ) ) : 
                            $wa_number = preg_replace( '/[^0-9]/', '', $whatsapp );
                            ?>
                            <a href="https://wa.me/<?php echo esc_attr( $wa_number ); ?>" target="_blank" rel="noopener noreferrer" class="bd-btn bd-btn-contact bd-btn-whatsapp">
                                <?php echo $svg_icons['whatsapp']; ?>
                                <span><?php esc_html_e( 'WhatsApp', 'babel-directory' ); ?></span>
                            </a>
                        <?php endif; ?>

                        <?php if ( ! empty( $email ) ) : ?>
                            <a href="mailto:<?php echo esc_attr( $email ); ?>" class="bd-btn bd-btn-contact bd-btn-email">
                                <?php echo $svg_icons['email']; ?>
                                <span><?php echo esc_html( $email ); ?></span>
                            </a>
                        <?php endif; ?>

                        <?php if ( ! empty( $email_alt ) ) : ?>
                            <a href="mailto:<?php echo esc_attr( $email_alt ); ?>" class="bd-btn bd-btn-contact bd-btn-email-alt">
                                <?php echo $svg_icons['email']; ?>
                                <span><?php echo esc_html( $email_alt ); ?></span>
                            </a>
                        <?php endif; ?>

                        <?php if ( ! empty( $website ) ) : ?>
                            <a href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener noreferrer" class="bd-btn bd-btn-contact bd-btn-website">
                                <?php echo $svg_icons['website']; ?>
                                <span><?php esc_html_e( 'Sitio Web', 'babel-directory' ); ?></span>
                            </a>
                        <?php endif; ?>

                        <?php if ( ! empty( $menu_url ) ) : ?>
                            <a href="<?php echo esc_url( $menu_url ); ?>" target="_blank" rel="noopener noreferrer" class="bd-btn bd-btn-contact bd-btn-menu">
                                <?php echo $svg_icons['menu']; ?>
                                <span><?php esc_html_e( 'Ver Menú / PDF', 'babel-directory' ); ?></span>
                            </a>
                        <?php endif; ?>

                        <?php if ( ! empty( $booking_url ) ) : ?>
                            <a href="<?php echo esc_url( $booking_url ); ?>" target="_blank" rel="noopener noreferrer" class="bd-btn bd-btn-contact bd-btn-booking">
                                <?php echo $svg_icons['booking']; ?>
                                <span><?php esc_html_e( 'Reservar Online', 'babel-directory' ); ?></span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 5. UBICACIÓN -->
            <?php if ( ! empty( $address ) || ! empty( $maps ) || ( ! empty( $lat ) && ! empty( $lng ) ) ) : ?>
                <div class="bd-profile-section bd-profile-location">
                    <h2 class="bd-section-subtitle"><?php esc_html_e( 'Ubicación', 'babel-directory' ); ?></h2>
                    
                    <?php if ( ! empty( $address ) ) : ?>
                        <div class="bd-location-address">
                            <?php echo $svg_icons['map-pin']; ?>
                            <span><?php echo esc_html( $address ); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php 
                    $map_iframe_url = '';
                    if ( ! empty( $lat ) && ! empty( $lng ) ) {
                        $map_iframe_url = "https://maps.google.com/maps?q=" . esc_attr( $lat ) . "," . esc_attr( $lng ) . "&z=15&output=embed";
                    } elseif ( ! empty( $address ) ) {
                        $map_iframe_url = "https://maps.google.com/maps?q=" . \rawurlencode( $address ) . "&z=15&output=embed";
                    }

                    if ( ! empty( $map_iframe_url ) ) :
                    ?>
                        <div class="bd-map-container">
                            <iframe src="<?php echo esc_url( $map_iframe_url ); ?>" width="100%" height="350" style="border:0; border-radius:12px;" allowfullscreen="" loading="lazy"></iframe>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- 6. HORARIOS -->
            <?php
            $active_days_count = 0;
            if ( \is_array( $hours ) ) {
                foreach ( $hours as $day => $day_data ) {
                    if ( empty( $day_data['closed'] ) && ( ! empty( $day_data['open'] ) || ! empty( $day_data['close'] ) ) ) {
                        $active_days_count++;
                    }
                }
            }

            if ( $active_days_count > 0 ) :
                // Calcular estado actual Abierto/Cerrado en Chile
                $current_time = \wp_date( 'H:i' );
                $current_day_num = intval( \wp_date( 'N' ) );
                $days_map = array(
                    1 => 'Lunes',
                    2 => 'Martes',
                    3 => 'Miércoles',
                    4 => 'Jueves',
                    5 => 'Viernes',
                    6 => 'Sábado',
                    7 => 'Domingo'
                );
                $current_day_name = isset( $days_map[ $current_day_num ] ) ? $days_map[ $current_day_num ] : 'Lunes';

                $is_open = false;
                if ( isset( $hours[ $current_day_name ] ) && empty( $hours[ $current_day_name ]['closed'] ) ) {
                    $open_time  = $hours[ $current_day_name ]['open'];
                    $close_time = $hours[ $current_day_name ]['close'];
                    if ( ! empty( $open_time ) && ! empty( $close_time ) ) {
                        if ( $close_time >= $open_time ) {
                            $is_open = ( $current_time >= $open_time && $current_time <= $close_time );
                        } else {
                            $is_open = ( $current_time >= $open_time || $current_time <= $close_time );
                        }
                    }
                }
            ?>
                <div class="bd-profile-section bd-profile-hours">
                    <div class="bd-hours-header">
                        <h2 class="bd-section-subtitle"><?php esc_html_e( 'Horarios de Atención', 'babel-directory' ); ?></h2>
                        <span class="bd-status-badge <?php echo $is_open ? 'bd-status-open' : 'bd-status-closed'; ?>">
                            <?php echo $is_open ? esc_html__( 'Abierto ahora', 'babel-directory' ) : esc_html__( 'Cerrado ahora', 'babel-directory' ); ?>
                        </span>
                    </div>

                    <div class="bd-hours-list">
                        <?php 
                        $ordered_days = array( 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo' );
                        foreach ( $ordered_days as $day ) :
                            if ( isset( $hours[ $day ] ) && empty( $hours[ $day ]['closed'] ) && ( ! empty( $hours[ $day ]['open'] ) || ! empty( $hours[ $day ]['close'] ) ) ) :
                                $is_today = ( $day === $current_day_name );
                            ?>
                                <div class="bd-hours-row <?php echo $is_today ? 'bd-hours-today' : ''; ?>">
                                    <span class="bd-hours-day-name"><?php echo esc_html( $day ); ?></span>
                                    <span class="bd-hours-time-range">
                                        <?php echo esc_html( $hours[ $day ]['open'] ); ?> - <?php echo esc_html( $hours[ $day ]['close'] ); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 7. MULTIMEDIA -->
            <?php
            $gallery_ids = array();
            if ( ! empty( $gallery_meta ) ) {
                $gallery_ids = array_filter( array_map( 'intval', explode( ',', $gallery_meta ) ) );
            }

            $has_gallery = ! empty( $gallery_ids );
            $has_video   = ( ! empty( $youtube_url ) || ! empty( $youtube_url_2 ) );

            if ( $has_gallery || $has_video ) :
            ?>
                <div class="bd-profile-section bd-profile-multimedia">
                    
                    <!-- Galería de Fotos -->
                    <?php if ( $has_gallery ) : ?>
                        <div class="bd-gallery-section">
                            <h2 class="bd-section-subtitle"><?php esc_html_e( 'Galería de fotos', 'babel-directory' ); ?></h2>
                            <div class="bd-photo-grid">
                                <?php 
                                $lightbox_modals = '';
                                foreach ( $gallery_ids as $index => $img_id ) : 
                                    $thumb_url = \wp_get_attachment_image_url( $img_id, 'medium' );
                                    $full_url  = \wp_get_attachment_image_url( $img_id, 'large' );
                                    if ( $thumb_url && $full_url ) :
                                        $modal_id = "bd-lightbox-{$post_id}-{$index}";
                                    ?>
                                        <a href="#<?php echo esc_attr( $modal_id ); ?>" class="bd-gallery-item-link">
                                            <img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php esc_attr_e( 'Foto del negocio', 'babel-directory' ); ?>" class="bd-gallery-thumb" loading="lazy" />
                                        </a>
                                        <?php 
                                        // Guardar HTML del modal para renderizarlo al final del shortcode
                                        \ob_start();
                                        ?>
                                        <div id="<?php echo esc_attr( $modal_id ); ?>" class="bd-lightbox-modal">
                                            <a href="#_" class="bd-lightbox-close" aria-label="<?php esc_attr_e( 'Cerrar', 'babel-directory' ); ?>">&times;</a>
                                            <div class="bd-lightbox-content">
                                                <img src="<?php echo esc_url( $full_url ); ?>" alt="<?php esc_attr_e( 'Foto ampliada', 'babel-directory' ); ?>" />
                                            </div>
                                        </div>
                                        <?php
                                        $lightbox_modals .= \ob_get_clean();
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Videos de YouTube -->
                    <?php if ( $has_video ) : ?>
                        <div class="bd-videos-section">
                            <h2 class="bd-section-subtitle"><?php esc_html_e( 'Videos destacados', 'babel-directory' ); ?></h2>
                            <div class="bd-videos-grid">
                                <?php 
                                $videos_urls = array_filter( array( $youtube_url, $youtube_url_2 ) );
                                foreach ( $videos_urls as $v_url ) :
                                    \preg_match( '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i', $v_url, $match );
                                    $video_id = isset( $match[1] ) ? $match[1] : '';
                                    if ( ! empty( $video_id ) ) :
                                    ?>
                                        <div class="bd-video-wrapper">
                                            <iframe src="https://www.youtube.com/embed/<?php echo esc_attr( $video_id ); ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            <?php endif; ?>

            <!-- 8. REDES SOCIALES -->
            <?php
            $social_channels = array();
            if ( ! empty( $instagram ) ) { $social_channels['instagram'] = array( 'url' => $instagram, 'label' => 'Instagram' ); }
            if ( ! empty( $facebook ) )  { $social_channels['facebook']  = array( 'url' => $facebook, 'label' => 'Facebook' ); }
            if ( ! empty( $linkedin ) )  { $social_channels['linkedin']  = array( 'url' => $linkedin, 'label' => 'LinkedIn' ); }
            if ( ! empty( $tiktok ) )    { $social_channels['tiktok']    = array( 'url' => $tiktok, 'label' => 'TikTok' ); }
            if ( ! empty( $twitter ) )   { $social_channels['twitter']   = array( 'url' => $twitter, 'label' => 'Twitter/X' ); }
            if ( ! empty( $pinterest ) ) { $social_channels['pinterest'] = array( 'url' => $pinterest, 'label' => 'Pinterest' ); }
            if ( ! empty( $youtube_channel ) ) { $social_channels['youtube'] = array( 'url' => $youtube_channel, 'label' => 'YouTube' ); }

            if ( ! empty( $social_channels ) ) :
            ?>
                <div class="bd-profile-section bd-profile-social">
                    <h2 class="bd-section-subtitle"><?php esc_html_e( 'Síguenos en nuestras redes', 'babel-directory' ); ?></h2>
                    <div class="bd-social-icons">
                        <?php foreach ( $social_channels as $key => $chan ) : ?>
                            <a href="<?php echo esc_url( $chan['url'] ); ?>" target="_blank" rel="noopener noreferrer" class="bd-social-link bd-social-<?php echo esc_attr( $key ); ?>" aria-label="<?php echo esc_attr( $chan['label'] ); ?>">
                                <?php echo $svg_icons[ $key ]; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 9. LEGAL (Chile) -->
            <?php
            $has_legal = ( ! empty( $rut ) || ! empty( $razon_social ) || ! empty( $nombre_comercial ) || ! empty( $giro ) || ! empty( $patente ) || ! empty( $rep_legal ) || ! empty( $founded_year ) );
            if ( $has_legal ) :
            ?>
                <div class="bd-profile-section bd-profile-legal">
                    <details class="bd-legal-accordion">
                        <summary class="bd-legal-summary">
                            <?php echo $svg_icons['shield']; ?>
                            <span><?php esc_html_e( 'Información Legal y Comercial (Chile)', 'babel-directory' ); ?></span>
                            <span class="bd-accordion-arrow">▼</span>
                        </summary>
                        <div class="bd-legal-details-content">
                            <div class="bd-legal-grid">
                                <?php if ( ! empty( $nombre_comercial ) ) : ?>
                                    <div class="bd-legal-item">
                                        <strong><?php esc_html_e( 'Nombre Comercial:', 'babel-directory' ); ?></strong>
                                        <span><?php echo esc_html( $nombre_comercial ); ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if ( ! empty( $razon_social ) ) : ?>
                                    <div class="bd-legal-item">
                                        <strong><?php esc_html_e( 'Razón Social:', 'babel-directory' ); ?></strong>
                                        <span><?php echo esc_html( $razon_social ); ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if ( ! empty( $rut ) ) : ?>
                                    <div class="bd-legal-item">
                                        <strong><?php esc_html_e( 'RUT:', 'babel-directory' ); ?></strong>
                                        <span><?php echo esc_html( $rut ); ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if ( ! empty( $giro ) ) : ?>
                                    <div class="bd-legal-item">
                                        <strong><?php esc_html_e( 'Giro Comercial:', 'babel-directory' ); ?></strong>
                                        <span><?php echo esc_html( $giro ); ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if ( ! empty( $patente ) ) : ?>
                                    <div class="bd-legal-item">
                                        <strong><?php esc_html_e( 'Patente Comercial:', 'babel-directory' ); ?></strong>
                                        <span><?php echo esc_html( $patente ); ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if ( ! empty( $rep_legal ) ) : ?>
                                    <div class="bd-legal-item">
                                        <strong><?php esc_html_e( 'Representante Legal:', 'babel-directory' ); ?></strong>
                                        <span><?php echo esc_html( $rep_legal ); ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if ( ! empty( $founded_year ) ) : ?>
                                    <div class="bd-legal-item">
                                        <strong><?php esc_html_e( 'Año de Fundación:', 'babel-directory' ); ?></strong>
                                        <span><?php echo esc_html( $founded_year ); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </details>
                </div>
            <?php endif; ?>

        </div>
        
        <?php
        // Renderizar los Modales Lightbox al final fuera del contenedor principal para evitar problemas de posicionamiento relativo
        if ( ! empty( $lightbox_modals ) ) {
            echo $lightbox_modals;
        }
        ?>

        <?php
        return \ob_get_clean();
    }
}
