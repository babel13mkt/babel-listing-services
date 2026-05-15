<?php 
/**
 * Template Name: Single Negocio Premium - Terral de Maule (Sovereign Integration)
 * Version: 6.0.0
 * Description: Integración del sistema de diseño Terral de Maule. Estética Editorial, Orgánica y de Alta Gama.
 */
get_header(); 

// 1. DATA ACQUISITION & VALIDATION (Preserving strict backend logic)
$post_id = get_the_ID();

// Metas Principales
$direccion = get_post_meta( $post_id, '_bd_direccion', true );
$telefono  = get_post_meta( $post_id, '_bd_telefono', true );
$web       = get_post_meta( $post_id, '_bd_web', true );
$lat       = get_post_meta( $post_id, '_bd_latitud', true );
$lng       = get_post_meta( $post_id, '_bd_longitud', true );

// Galería
$gallery_raw = get_post_meta( $post_id, '_bd_galeria', true );
$gallery_ids = ! empty( $gallery_raw ) ? (is_array($gallery_raw) ? $gallery_raw : explode( ',', $gallery_raw )) : array();
$featured_img_url = has_post_thumbnail() ? get_the_post_thumbnail_url( $post_id, 'full' ) : '';

// Logo & Mapa
$logo_id    = get_post_meta( $post_id, '_bd_logo_id', true );
$maps_embed = get_post_meta( $post_id, '_bd_maps_embed', true );

// Taxonomías
$categories = wp_get_post_terms( $post_id, 'directorio_categoria', array( 'orderby' => 'parent', 'order' => 'ASC' ) );

// Limpiar número para WhatsApp
$wa_clean = preg_replace('/[^0-9]/', '', $telefono);
if(strlen($wa_clean) === 9) $wa_clean = '56' . $wa_clean;

?>

