<?php 
/**
 * Template Name: Single Negocio Premium - Refactor Completo (NEXUS)
 * Version: 5.1.0
 * Description: Unificación de Fases 1, 2 y 3. Diseño Premium, Responsive y 100% Dinámico.
 */
get_header(); 

// 1. DATA ACQUISITION & VALIDATION
$post_id = get_the_ID();

// Metas Principales
$direccion = get_post_meta( $post_id, '_bd_direccion', true );
$telefono  = get_post_meta( $post_id, '_bd_telefono', true );
$web       = get_post_meta( $post_id, '_bd_web', true );
$lat       = get_post_meta( $post_id, '_bd_latitud', true );
$lng       = get_post_meta( $post_id, '_bd_longitud', true );

// Galería: Obtenemos IDs de postmeta. Formato esperado: "ID,ID,ID"
$gallery_raw = get_post_meta( $post_id, '_bd_galeria', true );
$gallery_ids = ! empty( $gallery_raw ) ? explode( ',', $gallery_raw ) : array();
$featured_img_url = has_post_thumbnail() ? get_the_post_thumbnail_url( $post_id, 'full' ) : '';

// Taxonomías
$categories = wp_get_post_terms( $post_id, 'directorio_categoria', array( 'orderby' => 'parent', 'order' => 'ASC' ) );

// Limpiar número para WhatsApp
$wa_clean = preg_replace('/[^0-9]/', '', $telefono);
if(strlen($wa_clean) === 9) $wa_clean = '56' . $wa_clean;

?>

<style>
    :root {
        --bd-primary: #1a6ef5;
        --bd-dark: #1a1a1a;
        --bd-bg-soft: #f8f9fa;
        --bd-whatsapp: #25D366;
        --bd-border: #eee;
    }

    .bd-premium-page {
        max-width: 1200px;
        margin: 20px auto 60px;
        padding: 0 20px;
        font-family: 'Inter', sans-serif;
    }

    /* 1. NAVEGACIÓN & TÍTULO */
    .bd-breadcrumbs {
        display: flex;
        gap: 8px;
        font-size: 13px;
        color: #888;
        margin-bottom: 15px;
        list-style: none;
        padding: 0;
    }
    .bd-breadcrumbs a { color: #555; text-decoration: none; }
    .bd-breadcrumbs a:hover { color: var(--bd-primary); }
    .bd-breadcrumbs li::after { content: '/'; margin-left: 8px; }
    .bd-breadcrumbs li:last-child::after { content: ''; }

    .bd-main-title {
        font-family: 'Outfit', sans-serif;
        font-size: 3rem;
        font-weight: 800;
        color: var(--bd-dark);
        margin: 0 0 30px 0;
        letter-spacing: -0.03em;
        line-height: 1.1;
    }

    /* 2. GRID ARCHITECTURE */
    .bd-content-grid {
        display: grid;
        grid-template-columns: 1.8fr 1fr;
        gap: 40px;
    }

    /* 3. MULTIMEDIA (COL IZQUIERDA) */
    .bd-gallery-engine { margin-bottom: 40px; }
    .bd-main-viewer {
        width: 100%;
        aspect-ratio: 16/9;
        background: #f5f5f5;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        margin-bottom: 15px;
    }
    .bd-main-viewer img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: opacity 0.4s ease;
    }
    .bd-thumbs-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
        gap: 12px;
    }
    .bd-thumb-item {
        aspect-ratio: 1/1;
        border-radius: 8px;
        overflow: hidden;
        cursor: pointer;
        border: 2px solid transparent;
        transition: all 0.2s ease;
    }
    .bd-thumb-item.active { border-color: var(--bd-primary); }
    .bd-thumb-item img { width: 100%; height: 100%; object-fit: cover; }

    /* 4. CONTENIDO Y COMENTARIOS */
    .bd-section-title {
        font-family: 'Outfit', sans-serif;
        font-size: 1.5rem;
        margin: 40px 0 20px;
        color: var(--bd-dark);
    }
    .bd-listing-body {
        line-height: 1.8;
        color: #444;
        font-size: 1.1rem;
    }
    .bd-comments-wrapper {
        background: #fff;
        padding: 30px;
        border-radius: 16px;
        border: 1px solid var(--bd-border);
        margin-top: 40px;
    }

    /* 5. RELACIONADOS (SCROLL SNAP) */
    .bd-related-carousel {
        display: flex;
        gap: 20px;
        overflow-x: auto;
        scroll-snap-type: x mandatory;
        padding-bottom: 20px;
        scrollbar-width: none;
    }
    .bd-related-carousel::-webkit-scrollbar { display: none; }
    .bd-related-card {
        flex: 0 0 280px;
        scroll-snap-align: start;
        text-decoration: none;
        border-radius: 12px;
        border: 1px solid var(--bd-border);
        overflow: hidden;
        transition: transform 0.3s;
    }
    .bd-related-card:hover { transform: translateY(-5px); }

    /* 6. SIDEBAR STICKY (COL DERECHA) */
    .bd-right-col { position: relative; }
    .bd-sticky-container {
        position: sticky;
        top: 25px;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    .bd-sidebar-card {
        background: #fff;
        border-radius: 20px;
        padding: 25px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.05);
        border: 1px solid var(--bd-border);
    }
    .bd-data-item { display: flex; gap: 12px; margin-bottom: 15px; font-size: 0.95rem; }
    .bd-data-item i { color: var(--bd-primary); width: 20px; text-align: center; }
    .bd-cta-wa {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        background: var(--bd-whatsapp);
        color: #fff;
        text-decoration: none;
        padding: 16px;
        border-radius: 14px;
        font-weight: 700;
    }

    @media (max-width: 992px) {
        .bd-content-grid { grid-template-columns: 1fr; }
        .bd-main-title { font-size: 2.2rem; }
    }
