<?php
/**
 * Template para Single Institución (bd_institution)
 * Muestra: datos de contacto, mapa OpenStreetMap, y link para abrir en Google Maps/Waze
 */

get_header();

while ( have_posts() ) :
    the_post();
    $post_id = get_the_ID();

    // Meta fields de la institución
    $nombre     = get_the_title();
    $comuna     = get_post_meta( $post_id, '_bd_institucion_comuna', true );
    $region     = get_post_meta( $post_id, '_bd_institucion_region', true );
    $telefono   = get_post_meta( $post_id, '_bd_institucion_telefono', true );
    $direccion  = get_post_meta( $post_id, '_bd_institucion_direccion', true );
    $web        = get_post_meta( $post_id, '_bd_institucion_web', true );
    $lat        = get_post_meta( $post_id, '_bd_institucion_latitud', true );
    $lng        = get_post_meta( $post_id, '_bd_institucion_longitud', true );
    $tipo       = get_post_meta( $post_id, '_bd_institucion_tipo', true );
    $verificada = get_post_meta( $post_id, '_bd_institucion_verificada', true );

    // URLs de navegación maps
    $maps_query = ! empty( $direccion ) ? $direccion : $nombre . ', ' . $comuna;
    $google_maps_url = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $maps_query );
    $waze_url        = 'https://waze.com/ll?' . ( $lat && $lng ? 'lat=' . rawurlencode( $lat ) . '&lon=' . rawurlencode( $lng ) . '&navigate=yes' : 'q=' . rawurlencode( $maps_query ) );
    $apple_maps_url  = 'https://maps.apple.com/?q=' . rawurlencode( $maps_query ) . ( $lat && $lng ? '&ll=' . rawurlencode( $lat ) . ',' . rawurlencode( $lng ) : '' );
?>

