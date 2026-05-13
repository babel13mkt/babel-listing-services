<?php
/**
 * Template Part: Single Main Content (Premium)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$post_id = get_the_ID();
$gallery_ids = get_post_meta( $post_id, '_bd_galeria', true );
$gallery     = ! empty( $gallery_ids ) ? explode( ',', $gallery_ids ) : array();
?>

<div class="bd-single-main-card">
    
    <!-- Descripción Editorial -->
    <section class="bd-single-section">
        <h2 style="font-size: 24px; font-weight: 800; margin-bottom: 20px; letter-spacing: -1px;">Sobre este negocio</h2>
        <div class="bd-single-description" style="font-size: 17px; color: #444;">
            <?php the_content(); ?>
        </div>
    </section>

    <!-- Atributos Modulares -->
    <section class="bd-single-section" style="margin-top: 40px; padding-top: 40px; border-top: 1px solid #eee;">
        <h2 style="font-size: 20px; font-weight: 800; margin-bottom: 25px;">Servicios y Comodidades</h2>
        <div class="bd-single-attributes" style="display: flex; flex-wrap: wrap; gap: 15px;">
            <?php 
            $amenities = array(
                'wifi'            => array('icon' => 'fa-wifi', 'label' => 'WiFi Gratis'),
                'estacionamiento' => array('icon' => 'fa-parking', 'label' => 'Estacionamiento'),
                'delivery'        => array('icon' => 'fa-motorcycle', 'label' => 'Despacho a Domicilio'),
                'accesibilidad'   => array('icon' => 'fa-wheelchair', 'label' => 'Accesibilidad'),
            );

            foreach($amenities as $key => $data):
                $val = get_post_meta($post_id, '_bd_' . $key, true);
                if($val === '1'): ?>
                    <div class="bd-attr-item" style="background: #f9f9f9; padding: 10px 18px; border-radius: 50px; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 10px; border: 1px solid #eee;">
                        <i class="fas <?php echo $data['icon']; ?>" style="color: #000;"></i>
                        <span><?php echo $data['label']; ?></span>
                    </div>
                <?php endif;
            endforeach; ?>
        </div>
    </section>

    <!-- Galería Premium -->
    <?php if ( ! empty( $gallery ) ) : ?>
    <section class="bd-single-section" style="margin-top: 40px; padding-top: 40px; border-top: 1px solid #eee;">
        <h2 style="font-size: 20px; font-weight: 800; margin-bottom: 25px;">Galería de Fotos</h2>
        <div class="bd-single-gallery" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">
            <?php foreach ( $gallery as $img_id ) : 
                $img_url = wp_get_attachment_image_url( $img_id, 'medium_large' );
                if ( $img_url ) : ?>
                    <div class="bd-gallery-item" style="aspect-ratio: 1/1; overflow: hidden; border-radius: 8px;">
                        <a href="<?php echo esc_url( wp_get_attachment_image_url( $img_id, 'full' ) ); ?>" class="bd-lightbox">
                            <img src="<?php echo esc_url( $img_url ); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                        </a>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Reseñas -->
    <section class="bd-single-section" style="margin-top: 40px; padding-top: 40px; border-top: 1px solid #eee;">
        <h2 style="font-size: 20px; font-weight: 800; margin-bottom: 25px;">Reseñas y Opiniones</h2>
        <?php 
        if ( comments_open() || get_comments_number() ) :
            comments_template();
        endif; 
        ?>
    </section>

</div>
