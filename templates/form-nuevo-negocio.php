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
    <div class="bd-progress-bar" role="progressbar" aria-valuenow="1" aria-valuemin="1" aria-valuemax="3">
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
        </div>
    </div>

    <form class="bd-submission-form" id="bd-submission-form" novalidate>
        <?php wp_nonce_field( 'bd_submission_nonce', 'nonce' ); ?>

        <!-- Honeypot anti-spam -->
        <div class="bd-hp-field" aria-hidden="true" tabindex="-1" style="display:none;">
            <input type="text" name="bd_hp_field" value="" autocomplete="off" tabindex="-1">
        </div>

        <!-- ── PASO 1: Datos Básicos ── -->
        <div class="bd-form-step active" data-step="1">
            <div class="bd-form-header">
                <h2 class="bd-form-title">¿Cuál es tu negocio?</h2>
                <p class="bd-form-sub">Empecemos con lo esencial. Te tomará menos de 2 minutos.</p>
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
                <label class="bd-label" for="bd_descripcion">Descripción</label>
                <textarea id="bd_descripcion" name="bd_descripcion" class="bd-textarea"
                          placeholder="Contá brevemente qué ofrece tu negocio y qué te hace especial..."
                          rows="4" maxlength="1000" required></textarea>
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

        <!-- ── PASO 2: Contacto & Ubicación ── -->
        <div class="bd-form-step" data-step="2">
            <div class="bd-form-header">
                <h2 class="bd-form-title">¿Cómo te encuentran?</h2>
                <p class="bd-form-sub">Datos de contacto y ubicación para que tus clientes lleguen a vos.</p>
            </div>

            <div class="bd-field-group">
                <label class="bd-label" for="bd_direccion">Dirección Física</label>
                <div class="bd-input-wrap">
                    <span class="bd-input-icon">📍</span>
                    <input type="text" id="bd_direccion" name="bd_direccion" class="bd-input"
                           placeholder="Ej: Av. Providencia 1234, Santiago" maxlength="255">
                </div>
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

        <!-- ── PASO 3: Atributos & Envío ── -->
        <div class="bd-form-step" data-step="3">
            <div class="bd-form-header">
                <h2 class="bd-form-title">Últimos detalles</h2>
                <p class="bd-form-sub">Agregá valor a tu ficha para destacar sobre la competencia.</p>
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
                <button type="submit" class="bd-btn bd-btn-submit" id="bd-submit-btn">
                    Publicar Negocio
                </button>
            </div>
        </div>
    </form>

    <!-- Estado de éxito (inicialmente oculto) -->
    <div id="bd-form-success" class="bd-form-success" style="display:none;">
        <div class="bd-success-icon">✨</div>
        <h2 class="bd-success-title">¡Negocio Enviado!</h2>
        <p class="bd-form-sub">Tu solicitud está en revisión. Te avisaremos pronto.</p>
        <div style="margin-top: 30px;">
            <button onclick="window.location.reload()" class="bd-btn bd-btn-next">Cargar otro</button>
        </div>
    </div>
</div>
