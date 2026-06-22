<?php
/**
 * Template Part: Single Hero (Minimalist)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$post_id    = get_the_ID();
$cover_url  = get_the_post_thumbnail_url( $post_id, 'full' ) ?: BD_URL . 'assets/images/default-hero.jpg';
$categories = get_the_terms( $post_id, 'babel_category' );
$main_cat   = ! empty( $categories ) ? $categories[0]->name : 'Sin Categoría';
$rating     = get_post_meta( $post_id, '_bd_reputacion', true );

/**
 * Render star rating as HTML using ★ and ☆ characters.
 *
 * @param float $rating Rating value (e.g., 4.5).
 * @return string HTML string of stars.
 */
if ( ! function_exists( 'render_stars' ) ) {
    function render_stars( $rating ) {
        $rating = (float) $rating;
        $full_stars = floor( $rating );
        $half_star  = ( $rating - $full_stars ) >= 0.25 && ( $rating - $full_stars ) < 0.75;
        $empty_stars = 5 - $full_stars - ( $half_star ? 1 : 0 );

        $html = '';
        for ( $i = 0; $i < $full_stars; $i++ ) {
            $html .= '★';
        }
        if ( $half_star ) {
            $html .= '★'; // approximate half as a full star
        }
        for ( $i = 0; $i < $empty_stars; $i++ ) {
            $html .= '☆';
        }
        return $html . ' <strong>' . number_format( $rating, 1, '.', '' ) . '</strong>';
    }
}
?>

<div class="bd-single-header">
    <div class="bd-container">
        <div style="margin-bottom: 24px;">
            <?php echo do_shortcode( '[bd_breadcrumbs]' ); ?>
        </div>
        <div class="bd-hero-content">
            <span class="bd-card-category"><?php echo esc_html( $main_cat ); ?></span>
            <h1 class="bd-single-title"><?php the_title(); ?></h1>
            
            <div class="bd-single-meta">
                <?php if( $rating ): ?>
                    <div class="bd-card-rating">
                        <?php echo render_stars( $rating ); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