<div class="bd-institution-single" style="max-width:800px;margin:0 auto;padding:24px;">

    <!-- Header -->
    <header class="bd-institution-header" style="margin-bottom:24px;">
        <?php if ( $tipo ) : ?>
            <span class="bd-badge" style="display:inline-block;padding:4px 12px;border-radius:20px;background:var(--color-primary,#2563eb);color:#fff;font-size:12px;margin-bottom:8px;">
                <?php echo esc_html( $tipo ); ?>
            </span>
        <?php endif; ?>

        <h1 style="margin:0 0 8px;font-size:28px;">
            <?php echo esc_html( $nombre ); ?>
            <?php if ( $verificada ) : ?>
                <span title="Institución verificada" style="color:#10b981;font-size:20px;">✓</span>
            <?php endif; ?>
        </h1>

        <p style="color:#6b7280;margin:0;">
            <?php echo esc_html( $comuna ); ?><?php if ( $region ) echo ', ' . esc_html( $region ); ?>
        </p>
    </header>

    <!-- Mapa + Navegación -->
    <?php if ( $lat && $lng ) : ?>
    <div class="bd-institution-map-section" style="margin-bottom:24px;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;">

        <!-- Mapa OpenStreetMap -->
        <div id="bd-institution-map" style="width:100%;height:250px;" data-lat="<?php echo esc_attr( $lat ); ?>" data-lng="<?php echo esc_attr( $lng ); ?>" data-name="<?php echo esc_attr( $nombre ); ?>"></div>

        <!-- Botones de navegación -->
        <div class="bd-map-actions" style="display:flex;gap:8px;padding:12px;background:#f9fafb;flex-wrap:wrap;">

            <!-- Botón principal: Detecta SO y abre app nativa -->
            <a href="#" id="bd-navigate-btn"
               onclick="bdOpenNavigation(event, '<?php echo esc_js( $lat ); ?>', '<?php echo esc_js( $lng ); ?>', '<?php echo esc_js( $maps_query ); ?>')"
               style="flex:1;min-width:200px;display:flex;align-items:center;justify-content:center;gap:8px;padding:12px 20px;background:#2563eb;color:#fff;border-radius:8px;text-decoration:none;font-weight:600;font-size:15px;">
                🗺️ <span>Cómo llegar</span>
            </a>

            <!-- Dropdown con opciones explícitas -->
            <div style="position:relative;">
                <button type="button" onclick="bdToggleMapOptions(this)"
                        style="padding:12px 16px;background:#fff;border:1px solid #d1d5db;border-radius:8px;cursor:pointer;font-size:14px;display:flex;align-items:center;gap:6px;">
                    Abrir en <span style="font-size:10px;">▼</span>
                </button>
                <div class="bd-map-options" style="display:none;position:absolute;right:0;top:100%;margin-top:4px;background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.1);z-index:100;min-width:180px;">
                    <a href="<?php echo esc_url( $google_maps_url ); ?>" target="_blank" rel="noopener" style="display:flex;align-items:center;gap:8px;padding:10px 16px;text-decoration:none;color:#374151;font-size:14px;border-bottom:1px solid #f3f4f6;">
                        📍 Google Maps
                    </a>
                    <a href="<?php echo esc_url( $waze_url ); ?>" target="_blank" rel="noopener" style="display:flex;align-items:center;gap:8px;padding:10px 16px;text-decoration:none;color:#374151;font-size:14px;border-bottom:1px solid #f3f4f6;">
                        🚗 Waze
                    </a>
                    <a href="<?php echo esc_url( $apple_maps_url ); ?>" target="_blank" rel="noopener" style="display:flex;align-items:center;gap:8px;padding:10px 16px;text-decoration:none;color:#374151;font-size:14px;">
                        🍎 Apple Maps
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
    function bdOpenNavigation(e, lat, lng, query) {
        e.preventDefault();
        var isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
        var isAndroid = /Android/.test(navigator.userAgent);

        if (isIOS) {
            // iOS: intentar Apple Maps primero, fallback a Google Maps
            window.location.href = 'https://maps.apple.com/?q=' + encodeURIComponent(query) + '&ll=' + lat + ',' + lng;
        } else if (isAndroid) {
            // Android: intentar Google Maps app directamente
            window.location.href = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(query);
        } else {
            // Desktop: Google Maps web
            window.open('https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(query), '_blank');
        }
    }

    function bdToggleMapOptions(btn) {
        var dropdown = btn.nextElementSibling;
        dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    }

    // Cerrar dropdown al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.bd-map-actions')) {
            var dropdowns = document.querySelectorAll('.bd-map-options');
            dropdowns.forEach(function(d) { d.style.display = 'none'; });
        }
    });

    // Inicializar Leaflet
    document.addEventListener('DOMContentLoaded', function() {
        var mapEl = document.getElementById('bd-institution-map');
        if (mapEl && typeof L !== 'undefined') {
            var lat = parseFloat(mapEl.getAttribute('data-lat'));
            var lng = parseFloat(mapEl.getAttribute('data-lng'));
            var name = mapEl.getAttribute('data-name');

            if (lat && lng) {
                var map = L.map('bd-institution-map', { zoomControl: true, scrollWheelZoom: false }).setView([lat, lng], 15);
                L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                    maxZoom: 19
                }).addTo(map);
                L.marker([lat, lng]).addTo(map).bindPopup(name).openPopup();
            }
        }
    });
    </script>
    <?php endif; ?>

    <!-- Datos de contacto -->
    <div class="bd-institution-contact" style="display:grid;gap:16px;margin-bottom:24px;">

        <?php if ( $direccion ) : ?>
        <div class="bd-contact-row" style="display:flex;align-items:flex-start;gap:12px;padding:12px;background:#f9fafb;border-radius:8px;">
            <span style="font-size:20px;">📍</span>
            <div>
                <strong style="display:block;font-size:13px;color:#6b7280;margin-bottom:2px;">Dirección</strong>
                <span style="font-size:15px;color:#111827;"><?php echo esc_html( $direccion ); ?></span>
            </div>
        </div>
        <?php endif; ?>

        <?php if ( $telefono ) : ?>
        <div class="bd-contact-row" style="display:flex;align-items:flex-start;gap:12px;padding:12px;background:#f9fafb;border-radius:8px;">
            <span style="font-size:20px;">📞</span>
            <div>
                <strong style="display:block;font-size:13px;color:#6b7280;margin-bottom:2px;">Teléfono</strong>
                <a href="tel:<?php echo esc_attr( preg_replace('/[^\d+]/', '', $telefono) ); ?>" style="font-size:15px;color:#2563eb;text-decoration:none;">
                    <?php echo esc_html( $telefono ); ?>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <?php if ( $web ) : ?>
        <div class="bd-contact-row" style="display:flex;align-items:flex-start;gap:12px;padding:12px;background:#f9fafb;border-radius:8px;">
            <span style="font-size:20px;">🌐</span>
            <div>
                <strong style="display:block;font-size:13px;color:#6b7280;margin-bottom:2px;">Sitio web</strong>
                <a href="<?php echo esc_url( strpos($web, 'http') === 0 ? $web : 'https://' . $web ); ?>" target="_blank" rel="noopener" style="font-size:15px;color:#2563eb;text-decoration:none;">
                    <?php echo esc_html( $web ); ?>
                </a>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- Descripción -->
    <?php if ( get_the_content() ) : ?>
    <div class="bd-institution-content" style="padding-top:24px;border-top:1px solid #e5e7eb;">
        <div style="font-size:15px;line-height:1.7;color:#374151;">
            <?php the_content(); ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php
endwhile;
get_footer();
