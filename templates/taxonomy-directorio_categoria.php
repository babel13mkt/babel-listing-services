<?php
/**
 * Template: Taxonomy Category Archive
 * v4.0 — Unified Horizontal Layout
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

$term = get_queried_object();
$term_id = $term->term_id;
$taxonomy = $term->taxonomy;

// Variables for Filter Logic
$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
$get_cat = $term->slug; // Current category
$get_reg = isset($_GET['region']) ? sanitize_text_field($_GET['region']) : '';
$keyword = isset($_GET['keyword']) ? sanitize_text_field($_GET['keyword']) : '';
$lat     = isset($_GET['lat']) ? sanitize_text_field($_GET['lat']) : '';
$lng     = isset($_GET['lng']) ? sanitize_text_field($_GET['lng']) : '';

// Data for Dropdowns
$categorias = get_terms( array( 'taxonomy' => 'directorio_categoria', 'parent' => 0, 'hide_empty' => false ) );
$regiones   = get_terms( array( 'taxonomy' => 'directorio_region', 'parent' => 0, 'hide_empty' => false ) );

global $wp_query;
?>

<div class="bd-archive-wrapper">
    
    <!-- Hero Section -->
    <div class="bd-archive-hero taxonomy-hero">
        <div class="bd-container">
            <nav class="bd-breadcrumbs">
                <a href="<?php echo home_url(); ?>">Inicio</a> / 
                <a href="<?php echo get_post_type_archive_link('directorio_negocio'); ?>">Directorio</a> / 
                <span><?php echo esc_html( $term->name ); ?></span>
            </nav>
            <h1 class="bd-hero-title"><?php echo esc_html( $term->name ); ?></h1>
            <?php if ( $term->description ) : ?>
                <p class="bd-hero-subtitle"><?php echo esc_html( $term->description ); ?></p>
            <?php else : ?>
                <p class="bd-hero-subtitle">Explora los mejores negocios en la categoría <?php echo esc_html( $term->name ); ?>.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="bd-container bd-archive-layout">
        
        <!-- Sidebar Filtros -->
        <aside class="bd-archive-sidebar">
            <div class="bd-filter-card">
                <div class="bd-filter-header">
                    <i class="fas fa-sliders-h"></i>
                    <span>Filtros de Búsqueda</span>
                </div>
                
                <form id="bd-filter-form">
                    <?php wp_nonce_field( 'bd_ajax_nonce', 'nonce' ); ?>
                    
                    <!-- Palabra Clave -->
                    <div class="bd-filter-group">
                        <label>Búsqueda libre</label>
                        <div class="bd-input-icon">
                            <i class="fas fa-search"></i>
                            <input type="text" name="keyword" placeholder="¿Qué buscas en <?php echo esc_attr($term->name); ?>?" value="<?php echo esc_attr($keyword); ?>">
                        </div>
                    </div>

                    <!-- Categorías -->
                    <div class="bd-filter-group">
                        <label>Categoría</label>
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
                    </div>

                    <!-- Ubicación -->
                    <div class="bd-filter-group">
                        <label>Zona / Región</label>
                        <select name="region" id="bd-filter-region">
                            <option value="">Todo Chile</option>
                            <?php foreach ( $regiones as $reg ) : 
                                $selected = ( $get_reg === $reg->slug ) ? 'selected' : '';
                                ?>
                                <option value="<?php echo esc_attr( $reg->slug ); ?>" <?php echo $selected; ?>><?php echo esc_html( $reg->name ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Radar -->
                    <div class="bd-filter-group bd-geo-group">
                        <label>Búsqueda por Radar</label>
                        <button type="button" id="bd-geo-btn" class="bd-btn-geo <?php echo ($lat && $lng) ? 'active' : ''; ?>">
                            <i class="fas <?php echo ($lat && $lng) ? 'fa-map-marker-alt' : 'fa-location-arrow'; ?>"></i> <?php echo ($lat && $lng) ? 'Radar Activo' : 'Activar Radar'; ?>
                        </button>
                        <div id="bd-filter-radius" class="bd-radius-select" style="<?php echo ($lat && $lng) ? 'display:block;' : 'display:none;'; ?>">
                            <span>Radio:</span>
                            <select name="radius">
                                <option value="5">5 km</option>
                                <option value="10">10 km</option>
                                <option value="25" selected>25 km</option>
                                <option value="50">50 km</option>
                                <option value="100">100 km</option>
                            </select>
                        </div>
                        <input type="hidden" id="bd-lat" name="lat" value="<?php echo esc_attr($lat); ?>">
                        <input type="hidden" id="bd-lng" name="lng" value="<?php echo esc_attr($lng); ?>">
                    </div>

                    <div class="bd-filter-actions">
                        <button type="submit" class="bd-btn bd-btn-primary">Aplicar Filtros</button>
                        <button type="button" id="bd-reset-filters" class="bd-btn-reset">Limpiar todo</button>
                    </div>
                </form>
            </div>
        </aside>

        <!-- Resultados -->
        <main class="bd-archive-main">
            <div class="bd-results-header">
                <div class="bd-results-info">
                    Mostrando <strong id="bd-count-shown"><?php echo $wp_query->found_posts; ?></strong> negocios en <?php echo esc_html($term->name); ?>
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
                <?php if ( have_posts() ) : ?>
                    <div class="bd-grid bd-cols-3">
                        <?php while ( have_posts() ) : the_post(); 
                            BD_Frontend::render_card( get_the_ID() );
                        endwhile; ?>
                    </div>
                    
                    <div class="bd-pagination">
                        <?php echo paginate_links( array(
                            'total'   => $wp_query->max_num_pages,
                            'current' => $paged,
                            'prev_text' => '<i class="fas fa-chevron-left"></i>',
                            'next_text' => '<i class="fas fa-chevron-right"></i>',
                        ) ); ?>
                    </div>
                <?php else : ?>
                    <div class="bd-no-results-premium">
                        <i class="fas fa-rocket"></i>
                        <h3>No hay nada por aquí todavía</h3>
                        <p>Tú puedes ser el primero en aparecer en esta sección.</p>
                        <a href="<?php echo home_url('/unete'); ?>" class="bd-btn bd-btn-primary" style="margin-top: 20px; display: inline-block;">¡Quiero ser el primero!</a>
                    </div>
                <?php endif; ?>
            </div>
        </main>

    </div>
</div>

<?php get_footer(); ?>
