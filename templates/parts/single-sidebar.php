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
        <h3 style="font-size: 16px; font-weight: 800; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 1px;">Ubicación</h3>
        <div id="bd-single-map" style="height: 250px; border-radius: 8px; background: #eee;" 
             data-lat="<?php echo esc_attr($lat); ?>" 
             data-lng="<?php echo esc_attr($lng); ?>">
        </div>
        <?php if($address): ?>
            <p style="font-size: 14px; color: #666; margin-top: 15px; display: flex; align-items: flex-start; gap: 10px;">
                <i class="fas fa-map-marker-alt" style="color: #000; margin-top: 3px;"></i>
                <?php echo esc_html($address); ?>
            </p>
        <?php endif; ?>
    </div>

    <!-- Contacto (Original) -->
    <div class="bd-sidebar-card bd-contact-card" style="margin-top: 25px;">
        <h3 style="font-size: 16px; font-weight: 800; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px;">Contacto</h3>
        
        <div class="bd-contact-buttons" style="display: flex; flex-direction: column; gap: 12px;">
            <?php if($whatsapp): ?>
                <a href="https://wa.me/<?php echo esc_attr($whatsapp); ?>" class="bd-btn-whatsapp" target="_blank" style="background: #25D366; color: white; padding: 15px; border-radius: 8px; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 10px; font-weight: 700;">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </a>
            <?php endif; ?>

            <?php if($telefono): ?>
                <a href="tel:<?php echo esc_attr($telefono); ?>" class="bd-btn-phone" style="background: #000; color: white; padding: 15px; border-radius: 8px; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 10px; font-weight: 700;">
                    <i class="fas fa-phone"></i> Llamar
                </a>
            <?php endif; ?>
        </div>
    </div>

</div>
