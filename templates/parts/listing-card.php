<?php
/**
 * Template part: Listing Card (bd-card)
 * v3.5 — Premium Design System & Clean HTML
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$post_id = get_the_ID();

// Obtener Meta
$direccion = get_post_meta( $post_id, '_bd_direccion', true );
$telefono  = get_post_meta( $post_id, '_bd_telefono', true );
$whatsapp  = get_post_meta( $post_id, '_bd_whatsapp', true );
$verificado = get_post_meta( $post_id, '_bd_verificado', true );
$destacado  = get_post_meta( $post_id, '_bd_destacado', true );
$rating = get_post_meta( $post_id, '_bd_reputacion', true );
$review_count = get_post_meta( $post_id, '_bd_review_count', true );
$rango_precio = get_post_meta( $post_id, '_bd_rango_precio', true );

// Categoría / Región Principal
$categorias = get_the_terms( $post_id, 'directorio_categoria' );
$cat_name = ( $categorias && ! is_wp_error( $categorias ) ) ? $categorias[0]->name : '';

$regiones = get_the_terms( $post_id, 'directorio_region' );
$region_name = ( $regiones && ! is_wp_error( $regiones ) ) ? $regiones[0]->name : '';

// Imagen destacada
$thumb_url = get_the_post_thumbnail_url( $post_id, 'medium_large' );
if ( ! $thumb_url ) $thumb_url = BD_URL . 'assets/images/default-hero.jpg';

// Función Helper para Formato Romano (Portado de class-frontend.php si existiera globalmente)
function bd_format_region_name( $name ) {
    if ( preg_match( '/^REG-([IVXLCDM]+)\s+(.*)$/', $name, $matches ) ) {
        return $matches[1] . ' REG - ' . $matches[2];
    }
    return $name;
}

// Badge a mostrar en la foto
$badge_text = '';
if ( $region_name ) {
    $badge_text = strtoupper( bd_format_region_name( $region_name ) );
} elseif ( $cat_name ) {
    $badge_text = strtoupper( $cat_name );
}
?>

<article class="bd-card bd-premium-card <?php echo $destacado === '1' ? 'bd-card-featured' : ''; ?>">
    <div class="bd-card-media">
        <a href="<?php the_permalink(); ?>">
            <img src="<?php echo esc_url( $thumb_url ); ?>" class="bd-card-img" alt="<?php the_title_attribute(); ?>">
        </a>
        <?php if ( $badge_text ) : ?>
            <div class="bd-card-image-badge">
                <?php echo esc_html( $badge_text ); ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="bd-card-body">
        <h3 class="bd-card-title">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        </h3>

        <?php if ( $region_name || $direccion ) : ?>
            <div class="bd-card-info bd-card-location">
                <i class="fas fa-map-marker-alt"></i>
                <span><?php echo esc_html( $region_name ? bd_format_region_name( $region_name ) : $direccion ); ?></span>
            </div>
        <?php endif; ?>

        <?php if ( $rating ) : ?>
            <div class="bd-card-info bd-card-rating-premium">
                <i class="fas fa-star"></i>
                <span><strong><?php echo number_format((float)$rating, 1, '.', ''); ?></strong> <?php if($review_count) echo '('.intval($review_count).' Reviews)'; ?></span>
            </div>
        <?php endif; ?>
    </div>

    <div class="bd-card-footer">
        <a href="<?php the_permalink(); ?>" class="bd-btn bd-btn-premium-outline">VER DETALLES</a>
    </div>
</article>
