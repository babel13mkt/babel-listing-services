<?php
/**
 * Template Name: Archivo de Directorio (AJAX Engine)
 * Babel Directory v3.5
 */

get_header();

$paged   = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
$keyword = isset( $_GET['keyword'] ) ? sanitize_text_field( $_GET['keyword'] ) : '';
$get_cat = isset( $_GET['category'] ) ? sanitize_text_field( $_GET['category'] ) : '';
$get_reg = isset( $_GET['region'] ) ? sanitize_text_field( $_GET['region'] ) : '';
$lat     = isset( $_GET['lat'] ) ? sanitize_text_field( $_GET['lat'] ) : '';
$lng     = isset( $_GET['lng'] ) ? sanitize_text_field( $_GET['lng'] ) : '';

$categorias = get_terms( array( 'taxonomy' => 'directorio_categoria', 'parent' => 0, 'hide_empty' => false ) );
$regiones   = get_terms( array( 'taxonomy' => 'directorio_region', 'parent' => 0, 'hide_empty' => false ) );

// Initial Query
$query_args = array(
    'post_type'      => 'directorio_negocio',
    'posts_per_page' => 12,
    'post_status'    => 'publish',
    'paged'          => $paged,
    'tax_query'      => array( 'relation' => 'AND' )
);

if ($keyword) $query_args['s'] = $keyword;

if ($get_cat) {
    $query_args['tax_query'][] = array(
        'taxonomy' => 'directorio_categoria',
        'field'    => 'slug',
        'terms'    => $get_cat
    );
}

if ($get_reg) {
    $query_args['tax_query'][] = array(
        'taxonomy' => 'directorio_region',
        'field'    => 'slug',
        'terms'    => $get_reg
    );
}

$query = new WP_Query( $query_args );
?>

<div class="bd-archive-wrapper">
    
    <div class="bd-archive-hero">
        <div class="bd-container">
            <h1 class="bd-hero-title">Directorio de Negocios</h1>
            <p class="bd-hero-subtitle">Descubre los mejores servicios y comercios cerca de ti.</p>
        </div>
    </div>

    <div class="bd-container">
        
        <!-- Horizontal Filter Bar -->
        <div id="bd-archive-search" style="margin-bottom: 40px; width: 100%;">
            <form id="bd-filter-form" class="bd-modern-search-form" method="get">
                <?php wp_nonce_field( 'bd_ajax_nonce', 'nonce' ); ?>
                <div class="search-form-container">

                    <!-- Campo 1: Keyword -->
                    <div class="bd-sfield">
                        <span class="bd-sfield-icon"><i class="fas fa-search"></i></span>
                        <input type="text" name="keyword" placeholder="Búsqueda libre..." value="<?php echo esc_attr($keyword); ?>">
                    </div>

                    <!-- Campo 2: Categoría -->
                    <div class="bd-sfield bd-select-wrapper">
                        <span class="bd-sfield-icon"><i class="fas fa-list"></i></span>
                        <select name="category" id="bd-filter-cat">
                            <option value="">Todas las Categorías</option>
                            <?php
                            foreach ( $categorias as $parent ) {
                                $selected = ( $get_cat === $parent->slug ) ? 'selected' : '';
                                echo '<option value="' . esc_attr( $parent->slug ) . '" class="opt-parent" ' . $selected . '>' . esc_html( $parent->name ) . '</option>';
                                $children = get_terms( array( 'taxonomy' => 'directorio_categoria', 'parent' => $parent->term_id, 'hide_empty' => false ) );
                                foreach ( $children as $child ) {
                                    $selected_child = ( $get_cat === $child->slug ) ? 'selected' : '';
                                    echo '<option value="' . esc_attr( $child->slug ) . '" ' . $selected_child . '>&nbsp;&nbsp;— ' . esc_html( $child->name ) . '</option>';
                                }
                            }
                            ?>
                        </select>
                        <i class="fas fa-chevron-down bd-select-arrow"></i>
                    </div>

                    <!-- Campo 3: Región -->
                    <div class="bd-sfield bd-select-wrapper">
                        <span class="bd-sfield-icon"><i class="fas fa-map"></i></span>
                        <select name="region" id="bd-filter-region">
                            <option value="">Todo Chile</option>
                            <?php foreach ( $regiones as $reg ) : 
                                $selected = ( $get_reg === $reg->slug ) ? 'selected' : '';
                                ?>
                                <option value="<?php echo esc_attr( $reg->slug ); ?>" <?php echo $selected; ?>><?php echo esc_html( $reg->name ); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down bd-select-arrow"></i>
                    </div>
                    
                    <!-- Campo 4: Radar -->
                    <div class="bd-sfield">
                        <span class="bd-sfield-icon"><i class="fas fa-map-marker-alt"></i></span>
                        <input type="text" id="bd-location-display" placeholder="Radar (Cerca de mí)" readonly value="<?php echo ($lat && $lng) ? 'Radar Activo' : ''; ?>">
                        <button type="button" id="bd-geo-btn" class="bd-radar-btn <?php echo ($lat && $lng) ? 'active' : ''; ?>" title="Activar Radar">
                            <i class="fas <?php echo ($lat && $lng) ? 'fa-check' : 'fa-location-arrow'; ?>"></i>
                        </button>
                        <input type="hidden" id="bd-lat" name="lat" value="<?php echo esc_attr($lat); ?>">
                        <input type="hidden" id="bd-lng" name="lng" value="<?php echo esc_attr($lng); ?>">
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
                window.location.href = window.location.pathname;
            });
        });
        </script>

        <!-- Resultados -->
        <main class="bd-archive-main" style="width: 100%;">
            <div class="bd-results-header">
                <div class="bd-results-info">
                    Mostrando <strong id="bd-count-shown"><?php echo $query->found_posts; ?></strong> negocios
                </div>
                <div class="bd-results-sort">
                    <span>Ordenar por:</span>
                    <select id="bd-sort">
                        <option value="newest">Más recientes</option>
                        <option value="rating">Mejor valorados</option>
                        <option value="az">Nombre (A-Z)</option>
                        <option value="distance" <?php echo ($lat && $lng) ? 'selected' : ''; ?>>Cercanía (Radar)</option>
                    </select>
                </div>
            </div>

            <div id="bd-grid-container" class="bd-results-grid-wrap">
                <?php if ( $query->have_posts() ) : ?>
                    <div class="bd-grid bd-cols-3">
                        <?php while ( $query->have_posts() ) : $query->the_post(); 
                            BD_Frontend::render_card( get_the_ID() );
                        endwhile; ?>
                    </div>
                    
                    <div class="bd-pagination">
                        <?php echo paginate_links( array(
                            'total'   => $query->max_num_pages,
                            'current' => $paged,
                            'prev_text' => '<i class="fas fa-chevron-left"></i>',
                            'next_text' => '<i class="fas fa-chevron-right"></i>',
                        ) ); ?>
                    </div>
                <?php else : ?>
                    <div class="bd-no-results-premium">
                        <i class="fas fa-search-minus"></i>
                        <h3>No encontramos nada por aquí</h3>
                        <p>Prueba ajustando los filtros o ampliando el radar de búsqueda.</p>
                    </div>
                <?php endif; wp_reset_postdata(); ?>
            </div>
        </main>

    </div>
</div>

<?php get_footer(); ?>
