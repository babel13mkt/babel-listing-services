<?php
/**
 * Template: Taxonomy Region Archive
 * v4.0 — Unified Horizontal Layout & Filter Engine Integration
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

$term       = get_queried_object();
$term_id    = $term->term_id;
$taxonomy   = $term->taxonomy;

// GET Parameters for Filters
$keyword    = isset($_GET['keyword']) ? sanitize_text_field($_GET['keyword']) : '';
$get_cat    = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
$get_reg    = $term->slug; // Forced current region
$lat        = isset($_GET['lat']) ? sanitize_text_field($_GET['lat']) : '';
$lng        = isset($_GET['lng']) ? sanitize_text_field($_GET['lng']) : '';
$radius     = isset($_GET['radius']) ? intval($_GET['radius']) : 25;
$paged      = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;

$categorias = get_terms( array( 'taxonomy' => 'directorio_categoria', 'parent' => 0, 'hide_empty' => false ) );
$regiones   = get_terms( array( 'taxonomy' => 'directorio_region', 'parent' => 0, 'hide_empty' => false ) );

global $wp_query;
?>

<?php 
$term_image_id = get_term_meta( $term_id, 'bd_term_image_id', true );
$term_image    = $term_image_id ? wp_get_attachment_image_url( $term_image_id, 'full' ) : 'https://picsum.photos/1920/600';
?>

<div class="bd-archive-wrapper">
    
    <!-- Hero Section -->
    <div class="bd-archive-hero taxonomy-hero region-hero" style="background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.8)), url('<?php echo esc_url($term_image); ?>');">
        <div class="bd-container">
            <nav class="bd-breadcrumbs">
                <a href="<?php echo home_url(); ?>">Inicio</a> / 
                <a href="<?php echo get_post_type_archive_link('directorio_negocio'); ?>">Directorio</a> / 
                <span>Región: <?php echo esc_html( $term->name ); ?></span>
            </nav>
            <h1 class="bd-hero-title">Negocios en <?php echo esc_html( $term->name ); ?></h1>
            <?php if ( $term->description ) : ?>
                <p class="bd-hero-subtitle"><?php echo esc_html( $term->description ); ?></p>
            <?php else : ?>
                <p class="bd-hero-subtitle">Descubre los mejores servicios locales y comercios en <?php echo esc_html( $term->name ); ?>.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="bd-container bd-archive-layout">
        


        <!-- Resultados -->
        <main class="bd-archive-main" style="width: 100%;">
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
                        <i class="fas fa-search-minus"></i>
                        <h3>No hay resultados</h3>
                        <p>No encontramos negocios en esta región con los filtros aplicados.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>

    </div>
</div>

<?php get_footer(); ?>
