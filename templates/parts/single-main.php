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
        <h2 class="bd-single-main-title">Sobre este negocio</h2>
        <div class="bd-single-description">
            <?php the_content(); ?>
        </div>
    </section>

    <!-- Atributos Modulares -->
    <section class="bd-single-section bd-single-section-divider">
        <h2 class="bd-single-section-title">Servicios y Comodidades</h2>
        <div class="bd-single-attributes">
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
                    <div class="bd-attr-item">
                        <i class="fas <?php echo $data['icon']; ?> bd-attr-icon"></i>
                        <span><?php echo $data['label']; ?></span>
                    </div>
                <?php endif;
            endforeach; ?>
        </div>
    </section>

    <!-- Galería Premium -->
    <?php if ( ! empty( $gallery ) ) : ?>
    <section class="bd-single-section bd-single-section-divider">
        <h2 class="bd-single-section-title">Galería de Fotos</h2>
        <div class="bd-single-gallery">
            <?php foreach ( $gallery as $img_id ) : 
                $img_url = wp_get_attachment_image_url( $img_id, 'medium_large' );
                if ( $img_url ) : ?>
                    <div class="bd-gallery-item">
                        <a href="<?php echo esc_url( wp_get_attachment_image_url( $img_id, 'full' ) ); ?>" class="bd-lightbox">
                            <img src="<?php echo esc_url( $img_url ); ?>" class="bd-gallery-img" alt="">
                        </a>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Reseñas -->
    <section class="bd-single-section bd-single-section-divider">
        <h2 class="bd-single-section-title">Reseñas y Opiniones</h2>
        <?php 
        if ( comments_open() || get_comments_number() ) :
            comments_template();
        endif; 
        ?>
    </section>

</div>
