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
}
