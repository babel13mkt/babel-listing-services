<?php
/**
 * Template Part: Single Main Content
 * Lee _babel_attr_* (sistema nuevo) con fallback a _bd_* para atributos y galería.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$post_id = get_the_ID();

// Helper local (evita redeclaración si el sidebar ya la definió)
if ( ! function_exists( 'babel_meta' ) ) {
    function babel_meta( $post_id, $new_key, $old_key = '' ) {
        $val = get_post_meta( $post_id, $new_key, true );
        if ( empty( $val ) && ! empty( $old_key ) ) {
            $val = get_post_meta( $post_id, $old_key, true );
        }
        return $val;
    }
}

// Galería: sistema nuevo primero, luego viejo
$gallery_new = get_post_meta( $post_id, '_babel_gallery', true );
$gallery_old = get_post_meta( $post_id, '_bd_galeria', true );
$gallery_str = ! empty( $gallery_new ) ? $gallery_new : $gallery_old;
$gallery     = ! empty( $gallery_str ) ? explode( ',', $gallery_str ) : array();

// Atributos: sistema nuevo primero, viejo como fallback
$attributes = array(
    'wifi'          => array(
        'icon'  => 'wifi',
        'label' => 'WiFi Gratis',
        'new'   => '_babel_attr_wifi',
        'old'   => '_bd_wifi',
    ),
    'parking'       => array(
        'icon'  => 'local_parking',
        'label' => 'Estacionamiento',
        'new'   => '_babel_attr_parking',
        'old'   => '_bd_estacionamiento',
    ),
    'delivery'      => array(
        'icon'  => 'delivery_dining',
        'label' => 'Despacho a Domicilio',
        'new'   => '_babel_attr_delivery',
        'old'   => '_bd_delivery',
    ),
    'accesibilidad' => array(
        'icon'  => 'accessible',
        'label' => 'Accesibilidad',
        'new'   => '_babel_attr_accesibilidad',
        'old'   => '_bd_accesibilidad',
    ),
    'tarjetas'      => array(
        'icon'  => 'credit_card',
        'label' => 'Acepta Tarjetas',
        'new'   => '_babel_attr_tarjetas',
        'old'   => '',
    ),
    'reservas'      => array(
        'icon'  => 'event_available',
        'label' => 'Reservas',
        'new'   => '_babel_attr_reservas',
        'old'   => '',
    ),
);

$active_attrs = array();
foreach ( $attributes as $key => $data ) {
    $val = babel_meta( $post_id, $data['new'], $data['old'] );
    if ( '1' === $val ) {
        $active_attrs[ $key ] = $data;
    }
}

// Atributos dinámicos (Tags extríadas vía IA / Manual)
$biz_tags_meta = babel_meta( $post_id, '_babel_biz_tags' );
$dynamic_tags = ! empty( $biz_tags_meta ) ? explode( ',', $biz_tags_meta ) : array();
?>

<div class="bd-single-main-card">

    <!-- Descripción Editorial -->
    <section class="bd-single-section">
        <h2 class="bd-single-main-title">Sobre este negocio</h2>
        <div class="bd-single-description">
            <?php the_content(); ?>
        </div>
    </section>

    <!-- Atributos Modulares y Dinámicos -->
    <?php if ( ! empty( $active_attrs ) || ! empty( $dynamic_tags ) ) : ?>
    <section class="bd-single-section bd-single-section-divider">
        <h2 class="bd-single-section-title">Servicios y Comodidades</h2>
        <div class="bd-single-attributes" style="display:flex;flex-wrap:wrap;gap:10px;">
            <?php foreach ( $active_attrs as $data ) : ?>
                <div class="bd-attr-item" style="display:flex;align-items:center;gap:8px;background:#f4f3f9;border-radius:8px;padding:8px 14px;">
                    <span class="material-symbols-outlined" style="font-size:20px;color:#00205b;"><?php echo esc_html( $data['icon'] ); ?></span>
                    <span style="font-size:14px;font-weight:500;color:#1a1b20;"><?php echo esc_html( $data['label'] ); ?></span>
                </div>
            <?php endforeach; ?>
            
            <?php foreach ( $dynamic_tags as $tag ) : $tag = trim($tag); if(empty($tag)) continue; ?>
                <div class="bd-attr-item bd-tag-item" style="display:flex;align-items:center;gap:8px;background:#e0f2fe;border:1px solid #bae6fd;border-radius:8px;padding:8px 14px;">
                    <span class="material-symbols-outlined" style="font-size:20px;color:#0369a1;">check_circle</span>
                    <span style="font-size:14px;font-weight:600;color:#0369a1;"><?php echo esc_html( $tag ); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Galería Premium -->
    <?php if ( ! empty( $gallery ) ) : ?>
    <section class="bd-single-section bd-single-section-divider">
        <h2 class="bd-single-section-title">Galería de Fotos</h2>
        <div class="bd-single-gallery" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px;">
            <?php foreach ( $gallery as $img_id ) :
                $img_id  = intval( trim( $img_id ) );
                $img_url = wp_get_attachment_image_url( $img_id, 'medium_large' );
                if ( ! $img_url ) continue;
            ?>
                <div class="bd-gallery-item" style="border-radius:8px;overflow:hidden;">
                    <a href="<?php echo esc_url( wp_get_attachment_image_url( $img_id, 'full' ) ); ?>" class="bd-lightbox">
                        <img src="<?php echo esc_url( $img_url ); ?>" class="bd-gallery-img"
                             style="width:100%;height:140px;object-fit:cover;display:block;" alt="">
                    </a>
                </div>
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