<!-- TERRAL DE MAULE DESIGN SYSTEM TOKENS -->
<style>
    @import url('https://fonts.googleapis.com/css2?family=Newsreader:ital,opsz,wght@0,6..72,200..800;1,6..72,200..800&family=Work+Sans:wght@300;400;500;600;700&display=swap');

    :root {
        --terral-primary: #154212;
        --terral-primary-container: #2D5A27;
        --terral-secondary: #9D4227;
        --terral-secondary-container: #FE8C6B;
        --terral-tertiary: #003A6D;
        --terral-bg: #FBFAE8;
        --terral-surface: #F0EFDD;
        --terral-surface-high: #EAE9D7;
        --terral-on-surface: #1B1C12;
        --terral-on-surface-variant: #42493E;
        --terral-outline: rgba(114, 121, 110, 0.2);
        
        --font-editorial: 'Newsreader', serif;
        --font-utility: 'Work Sans', sans-serif;
    }

    .bd-terral-wrapper {
        background-color: var(--terral-bg);
        color: var(--terral-on-surface);
        font-family: var(--font-utility);
        padding: 40px 0 100px;
        line-height: 1.6;
    }

    .container-editorial {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 24px;
    }

    /* 1. HEADER & BREADCRUMBS */
    .bd-editorial-header {
        margin-bottom: 60px;
        position: relative;
    }

    .bd-terral-breadcrumbs {
        display: flex;
        gap: 12px;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: var(--terral-on-surface-variant);
        margin-bottom: 24px;
        list-style: none;
        padding: 0;
        font-weight: 600;
    }
    .bd-terral-breadcrumbs a { color: inherit; text-decoration: none; transition: color 0.3s; }
    .bd-terral-breadcrumbs a:hover { color: var(--terral-secondary); }
    .bd-terral-breadcrumbs li:not(:last-child)::after { content: '•'; margin-left: 12px; opacity: 0.3; }

    .bd-display-title {
        font-family: var(--font-editorial);
        font-size: clamp(2.5rem, 8vw, 4rem);
        font-weight: 700;
        line-height: 1.05;
        letter-spacing: -0.04em;
        margin: 0;
        color: var(--terral-primary);
    }

    /* 2. LAYOUT GRID */
    .bd-editorial-grid {
        display: grid;
        grid-template-columns: 1.6fr 1fr;
        gap: 80px;
        align-items: start;
    }

    /* 3. MULTIMEDIA ENGINE */
    .bd-multimedia-stack {
        margin-bottom: 60px;
    }

    .bd-main-frame {
        width: 100%;
        aspect-ratio: 16 / 9;
        background: var(--terral-surface);
        border-radius: 8px;
        overflow: hidden;
        margin-bottom: 24px;
        box-shadow: 0 30px 60px rgba(27, 28, 18, 0.05);
        position: relative;
    }

    .bd-main-frame img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: opacity 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .bd-editorial-thumbs {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        gap: 16px;
    }

    .bd-thumb-editorial {
        aspect-ratio: 4 / 3;
        border-radius: 4px;
        overflow: hidden;
        cursor: pointer;
        opacity: 0.6;
        transition: all 0.3s ease;
        filter: grayscale(0.2);
    }

    .bd-thumb-editorial.active,
    .bd-thumb-editorial:hover {
        opacity: 1;
        filter: grayscale(0);
        transform: translateY(-2px);
    }

    .bd-thumb-editorial img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* 4. CONTENT PROSE */
    .bd-prose-section {
        margin-top: 60px;
    }

    .bd-prose-title {
        font-family: var(--font-editorial);
        font-size: 2rem;
        margin-bottom: 24px;
        color: var(--terral-primary);
    }

    .bd-editorial-body {
        font-size: 1.125rem;
        color: var(--terral-on-surface);
        max-width: 680px;
    }
    .bd-editorial-body p { margin-bottom: 1.5rem; }

    /* 5. SIDEBAR SOVEREIGN CARD */
    .bd-sidebar-sovereign {
        position: sticky;
        top: 40px;
        display: flex;
        flex-direction: column;
        gap: 32px;
    }

    .bd-card-tonal {
        background-color: var(--terral-surface);
        padding: 40px;
        border-radius: 12px;
        position: relative;
    }

    .bd-logo-frame {
        width: 120px;
        height: 120px;
        margin: -80px 0 24px 0;
        background: var(--terral-bg);
        border-radius: 16px;
        padding: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 10px 30px rgba(0,0,0,0.03);
    }
    .bd-logo-frame img { max-width: 100%; max-height: 100%; object-fit: contain; }

    .bd-contact-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: var(--terral-on-surface-variant);
        margin-bottom: 8px;
        font-weight: 700;
        display: block;
    }

    .bd-contact-value {
        font-size: 1rem;
        margin-bottom: 24px;
        display: block;
        color: var(--terral-primary);
        font-weight: 500;
        text-decoration: none;
    }

    .btn-terral {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        padding: 16px 24px;
        border-radius: 8px;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
        width: 100%;
        font-size: 0.9rem;
    }

    .btn-terral-primary {
        background-color: var(--terral-secondary);
        color: #FFF;
    }
    .btn-terral-primary:hover {
        background-color: #83351F;
        transform: translateY(-2px);
    }

    .btn-terral-outline {
        background-color: transparent;
        color: var(--terral-primary);
        border: 1px solid var(--terral-outline);
        margin-top: 12px;
    }
    .btn-terral-outline:hover {
        background-color: var(--terral-surface-high);
    }

    /* 6. EDITORIAL REVIEW */
    .bd-review-editorial {
        margin-top: 80px;
        padding: 60px;
        background-color: var(--terral-surface-high);
        border-radius: 16px;
        position: relative;
    }

    .bd-review-editorial::before {
        content: '“';
        position: absolute;
        top: 20px;
        left: 40px;
        font-family: var(--font-editorial);
        font-size: 12rem;
        line-height: 1;
        color: var(--terral-primary);
        opacity: 0.05;
    }

    .bd-review-quote {
        font-family: var(--font-editorial);
        font-size: 1.75rem;
        font-style: italic;
        color: var(--terral-primary);
        margin: 0 0 32px 0;
        position: relative;
        z-index: 1;
    }

    .bd-review-meta {
        display: flex;
        align-items: center;
        gap: 16px;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--terral-on-surface-variant);
    }
    .bd-stars { color: var(--terral-secondary); display: flex; gap: 4px; }

    /* 7. RELACIONADOS (Editorial Snap) */
    .bd-related-editorial {
        margin-top: 100px;
    }
    .bd-related-title {
        font-family: var(--font-editorial);
        font-size: 1.5rem;
        margin-bottom: 40px;
    }
    .bd-editorial-carousel {
        display: flex;
        gap: 32px;
        overflow-x: auto;
        scroll-snap-type: x mandatory;
        padding-bottom: 40px;
        scrollbar-width: none;
    }
    .bd-editorial-carousel::-webkit-scrollbar { display: none; }
    
    .bd-related-item {
        flex: 0 0 320px;
        scroll-snap-align: start;
        text-decoration: none;
        color: inherit;
    }
    .bd-related-image {
        aspect-ratio: 4 / 3;
        border-radius: 6px;
        overflow: hidden;
        background: var(--terral-surface);
        margin-bottom: 16px;
    }
    .bd-related-image img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s; }
    .bd-related-item:hover img { transform: scale(1.05); }
    .bd-related-name { font-family: var(--font-editorial); font-size: 1.25rem; color: var(--terral-primary); }

    /* 8. INTERACTIVE MAP BOX */
    .bd-map-frame {
        width: 100%;
        height: 240px;
        border-radius: 8px;
        overflow: hidden;
        margin-top: 32px;
        background: var(--terral-surface);
    }

    /* RESPONSIVE FIXES */
    @media (max-width: 900px) {
        .bd-editorial-grid { grid-template-columns: 1fr; gap: 60px; }
        .bd-display-title { font-size: 2.8rem; }
        .bd-sidebar-sovereign { position: static; }
        .bd-logo-frame { margin-top: 0; }
        .bd-review-editorial { padding: 40px; }
    }
