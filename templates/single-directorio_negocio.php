<?php
/**
 * Template: Single Listing
 * v3.1 — Micro-landing Premium para Negocios (Refactored)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

while ( have_posts() ) : the_post();
    $post_id = get_the_ID();
    
    // Obtener Metadata
    $whatsapp   = get_post_meta( $post_id, '_bd_whatsapp', true );
    $phone      = get_post_meta( $post_id, '_bd_telefono', true );
    $email      = get_post_meta( $post_id, '_bd_email', true );
    $website    = get_post_meta( $post_id, '_bd_sitio_web', true );
    $address    = get_post_meta( $post_id, '_bd_direccion', true );
    $price      = get_post_meta( $post_id, '_bd_rango_precio', true );
    $rating     = get_post_meta( $post_id, '_bd_reputacion', true );
    $fb         = get_post_meta( $post_id, '_bd_facebook', true );
    $ig         = get_post_meta( $post_id, '_bd_instagram', true );
    $lat        = get_post_meta( $post_id, '_bd_latitud', true );
    $lng        = get_post_meta( $post_id, '_bd_longitud', true );
    
    // Imagen de Portada (Featured Image como Background)
    $cover_url = get_the_post_thumbnail_url( $post_id, 'full' ) ?: BD_URL . 'assets/images/default-hero.jpg';
    $logo_url  = get_post_meta( $post_id, '_bd_logo_url', true ) ?: BD_URL . 'assets/images/default-logo.png';
    
    // Categoría principal
    $categories = get_the_terms( $post_id, 'directorio_categoria' );
    $main_cat   = ! empty( $categories ) ? $categories[0]->name : 'Sin Categoría';

    // Galería
    $gallery_ids = get_post_meta( $post_id, '_bd_galeria', true );
    $gallery     = ! empty( $gallery_ids ) ? explode( ',', $gallery_ids ) : array();
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('bd-single-container'); ?>>
    
    <?php require plugin_dir_path( __FILE__ ) . 'parts/single-hero.php'; ?>

    <div class="bd-container">
        <div class="bd-single-layout">
            
            <?php require plugin_dir_path( __FILE__ ) . 'parts/single-main.php'; ?>
            
            <?php require plugin_dir_path( __FILE__ ) . 'parts/single-sidebar.php'; ?>

        </div>
    </div>

</article>

<?php 
endwhile;
get_footer();
