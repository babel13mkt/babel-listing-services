<?php
/**
 * Template: Formulario Frontend de Alta de Negocio PREMIUM
 * Hito 23 — Sovereign Design System (Inter, glassmorphism).
 * Variables: $categorias, $regiones (inyectadas desde BD_Submission::render_form)
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="bd-form-wrapper" id="bd-submission-wrapper">

    <!-- Barra de progreso Premium -->
    <div class="bd-progress-bar" role="progressbar" aria-valuenow="1" aria-valuemin="1" aria-valuemax="<?php echo $is_admin ? '4' : '3'; ?>">
        <div class="bd-progress-steps">
            <div class="bd-progress-track-fill" id="bd-progress-fill"></div>
            
            <div class="bd-step-dot active" data-step="1">
                <span class="bd-step-num">1</span>
                <span class="bd-step-label">Básico</span>
            </div>
            <div class="bd-step-dot" data-step="2">
                <span class="bd-step-num">2</span>
                <span class="bd-step-label">Contacto</span>
            </div>
            <div class="bd-step-dot" data-step="3">
                <span class="bd-step-num">3</span>
                <span class="bd-step-label">Detalles</span>
            </div>
            <?php if ( $is_admin ) : ?>
            <div class="bd-step-dot" data-step="4">
                <span class="bd-step-num">4</span>
                <span class="bd-step-label">Soberanía</span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <form class="bd-submission-form" id="bd-submission-form" novalidate>
        <?php wp_nonce_field( 'bd_submission_nonce', 'nonce' ); ?>
        <?php if ( $is_admin ) : ?>
            <input type="hidden" name="bd_admin_mode" value="1">
        <?php endif; ?>

        <!-- Honeypot anti-spam -->
        <div class="bd-hp-field" aria-hidden="true" tabindex="-1" style="display:none;">
            <input type="text" name="bd_hp_field" value="" autocomplete="off" tabindex="-1">
        </div>

        <!-- ── PASO 1: Identidad Editorial ── -->
        <div class="bd-form-step active" data-step="1">
            <div class="bd-form-header">
                <h2 class="bd-form-title">Identidad del Negocio</h2>
                <p class="bd-form-sub">Definí la presencia visual de tu establecimiento. El logo y la portada son la primera impresión de tu historia.</p>
            </div>

            <!-- Carga de Imágenes de Identidad -->
            <div class="bd-upload-row">
                <div class="bd-field-group">
                    <label class="bd-label">Logo del Negocio</label>
                    <div class="bd-dropzone" id="dz-logo">
                        <input type="file" name="bd_logo" id="bd_logo" accept="image/*" hidden>
                        <div class="dz-ui" id="dz-ui-logo">
                            <span class="dz-icon">🖼️</span>
                            <span class="dz-text">Subir Logo</span>
                        </div>
                    </div>
                </div>
                <div class="bd-field-group">
                    <label class="bd-label">Foto de Portada (Principal)</label>
                    <div class="bd-dropzone" id="dz-cover">
                        <input type="file" name="bd_cover" id="bd_cover" accept="image/*" hidden>
                        <div class="dz-ui" id="dz-ui-cover">
                            <span class="dz-icon">📸</span>
                            <span class="dz-text">Subir Portada</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bd-field-group">
                <label class="bd-label" for="bd_nombre">Nombre del negocio</label>
                <div class="bd-input-wrap">
                    <span class="bd-input-icon">🏢</span>
                    <input type="text" id="bd_nombre" name="bd_nombre" class="bd-input"
                           placeholder="Ej: Café del Centro, Taller Mecánico..."
                           maxlength="150" required autocomplete="organization">
                </div>
                <span class="bd-field-error" id="err-bd_nombre"></span>
            </div>

            <div class="bd-field-group">
                <label class="bd-label" for="bd_descripcion">Historia y Descripción</label>
                <textarea id="bd_descripcion" name="bd_descripcion" class="bd-textarea"
                          placeholder="Contanos tu historia... Ej: Somos un café ubicado en Chile con más de 30 años en la zona, un lugar icónico..."
                          rows="5" maxlength="2000" required></textarea>
                <span class="bd-field-error" id="err-bd_descripcion"></span>
            </div>

            <div class="bd-field-row">
                <div class="bd-field-group">
                    <label class="bd-label" for="bd_categoria">Categoría</label>
                    <div class="bd-select-wrap">
                        <select id="bd_categoria" name="bd_categoria" class="bd-select" required>
                            <option value="">Seleccioná una categoría...</option>
                            <?php foreach ( $categorias as $cat ) :
                                $children = get_terms( array(
                                    'taxonomy'   => 'directorio_categoria',
                                    'parent'     => $cat->term_id,
                                    'hide_empty' => false,
                                ) );
                                if ( ! is_wp_error( $children ) && ! empty( $children ) ) : ?>
                                    <optgroup label="<?php echo esc_attr( $cat->name ); ?>">
                                        <?php foreach ( $children as $child ) : ?>
                                            <option value="<?php echo esc_attr( $child->term_id ); ?>">
                                                <?php echo esc_html( $child->name ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php else : ?>
                                    <option value="<?php echo esc_attr( $cat->term_id ); ?>">
                                        <?php echo esc_html( $cat->name ); ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <span class="bd-select-caret">▼</span>
                    </div>
                    <span class="bd-field-error" id="err-bd_categoria"></span>
                </div>

                <div class="bd-field-group">
                    <label class="bd-label" for="bd_region">Región / Comuna</label>
                    <div class="bd-select-wrap">
                        <select id="bd_region" name="bd_region" class="bd-select" required>
                            <option value="">Seleccioná una región...</option>
                            <?php foreach ( $regiones as $region ) :
                                $comunas = get_terms( array(
                                    'taxonomy'   => 'directorio_region',
                                    'parent'     => $region->term_id,
                                    'hide_empty' => false,
                                ) );
                                if ( ! is_wp_error( $comunas ) && ! empty( $comunas ) ) : ?>
                                    <optgroup label="<?php echo esc_attr( $region->name ); ?>">
                                        <?php foreach ( $comunas as $comuna ) : ?>
                                            <option value="<?php echo esc_attr( $comuna->term_id ); ?>">
                                                <?php echo esc_html( $comuna->name ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php else : ?>
                                    <option value="<?php echo esc_attr( $region->term_id ); ?>">
                                        <?php echo esc_html( $region->name ); ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <span class="bd-select-caret">▼</span>
                    </div>
                    <span class="bd-field-error" id="err-bd_region"></span>
                </div>
            </div>

            <div class="bd-form-actions">
                <div></div> <!-- Spacer -->
                <button type="button" class="bd-btn bd-btn-next" data-next="2">
                    Continuar <span>→</span>
                </button>
            </div>
        </div>

        <!-- ── PASO 2: Ubicación & Conexión ── -->
        <div class="bd-form-step" data-step="2">
            <div class="bd-form-header">
                <h2 class="bd-form-title">Conexión con el Cliente</h2>
                <p class="bd-form-sub">Facilitá el encuentro. Proporcioná los datos precisos para que la audiencia local llegue a tu puerta.</p>
            </div>

            <div class="bd-field-group">
                <label class="bd-label" for="bd_direccion">Dirección Física</label>
                <div class="bd-input-wrap">
                    <span class="bd-input-icon">📍</span>
                    <input type="text" id="bd_direccion" name="bd_direccion" class="bd-input"
                           placeholder="Ej: Av. Providencia 1234, Santiago" maxlength="255">
                </div>
            </div>

            <div class="bd-field-group">
                <label class="bd-label" for="bd_maps_embed">Mapa (Código Iframe)</label>
                <textarea id="bd_maps_embed" name="bd_maps_embed" class="bd-textarea"
                          placeholder="Pegá aquí el código <iframe> que obtenés de Google Maps (Compartir > Insertar un mapa)"
                          rows="3"></textarea>
            </div>

            <div class="bd-field-row">
                <div class="bd-field-group">
                    <label class="bd-label" for="bd_telefono">Teléfono</label>
                    <div class="bd-input-wrap">
                        <span class="bd-input-icon">📞</span>
                        <input type="tel" id="bd_telefono" name="bd_telefono" class="bd-input"
                               placeholder="+56 9 1234 5678">
                    </div>
                </div>
                <div class="bd-field-group">
                    <label class="bd-label" for="bd_whatsapp">WhatsApp</label>
                    <div class="bd-input-wrap">
                        <span class="bd-input-icon">💬</span>
                        <input type="tel" id="bd_whatsapp" name="bd_whatsapp" class="bd-input"
                               placeholder="+56 9 8765 4321">
                    </div>
                </div>
            </div>

            <div class="bd-field-row">
                <div class="bd-field-group">
                    <label class="bd-label" for="bd_email">Email</label>
                    <div class="bd-input-wrap">
                        <span class="bd-input-icon">✉️</span>
                        <input type="email" id="bd_email" name="bd_email" class="bd-input"
                               placeholder="contacto@empresa.cl">
                    </div>
                </div>
                <div class="bd-field-group">
                    <label class="bd-label" for="bd_sitio_web">Web</label>
                    <div class="bd-input-wrap">
                        <span class="bd-input-icon">🌐</span>
                        <input type="url" id="bd_sitio_web" name="bd_sitio_web" class="bd-input"
                               placeholder="https://www.tusitio.cl">
                    </div>
                </div>
            </div>

            <div class="bd-form-actions">
                <button type="button" class="bd-btn bd-btn-back" data-back="1">
                    <span>←</span> Atrás
                </button>
                <button type="button" class="bd-btn bd-btn-next" data-next="3">
                    Continuar <span>→</span>
                </button>
            </div>
        </div>

        <!-- ── PASO 3: Relato Visual & Atributos ── -->
        <div class="bd-form-step" data-step="3">
            <div class="bd-form-header">
                <h2 class="bd-form-title">Detalles & Curaduría</h2>
                <p class="bd-form-sub">Completá el relato de tu negocio con una galería extendida y definí los servicios que te hacen único.</p>
            </div>

            <div class="bd-field-group">
                <label class="bd-label">Galería de Fotos</label>
                <div class="bd-dropzone multi" id="dz-gallery">
                    <input type="file" name="bd_gallery[]" id="bd_gallery" accept="image/*" multiple hidden>
                    <div class="dz-ui" id="dz-ui-gallery">
                        <span class="dz-icon">📸</span>
                        <span class="dz-text">Seleccionar múltiples imágenes</span>
                    </div>
                </div>
                <div id="gallery-preview" class="bd-gallery-preview"></div>
            </div>

            <div class="bd-field-group">
                <label class="bd-label">Rango de precios</label>
                <div class="bd-price-selector">
                    <?php
                    $precios = array(
                        '$'    => 'Económico',
                        '$$'   => 'Moderado',
                        '$$$'  => 'Premium',
                        '$$$$' => 'Exclusivo',
                    );
                    foreach ( $precios as $val => $label ) : ?>
                        <label class="bd-price-option">
                            <input type="radio" name="bd_rango_precio" value="<?php echo esc_attr( $val ); ?>">
                            <div class="bd-price-badge">
                                <span class="bd-price-val"><?php echo esc_html( $val ); ?></span>
                                <span class="bd-price-label"><?php echo esc_html( $label ); ?></span>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="bd-field-group">
                <label class="bd-label">Características destacadas</label>
                <div class="bd-attrs-grid">
                    <?php
                    $atributos = array(
                        'wifi'            => array( '📶', 'WiFi' ),
                        'estacionamiento' => array( '🅿️', 'Parking' ),
                        'delivery'        => array( '🛵', 'Delivery' ),
                        'reservas'        => array( '📅', 'Reservas' ),
                        'accesibilidad'   => array( '♿', 'Acceso' ),
                    );
                    foreach ( $atributos as $key => $data ) : ?>
                        <label class="bd-attr-toggle">
                            <input type="checkbox" name="bd_<?php echo esc_attr( $key ); ?>" value="1">
                            <div class="bd-attr-card">
                                <span class="bd-attr-icon"><?php echo $data[0]; ?></span>
                                <span class="bd-attr-label"><?php echo esc_html( $data[1] ); ?></span>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="bd-form-actions">
                <button type="button" class="bd-btn bd-btn-back" data-back="2">
                    <span>←</span> Atrás
                </button>
                <?php if ( $is_admin ) : ?>
                    <button type="button" class="bd-btn bd-btn-next" data-next="4">
                        Control de Soberanía <span>→</span>
                    </button>
                <?php else : ?>
                    <button type="submit" class="bd-btn bd-btn-submit" id="bd-submit-btn">
                        Publicar Negocio
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <?php if ( $is_admin ) : ?>
        <!-- ── PASO 4: Control de Soberanía (SOLO ADMIN) ── -->
        <div class="bd-form-step" data-step="4">
            <div class="bd-form-header">
                <h2 class="bd-form-title">Control de Soberanía Editorial</h2>
                <p class="bd-form-sub">Aprobación, validación y creación de reseña premium (Google Style).</p>
            </div>

            <div class="bd-card bd-admin-review-card" style="background: rgba(255,255,255,0.8); border: 1px solid #e2e8f0; padding: 30px; border-radius: 12px; margin-bottom: 30px;">
                <label class="bd-label" style="display:block; margin-bottom:15px; font-weight:700;">Calificación de Bienvenida (Google Style)</label>
                
                <div class="bd-rating-selector" style="margin-bottom: 20px;">
                    <div class="bd-stars-input-admin" style="display:flex; flex-direction:row-reverse; justify-content:flex-end; gap:10px;">
                        <?php for ( $i = 5; $i >= 1; $i-- ) : ?>
                            <input type="radio" id="admin-star<?php echo $i; ?>" name="bd_admin_review_stars" value="<?php echo $i; ?>" hidden>
                            <label for="admin-star<?php echo $i; ?>" class="bd-star-label" style="font-size:32px; cursor:pointer; color:#cbd5e1;">★</label>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="bd-field-group">
                    <label class="bd-label" for="bd_admin_review_text">Reseña del Negocio</label>
                    <textarea id="bd_admin_review_text" name="bd_admin_review_text" class="bd-textarea"
                              placeholder="Escribí una reseña premium para darle un empujón al negocio (estética Google)..."
                              rows="4"></textarea>
                </div>
            </div>

            <div class="bd-field-group" style="margin-bottom: 30px;">
                <label class="bd-attr-toggle" style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                    <input type="checkbox" name="bd_publicar_inmediato" value="1" checked style="width: 20px; height: 20px;">
                    <span style="font-weight: 700; color: var(--bda-text);">Aprobar y Publicar inmediatamente</span>
                </label>
                <p style="font-size: 13px; color: var(--bda-text-soft); margin-left: 32px;">El negocio será visible en el mapa y listados al instante.</p>
            </div>

            <div class="bd-form-actions">
                <button type="button" class="bd-btn bd-btn-back" data-back="3">
                    <span>←</span> Atrás
                </button>
                <button type="submit" class="bd-btn bd-btn-submit" id="bd-submit-btn" style="background: var(--bda-accent); border-color: var(--bda-accent);">
                    Finalizar y Validar 🚀
                </button>
            </div>
        </div>
        <style>
            .bd-stars-input-admin input:checked ~ label,
            .bd-stars-input-admin label:hover,
            .bd-stars-input-admin label:hover ~ label { color: #f59e0b !important; }
        </style>
        <?php endif; ?>
    </form>

    <!-- Estado de éxito (Página de Confirmación Editorial) -->
    <div id="bd-form-success" class="bd-form-success" style="display:none;">
        <div class="bd-success-icon">🪶</div>
        <h2 class="bd-success-title">Relato Recibido</h2>
        <p class="bd-form-sub">Tu negocio ha sido ingresado a nuestro sistema de curaduría. Una vez validada la información, será publicado en el directorio.</p>
        <div style="margin-top: 48px;">
            <button onclick="window.location.reload()" class="bd-btn bd-btn-next">Inscribir otro negocio</button>
        </div>
    </div>
</div>
