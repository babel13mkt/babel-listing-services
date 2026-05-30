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
        add_shortcode( 'bd_filter_bar', array( $this, 'render_filter_bar' ) );
    }

    public function render_filter_bar( $atts ) {
        wp_enqueue_style( 'babel-public-css' );
        wp_enqueue_script( 'babel-public-js' );

        $atts = shortcode_atts( array(
            'region'       => '',
            'show_results' => 'no',
        ), $atts, 'bd_filter_bar' );

        // Obtener la región actual si estamos en una página de taxonomía
        $current_region_slug = $atts['region'];
        if ( empty( $current_region_slug ) && is_tax( 'babel_region' ) ) {
            $term = get_queried_object();
            if ( $term ) {
                $current_region_slug = $term->slug;
            }
        }

        // Obtener todas las regiones para el dropdown
        $regions = get_terms( array(
            'taxonomy'   => 'babel_region',
            'hide_empty' => false,
            'parent'     => 0,
        ) );

        ob_start();
        ?>
        <div class="babel-filter-bar-section">
            <form id="babel-search-form" class="babel-filter-bar-form" action="/buscar/" method="GET" autocomplete="off">
                <div class="babel-filter-bar-inner" data-babel-filter="true">
                    <!-- 1. Búsqueda libre -->
                    <div class="babel-filter-keyword">
                        <span class="babel-input-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                        </span>
                        <input type="text" id="babel-search-keyword" name="keyword" placeholder="ej: Sushi, región metropolitana" />
                    </div>

                    <!-- 2. Selector de Región -->
                    <div class="babel-filter-region">
                        <select id="babel-search-region-select" name="region">
                            <option value="">Todas las regiones</option>
                            <?php
                            if ( ! \is_wp_error( $regions ) && ! empty( $regions ) ) {
                                foreach ( $regions as $reg ) {
                                    $selected = ( $current_region_slug === $reg->slug ) ? 'selected="selected"' : '';
                                    echo '<option value="' . esc_attr( $reg->slug ) . '" ' . $selected . '>' . esc_html( $reg->name ) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <!-- 3. Radar GPS -->
                    <div class="babel-filter-radar">
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

                    <!-- 4. Botón Buscar -->
                    <div class="babel-filter-submit">
                        <button type="submit" class="babel-search-submit-btn">BUSCAR</button>
                    </div>
                </div>
            </form>

            <!-- Contenedor Dinámico para Carga Asíncrona (AJAX) -->
            <!-- Usamos data-region dinámico basado en PHP para heredar el contexto -->
            <?php if ( 'yes' === $atts['show_results'] || 'true' === $atts['show_results'] || true === $atts['show_results'] || '1' === $atts['show_results'] ) : ?>
                <div id="babel-directory-results" class="babel-results-container" data-region="<?php echo esc_attr( $current_region_slug ); ?>" data-category=""></div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_radar_search( $atts ) {
        return $this->render_filter_bar( $atts );
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

        $terms = get_terms( $terms_args );

        if ( \is_wp_error( $terms ) || empty( $terms ) ) {
            return '<p>No se encontraron regiones.</p>';
        }

        // Función auxiliar para convertir números romanos a enteros
        $roman_to_int = function( $roman ) {
            $romans = array(
                'I' => 1, 'V' => 5, 'X' => 10, 'L' => 50, 'C' => 100, 'D' => 500, 'M' => 1000
            );
            $result = 0;
            $roman = strtoupper( trim( $roman ) );
            for ( $i = 0; $i < strlen( $roman ); $i++ ) {
                if ( $i + 1 < strlen( $roman ) && isset( $romans[$roman[$i]], $romans[$roman[$i + 1]] ) && $romans[$roman[$i]] < $romans[$roman[$i + 1]] ) {
                    $result -= $romans[$roman[$i]];
                } elseif ( isset( $romans[$roman[$i]] ) ) {
                    $result += $romans[$roman[$i]];
                }
            }
            return $result;
        };

        // Ordenar las regiones geográficamente usando el número romano de la nomenclatura
        usort( $terms, function( $a, $b ) use ( $roman_to_int ) {
            preg_match( '/^([IVXLCDM]+)/i', $a->name, $a_matches );
            preg_match( '/^([IVXLCDM]+)/i', $b->name, $b_matches );

            $val_a = ! empty( $a_matches[1] ) ? $roman_to_int( $a_matches[1] ) : 999;
            $val_b = ! empty( $b_matches[1] ) ? $roman_to_int( $b_matches[1] ) : 999;

            return $val_a <=> $val_b;
        } );

        // Aplicar el límite después de la ordenación
        if ( $limit > 0 && count( $terms ) > $limit ) {
            $terms = array_slice( $terms, 0, $limit );
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

                    $post_id   = get_the_ID();
                    $categorias = get_the_terms( $post_id, 'babel_category' );
                    $regiones   = get_the_terms( $post_id, 'babel_region' );

                    $cat_name   = ( ! empty( $categorias ) && ! \is_wp_error( $categorias ) )
                                    ? esc_html( $categorias[0]->name )
                                    : '';
                    $reg_name   = '';
                    if ( ! empty( $regiones ) && ! \is_wp_error( $regiones ) ) {
                        $reg_name = preg_replace( '/^[IVX]+\s*-\s*REG\s*-\s*/i', '', $regiones[0]->name );
                        $reg_name = esc_html( $reg_name );
                    }

                    $price_range = \get_post_meta( $post_id, '_babel_price_range', true );
                    $rating_avg  = (float) \get_post_meta( $post_id, '_babel_rating_avg', true );
                    $rating_count = (int) \get_post_meta( $post_id, '_babel_rating_count', true );
                    $is_featured = \get_post_meta( $post_id, '_babel_featured', true );
                    $is_verified = \get_post_meta( $post_id, '_babel_verified', true );

                    // Thumbnail: usa imagen destacada del post, sin fallbacks externos.
                    $thumb_id  = get_post_thumbnail_id( $post_id );
                    $thumb_url = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'medium_large' ) : '';
                ?>
                    <a href="<?php the_permalink(); ?>" class="babel-biz-card" aria-label="<?php the_title_attribute(); ?>">

                        <!-- Zona de imagen -->
                        <div class="babel-biz-card__image-wrap">
                            <?php if ( $thumb_url ) : ?>
                                <img
                                    src="<?php echo esc_url( $thumb_url ); ?>"
                                    alt="<?php the_title_attribute(); ?>"
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

                            <h3 class="babel-biz-card__title"><?php the_title(); ?></h3>

                            <?php if ( $rating_count > 0 ) : ?>
                                <div class="babel-biz-card__rating" aria-label="<?php echo esc_attr( number_format( $rating_avg, 1 ) ); ?> de 5 estrellas">
                                    <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;" aria-hidden="true">star</span>
                                    <span class="babel-biz-card__rating-score"><?php echo esc_html( number_format( $rating_avg, 1 ) ); ?></span>
                                    <span class="babel-biz-card__rating-count">(<?php echo esc_html( $rating_count ); ?>)</span>
                                </div>
                            <?php endif; ?>

                            <?php if ( $cat_name || $reg_name ) : ?>
                                <div class="babel-biz-card__meta">
                                    <?php if ( $cat_name ) : ?>
                                        <span class="babel-biz-card__meta-item">
                                            <span class="material-symbols-outlined" aria-hidden="true">category</span>
                                            <?php echo $cat_name; ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ( $cat_name && $reg_name ) : ?>
                                        <span class="babel-biz-card__meta-sep" aria-hidden="true"></span>
                                    <?php endif; ?>
                                    <?php if ( $reg_name ) : ?>
                                        <span class="babel-biz-card__meta-item">
                                            <span class="material-symbols-outlined" aria-hidden="true">location_on</span>
                                            <?php echo $reg_name; ?>
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
                <?php endwhile; ?>
            <?php else : ?>
                <p class="sdc-no-results"><?php esc_html_e( 'No se encontraron negocios en esta categoría.', 'babel-directory' ); ?></p>
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
                    <?php $base_region_url = home_url( '/region/' . $term->slug . '/' ); ?>
                    <a href="<?php echo esc_url( $base_region_url ); ?>" class="bd-category-pill active" data-category="">
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
                            <?php $cat_url = home_url( '/region/' . $term->slug . '/categoria/' . $cat->slug . '/' ); ?>
                            <a href="<?php echo esc_url( $cat_url ); ?>" class="bd-category-pill" data-category="<?php echo esc_attr( $cat->slug ); ?>">
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
        <!-- Inyección de dependencias del diseño Stitch -->
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Montserrat:wght@500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet"/>
        <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
        <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
        <script id="tailwind-config">
            if(window.tailwind) {
                window.tailwind.config = {
                    darkMode: "class",
                    theme: {
                    extend: {
                        "colors": {
                            "surface-variant": "#e2e2e2", "inverse-on-surface": "#f1f1f1", "error": "#ba1a1a",
                            "secondary-fixed-dim": "#e9c349", "surface-dim": "#dadada", "on-primary-container": "#858383",
                            "secondary-container": "#fed65b", "inverse-primary": "#c8c6c5", "surface": "#f9f9f9",
                            "surface-container-highest": "#e2e2e2", "on-error": "#ffffff", "on-primary-fixed-variant": "#474746",
                            "on-tertiary-container": "#838484", "secondary-fixed": "#ffe088", "tertiary": "#000000",
                            "surface-container-lowest": "#ffffff", "on-secondary-container": "#745c00", "surface-container-low": "#f3f3f3",
                            "secondary": "#735c00", "primary-fixed": "#e5e2e1", "outline-variant": "#c4c7c7",
                            "on-tertiary": "#ffffff", "surface-container-high": "#e8e8e8", "on-primary-fixed": "#1c1b1b",
                            "on-secondary-fixed-variant": "#574500", "primary-container": "#1c1b1b", "surface-container": "#eeeeee",
                            "tertiary-container": "#1a1c1c", "on-secondary-fixed": "#241a00", "on-secondary": "#ffffff",
                            "on-tertiary-fixed-variant": "#454747", "background": "#f9f9f9", "error-container": "#ffdad6",
                            "on-background": "#1a1c1c", "primary": "#000000", "primary-fixed-dim": "#c8c6c5",
                            "surface-bright": "#f9f9f9", "on-primary": "#ffffff", "outline": "#747878", "on-surface": "#1a1c1c",
                            "tertiary-fixed": "#e2e2e2", "inverse-surface": "#2f3131", "on-tertiary-fixed": "#1a1c1c",
                            "surface-tint": "#5f5e5e", "on-error-container": "#93000a", "tertiary-fixed-dim": "#c6c6c7",
                            "on-surface-variant": "#444748"
                        },
                        "borderRadius": {
                            "DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "2xl": "1rem", "full": "9999px"
                        },
                        "spacing": {
                            "unit": "8px", "gutter": "32px", "margin-mobile": "20px", "container-max": "1200px", "margin-desktop": "64px"
                        },
                        "fontFamily": {
                            "body-md": ["Inter"], "label-md": ["Montserrat"], "label-caps": ["Montserrat"],
                            "display-lg-mobile": ["Playfair Display"], "display-lg": ["Playfair Display"],
                            "headline-sm": ["Playfair Display"], "headline-md": ["Playfair Display"], "body-lg": ["Inter"]
                        },
                        "fontSize": {
                            "body-md": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
                            "label-md": ["14px", {"lineHeight": "20px", "fontWeight": "500"}],
                            "label-caps": ["12px", {"lineHeight": "16px", "letterSpacing": "0.1em", "fontWeight": "600"}],
                            "display-lg-mobile": ["40px", {"lineHeight": "48px", "letterSpacing": "-0.01em", "fontWeight": "700"}],
                            "display-lg": ["64px", {"lineHeight": "72px", "letterSpacing": "-0.02em", "fontWeight": "700"}],
                            "headline-sm": ["24px", {"lineHeight": "32px", "fontWeight": "600"}],
                            "headline-md": ["32px", {"lineHeight": "40px", "fontWeight": "600"}],
                            "body-lg": ["18px", {"lineHeight": "28px", "fontWeight": "400"}]
                        }
                    }
                    }
                };
            }
        </script>
        <style>
            .material-symbols-outlined {
                font-family: 'Material Symbols Outlined';
                font-weight: normal;
                font-style: normal;
                font-size: 24px;
                line-height: 1;
                letter-spacing: normal;
                text-transform: none;
                display: inline-block;
                white-space: nowrap;
                word-wrap: normal;
                direction: ltr;
                -webkit-font-feature-settings: 'liga';
                -webkit-font-smoothing: antialiased;
                font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
                vertical-align: middle;
            }
            .premium-shadow { box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08); }
            .hide-scrollbar::-webkit-scrollbar { display: none; }
            .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        </style>

        <div class="bg-surface text-on-surface font-body-md overflow-x-hidden pt-10 pb-32 max-w-[1200px] mx-auto px-margin-mobile md:px-margin-desktop">
            
            <!-- Hero Section -->
            <section class="flex flex-col md:flex-row items-center md:items-start gap-gutter mb-20">
                <?php if ( \has_post_thumbnail( $post_id ) ) : ?>
                    <div class="w-40 h-40 md:w-56 md:h-56 rounded-full overflow-hidden border-4 border-white premium-shadow flex-shrink-0">
                        <?php echo \get_the_post_thumbnail( $post_id, 'medium', array( 'class' => 'w-full h-full object-cover' ) ); ?>
                    </div>
                <?php endif; ?>

                <div class="flex-1 text-center md:text-left pt-4">
                    <div class="flex flex-wrap justify-center md:justify-start gap-3 mb-4">
                        <?php if ( '1' === $verified ) : ?>
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-surface-container-high border border-outline-variant/30 rounded-full text-label-caps font-label-caps text-primary">
                                <span class="material-symbols-outlined text-[14px]" data-icon="verified" style="font-variation-settings: 'FILL' 1;">verified</span>
                                Verificado
                            </span>
                        <?php endif; ?>
                        <?php if ( '1' === $featured ) : ?>
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-secondary-fixed text-on-secondary-fixed rounded-full text-label-caps font-label-caps">
                                <span class="material-symbols-outlined text-[14px]" data-icon="stars" style="font-variation-settings: 'FILL' 1;">stars</span>
                                Destacado
                            </span>
                        <?php endif; ?>
                    </div>
                    <h1 class="font-display-lg text-headline-sm md:text-display-lg text-primary mb-2 leading-tight">
                        <?php echo esc_html( \get_the_title( $post_id ) ); ?>
                    </h1>
                    <div class="flex justify-center md:justify-start items-center gap-4 text-on-surface-variant font-label-md text-label-md">
                        <?php if ( ! empty( $price_range ) ) : ?>
                            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[18px]" data-icon="payments">payments</span> <?php echo esc_html( $price_range ); ?></span>
                        <?php endif; ?>
                        <?php if ( ! empty( $biz_type ) ) : 
                            $biz_types = array( 'physical' => 'Local físico', 'online' => 'Solo online', 'hybrid' => 'Híbrido', 'mobile' => 'Móvil' );
                            if ( isset( $biz_types[ $biz_type ] ) ) : ?>
                                <span class="w-1 h-1 bg-outline rounded-full"></span>
                                <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[18px]" data-icon="location_on">location_on</span> <?php echo esc_html( $biz_types[ $biz_type ] ); ?></span>
                            <?php endif; 
                        endif; ?>
                    </div>
                </div>
            </section>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-gutter relative">
                <!-- Main Content Area -->
                <div class="lg:col-span-8 space-y-16">
                    
                    <?php if ( ! empty( $content ) ) : ?>
                        <section>
                            <h2 class="font-headline-md text-headline-md text-primary mb-6 border-l-4 border-secondary-fixed-dim pl-6">Sobre nosotros</h2>
                            <div class="font-body-lg text-body-lg text-on-surface-variant leading-relaxed space-y-4">
                                <?php echo \wpautop( $content ); ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- Gallery Highlight -->
                    <?php if ( ! empty( $gallery_meta ) ) : 
                        $gallery_ids = is_array( $gallery_meta ) ? $gallery_meta : explode( ',', $gallery_meta );
                        if ( count( $gallery_ids ) > 0 ) :
                            $main_img_url = wp_get_attachment_image_url( $gallery_ids[0], 'large' );
                            if ( $main_img_url ) :
                    ?>
                        <section>
                            <div class="h-[400px] rounded-3xl overflow-hidden relative group mb-4">
                                <img id="main-gallery-img" src="<?php echo esc_url( $main_img_url ); ?>" alt="Galería" class="w-full h-full object-cover transition-transform duration-700" />
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent flex items-end p-8 pointer-events-none">
                                    <div class="text-white">
                                        <h3 class="font-headline-sm text-headline-sm">Galería de Imágenes</h3>
                                    </div>
                                </div>
                            </div>
                            <?php if ( count( $gallery_ids ) > 1 ) : ?>
                                <div class="flex gap-3 overflow-x-auto pb-2 hide-scrollbar">
                                    <?php foreach ( $gallery_ids as $img_id ) : 
                                        $thumb_url = wp_get_attachment_image_url( $img_id, 'thumbnail' );
                                        $full_url = wp_get_attachment_image_url( $img_id, 'large' );
                                        if ( ! $thumb_url ) continue;
                                    ?>
                                        <img src="<?php echo esc_url( $thumb_url ); ?>" onclick="document.getElementById('main-gallery-img').src='<?php echo esc_url( $full_url ); ?>'" alt="Miniatura" class="w-24 h-24 rounded-2xl object-cover cursor-pointer border-2 border-transparent hover:border-secondary-fixed-dim transition-all flex-shrink-0" />
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </section>
                    <?php endif; endif; endif; ?>

                    <!-- Features Section (Bento Style) -->
                    <section>
                        <h2 class="font-headline-md text-headline-md text-primary mb-8">Características</h2>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            <?php if ( ! empty( $parking ) && 'none' !== $parking ) : ?>
                                <div class="p-6 bg-surface-container-low rounded-2xl border border-outline-variant/20 flex flex-col items-center text-center gap-3 group hover:border-secondary-fixed-dim transition-all duration-300">
                                    <span class="material-symbols-outlined text-secondary-fixed-dim text-3xl group-hover:scale-110 transition-transform" data-icon="local_parking">local_parking</span>
                                    <span class="font-label-md text-label-md">Estacionamiento</span>
                                </div>
                            <?php endif; ?>
                            <?php if ( ! empty( $wifi ) && 'none' !== $wifi ) : ?>
                                <div class="p-6 bg-surface-container-low rounded-2xl border border-outline-variant/20 flex flex-col items-center text-center gap-3 group hover:border-secondary-fixed-dim transition-all duration-300">
                                    <span class="material-symbols-outlined text-secondary-fixed-dim text-3xl group-hover:scale-110 transition-transform" data-icon="wifi">wifi</span>
                                    <span class="font-label-md text-label-md">Wi-Fi Gratis</span>
                                </div>
                            <?php endif; ?>
                            <?php if ( ! empty( $pet_friendly ) && 'no' !== $pet_friendly ) : ?>
                                <div class="p-6 bg-surface-container-low rounded-2xl border border-outline-variant/20 flex flex-col items-center text-center gap-3 group hover:border-secondary-fixed-dim transition-all duration-300">
                                    <span class="material-symbols-outlined text-secondary-fixed-dim text-3xl group-hover:scale-110 transition-transform" data-icon="pets">pets</span>
                                    <span class="font-label-md text-label-md">Pet Friendly</span>
                                </div>
                            <?php endif; ?>
                            <div class="p-6 bg-surface-container-low rounded-2xl border border-outline-variant/20 flex flex-col items-center text-center gap-3 group hover:border-secondary-fixed-dim transition-all duration-300">
                                <span class="material-symbols-outlined text-secondary-fixed-dim text-3xl group-hover:scale-110 transition-transform" data-icon="accessible">accessible</span>
                                <span class="font-label-md text-label-md">Accesibilidad Universal</span>
                            </div>
                            <?php if ( '1' === $delivery ) : ?>
                                <div class="p-6 bg-surface-container-low rounded-2xl border border-outline-variant/20 flex flex-col items-center text-center gap-3 group hover:border-secondary-fixed-dim transition-all duration-300">
                                    <span class="material-symbols-outlined text-secondary-fixed-dim text-3xl group-hover:scale-110 transition-transform" data-icon="delivery_dining">delivery_dining</span>
                                    <span class="font-label-md text-label-md">Delivery Express</span>
                                </div>
                            <?php endif; ?>
                            <?php if ( '1' === $reservations ) : ?>
                                <div class="p-6 bg-surface-container-low rounded-2xl border border-outline-variant/20 flex flex-col items-center text-center gap-3 group hover:border-secondary-fixed-dim transition-all duration-300">
                                    <span class="material-symbols-outlined text-secondary-fixed-dim text-3xl group-hover:scale-110 transition-transform" data-icon="calendar_month">calendar_month</span>
                                    <span class="font-label-md text-label-md">Reservas Activas</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <!-- Puntaje y Comentarios -->
                    <?php
                    $rating_avg = \get_post_meta( $post_id, '_babel_rating_avg', true ) ?: 0;
                    $rating_count = \get_post_meta( $post_id, '_babel_rating_count', true ) ?: 0;

                    $reviews = get_comments( array(
                        'post_id' => $post_id,
                        'type' => 'babel_review',
                        'status' => 'approve',
                        'number' => 4
                    ) );
                    
                    if ( $rating_count > 0 || ! empty( $reviews ) ) :
                    ?>
                    <section>
                        <h2 class="font-headline-md text-headline-md text-primary mb-6">Puntaje y Comentarios</h2>
                        <div class="flex items-center gap-4 mb-8">
                            <div class="flex text-secondary-fixed-dim text-3xl">
                                <?php 
                                $avg_int = floor( $rating_avg );
                                $avg_half = ( $rating_avg - $avg_int ) >= 0.5 ? 1 : 0;
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $avg_int) {
                                        echo '<span class="material-symbols-outlined" style="font-variation-settings: \'FILL\' 1;">star</span>';
                                    } elseif ($i == $avg_int + 1 && $avg_half) {
                                        echo '<span class="material-symbols-outlined" style="font-variation-settings: \'FILL\' 0.5;">star_half</span>';
                                    } else {
                                        echo '<span class="material-symbols-outlined">star</span>';
                                    }
                                }
                                ?>
                            </div>
                            <div class="flex items-baseline gap-2">
                                <span class="font-headline-md text-[32px] text-primary"><?php echo number_format((float)$rating_avg, 1, '.', ''); ?></span>
                                <span class="font-body-md text-on-surface-variant">/ 5</span>
                            </div>
                            <span class="text-on-surface-variant text-sm border-l border-outline-variant/40 pl-4">(<?php echo esc_html( $rating_count ); ?> reseñas)</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php if ( ! empty( $reviews ) ) : ?>
                                <?php foreach ( $reviews as $review ) : 
                                    $r_rating = get_comment_meta( $review->comment_ID, 'babel_rating', true ) ?: 5;
                                    $initials = strtoupper( substr( $review->comment_author, 0, 2 ) );
                                ?>
                                <div class="p-6 bg-surface-container-lowest rounded-2xl border border-outline-variant/20">
                                    <div class="flex items-center gap-3 mb-4">
                                        <div class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center font-bold text-label-md"><?php echo esc_html( $initials ); ?></div>
                                        <div>
                                            <p class="font-label-md font-bold text-primary"><?php echo esc_html( $review->comment_author ); ?></p>
                                            <div class="flex text-secondary-fixed-dim text-sm">
                                                <?php for ($i=1; $i<=5; $i++) {
                                                    if ($i <= $r_rating) {
                                                        echo '<span class="material-symbols-outlined text-[16px]" style="font-variation-settings: \'FILL\' 1;">star</span>';
                                                    } else {
                                                        echo '<span class="material-symbols-outlined text-[16px]">star</span>';
                                                    }
                                                } ?>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="font-body-md text-on-surface-variant italic">"<?php echo esc_html( $review->comment_content ); ?>"</p>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </section>
                    <?php endif; ?>
                </div>

                <!-- Sidebar / Sticky Contact Section -->
                <div class="lg:col-span-4 lg:sticky lg:top-24 h-fit">
                    <div class="bg-white p-8 rounded-3xl border border-outline-variant/30 premium-shadow space-y-8">
                        <div class="space-y-4">
                            <?php if ( ! empty( $phone ) ) : ?>
                                <div class="flex items-center gap-4 group cursor-pointer">
                                    <div class="w-10 h-10 rounded-full bg-surface-container flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-white transition-all">
                                        <span class="material-symbols-outlined" data-icon="call">call</span>
                                    </div>
                                    <span class="font-label-md text-label-md"><?php echo esc_html( $phone ); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ( ! empty( $whatsapp ) ) : ?>
                                <a href="https://wa.me/<?php echo esc_attr( preg_replace('/[^0-9]/', '', $whatsapp) ); ?>" target="_blank" class="flex items-center gap-4 group cursor-pointer no-underline">
                                    <div class="w-10 h-10 rounded-full bg-surface-container flex items-center justify-center text-[#25D366] group-hover:bg-[#25D366] group-hover:text-white transition-all">
                                        <span class="material-symbols-outlined" data-icon="chat">chat</span>
                                    </div>
                                    <span class="font-label-md text-label-md text-secondary font-bold">WhatsApp Directo</span>
                                </a>
                            <?php endif; ?>
                            <?php if ( ! empty( $email ) ) : ?>
                                <div class="flex items-center gap-4 group cursor-pointer">
                                    <div class="w-10 h-10 rounded-full bg-surface-container flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-white transition-all">
                                        <span class="material-symbols-outlined" data-icon="mail">mail</span>
                                    </div>
                                    <span class="font-label-md text-label-md"><?php echo esc_html( $email ); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ( ! empty( $address ) ) : ?>
                                <div class="flex items-start gap-4 pt-2">
                                    <div class="w-10 h-10 rounded-full bg-surface-container flex items-center justify-center text-primary mt-1">
                                        <span class="material-symbols-outlined" data-icon="pin_drop">pin_drop</span>
                                    </div>
                                    <div>
                                        <span class="font-label-md text-label-md block"><?php echo esc_html( $address ); ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ( ! empty( $website ) ) : ?>
                            <a href="<?php echo esc_url( $website ); ?>" target="_blank" class="block w-full py-4 bg-primary text-on-primary text-center rounded-2xl font-label-caps text-label-caps uppercase tracking-widest hover:bg-tertiary transition-colors active:scale-95 duration-200 no-underline">
                                Visitar Sitio Web
                            </a>
                        <?php endif; ?>

                        <!-- Map -->
                        <?php 
                        $lat = \get_post_meta( $post_id, '_babel_lat', true );
                        $lng = \get_post_meta( $post_id, '_babel_lng', true );
                        if ( $lat && $lng ) :
                        ?>
                        <div class="pt-4">
                            <iframe 
                                width="100%" 
                                height="128" 
                                class="rounded-2xl border border-outline-variant/30" 
                                style="border:0;" 
                                loading="lazy" 
                                allowfullscreen 
                                src="https://maps.google.com/maps?q=<?php echo esc_attr($lat); ?>,<?php echo esc_attr($lng); ?>&z=15&output=embed">
                            </iframe>
                        </div>
                        <?php endif; ?>

                        <!-- Condensed Weekly Hours -->
                        <?php if ( ! empty( $hours ) ) : ?>
                            <div class="pt-6 border-t border-outline-variant/30">
                                <h3 class="font-headline-sm text-[20px] text-primary mb-4 text-center">Horario</h3>
                                <div class="space-y-2 text-sm">
                                    <?php
                                    $dias_es = array('monday'=>'Lunes', 'tuesday'=>'Martes', 'wednesday'=>'Miércoles', 'thursday'=>'Jueves', 'friday'=>'Viernes', 'saturday'=>'Sábado', 'sunday'=>'Domingo');
                                    foreach ( $dias_es as $key => $label ) {
                                        $day_data = null;
                                        if ( is_array( $hours ) ) {
                                            if ( isset( $hours[$key] ) ) {
                                                $day_data = $hours[$key];
                                            } elseif ( isset( $hours[$label] ) ) {
                                                $day_data = $hours[$label];
                                            }
                                        }
                                        if ( ! empty( $day_data ) ) {
                                            if ( is_array( $day_data ) ) {
                                                $val = ! empty( $day_data['closed'] ) ? 'Cerrado' : esc_html( ( $day_data['open'] ?? '' ) . ' - ' . ( $day_data['close'] ?? '' ) );
                                            } else {
                                                $val = esc_html( $day_data );
                                            }
                                            echo '<div class="flex justify-between items-center py-1.5 border-b border-outline-variant/20">';
                                            echo '<span class="font-label-md text-on-surface-variant">' . esc_html( $label ) . '</span>';
                                            echo '<span class="font-body-md text-xs font-medium">' . $val . '</span>';
                                            echo '</div>';
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Social -->
                        <div class="pt-6 border-t border-outline-variant/30">
                            <p class="font-label-caps text-label-caps text-on-surface-variant mb-4">Nuestras Redes</p>
                            <div class="flex flex-wrap gap-3">
                                <?php if ( ! empty( $instagram ) ) : ?>
                                    <a href="https://instagram.com/<?php echo esc_attr( $instagram ); ?>" target="_blank" class="w-10 h-10 rounded-full border border-outline-variant/30 flex items-center justify-center hover:bg-surface-container transition-colors cursor-pointer text-primary"><span class="material-symbols-outlined text-[18px]" data-icon="photo_camera">photo_camera</span></a>
                                <?php endif; ?>
                                <?php if ( ! empty( $facebook ) ) : ?>
                                    <a href="https://facebook.com/<?php echo esc_attr( $facebook ); ?>" target="_blank" class="w-10 h-10 rounded-full border border-outline-variant/30 flex items-center justify-center hover:bg-surface-container transition-colors cursor-pointer text-primary"><span class="material-symbols-outlined text-[18px]" data-icon="facebook">social_leaderboard</span></a>
                                <?php endif; ?>
                                <?php if ( ! empty( $linkedin ) ) : ?>
                                    <a href="https://linkedin.com/company/<?php echo esc_attr( $linkedin ); ?>" target="_blank" class="w-10 h-10 rounded-full border border-outline-variant/30 flex items-center justify-center hover:bg-surface-container transition-colors cursor-pointer text-primary"><span class="material-symbols-outlined text-[18px]" data-icon="hub">hub</span></a>
                                <?php endif; ?>
                                <?php if ( ! empty( $tiktok ) ) : ?>
                                    <a href="https://tiktok.com/@<?php echo esc_attr( $tiktok ); ?>" target="_blank" class="w-10 h-10 rounded-full border border-outline-variant/30 flex items-center justify-center hover:bg-surface-container transition-colors cursor-pointer text-primary"><span class="material-symbols-outlined text-[18px]" data-icon="videocam">videocam</span></a>
                                <?php endif; ?>
                                <?php if ( ! empty( $twitter ) ) : ?>
                                    <a href="https://twitter.com/<?php echo esc_attr( $twitter ); ?>" target="_blank" class="w-10 h-10 rounded-full border border-outline-variant/30 flex items-center justify-center hover:bg-surface-container transition-colors cursor-pointer text-primary"><span class="material-symbols-outlined text-[18px]" data-icon="close">close</span></a>
                                <?php endif; ?>
                                <?php if ( ! empty( $youtube_channel ) ) : ?>
                                    <a href="https://youtube.com/<?php echo esc_attr( $youtube_channel ); ?>" target="_blank" class="w-10 h-10 rounded-full border border-outline-variant/30 flex items-center justify-center hover:bg-surface-container transition-colors cursor-pointer text-primary"><span class="material-symbols-outlined text-[18px]" data-icon="smart_display">smart_display</span></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Legal Footer Info -->
            <section class="mt-12 text-center text-on-surface-variant/60 font-label-caps text-label-caps pt-8 border-t border-outline-variant/30">
                <?php if ( ! empty( $rut ) && ! empty( $razon_social ) ) : ?>
                    <p>RUT <?php echo esc_html( $rut ); ?> | Razón Social <?php echo esc_html( $razon_social ); ?></p>
                <?php endif; ?>
            </section>
        </div>
        <?php
        return \ob_get_clean();
    }
}
