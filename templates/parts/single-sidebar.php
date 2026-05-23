<?php
/**
 * Template Part: Single Sidebar (Minimalist)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$post_id = get_the_ID();
$whatsapp = get_post_meta( $post_id, '_bd_whatsapp', true );
$telefono = get_post_meta( $post_id, '_bd_telefono', true );
$lat      = get_post_meta( $post_id, '_bd_latitud', true );
$lng      = get_post_meta( $post_id, '_bd_longitud', true );
$address  = get_post_meta( $post_id, '_bd_direccion', true );
?>

<div class="bd-sidebar-wrapper">
    
    <!-- Mapa (Minimalista) -->
    <div class="bd-sidebar-card bd-map-card">
        <h3 class="bd-sidebar-title">Ubicación</h3>
        <div id="bd-single-map" class="bd-sidebar-map-canvas" 
             data-lat="<?php echo esc_attr($lat); ?>" 
             data-lng="<?php echo esc_attr($lng); ?>">
        </div>
        <?php if($address): ?>
            <p class="bd-sidebar-address">
                <i class="fas fa-map-marker-alt bd-sidebar-address-icon"></i>
                <?php echo esc_html($address); ?>
            </p>
        <?php endif; ?>
    </div>

    <!-- Contacto (Original) -->
    <div class="bd-sidebar-card bd-contact-card">
        <h3 class="bd-sidebar-title">Contacto</h3>
        
        <div class="bd-contact-buttons">
            <?php if($whatsapp): ?>
                <a href="https://wa.me/<?php echo esc_attr($whatsapp); ?>" class="bd-btn-whatsapp" target="_blank">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </a>
            <?php endif; ?>

            <?php if($telefono): ?>
                <a href="tel:<?php echo esc_attr($telefono); ?>" class="bd-btn-phone">
                    <i class="fas fa-phone"></i> Llamar
                </a>
            <?php endif; ?>
        </div>
    </div>

</div>