</style>

<div class="bd-terral-wrapper">
    <div class="container-editorial">
        
        <!-- HEADER EDITORIAL -->
        <header class="bd-editorial-header">
            <nav>
                <ul class="bd-terral-breadcrumbs">
                    <li><a href="<?php echo home_url(); ?>">Antología</a></li>
                    <?php if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) : ?>
                        <?php foreach ( $categories as $cat ) : ?>
                            <li><a href="<?php echo get_term_link( $cat ); ?>"><?php echo $cat->name; ?></a></li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <li><?php the_title(); ?></li>
                </ul>
            </nav>
            <h1 class="bd-display-title"><?php the_title(); ?></h1>
        </header>

        <div class="bd-editorial-grid">
            
            <!-- CONTENIDO PRINCIPAL (IZQUIERDA) -->
            <main class="bd-editorial-main">
                
                <!-- MULTIMEDIA ENGINE -->
                <section class="bd-multimedia-stack">
                    <div class="bd-main-frame">
                        <?php if ( $featured_img_url ) : ?>
                            <img id="bd-terral-viewport" src="<?php echo esc_url( $featured_img_url ); ?>" alt="<?php the_title(); ?>">
                        <?php else : ?>
                            <div style="display:flex;align-items:center;justify-content:center;height:100%; opacity:0.2;">
                                <i class="fas fa-image fa-3x"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ( ! empty( $gallery_ids ) || $featured_img_url ) : ?>
                        <div class="bd-editorial-thumbs">
                            <?php if ( $featured_img_url ) : ?>
                                <div class="bd-thumb-editorial active" onclick="terralSwap('<?php echo esc_url($featured_img_url); ?>', this)">
                                    <img src="<?php echo esc_url($featured_img_url); ?>">
                                </div>
                            <?php endif; ?>
                            <?php foreach ( $gallery_ids as $id ) : 
                                $full = wp_get_attachment_image_src($id, 'full'); 
                                $th = wp_get_attachment_image_src($id, 'thumbnail'); 
                                if($full): ?>
                                <div class="bd-thumb-editorial" onclick="terralSwap('<?php echo esc_url($full[0]); ?>', this)">
                                    <img src="<?php echo esc_url($th[0]); ?>">
                                </div>
                            <?php endif; endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- PROSE SECTION -->
                <section class="bd-prose-section">
                    <h2 class="bd-prose-title">La Historia</h2>
                    <div class="bd-editorial-body">
                        <?php while ( have_posts() ) : the_post(); the_content(); endwhile; ?>
                    </div>
                </section>

                <!-- SOVEREIGN REVIEW (Terral Style) -->
                <?php 
                $admin_rating = get_post_meta( $post_id, '_bd_admin_review_rating', true );
                $admin_text   = get_post_meta( $post_id, '_bd_admin_review_text', true );
                if ( $admin_rating && $admin_text ) : ?>
                    <section class="bd-review-editorial">
                        <blockquote class="bd-review-quote">
                            “<?php echo esc_html( $admin_text ); ?>”
                        </blockquote>
                        <div class="bd-review-meta">
                            <div class="bd-stars">
                                <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                                    <i class="<?php echo $i <= $admin_rating ? 'fas' : 'far'; ?> fa-star"></i>
                                <?php endfor; ?>
                            </div>
                            <span>RESEÑA EDITORIAL SOBERANA</span>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- COMENTARIOS -->
                <section class="bd-prose-section" style="margin-top:100px;">
                    <h3 class="bd-prose-title" style="font-size:1.5rem;">Voces de la comunidad</h3>
                    <div class="bd-comments-container" style="background:var(--terral-surface); padding:40px; border-radius:12px;">
                        <?php if ( comments_open() || get_comments_number() ) comments_template(); ?>
                    </div>
                </section>

            </main>

            <!-- SIDEBAR SOBERANO (DERECHA) -->
            <aside class="bd-sidebar-sovereign">
                
                <div class="bd-card-tonal">
                    <?php if ( $logo_id ) : 
                        $logo_url = wp_get_attachment_image_url( $logo_id, 'medium' );
                        if ( $logo_url ) : ?>
                        <div class="bd-logo-frame">
                            <img src="<?php echo esc_url( $logo_url ); ?>" alt="Logo <?php the_title(); ?>">
                        </div>
                    <?php endif; endif; ?>

                    <div class="bd-contact-group">
                        <?php if($direccion): ?>
                            <span class="bd-contact-label">Ubicación</span>
                            <span class="bd-contact-value"><?php echo esc_html($direccion); ?></span>
                        <?php endif; ?>

                        <?php if($telefono): ?>
                            <span class="bd-contact-label">Teléfono de Contacto</span>
                            <span class="bd-contact-value"><?php echo esc_html($telefono); ?></span>
                        <?php endif; ?>

                        <?php if($web): ?>
                            <span class="bd-contact-label">Sitio Web</span>
                            <a href="<?php echo esc_url($web); ?>" class="bd-contact-value" target="_blank" rel="nofollow">Visitar Portal <i class="fas fa-external-link-alt" style="font-size:0.8em; margin-left:4px;"></i></a>
                        <?php endif; ?>
                    </div>

                    <?php if($wa_clean): ?>
                        <a href="https://wa.me/<?php echo esc_attr($wa_clean); ?>" class="btn-terral btn-terral-primary" target="_blank">
                            <i class="fab fa-whatsapp"></i> CONECTAR POR WHATSAPP
                        </a>
                    <?php endif; ?>

                    <button class="btn-terral btn-terral-outline">
                        <i class="far fa-bookmark"></i> GUARDAR EN MI ANTOLOGÍA
                    </button>

                    <!-- MAP ENGINE -->
                    <?php if($lat && $lng): ?>
                        <div class="bd-map-frame">
                            <div id="terral-map-instance" style="width:100%; height:100%;" data-lat="<?php echo esc_attr($lat); ?>" data-lng="<?php echo esc_attr($lng); ?>"></div>
                        </div>
                        <a href="https://www.google.com/maps/search/?api=1&query=<?php echo $lat.','.$lng; ?>" target="_blank" style="display:block; text-align:center; margin-top:12px; font-size:0.7rem; font-weight:700; color:var(--terral-secondary); text-decoration:none;">ABRIR EN GOOGLE MAPS <i class="fas fa-arrow-right"></i></a>
                    <?php endif; ?>
                </div>

                <!-- INFO DE SOBERANÍA (Nivel Admin) -->
                <div style="padding:20px; font-size:0.75rem; color:var(--terral-on-surface-variant); border-top:1px solid var(--terral-outline);">
                    <i class="fas fa-shield-alt" style="margin-right:8px;"></i> Este comercio ha pasado la auditoría de autenticidad regional de Babel Directory.
                </div>
            </aside>
        </div>

        <!-- SECCIÓN RELACIONADOS -->
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
                <section class="bd-related-editorial">
                    <h3 class="bd-related-title">Otros rincones que te pueden interesar</h3>
                    <div class="bd-editorial-carousel">
                        <?php while ( $rel_query->have_posts() ) : $rel_query->the_post(); ?>
                            <a href="<?php the_permalink(); ?>" class="bd-related-item">
                                <div class="bd-related-image">
                                    <?php if(has_post_thumbnail()) the_post_thumbnail('medium'); ?>
                                </div>
                                <h4 class="bd-related-name"><?php the_title(); ?></h4>
                            </a>
                        <?php endwhile; ?>
                    </div>
                </section>
            <?php endif; wp_reset_postdata(); endif; ?>

    </div>
</div>

<script>
/**
 * Terral Multimedia Engine
 */
function terralSwap(src, el) {
    const vp = document.getElementById('bd-terral-viewport');
    if(!vp || vp.src === src) return;
    
    document.querySelectorAll('.bd-thumb-editorial').forEach(i => i.classList.remove('active'));
    el.classList.add('active');
    
    vp.style.opacity = '0.1';
    setTimeout(() => {
        vp.src = src;
        vp.style.opacity = '1';
    }, 300);
}

/**
 * Leaflet Integration (Terral Skin)
 */
document.addEventListener('DOMContentLoaded', function() {
    const mapEl = document.getElementById('terral-map-instance');
    if (!mapEl) return;
    
    const lat = parseFloat(mapEl.dataset.lat);
    const lng = parseFloat(mapEl.dataset.lng);
    
    const map = L.map('terral-map-instance', { 
        scrollWheelZoom: false,
        zoomControl: false 
    }).setView([lat, lng], 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    // Custom Icon or Simple Marker
    L.marker([lat, lng]).addTo(map);
});
</script>

<?php get_footer(); ?>
