<?php
/**
 * Template: Formulario Frontend de Alta de Negocio
 * Hito 13 — Sin dependencias de wp-admin ni Divi.
 * Variables disponibles: $categorias, $regiones (inyectadas desde BD_Submission::render_form)
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="bd-form-wrapper" id="bd-submission-wrapper">

    <!-- Barra de progreso -->
    <div class="bd-progress-bar" role="progressbar" aria-valuenow="1" aria-valuemin="1" aria-valuemax="3">
        <div class="bd-progress-track">
            <div class="bd-progress-fill" id="bd-progress-fill"></div>
        </div>
        <div class="bd-progress-steps">
            <div class="bd-step-dot active" data-step="1">
                <span class="bd-step-num">1</span>
                <span class="bd-step-label">Lo básico</span>
            </div>
            <div class="bd-step-dot" data-step="2">
                <span class="bd-step-num">2</span>
                <span class="bd-step-label">Contacto</span>
            </div>
            <div class="bd-step-dot" data-step="3">
                <span class="bd-step-num">3</span>
                <span class="bd-step-label">Detalles</span>
            </div>
        </div>
    </div>

    <form class="bd-submission-form" id="bd-submission-form" novalidate>
        <?php wp_nonce_field( 'bd_submission_nonce', 'nonce' ); ?>

        <!-- Honeypot anti-spam -->
        <div class="bd-hp-field" aria-hidden="true" tabindex="-1">
            <input type="text" name="bd_hp_field" value="" autocomplete="off" tabindex="-1">
        </div>

        <!-- ── PASO 1: Datos Básicos ── -->
        <div class="bd-form-step active" data-step="1">
            <div class="bd-form-header">
                <h2 class="bd-form-title">¿Cuál es tu negocio?</h2>
                <p class="bd-form-sub">Empecemos con lo esencial. Solo te llevará 2 minutos.</p>
            </div>

            <div class="bd-field-group">
                <label class="bd-label" for="bd_nombre">
                    Nombre del negocio <span class="bd-req" aria-hidden="true">*</span>
                </label>
                <div class="bd-input-wrap">
                    <span class="bd-input-icon" aria-hidden="true">🏪</span>
                    <input type="text" id="bd_nombre" name="bd_nombre" class="bd-input"
                           placeholder="Ej: Café del Centro, Taller Mecánico Los Andes..."
                           maxlength="150" required autocomplete="organization">
                </div>
                <span class="bd-field-error" id="err-bd_nombre" role="alert"></span>
            </div>

            <div class="bd-field-group">
                <label class="bd-label" for="bd_descripcion">
                    Descripción <span class="bd-req" aria-hidden="true">*</span>
                </label>
                <textarea id="bd_descripcion" name="bd_descripcion" class="bd-textarea"
                          placeholder="Contá brevemente qué ofrece tu negocio y qué te hace especial..."
                          rows="4" maxlength="1000" required></textarea>
                <div class="bd-textarea-meta">
                    <span class="bd-field-error" id="err-bd_descripcion" role="alert"></span>
                    <span class="bd-char-count"><span id="bd-desc-count">0</span>/1000</span>
                </div>
            </div>

            <div class="bd-field-row">
                <div class="bd-field-group">
                    <label class="bd-label" for="bd_categoria">
                        Categoría <span class="bd-req" aria-hidden="true">*</span>
                    </label>
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
                        <span class="bd-select-caret" aria-hidden="true">▾</span>
                    </div>
                    <span class="bd-field-error" id="err-bd_categoria" role="alert"></span>
                </div>

                <div class="bd-field-group">
                    <label class="bd-label" for="bd_region">
                        Región / Comuna <span class="bd-req" aria-hidden="true">*</span>
                    </label>
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
                        <span class="bd-select-caret" aria-hidden="true">▾</span>
                    </div>
                    <span class="bd-field-error" id="err-bd_region" role="alert"></span>
                </div>
            </div>

            <div class="bd-form-actions">
                <button type="button" class="bd-btn-next" data-next="2">
                    Continuar <span class="bd-btn-arrow">→</span>
                </button>
            </div>
        </div>

        <!-- ── PASO 2: Contacto & Ubicación ── -->
        <div class="bd-form-step" data-step="2">
            <div class="bd-form-header">
                <h2 class="bd-form-title">¿Cómo te encuentran?</h2>
                <p class="bd-form-sub">Datos de contacto y ubicación. Todo es opcional en este paso.</p>
            </div>

            <div class="bd-field-group">
                <label class="bd-label" for="bd_direccion">Dirección</label>
                <div class="bd-input-wrap">
                    <span class="bd-input-icon" aria-hidden="true">📍</span>
                    <input type="text" id="bd_direccion" name="bd_direccion" class="bd-input"
                           placeholder="Ej: Av. Providencia 1234, Santiago" maxlength="255"
                           autocomplete="street-address">
                </div>
            </div>

            <div class="bd-field-row">
                <div class="bd-field-group">
                    <label class="bd-label" for="bd_telefono">Teléfono</label>
                    <div class="bd-input-wrap">
                        <span class="bd-input-icon" aria-hidden="true">📞</span>
                        <input type="tel" id="bd_telefono" name="bd_telefono" class="bd-input"
                               placeholder="+56 9 1234 5678" maxlength="30" autocomplete="tel">
                    </div>
                </div>
                <div class="bd-field-group">
                    <label class="bd-label" for="bd_whatsapp">WhatsApp</label>
                    <div class="bd-input-wrap">
                        <span class="bd-input-icon" aria-hidden="true">💬</span>
                        <input type="tel" id="bd_whatsapp" name="bd_whatsapp" class="bd-input"
                               placeholder="+56 9 1234 5678" maxlength="30">
                    </div>
                </div>
            </div>

            <div class="bd-field-row">
                <div class="bd-field-group">
                    <label class="bd-label" for="bd_email">Email de contacto</label>
                    <div class="bd-input-wrap">
                        <span class="bd-input-icon" aria-hidden="true">✉️</span>
                        <input type="email" id="bd_email" name="bd_email" class="bd-input"
                               placeholder="contacto@tunegocio.cl" maxlength="100" autocomplete="email">
                    </div>
                </div>
                <div class="bd-field-group">
                    <label class="bd-label" for="bd_sitio_web">Sitio Web</label>
                    <div class="bd-input-wrap">
                        <span class="bd-input-icon" aria-hidden="true">🌐</span>
                        <input type="url" id="bd_sitio_web" name="bd_sitio_web" class="bd-input"
                               placeholder="https://www.tunegocio.cl" maxlength="255" autocomplete="url">
                    </div>
                </div>
            </div>

            <div class="bd-field-group">
                <label class="bd-label" for="bd_horario">Horario de atención</label>
                <div class="bd-input-wrap">
                    <span class="bd-input-icon" aria-hidden="true">🕐</span>
                    <input type="text" id="bd_horario" name="bd_horario" class="bd-input"
                           placeholder="Ej: Lun-Vie 09:00–18:00, Sáb 10:00–14:00" maxlength="255">
                </div>
            </div>

            <div class="bd-field-row bd-coords-row">
                <div class="bd-field-group">
                    <label class="bd-label" for="bd_latitud">
                        Latitud <span class="bd-label-hint">para aparecer en el mapa (opcional)</span>
                    </label>
                    <input type="number" id="bd_latitud" name="bd_latitud" class="bd-input bd-input-coord"
                           placeholder="-33.4489" step="any" min="-90" max="90">
                </div>
                <div class="bd-field-group">
                    <label class="bd-label" for="bd_longitud">
                        Longitud <span class="bd-label-hint">opcional</span>
                    </label>
                    <input type="number" id="bd_longitud" name="bd_longitud" class="bd-input bd-input-coord"
                           placeholder="-70.6693" step="any" min="-180" max="180">
                </div>
            </div>

            <div class="bd-form-actions">
                <button type="button" class="bd-btn-back" data-back="1">
                    <span class="bd-btn-arrow">←</span> Atrás
                </button>
                <button type="button" class="bd-btn-next" data-next="3">
                    Continuar <span class="bd-btn-arrow">→</span>
                </button>
            </div>
        </div>

        <!-- ── PASO 3: Atributos & Envío ── -->
        <div class="bd-form-step" data-step="3">
            <div class="bd-form-header">
                <h2 class="bd-form-title">Últimos detalles</h2>
                <p class="bd-form-sub">Esto ayuda a los usuarios a encontrarte usando los filtros.</p>
            </div>

            <div class="bd-field-group">
                <label class="bd-label">Rango de precio</label>
                <div class="bd-price-selector" role="group" aria-label="Rango de precio">
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
                            <span class="bd-price-badge"><?php echo esc_html( $val ); ?></span>
                            <span class="bd-price-label"><?php echo esc_html( $label ); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="bd-field-group">
                <label class="bd-label">Características del negocio</label>
                <div class="bd-attrs-grid" role="group" aria-label="Características">
                    <?php
                    $atributos = array(
                        'wifi'            => array( '📶', 'WiFi' ),
                        'estacionamiento' => array( '🅿️', 'Estacionamiento' ),
                        'delivery'        => array( '🛵', 'Delivery' ),
                        'reservas'        => array( '📅', 'Reservas' ),
                        'accesibilidad'   => array( '♿', 'Accesibilidad' ),
                    );
                    foreach ( $atributos as $key => $data ) : ?>
                        <label class="bd-attr-toggle">
                            <input type="checkbox" name="bd_<?php echo esc_attr( $key ); ?>" value="1">
                            <span class="bd-attr-icon" aria-hidden="true"><?php echo $data[0]; ?></span>
                            <span class="bd-attr-label"><?php echo esc_html( $data[1] ); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="bd-terms-notice">
                <p>Al enviar confirmás que tenés autorización para publicar este negocio
                y que será revisado por nuestro equipo antes de aparecer en el directorio.</p>
            </div>

            <div class="bd-form-actions">
                <button type="button" class="bd-btn-back" data-back="2">
                    <span class="bd-btn-arrow">←</span> Atrás
                </button>
                <button type="submit" class="bd-btn-submit" id="bd-submit-btn">
                    <span class="bd-btn-text">Publicar mi negocio</span>
                    <span class="bd-btn-spinner" hidden aria-hidden="true"></span>
                </button>
            </div>
        </div>

    </form>

    <!-- Estado de éxito -->
    <div class="bd-form-success" id="bd-form-success" hidden aria-live="polite">
        <div class="bd-success-icon" aria-hidden="true">✅</div>
        <h2 class="bd-success-title">¡Negocio enviado!</h2>
        <p class="bd-success-msg" id="bd-success-msg"></p>
        <p class="bd-success-sub">Lo revisaremos y publicaremos en el directorio muy pronto.</p>
    </div>

</div><!-- /.bd-form-wrapper -->