</style>

<div class="bd-premium-page">
    
    <!-- BREADCRUMBS -->
    <nav>
        <ul class="bd-breadcrumbs">
            <li><a href="<?php echo home_url(); ?>">Inicio</a></li>
            <?php if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) : ?>
                <?php foreach ( $categories as $cat ) : ?>
                    <li><a href="<?php echo get_term_link( $cat ); ?>"><?php echo $cat->name; ?></a></li>
                <?php endforeach; ?>
            <?php endif; ?>
            <li><?php the_title(); ?></li>
        </ul>
    </nav>

    <h1 class="bd-main-title"><?php the_title(); ?></h1>

    <div class="bd-content-grid">
        
        <!-- COLUMNA IZQUIERDA -->
        <div class="bd-left-col">
            
            <!-- GALERÍA -->
            <div class="bd-gallery-engine">
                <div class="bd-main-viewer">
                    <?php if ( $featured_img_url ) : ?>
                        <img id="bd-viewport" src="<?php echo esc_url( $featured_img_url ); ?>" alt="<?php the_title(); ?>">
                    <?php else : ?>
                        <div style="display:flex;align-items:center;justify-content:center;height:100%;color:#ccc;">📍 Sin imagen</div>
                    <?php endif; ?>
                </div>

                <?php if ( ! empty( $gallery_ids ) || $featured_img_url ) : ?>
                    <div class="bd-thumbs-grid">
                        <?php if ( $featured_img_url ) : ?>
                            <div class="bd-thumb-item active" onmouseover="nexusSwap('<?php echo esc_url($featured_img_url); ?>', this)">
                                <img src="<?php echo esc_url($featured_img_url); ?>">
                            </div>
                        <?php endif; ?>
                        <?php foreach ( $gallery_ids as $id ) : 
                            $full = wp_get_attachment_image_src($id, 'full'); 
                            $th = wp_get_attachment_image_src($id, 'thumbnail'); 
                            if($full): ?>
                            <div class="bd-thumb-item" onmouseover="nexusSwap('<?php echo esc_url($full[0]); ?>', this)">
                                <img src="<?php echo esc_url($th[0]); ?>">
                            </div>
                        <?php endif; endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- DESCRIPCIÓN -->
            <div class="bd-listing-body">
                <h3 class="bd-section-title">Sobre este lugar</h3>
                <?php while ( have_posts() ) : the_post(); the_content(); endwhile; ?>
            </div>

            <!-- COMENTARIOS -->
            <div class="bd-comments-wrapper">
                <h3 class="bd-section-title" style="margin-top:0;">Opiniones de la comunidad</h3>
                <?php if ( comments_open() || get_comments_number() ) comments_template(); ?>
            </div>

            <!-- RELACIONADOS -->
            <?php 
            $current_cats = wp_get_post_terms( get_the_ID(), 'directorio_categoria', array('fields' => 'ids') );
            if ( ! empty( $current_cats ) ) :
                $rel_query = new WP_Query( array(
                    'post_type' => 'directorio_negocio',
                    'posts_per_page' => 6,
                    'post__not_in' => array( get_the_ID() ),
                    'tax_query' => array( array('taxonomy' => 'directorio_categoria', 'field' => 'term_id', 'terms' => $current_cats) ),
                    'orderby' => 'rand'
                ));
                if ( $rel_query->have_posts() ) : ?>
                    <section style="margin-top:50px;">
                        <h3 class="bd-section-title">Negocios relacionados</h3>
                        <div class="bd-related-carousel">
                            <?php while ( $rel_query->have_posts() ) : $rel_query->the_post(); ?>
                                <a href="<?php the_permalink(); ?>" class="bd-related-card">
                                    <div class="bd-related-img" style="aspect-ratio:4/3; background:#eee;">
                                        <?php if(has_post_thumbnail()) the_post_thumbnail('medium', array('style'=>'width:100%;height:100%;object-fit:cover;')); ?>
                                    </div>
                                    <div style="padding:15px;">
                                        <h4 style="margin:0; font-size:1rem; color:var(--bd-dark);"><?php the_title(); ?></h4>
                                    </div>
                                </a>
                            <?php endwhile; ?>
                        </div>
                    </section>
                <?php endif; wp_reset_postdata(); endif; ?>
        </div>

        <!-- COLUMNA DERECHA -->
        <div class="bd-right-col">
            <div class="bd-sticky-container">
                <div class="bd-sidebar-card">
                    <h3 style="margin-top:0; font-family:'Outfit';">Contacto</h3>
                    <ul style="list-style:none; padding:0; margin-bottom:20px;">
                        <?php if($direccion): ?><li class="bd-data-item"><i class="fas fa-map-marker-alt"></i><span><?php echo esc_html($direccion); ?></span></li><?php endif; ?>
                        <?php if($telefono): ?><li class="bd-data-item"><i class="fas fa-phone-alt"></i><span><?php echo esc_html($telefono); ?></span></li><?php endif; ?>
                        <?php if($web): ?><li class="bd-data-item"><i class="fas fa-globe"></i><a href="<?php echo esc_url($web); ?>" target="_blank" rel="nofollow">Sitio Web</a></li><?php endif; ?>
                    </ul>
                    <?php if($wa_clean): ?>
                        <a href="https://wa.me/<?php echo esc_attr($wa_clean); ?>" class="bd-cta-wa" target="_blank"><i class="fab fa-whatsapp"></i> WhatsApp</a>
                    <?php endif; ?>
                </div>

                <?php if($lat && $lng): ?>
                    <div class="bd-sidebar-card" style="padding:10px;">
                        <div id="bd-interactive-map" style="width:100%; height:200px; border-radius:12px; background:#eee;" data-lat="<?php echo esc_attr($lat); ?>" data-lng="<?php echo esc_attr($lng); ?>"></div>
                        <div style="text-align:center; padding-top:10px;">
                            <a href="https://www.google.com/maps/search/?api=1&query=<?php echo $lat.','.$lng; ?>" target="_blank" style="font-size:12px; color:var(--bd-primary); text-decoration:none; font-weight:600;">Ver en Google Maps</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
function nexusSwap(src, el) {
    const vp = document.getElementById('bd-viewport');
    if(!vp || vp.src === src) return;
    document.querySelectorAll('.bd-thumb-item').forEach(i => i.classList.remove('active'));
    el.classList.add('active');
    vp.style.opacity = '0.3';
    const t = new Image(); t.src = src;
    t.onload = () => { vp.src = src; vp.style.opacity = '1'; };
}

// Map Engine (Leaflet)
document.addEventListener('DOMContentLoaded', function() {
    const mapEl = document.getElementById('bd-interactive-map');
    if (!mapEl) return;
    const lat = parseFloat(mapEl.dataset.lat);
    const lng = parseFloat(mapEl.dataset.lng);
    if (isNaN(lat) || isNaN(lng)) return;

    const map = L.map('bd-interactive-map', { scrollWheelZoom: false }).setView([lat, lng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
    L.marker([lat, lng]).addTo(map);
});
</script>
<?php get_footer(); ?>
