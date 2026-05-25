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
                    <article id="post-<?php the_ID(); ?>" <?php post_class('sdc-card-business bg-white rounded-2xl overflow-hidden bd-premium-shadow border border-gray-100 hover:-translate-y-1 hover:shadow-2xl transition-all duration-300 block w-full group relative flex flex-col'); ?>>
                        <a href="<?php the_permalink(); ?>" class="block h-full no-underline flex flex-col">
                            <!-- Cover Image -->
                            <div class="w-full h-48 relative overflow-hidden bg-gray-100">
                                <?php if ( has_post_thumbnail() ) : ?>
                                    <?php the_post_thumbnail( 'medium', array('class' => 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-500') ); ?>
                                <?php else : ?>
                                    <img src="https://images.unsplash.com/photo-1473116763249-2faaef81ccda?auto=format&fit=crop&q=80&w=600" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt="<?php the_title_attribute(); ?>">
                                <?php endif; ?>
                                
                                <!-- Floating Badge -->
                                <div class="absolute top-3 right-3 bg-black/60 backdrop-blur-md text-white px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
                                    <?php echo esc_html( $cat_name ); ?>
                                </div>
                            </div>
                            
                            <!-- Content -->
                            <div class="p-5 flex flex-col flex-grow">
                                <h3 class="bd-premium-font text-xl font-bold text-black mb-2 group-hover:text-yellow-600 transition-colors">
                                    <?php the_title(); ?>
                                </h3>
                                <div class="text-gray-500 text-sm leading-relaxed mb-4 flex-grow">
                                    <?php echo wp_trim_words( get_the_excerpt(), 15, '...' ); ?>
                                </div>
                                
                                <!-- Footer interno -->
                                <div class="pt-4 border-t border-gray-100 flex justify-between items-center mt-auto">
                                    <div class="flex items-center gap-1 text-yellow-500 font-bold text-sm">
                                        <span class="material-symbols-outlined text-[16px]" style="font-variation-settings: 'FILL' 1;">star</span> 4.5
                                    </div>
                                    <?php if ( ! empty( $reg_name ) ) : ?>
                                        <div class="flex items-center gap-1 text-gray-400 text-xs uppercase tracking-wider font-bold">
                                            <span class="material-symbols-outlined text-[14px]">location_on</span> <?php echo esc_html( $reg_name ); ?>
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
     * Shortcode [bd_business_profile] para renderizar el perfil de negocio premium (Stitch UI Reestructurado)
     */
    public function render_business_profile( $atts ) {
        \wp_enqueue_style( 'babel-public-css' );

        $atts = shortcode_atts( array(
            'id' => '',
        ), $atts, 'bd_business_profile' );

        if ( ! empty( $atts['id'] ) ) {
            $post_id = intval( $atts['id'] );
        } else {
            $queried_id = \get_queried_object_id();
            if ( $queried_id && 'babel_business' === \get_post_type( $queried_id ) ) {
                $post_id = $queried_id;
            } else {
                $post_id = \get_the_ID();
            }
        }

        if ( ! $post_id || 'babel_business' !== \get_post_type( $post_id ) ) {
            // Último intento: revisar el global post
            global $post;
            if ( isset( $post ) && 'babel_business' === $post->post_type ) {
                $post_id = $post->ID;
            } else {
                return '';
            }
        }

        // Obtener metadatos
        $phone            = \get_post_meta( $post_id, '_babel_phone', true );
        $whatsapp         = \get_post_meta( $post_id, '_babel_whatsapp', true );
        $email            = \get_post_meta( $post_id, '_babel_email', true );
        $website          = \get_post_meta( $post_id, '_babel_website', true );
        $address          = \get_post_meta( $post_id, '_babel_address', true );
        $instagram        = \get_post_meta( $post_id, '_babel_instagram', true );
        $facebook         = \get_post_meta( $post_id, '_babel_facebook', true );
        $linkedin         = \get_post_meta( $post_id, '_babel_linkedin', true );
        $tiktok           = \get_post_meta( $post_id, '_babel_tiktok', true );
        $twitter          = \get_post_meta( $post_id, '_babel_twitter', true );
        $youtube_channel  = \get_post_meta( $post_id, '_babel_youtube_channel', true );
        $verified         = \get_post_meta( $post_id, '_babel_verified', true );
        $featured         = \get_post_meta( $post_id, '_babel_featured', true );
        $gallery_meta     = \get_post_meta( $post_id, '_babel_gallery', true );
        $hours_meta       = \get_post_meta( $post_id, '_babel_hours', true );
        $rut              = \get_post_meta( $post_id, '_babel_rut', true );
        $razon_social     = \get_post_meta( $post_id, '_babel_razon_social', true );
        $parking          = \get_post_meta( $post_id, '_babel_parking', true );
        $pet_friendly     = \get_post_meta( $post_id, '_babel_pet_friendly', true );
        $wifi             = \get_post_meta( $post_id, '_babel_wifi', true );
        $reservations     = \get_post_meta( $post_id, '_babel_reservations', true );
        $delivery         = \get_post_meta( $post_id, '_babel_delivery', true );
        $price_range      = \get_post_meta( $post_id, '_babel_price_range', true );
        $biz_type         = \get_post_meta( $post_id, '_babel_biz_type', true );

        if ( \is_array( $hours_meta ) ) {
            $hours = $hours_meta;
        } else {
            $hours = ! empty( $hours_meta ) ? \json_decode( $hours_meta, true ) : array();
            if ( ! \is_array( $hours ) ) { $hours = array(); }
        }

        $content = \get_post_field( 'post_content', $post_id );

        \ob_start();
        ?>
        <div class="bd-premium-font max-w-[1200px] mx-auto py-10">
            <!-- 1. HERO HEADER -->
            <section class="flex flex-col md:flex-row items-center md:items-start gap-8 mb-16 border-b border-gray-200 pb-10">
                <?php if ( \has_post_thumbnail( $post_id ) ) : ?>
                    <div class="w-40 h-40 md:w-56 md:h-56 rounded-full overflow-hidden border-4 border-white bd-premium-shadow flex-shrink-0">
                        <?php echo \get_the_post_thumbnail( $post_id, 'medium', array( 'class' => 'w-full h-full object-cover' ) ); ?>
                    </div>
                <?php endif; ?>

                <div class="flex-1 text-center md:text-left pt-4">
                    <div class="flex flex-wrap justify-center md:justify-start gap-3 mb-4">
                        <?php if ( '1' === $verified ) : ?>
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-gray-100 border border-gray-200 rounded-full text-xs font-bold uppercase tracking-wider text-black">
                                <span class="material-symbols-outlined text-[14px]">verified</span> Verificado
                            </span>
                        <?php endif; ?>
                        <?php if ( '1' === $featured ) : ?>
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-yellow-400 text-yellow-900 rounded-full text-xs font-bold uppercase tracking-wider">
                                <span class="material-symbols-outlined text-[14px]">stars</span> Destacado
                            </span>
                        <?php endif; ?>
                    </div>
                    <h1 class="bd-display-font text-4xl md:text-6xl font-bold text-black mb-2 leading-tight">
                        <?php echo esc_html( \get_the_title( $post_id ) ); ?>
                    </h1>
                    <div class="flex justify-center md:justify-start items-center gap-4 text-gray-600 font-medium">
                        <?php if ( ! empty( $price_range ) ) : ?>
                            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[18px]">payments</span> <?php echo esc_html( $price_range ); ?></span>
                        <?php endif; ?>
                        <?php if ( ! empty( $biz_type ) ) : 
                            $biz_types = array( 'physical' => 'Local físico', 'online' => 'Solo online', 'hybrid' => 'Híbrido', 'mobile' => 'Móvil' );
                            if ( isset( $biz_types[ $biz_type ] ) ) : ?>
                                <span class="w-1 h-1 bg-gray-400 rounded-full"></span>
                                <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[18px]">location_on</span> <?php echo esc_html( $biz_types[ $biz_type ] ); ?></span>
                            <?php endif; 
                        endif; ?>
                    </div>
                </div>
            </section>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 relative">
                <!-- Main Content Area -->
                <div class="lg:col-span-8 space-y-12">
                    
                    <?php if ( ! empty( $content ) ) : ?>
                        <section>
                            <h2 class="bd-display-font text-3xl font-bold text-black mb-6 border-l-4 border-yellow-400 pl-4">Sobre nosotros</h2>
                            <div class="text-gray-700 leading-relaxed text-lg space-y-4">
                                <?php echo \wpautop( $content ); ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- Features Bento -->
                    <section>
                        <h2 class="bd-display-font text-3xl font-bold text-black mb-6">Características</h2>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            <?php if ( ! empty( $parking ) && 'none' !== $parking ) : ?>
                                <div class="p-6 bg-gray-50 rounded-2xl border border-gray-100 flex flex-col items-center text-center gap-3 hover:border-yellow-400 transition-all">
                                    <span class="material-symbols-outlined text-yellow-600 text-3xl">local_parking</span>
                                    <span class="font-bold text-sm">Estacionamiento</span>
                                </div>
                            <?php endif; ?>
                            <?php if ( ! empty( $wifi ) && 'none' !== $wifi ) : ?>
                                <div class="p-6 bg-gray-50 rounded-2xl border border-gray-100 flex flex-col items-center text-center gap-3 hover:border-yellow-400 transition-all">
                                    <span class="material-symbols-outlined text-yellow-600 text-3xl">wifi</span>
                                    <span class="font-bold text-sm">Wi-Fi Gratis</span>
                                </div>
                            <?php endif; ?>
                            <?php if ( ! empty( $pet_friendly ) && 'no' !== $pet_friendly ) : ?>
                                <div class="p-6 bg-gray-50 rounded-2xl border border-gray-100 flex flex-col items-center text-center gap-3 hover:border-yellow-400 transition-all">
                                    <span class="material-symbols-outlined text-yellow-600 text-3xl">pets</span>
                                    <span class="font-bold text-sm">Pet Friendly</span>
                                </div>
                            <?php endif; ?>
                            <div class="p-6 bg-gray-50 rounded-2xl border border-gray-100 flex flex-col items-center text-center gap-3 hover:border-yellow-400 transition-all">
                                <span class="material-symbols-outlined text-yellow-600 text-3xl">accessible</span>
                                <span class="font-bold text-sm">Accesibilidad</span>
                            </div>
                            <?php if ( '1' === $delivery ) : ?>
                                <div class="p-6 bg-gray-50 rounded-2xl border border-gray-100 flex flex-col items-center text-center gap-3 hover:border-yellow-400 transition-all">
                                    <span class="material-symbols-outlined text-yellow-600 text-3xl">delivery_dining</span>
                                    <span class="font-bold text-sm">Delivery</span>
                                </div>
                            <?php endif; ?>
                            <?php if ( '1' === $reservations ) : ?>
                                <div class="p-6 bg-gray-50 rounded-2xl border border-gray-100 flex flex-col items-center text-center gap-3 hover:border-yellow-400 transition-all">
                                    <span class="material-symbols-outlined text-yellow-600 text-3xl">calendar_month</span>
                                    <span class="font-bold text-sm">Reservas Activas</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>

                <!-- Sidebar / Sticky Contact Section -->
                <div class="lg:col-span-4 lg:sticky lg:top-24 h-fit">
                    <div class="bg-white p-8 rounded-3xl border border-gray-200 bd-premium-shadow space-y-8">
                        <div class="space-y-4">
                            <?php if ( ! empty( $phone ) ) : ?>
                                <div class="flex items-center gap-4 group cursor-pointer">
                                    <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-black group-hover:bg-black group-hover:text-white transition-all">
                                        <span class="material-symbols-outlined">call</span>
                                    </div>
                                    <span class="font-bold"><?php echo esc_html( $phone ); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ( ! empty( $whatsapp ) ) : ?>
                                <a href="https://wa.me/<?php echo esc_attr( preg_replace('/[^0-9]/', '', $whatsapp) ); ?>" target="_blank" class="flex items-center gap-4 group cursor-pointer no-underline">
                                    <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-[#25D366] group-hover:bg-[#25D366] group-hover:text-white transition-all">
                                        <span class="material-symbols-outlined">chat</span>
                                    </div>
                                    <span class="font-bold text-[#25D366]">WhatsApp Directo</span>
                                </a>
                            <?php endif; ?>
                            <?php if ( ! empty( $email ) ) : ?>
                                <div class="flex items-center gap-4 group cursor-pointer">
                                    <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-black group-hover:bg-black group-hover:text-white transition-all">
                                        <span class="material-symbols-outlined">mail</span>
                                    </div>
                                    <span class="font-bold"><?php echo esc_html( $email ); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ( ! empty( $address ) ) : ?>
                                <div class="flex items-start gap-4 pt-2">
                                    <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-black mt-1">
                                        <span class="material-symbols-outlined">pin_drop</span>
                                    </div>
                                    <div>
                                        <span class="font-bold block"><?php echo esc_html( $address ); ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ( ! empty( $website ) ) : ?>
                            <a href="<?php echo esc_url( $website ); ?>" target="_blank" class="block w-full py-4 bg-black text-white text-center rounded-2xl font-bold uppercase tracking-widest hover:bg-gray-800 transition-colors no-underline">
                                Visitar Sitio Web
                            </a>
                        <?php endif; ?>

                        <!-- Condensed Weekly Hours -->
                        <?php if ( ! empty( $hours ) ) : ?>
                            <div class="pt-6 border-t border-gray-200">
                                <h3 class="bd-display-font text-2xl text-black mb-4 text-center font-bold">Horario</h3>
                                <div class="space-y-2 text-sm">
                                    <?php
                                    $dias_es = array('monday'=>'Lunes', 'tuesday'=>'Martes', 'wednesday'=>'Miércoles', 'thursday'=>'Jueves', 'friday'=>'Viernes', 'saturday'=>'Sábado', 'sunday'=>'Domingo');
                                    foreach ( $dias_es as $key => $label ) {
                                        if ( isset( $hours[$key] ) ) {
                                            $day = $hours[$key];
                                            $val = $day['closed'] ? 'Cerrado' : esc_html( $day['open'] . ' - ' . $day['close'] );
                                            echo '<div class="flex justify-between items-center py-1.5 border-b border-gray-100">';
                                            echo '<span class="text-gray-600">' . esc_html( $label ) . '</span>';
                                            echo '<span class="font-bold">' . $val . '</span>';
                                            echo '</div>';
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Social -->
                        <div class="pt-6 border-t border-gray-200">
                            <p class="font-bold text-xs uppercase tracking-widest text-gray-500 mb-4">Redes Sociales</p>
                            <div class="flex flex-wrap gap-3">
                                <?php if ( ! empty( $instagram ) ) : ?>
                                    <a href="https://instagram.com/<?php echo esc_attr( $instagram ); ?>" target="_blank" class="w-10 h-10 rounded-full border border-gray-200 flex items-center justify-center hover:bg-gray-100 transition-colors cursor-pointer text-black"><span class="material-symbols-outlined">photo_camera</span></a>
                                <?php endif; ?>
                                <?php if ( ! empty( $facebook ) ) : ?>
                                    <a href="https://facebook.com/<?php echo esc_attr( $facebook ); ?>" target="_blank" class="w-10 h-10 rounded-full border border-gray-200 flex items-center justify-center hover:bg-gray-100 transition-colors cursor-pointer text-black"><span class="material-symbols-outlined">social_leaderboard</span></a>
                                <?php endif; ?>
                                <?php if ( ! empty( $linkedin ) ) : ?>
                                    <a href="https://linkedin.com/company/<?php echo esc_attr( $linkedin ); ?>" target="_blank" class="w-10 h-10 rounded-full border border-gray-200 flex items-center justify-center hover:bg-gray-100 transition-colors cursor-pointer text-black"><span class="material-symbols-outlined">hub</span></a>
                                <?php endif; ?>
                                <?php if ( ! empty( $tiktok ) ) : ?>
                                    <a href="https://tiktok.com/@<?php echo esc_attr( $tiktok ); ?>" target="_blank" class="w-10 h-10 rounded-full border border-gray-200 flex items-center justify-center hover:bg-gray-100 transition-colors cursor-pointer text-black"><span class="material-symbols-outlined">videocam</span></a>
                                <?php endif; ?>
                                <?php if ( ! empty( $twitter ) ) : ?>
                                    <a href="https://twitter.com/<?php echo esc_attr( $twitter ); ?>" target="_blank" class="w-10 h-10 rounded-full border border-gray-200 flex items-center justify-center hover:bg-gray-100 transition-colors cursor-pointer text-black"><span class="material-symbols-outlined">close</span></a>
                                <?php endif; ?>
                                <?php if ( ! empty( $youtube_channel ) ) : ?>
                                    <a href="https://youtube.com/<?php echo esc_attr( $youtube_channel ); ?>" target="_blank" class="w-10 h-10 rounded-full border border-gray-200 flex items-center justify-center hover:bg-gray-100 transition-colors cursor-pointer text-black"><span class="material-symbols-outlined">smart_display</span></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Legal Footer Info -->
            <section class="mt-12 text-center text-gray-400 font-bold text-xs uppercase tracking-widest border-t border-gray-200 pt-8">
                <?php if ( ! empty( $rut ) && ! empty( $razon_social ) ) : ?>
                    <p>RUT <?php echo esc_html( $rut ); ?> | Razón Social <?php echo esc_html( $razon_social ); ?></p>
                <?php endif; ?>
            </section>
        </div>
        <?php
        return \ob_get_clean();
    }
}
