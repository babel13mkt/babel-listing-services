<?php
/**
 * Lógica de Frontend (BD_Frontend)
 * v3.5 — Integración de Radar en Buscador Home & Design System.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BD_Frontend {

    public function __construct() {
        add_shortcode( 'sdc_buscador', array( $this, 'render_buscador' ) );
        add_shortcode( 'bd_filter_bar', array( $this, 'render_filter_bar' ) );
        add_shortcode( 'bd_grid', array( $this, 'render_grid' ) );
        add_shortcode( 'bd_footer_taxonomies', array( $this, 'render_footer_taxonomies' ) );

        // SEO dinámico para filtros
        add_filter( 'pre_get_document_title', array( $this, 'dynamic_seo_title' ), 999 );
        add_filter( 'wp_title', array( $this, 'dynamic_seo_title' ), 999 );
    }

    /**
     * SEO Dinámico: Genera títulos basados en filtros de Región/Categoría
     */
    public function dynamic_seo_title( $title ) {
        $get_cat = isset( $_GET['category'] ) ? sanitize_text_field( $_GET['category'] ) : '';
        $get_reg = isset( $_GET['region'] ) ? sanitize_text_field( $_GET['region'] ) : '';

        if ( ! empty( $get_reg ) || ! empty( $get_cat ) ) {
            $parts = array();
            
            if ( ! empty( $get_cat ) ) {
                $term = get_term_by( 'slug', $get_cat, 'directorio_categoria' );
                if ( $term ) $parts[] = $term->name;
            }

            if ( ! empty( $get_reg ) ) {
                $term = get_term_by( 'slug', $get_reg, 'directorio_region' );
                if ( $term ) $parts[] = self::format_term_name( $term->name, 'directorio_region' );
            }

            if ( ! empty( $parts ) ) {
                return implode( ' en ', $parts ) . ' | ' . get_bloginfo( 'name' );
            }
        }

        return $title;
    }

    /**
     * Helper: Formatea REG-X a X REG - ...
     */
    public static function format_term_name( $name, $taxonomy = '' ) {
        if ( $taxonomy === 'directorio_region' || strpos( $name, 'REG-' ) === 0 ) {
            if ( preg_match( '/^REG-([IVXLCDM]+)\s+(.*)$/', $name, $matches ) ) {
                return $matches[1] . ' REG - ' . $matches[2];
            }
        }
        return $name;
    }

    /**
     * Renderiza la barra de filtros AJAX (para archive/taxonomías)
     */
    public function render_filter_bar() {
        if ( is_singular( 'directorio_negocio' ) ) return '';

        $categorias = get_terms( array( 'taxonomy' => 'directorio_categoria', 'parent' => 0, 'hide_empty' => false ) );
        $regiones   = get_terms( array( 'taxonomy' => 'directorio_region', 'parent' => 0, 'hide_empty' => false ) );

        ob_start();
        ?>
        <div id="bd-archive-search">
            <form id="bd-filter-form" class="bd-modern-search-form" method="get">
                <?php wp_nonce_field( 'bd_ajax_nonce', 'nonce' ); ?>
                <div class="search-form-container">

                    <!-- Campo 1: Keyword -->
                    <div class="bd-sfield">
                        <span class="bd-sfield-icon"><i class="fas fa-search"></i></span>
                        <input type="text" name="keyword" placeholder="Búsqueda libre...">
                    </div>

                    <!-- Campo 2: Categoría -->
                    <div class="bd-sfield bd-select-wrapper">
                        <span class="bd-sfield-icon"><i class="fas fa-list"></i></span>
                        <select name="category" id="bd-filter-cat">
                            <option value="">Todas las Categorías</option>
                            <?php
                            foreach ( $categorias as $parent ) {
                                echo '<option value="' . esc_attr( $parent->slug ) . '" class="opt-parent">' . esc_html( $parent->name ) . '</option>';
                                $children = get_terms( array( 'taxonomy' => 'directorio_categoria', 'parent' => $parent->term_id, 'hide_empty' => false ) );
                                foreach ( $children as $child ) {
                                    echo '<option value="' . esc_attr( $child->slug ) . '">&nbsp;&nbsp;— ' . esc_html( $child->name ) . '</option>';
                                }
                            }
                            ?>
                        </select>
                        <i class="fas fa-chevron-down bd-select-arrow"></i>
                    </div>

                    <!-- Campo 3: Ubicación (Región/Comuna) -->
                    <div class="bd-sfield bd-select-wrapper">
                        <span class="bd-sfield-icon"><i class="fas fa-map"></i></span>
                        <select name="region" id="bd-filter-region">
                            <option value="">Todo Chile</option>
                            <?php
                            foreach ( $regiones as $parent ) {
                                echo '<option value="' . esc_attr( $parent->slug ) . '" class="opt-parent">' . esc_html( self::format_term_name( $parent->name, 'directorio_region' ) ) . '</option>';
                                $children = get_terms( array( 'taxonomy' => 'directorio_region', 'parent' => $parent->term_id, 'hide_empty' => false ) );
                                foreach ( $children as $child ) {
                                echo '<option value="' . esc_attr( $child->slug ) . '" class="opt-child">&nbsp;&nbsp;— ' . esc_html( self::format_term_name( $child->name, 'directorio_region' ) ) . '</option>';
                                }
                            }
                            ?>
                        </select>
                        <i class="fas fa-chevron-down bd-select-arrow"></i>
                    </div>
                    
                    <!-- Campo 4: Radar -->
                    <div class="bd-sfield">
                        <span class="bd-sfield-icon"><i class="fas fa-map-marker-alt"></i></span>
                        <input type="text" id="bd-location-display" placeholder="Radar (Cerca de mí)" readonly>
                        <button type="button" id="bd-geo-btn" class="bd-radar-btn" title="Activar Radar">
                            <i class="fas fa-location-arrow"></i>
                        </button>
                        <input type="hidden" id="bd-lat" name="lat" value="">
                        <input type="hidden" id="bd-lng" name="lng" value="">
                        <input type="hidden" name="radius" value="25">
                    </div>

                    <!-- Botones -->
                    <div class="bd-ssubmit">
                        <button type="submit" class="bd-search-btn">Filtrar</button>
                        <button type="button" id="bd-reset-filters" class="bd-reset-btn" title="Limpiar"><i class="fas fa-times"></i></button>
                    </div>

                </div>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var $btn     = $('#bd-geo-btn');
            var $lat     = $('#bd-lat');
            var $lng     = $('#bd-lng');
            var $display = $('#bd-location-display');

            $btn.on('click', function(e) {
                e.preventDefault();
                if (!navigator.geolocation) {
                    alert('Geolocalización no disponible.');
                    return;
                }
                $btn.addClass('loading').html('<i class="fas fa-sync fa-spin"></i>');

                navigator.geolocation.getCurrentPosition(
                    function(pos) {
                        var lat = pos.coords.latitude;
                        var lng = pos.coords.longitude;
                        $lat.val(lat);
                        $lng.val(lng);
                        $btn.removeClass('loading').addClass('active')
                            .html('<i class="fas fa-check"></i>')
                            .attr('title', 'Radar Activado ✓');

                        $.getJSON(
                            'https://nominatim.openstreetmap.org/reverse',
                            { format: 'json', lat: lat, lon: lng, addressdetails: 1 },
                            function(data) {
                                if (data && data.address) {
                                    var a = data.address;
                                    var label = [
                                        a.road || a.pedestrian || a.suburb || '',
                                        a.city || a.town || a.village || a.municipality || ''
                                    ].filter(Boolean).join(', ');
                                    $display.val(label || data.display_name.split(',').slice(0,2).join(',').trim());
                                } else {
                                    $display.val('Radar Activado');
                                }
                            }
                        ).fail(function() {
                            $display.val('Radar Activado');
                        });
                    },
                    function() {
                        $btn.removeClass('loading').html('<i class="fas fa-location-arrow"></i>');
                        alert('No se pudo obtener tu ubicación.');
                    },
                    { timeout: 8000 }
                );
            });
            
            $('#bd-reset-filters').on('click', function(e) {
                e.preventDefault();
                $('#bd-filter-form')[0].reset();
                $lat.val('');
                $lng.val('');
                $display.val('');
                $btn.removeClass('active loading').html('<i class="fas fa-location-arrow"></i>');
                // Trigger form submission or let the user click submit
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza la grilla de categorías o regiones (Agnóstico a Divi)
     * Shortcode: [bd_grid type="category|region" limit="8" columns="4"]
     */
    public function render_grid( $atts ) {
        $a = shortcode_atts( array(
            'type'    => 'category', // category o region
            'limit'   => 0,          // 0 = todos
            'columns' => 4,          // columnas en desktop (por defecto 4)
        ), $atts );

        $taxonomy = ( $a['type'] === 'region' ) ? 'directorio_region' : 'directorio_categoria';
        
        $args = array(
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
            'parent'     => 0, // Solo terms nivel superior
            'orderby'    => 'name',
            'order'      => 'ASC'
        );

        if ( intval( $a['limit'] ) > 0 ) {
            $args['number'] = intval( $a['limit'] );
        }

        $terms = get_terms( $args );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return '';
        }

        $columns_class = 'bd-grid-cols-' . intval( $a['columns'] );
        $search_url = home_url( '/empresas/' );

        ob_start();
        ?>
        <div class="bd-grid-container <?php echo esc_attr( $columns_class ); ?>">
            <?php foreach ( $terms as $term ) : ?>
                <?php 
                // FORZAR CONVERSIÓN DE ID A OBJETO WP_TERM
                if ( is_numeric( $term ) ) {
                    $term = get_term( (int) $term, 'directorio_region' );
                }

                // SALIR SI NO ES UN OBJETO VÁLIDO PARA EVITAR ERRORES FATALES
                if ( ! is_a( $term, 'WP_Term' ) || is_wp_error( $term ) ) {
                    continue; 
                }

                $param = ( $a['type'] === 'region' ) ? 'region' : 'category';
                $term_link = esc_url( add_query_arg( $param, $term->slug, $search_url ) ); 
                
                // Obtener imagen real desde Term Meta (Sideloaded)
                $term_image_id = get_term_meta( $term->term_id, 'bd_term_image_id', true );
                $term_image    = $term_image_id ? wp_get_attachment_image_url( $term_image_id, 'large' ) : '';
                ?>
                <a href="<?php echo $term_link; ?>" class="bd-grid-card bd-grid-<?php echo esc_attr( $a['type'] ); ?>">
                    <div class="bd-grid-card-content">
                        <div class="bd-grid-image">
                            <?php if ( $term_image ) : ?>
                                <img src="<?php echo esc_url($term_image); ?>" alt="<?php echo esc_attr( $term->name ); ?>" loading="lazy">
                            <?php endif; ?>
                        </div>
                        <div class="bd-grid-header">
                            <h3 class="bd-grid-title"><?php echo esc_html( self::format_term_name( $term->name, $taxonomy ) ); ?></h3>
                        </div>
                        <div class="bd-grid-footer">
                            <?php if (!empty($term->description)): ?>
                                <p class="bd-grid-desc"><?php echo wp_trim_words(esc_html($term->description), 10); ?></p>
                            <?php endif; ?>
                            <span class="bd-grid-count"><?php echo intval( $term->count ); ?> <?php echo ( intval( $term->count ) === 1 ) ? 'negocio' : 'negocios'; ?></span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function render_card( $post_id ) {
        if ( ! $post_id ) return;
        $template_path = BD_PATH . 'templates/parts/listing-card.php';
        if ( file_exists( $template_path ) ) {
            global $post;
            $original_post = $post;
            $post = get_post( $post_id );
            setup_postdata( $post );
            include $template_path;
            wp_reset_postdata();
            $post = $original_post;
        } else {
            echo '<div class="bd-error">Template part missing.</div>';
        }
    }

    public static function render_stars( $rating, $count = 0 ) {
        $rating = is_numeric( $rating ) ? floatval( $rating ) : 0.0;
        $full_stars = floor( $rating );
        $half_star  = ( $rating - $full_stars ) >= 0.5 ? 1 : 0;
        $empty_stars = max( 0, 5 - $full_stars - $half_star );

        echo '<div class="bd-rating-display" title="' . esc_attr( $rating ) . ' estrellas">';
        for ( $i = 0; $i < $full_stars; $i++ ) {
            echo '<i class="fas fa-star"></i>';
        }
        if ( $half_star ) {
            echo '<i class="fas fa-star-half-alt"></i>';
        }
        for ( $i = 0; $i < $empty_stars; $i++ ) {
            echo '<i class="far fa-star"></i>';
        }
        if ( $count > 0 ) {
            echo '<span class="bd-rating-count">(' . intval( $count ) . ')</span>';
        }
        echo '</div>';
    }

    /**
     * Shortcode [sdc_buscador] para la Home
     * v4.1 — Estructura plana + ID ancla para ganar cascada Divi.
     */
    public function render_buscador() {
        $categorias = get_terms( array( 'taxonomy' => 'directorio_categoria', 'parent' => 0, 'hide_empty' => false ) );
        ob_start();
        ?>
        <div id="bd-home-search">
            <form class="bd-modern-search-form" method="get" action="<?php echo esc_url( home_url( '/empresas/' ) ); ?>">

                <?php /* Categoría viaja como GET, no se muestra */ ?>
                <select name="category" class="bd-field-hidden" aria-hidden="true">
                    <option value="">Todas las Categorías</option>
                    <?php
                    foreach ( $categorias as $parent ) {
                        echo '<option value="' . esc_attr( $parent->slug ) . '">' . esc_html( $parent->name ) . '</option>';
                        $children = get_terms( array( 'taxonomy' => 'directorio_categoria', 'parent' => $parent->term_id, 'hide_empty' => false ) );
                        foreach ( $children as $child ) {
                            echo '<option value="' . esc_attr( $child->slug ) . '">&nbsp;&nbsp;— ' . esc_html( $child->name ) . '</option>';
                        }
                    }
                    ?>
                </select>

                <input type="hidden" name="post_type" value="directorio_negocio">
                <input type="hidden" name="lat" id="home-lat">
                <input type="hidden" name="lng" id="home-lng">

                <div class="search-form-container">

                    <!-- Campo 1: Qué buscás -->
                    <div class="bd-sfield">
                        <span class="bd-sfield-icon"><i class="fas fa-search"></i></span>
                        <input type="text" name="keyword" placeholder="¿Qué buscas? (Restaurant, Abogado...)">
                    </div>

                    <!-- Campo 2: Ubicación con radar integrado -->
                    <div class="bd-sfield">
                        <span class="bd-sfield-icon"><i class="fas fa-map-marker-alt"></i></span>
                        <input type="text" id="home-location-display" placeholder="Tu ubicación..." autocomplete="off" readonly>
                        <button type="button" id="home-geo-btn" class="bd-radar-btn" title="Usar mi ubicación">
                            <i class="fas fa-location-arrow"></i>
                        </button>
                    </div>

                    <!-- Botón Buscar -->
                    <div class="bd-ssubmit">
                        <button type="submit" class="bd-search-btn">Buscar</button>
                    </div>

                </div>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            var $btn     = $('#home-geo-btn');
            var $lat     = $('#home-lat');
            var $lng     = $('#home-lng');
            var $display = $('#home-location-display');

            $btn.on('click', function() {
                if (!navigator.geolocation) {
                    alert('Geolocalización no disponible.');
                    return;
                }
                $btn.addClass('loading').html('<i class="fas fa-sync fa-spin"></i>');

                navigator.geolocation.getCurrentPosition(
                    function(pos) {
                        var lat = pos.coords.latitude;
                        var lng = pos.coords.longitude;
                        $lat.val(lat);
                        $lng.val(lng);
                        $btn.removeClass('loading').addClass('active')
                            .html('<i class="fas fa-check"></i>')
                            .attr('title', 'Radar Activado ✓');

                        /* Reverse geocoding sin API key (Nominatim OSM) */
                        $.getJSON(
                            'https://nominatim.openstreetmap.org/reverse',
                            { format: 'json', lat: lat, lon: lng, addressdetails: 1 },
                            function(data) {
                                if (data && data.address) {
                                    var a = data.address;
                                    var label = [
                                        a.road || a.pedestrian || a.suburb || '',
                                        a.city || a.town || a.village || a.municipality || '',
                                        a.state || ''
                                    ].filter(Boolean).join(', ');
                                    $display.val(label || data.display_name.split(',').slice(0,2).join(',').trim());
                                } else {
                                    $display.val(lat.toFixed(4) + ', ' + lng.toFixed(4));
                                }
                            }
                        ).fail(function() {
                            $display.val(lat.toFixed(4) + ', ' + lng.toFixed(4));
                        });
                    },
                    function() {
                        $btn.removeClass('loading').html('<i class="fas fa-location-arrow"></i>');
                        alert('No se pudo obtener tu ubicación.');
                    },
                    { timeout: 8000 }
                );
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode [bd_footer_taxonomies] para inyectar en el Footer (Widgets)
     * Grid CSS: 1fr (Regiones) / 3fr (Categorías)
     */
    public function render_footer_taxonomies() {
        wp_enqueue_style( 'babel-public-css' );
        $regiones = get_terms( array( 'taxonomy' => 'directorio_region', 'parent' => 0, 'hide_empty' => false ) );
        $categorias = get_terms( array( 'taxonomy' => 'directorio_categoria', 'parent' => 0, 'hide_empty' => false ) );

        $search_url = home_url( '/empresas/' );

        ob_start();
        ?>
        <div class="bd-taxonomies-grid">
            <div class="bd-regions-col">
                <h4>Ubicaciones</h4>
                <ul>
                    <?php foreach ( $regiones as $region ) : ?>
                        <?php 
                        // Failsafe Hito 15.8
                        if ( is_numeric( $region ) ) {
                            $region = get_term( $region, 'directorio_region' );
                        }
                        if ( ! is_object( $region ) || is_wp_error( $region ) ) continue;
                        ?>
                        <li>
                            <a href="<?php echo esc_url( add_query_arg( 'region', $region->slug, $search_url ) ); ?>">
                                <?php echo esc_html( $region->name ); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="bd-categories-col">
                <h4>Categorías</h4>
                <ul>
                    <?php foreach ( $categorias as $categoria ) : ?>
                        <li>
                            <a href="<?php echo esc_url( add_query_arg( 'category', $categoria->slug, $search_url ) ); ?>">
                                <?php echo esc_html( $categoria->name ); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
