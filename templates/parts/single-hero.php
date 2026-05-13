<?php
/**
 * Template Part: Single Hero (Minimalist)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$post_id    = get_the_ID();
$cover_url  = get_the_post_thumbnail_url( $post_id, 'full' ) ?: BD_URL . 'assets/images/default-hero.jpg';
$categories = get_the_terms( $post_id, 'directorio_categoria' );
$main_cat   = ! empty( $categories ) ? $categories[0]->name : 'Sin Categoría';
$rating     = get_post_meta( $post_id, '_bd_reputacion', true );
?>

<div class="bd-single-header">
    <div class="bd-container">
        <div class="bd-hero-content">
            <span class="bd-card-category"><?php echo esc_html( $main_cat ); ?></span>
            <h1 class="bd-single-title"><?php the_title(); ?></h1>
            
            <div class="bd-single-meta">
                <?php if( $rating ): ?>
                    <div class="bd-card-rating">
                        <?php BD_Frontend::render_stars( $rating, get_post_meta( $post_id, '_bd_review_count', true ) ); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
