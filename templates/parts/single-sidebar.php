<?php
/**
 * Template Part: Single Sidebar
 * Lee _babel_* (sistema nuevo) con fallback a _bd_* (sistema viejo) para compatibilidad total.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$post_id = get_the_ID();

// Helper: leer meta con fallback entre dos sistemas de keys
function babel_meta( $post_id, $new_key, $old_key = '' ) {
    $val = get_post_meta( $post_id, $new_key, true );
    if ( empty( $val ) && ! empty( $old_key ) ) {
        $val = get_post_meta( $post_id, $old_key, true );
    }
    return $val;
}

$phone    = babel_meta( $post_id, '_babel_phone',    '_bd_telefono' );
$whatsapp = babel_meta( $post_id, '_babel_whatsapp', '_bd_whatsapp' ) ?: $phone;
$email    = babel_meta( $post_id, '_babel_email',    '' );
$website  = babel_meta( $post_id, '_babel_website',  '' );
$instagram= babel_meta( $post_id, '_babel_instagram','');
$lat      = babel_meta( $post_id, '_babel_lat',      '_bd_latitud'  );
$lng      = babel_meta( $post_id, '_babel_lng',      '_bd_longitud' );
$address  = babel_meta( $post_id, '_babel_address',  '_bd_direccion' );

// Horarios (sólo sistema nuevo)
$horarios_json = get_post_meta( $post_id, '_babel_horarios', true );
$horarios      = $horarios_json ? json_decode( $horarios_json, true ) : array();
$days_labels   = array(
    'lun' => 'Lunes', 'mar' => 'Martes', 'mie' => 'Miércoles',
    'jue' => 'Jueves', 'vie' => 'Viernes', 'sab' => 'Sábado', 'dom' => 'Domingo',
);
?>

<div class="bd-sidebar-wrapper">

    <!-- ── Mapa de Ubicación ── -->
    <?php if ( $lat && $lng ) : ?>
    <div class="bd-sidebar-card bd-map-card">
        <h3 class="bd-sidebar-title">Ubicación</h3>
        <div id="bd-single-map" class="bd-sidebar-map-canvas"
             data-lat="<?php echo esc_attr( $lat ); ?>"
             data-lng="<?php echo esc_attr( $lng ); ?>">
        </div>
        <?php if ( $address ) : ?>
            <p class="bd-sidebar-address">
                <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;margin-right:4px;">location_on</span>
                <?php echo esc_html( $address ); ?>
            </p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ── Contacto ── -->
    <?php if ( $whatsapp || $phone || $email || $website ) : ?>
    <div class="bd-sidebar-card bd-contact-card">
        <h3 class="bd-sidebar-title">Contacto</h3>
        <div class="bd-contact-buttons">
            <?php if ( $whatsapp ) : ?>
                <a href="https://wa.me/<?php echo esc_attr( preg_replace( '/[^0-9]/', '', $whatsapp ) ); ?>"
                   class="bd-btn-whatsapp" target="_blank" rel="noopener">
                    <svg viewBox="0 0 24 24" fill="currentColor" style="width:18px;height:18px;display:inline-block;vertical-align:middle;margin-right:6px;">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.123.554 4.118 1.528 5.855L0 24l6.335-1.652A11.954 11.954 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.891 0-3.658-.523-5.168-1.428l-.371-.22-3.762.981.999-3.661-.243-.377A9.939 9.939 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
                    </svg>
                    WhatsApp
                </a>
            <?php endif; ?>
            <?php if ( $phone ) : ?>
                <a href="tel:<?php echo esc_attr( $phone ); ?>" class="bd-btn-phone">
                    <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;margin-right:4px;">phone</span>
                    <?php echo esc_html( $phone ); ?>
                </a>
            <?php endif; ?>
            <?php if ( $email ) : ?>
                <a href="mailto:<?php echo esc_attr( $email ); ?>" class="bd-btn-email" style="display:flex;align-items:center;gap:6px;">
                    <span class="material-symbols-outlined" style="font-size:16px;">alternate_email</span>
                    <?php echo esc_html( $email ); ?>
                </a>
            <?php endif; ?>
            <?php if ( $website ) : ?>
                <a href="<?php echo esc_url( $website ); ?>" class="bd-btn-web" target="_blank" rel="noopener" style="display:flex;align-items:center;gap:6px;">
                    <span class="material-symbols-outlined" style="font-size:16px;">language</span>
                    Visitar sitio web
                </a>
            <?php endif; ?>
            <?php if ( $instagram ) : ?>
                <a href="https://instagram.com/<?php echo esc_attr( ltrim( $instagram, '@' ) ); ?>" class="bd-btn-instagram" target="_blank" rel="noopener" style="display:flex;align-items:center;gap:6px;">
                    <span class="material-symbols-outlined" style="font-size:16px;">photo_camera</span>
                    @<?php echo esc_html( ltrim( $instagram, '@' ) ); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Horarios de Atención ── -->
    <?php if ( ! empty( $horarios ) ) : ?>
    <div class="bd-sidebar-card">
        <h3 class="bd-sidebar-title">Horarios de Atención</h3>
        <div style="display:flex;flex-direction:column;gap:6px;">
            <?php foreach ( $days_labels as $key => $label ) :
                $day_data = isset( $horarios[ $key ] ) ? $horarios[ $key ] : null;
                if ( ! $day_data ) continue;
                $cerrado  = ! empty( $day_data['cerrado'] );
                $abre     = $day_data['abre'] ?? '';
                $cierra   = $day_data['cierra'] ?? '';
                // Determinar si es hoy para resaltar
                $today_map = array( 'lun' => 1, 'mar' => 2, 'mie' => 3, 'jue' => 4, 'vie' => 5, 'sab' => 6, 'dom' => 0 );
                $is_today  = isset( $today_map[ $key ] ) && intval( date( 'w' ) ) === $today_map[ $key ];
            ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:4px 0;<?php echo $is_today ? 'font-weight:700;color:#00205b;' : 'color:#64748b;'; ?>font-size:14px;">
                <span><?php echo esc_html( $label ); ?><?php if ( $is_today ) echo ' <span style="font-size:10px;background:#00205b;color:#fff;padding:1px 5px;border-radius:10px;vertical-align:middle;margin-left:4px;">HOY</span>'; ?></span>
                <span><?php echo $cerrado ? '<span style="color:#ef4444;">Cerrado</span>' : esc_html( $abre . ' – ' . $cierra ); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>
