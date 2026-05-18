<?php
/**
 * Clase para el manejo de Metaboxes y Custom Fields unificados con estética SaaS moderna.
 * v7.0.0 — Hito 11: Panel Unificado de Carga Rápida sin Sidebar y soporte de Medios/Galerías.
 *
 * @package Babel_Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}

class Babel_Directory_Metaboxes {

    /**
     * Constructor de la clase de metaboxes.
     * Enlaza los hooks de WordPress para renderizado, guardado y assets del administrador.
     */
    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_business_meta_box' ) );
        add_action( 'save_post_babel_business', array( $this, 'save_business_meta' ), 10, 2 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    /**
     * Registra la metabox nativa para el post type 'babel_business'.
     */
    public function add_business_meta_box() {
        add_meta_box(
            'babel_business_details',
            __( 'Panel Central de Control de Negocio', 'babel-directory' ),
            array( $this, 'render_business_meta_box' ),
            'babel_business',
            'normal',
            'high'
        );
    }

    /**
     * Encola los scripts nativos de medios y ordenación de WordPress en la pantalla del CPT.
     *
     * @param string $hook Identificador de la página actual del panel de administración.
     */
    public function enqueue_admin_assets( $hook ) {
        $screen = get_current_screen();
        if ( $screen && 'babel_business' === $screen->post_type ) {
            wp_enqueue_media();
            wp_enqueue_script( 'jquery-ui-sortable' );
        }
    }

    /**
     * Renderiza los campos de la metabox en el backend de WordPress con CSS Grid y estética SaaS.
     *
     * @param WP_Post $post El objeto del post actual.
     */
    public function render_business_meta_box( $post ) {
        // Generar token de seguridad (Nonce)
        wp_nonce_field( 'babel_business_meta_box_nonce_action', 'babel_business_meta_box_nonce' );

        // Recuperar valores guardados actualmente con fallback seguro a llaves anteriores
        $phone       = get_post_meta( $post->ID, '_babel_phone', true );
        $whatsapp    = get_post_meta( $post->ID, '_babel_whatsapp', true );
        $email       = get_post_meta( $post->ID, '_babel_email', true );
        $address     = get_post_meta( $post->ID, '_babel_address', true );
        
        $maps        = get_post_meta( $post->ID, '_babel_maps', true );
        if ( empty( $maps ) ) {
            $maps    = get_post_meta( $post->ID, '_babel_gmaps', true );
        }
        
        $lat         = get_post_meta( $post->ID, '_babel_lat', true );
        if ( empty( $lat ) ) {
            $lat     = get_post_meta( $post->ID, '_babel_latitude', true );
        }
        
        $lng         = get_post_meta( $post->ID, '_babel_lng', true );
        if ( empty( $lng ) ) {
            $lng     = get_post_meta( $post->ID, '_babel_longitude', true );
        }
        
        $website     = get_post_meta( $post->ID, '_babel_website', true );
        $instagram   = get_post_meta( $post->ID, '_babel_instagram', true );
        $facebook    = get_post_meta( $post->ID, '_babel_facebook', true );
        $linkedin    = get_post_meta( $post->ID, '_babel_linkedin', true );
        
        $verified    = get_post_meta( $post->ID, '_babel_verified', true );
        if ( $verified === '' ) {
            $verified = get_post_meta( $post->ID, '_babel_is_verified', true );
        }
        
        $featured    = get_post_meta( $post->ID, '_babel_featured', true );
        if ( $featured === '' ) {
            $featured = get_post_meta( $post->ID, '_babel_is_featured', true );
        }

        $gallery     = get_post_meta( $post->ID, '_babel_gallery', true );
        $hours_meta  = get_post_meta( $post->ID, '_babel_hours', true );

        // Configurar los días de la semana y los horarios decodificados
        $days_of_week = array( 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo' );
        $hours = array();
        if ( ! empty( $hours_meta ) ) {
            $hours = json_decode( $hours_meta, true );
        }
        if ( ! is_array( $hours ) ) {
            $hours = array();
        }

        // Obtener la categoría del negocio asignada actualmente
        $current_categories = wp_get_post_terms( $post->ID, 'babel_category', array( 'fields' => 'ids' ) );
        $selected_cat_id    = ! empty( $current_categories ) && ! is_wp_error( $current_categories ) ? intval( $current_categories[0] ) : 0;

        // Obtener datos del Logotipo (Imagen destacada)
        $logo_id  = get_post_thumbnail_id( $post->ID );
        $logo_url = '';
        if ( $logo_id ) {
            $logo_url = wp_get_attachment_image_url( $logo_id, 'thumbnail' );
        }

        // Parsear IDs de galería
        $gallery_ids = array();
        if ( ! empty( $gallery ) ) {
            $gallery_ids = explode( ',', $gallery );
            $gallery_ids = array_filter( array_map( 'intval', $gallery_ids ) );
        }
        ?>
        <style>
            .babel-metabox-wrapper {
                background: #ffffff;
                border-radius: 8px;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                color: #334155;
                padding: 10px;
                box-sizing: border-box;
            }
            .babel-grid-container {
                display: grid;
                grid-template-columns: repeat(12, 1fr);
                gap: 16px;
                align-items: start;
            }
            .babel-grid-span-12 { grid-column: span 12; }
            .babel-grid-span-8 { grid-column: span 8; }
            .babel-grid-span-6 { grid-column: span 6; }
            .babel-grid-span-4 { grid-column: span 4; }
            .babel-grid-span-3 { grid-column: span 3; }

            .babel-field-group {
                display: flex;
                flex-direction: column;
                gap: 6px;
            }
            .babel-field-group label {
                font-weight: 600;
                font-size: 13px;
                color: #1e293b;
            }
            .babel-field-group input[type="text"],
            .babel-field-group input[type="email"],
            .babel-field-group input[type="url"],
            .babel-field-group input[type="time"],
            .babel-field-group select {
                width: 100%;
                border: 1px solid #cbd5e1;
                border-radius: 6px;
                padding: 8px 12px;
                font-size: 13px;
                color: #334155;
                background-color: #f8fafc;
                box-sizing: border-box;
                transition: all 0.2s ease;
            }
            .babel-field-group input:focus,
            .babel-field-group select:focus {
                background-color: #ffffff;
                border-color: #3b82f6;
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
                outline: none;
            }
            .babel-field-desc {
                margin: 2px 0 0;
                font-size: 11px;
                color: #64748b;
            }

            /* Media Selector Box */
            .babel-media-upload-container {
                display: flex;
                gap: 16px;
                align-items: center;
                border: 1px dashed #cbd5e1;
                padding: 10px;
                border-radius: 6px;
                background: #f8fafc;
                min-height: 84px;
                box-sizing: border-box;
            }
            .babel-media-preview-box {
                width: 64px;
                height: 64px;
                border-radius: 6px;
                border: 1px solid #e2e8f0;
                background: #ffffff;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
                position: relative;
                flex-shrink: 0;
            }
            .babel-media-preview-box img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            .babel-media-placeholder-icon {
                font-size: 24px;
                color: #94a3b8;
            }
            .babel-media-actions {
                display: flex;
                flex-direction: row;
                gap: 8px;
            }
            .babel-btn-primary {
                background: #3b82f6 !important;
                color: #ffffff !important;
                border: none !important;
                border-radius: 6px !important;
                padding: 6px 12px !important;
                font-weight: 500 !important;
                font-size: 12px !important;
                cursor: pointer !important;
                transition: background 0.2s !important;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
            }
            .babel-btn-primary:hover {
                background: #2563eb !important;
            }
            .babel-btn-danger {
                background: #ef4444 !important;
                color: #ffffff !important;
                border: none !important;
                border-radius: 6px !important;
                padding: 6px 12px !important;
                font-weight: 500 !important;
                font-size: 12px !important;
                cursor: pointer !important;
                transition: background 0.2s !important;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
            }
            .babel-btn-danger:hover {
                background: #dc2626 !important;
            }

            /* States Section */
            .babel-states-container {
                display: flex;
                gap: 24px;
                background: #f8fafc;
                border: 1px solid #cbd5e1;
                padding: 12px 16px;
                border-radius: 6px;
                align-items: center;
            }
            .babel-state-checkbox {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                cursor: pointer;
                font-weight: 600;
                font-size: 13px;
                color: #1e293b;
            }
            .babel-state-checkbox input[type="checkbox"] {
                width: 16px;
                height: 16px;
                border-radius: 4px;
                border: 1px solid #cbd5e1;
                cursor: pointer;
                margin: 0;
            }

            /* Horas Section */
            .babel-hours-container {
                background: #f8fafc;
                border: 1px solid #cbd5e1;
                border-radius: 6px;
                padding: 12px;
                display: flex;
                flex-direction: column;
                gap: 8px;
            }
            .babel-hours-row {
                display: flex;
                align-items: center;
                gap: 12px;
                font-size: 13px;
                border-bottom: 1px solid #e2e8f0;
                padding-bottom: 6px;
            }
            .babel-hours-row:last-child {
                border-bottom: none;
                padding-bottom: 0;
            }
            .babel-hours-day {
                width: 80px;
                font-weight: 600;
                color: #475569;
            }
            .babel-hours-inputs {
                display: flex;
                align-items: center;
                gap: 6px;
            }
            .babel-hours-closed-label {
                display: flex;
                align-items: center;
                gap: 4px;
                cursor: pointer;
                margin-left: auto;
                font-weight: 500;
                color: #64748b;
            }
            .babel-hours-closed-label input[type="checkbox"] {
                margin: 0;
            }

            /* Gallery Section */
            .babel-gallery-container {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }
            .babel-gallery-grid {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                min-height: 84px;
                border: 1px dashed #cbd5e1;
                border-radius: 6px;
                padding: 10px;
                background: #f8fafc;
                box-sizing: border-box;
            }
            .babel-gallery-item {
                width: 70px;
                height: 70px;
                border-radius: 6px;
                border: 1px solid #cbd5e1;
                background: #ffffff;
                position: relative;
                cursor: grab;
                overflow: hidden;
                display: flex;
                align-items: center;
                justify-content: center;
                box-sizing: border-box;
            }
            .babel-gallery-item img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            .babel-gallery-item .babel-remove-gallery-item {
                position: absolute;
                top: 2px;
                right: 2px;
                background: rgba(239, 68, 68, 0.9);
                color: white;
                border: none;
                border-radius: 50%;
                width: 16px;
                height: 16px;
                font-size: 10px;
                cursor: pointer;
                line-height: 1;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 0;
            }
        </style>

        <div class="babel-metabox-wrapper">
            <div class="babel-grid-container">

                <!-- FILA 1 (Categoría y Medios Base): Categoría (span 4), Logotipo (span 8) -->
                <div class="babel-field-group babel-grid-span-4">
                    <label for="babel_category_id"><?php esc_html_e( 'Categoría Principal', 'babel-directory' ); ?></label>
                    <select id="babel_category_id" name="babel_category_id">
                        <option value="0"><?php esc_html_e( '-- Selecciona una Categoría --', 'babel-directory' ); ?></option>
                        <?php $this->render_hierarchical_category_options( 'babel_category', 0, 0, $selected_cat_id ); ?>
                    </select>
                    <p class="babel-field-desc"><?php esc_html_e( 'Rubro o clasificación del negocio.', 'babel-directory' ); ?></p>
                </div>

                <div class="babel-field-group babel-grid-span-8">
                    <label><?php esc_html_e( 'Imagen Principal / Logotipo', 'babel-directory' ); ?></label>
                    <div class="babel-media-upload-container">
                        <div class="babel-media-preview-box" id="babel-logo-preview">
                            <?php if ( $logo_url ) : ?>
                                <img src="<?php echo esc_url( $logo_url ); ?>" alt="Preview" />
                            <?php else : ?>
                                <span class="babel-media-placeholder-icon">🏢</span>
                            <?php endif; ?>
                        </div>
                        <div class="babel-media-actions">
                            <input type="hidden" id="babel_logo_id" name="babel_logo_id" value="<?php echo esc_attr( $logo_id ); ?>" />
                            <button type="button" class="button babel-btn-primary" id="babel-select-logo-btn">
                                <?php esc_html_e( 'Seleccionar Imagen', 'babel-directory' ); ?>
                            </button>
                            <button type="button" class="button babel-btn-danger" id="babel-remove-logo-btn" style="<?php echo $logo_url ? '' : 'display: none;'; ?>">
                                <?php esc_html_e( 'Eliminar', 'babel-directory' ); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- FILA 2 (Contacto): Teléfono (span 4), WhatsApp (span 4), Email (span 4) -->
                <div class="babel-field-group babel-grid-span-4">
                    <label for="babel_phone"><?php esc_html_e( 'Teléfono de Contacto', 'babel-directory' ); ?></label>
                    <input type="text" id="babel_phone" name="babel_phone" value="<?php echo esc_attr( $phone ); ?>" placeholder="Ej: +56 9 1234 5678" />
                    <p class="babel-field-desc"><?php esc_html_e( 'Teléfono comercial directo.', 'babel-directory' ); ?></p>
                </div>

                <div class="babel-field-group babel-grid-span-4">
                    <label for="babel_whatsapp"><?php esc_html_e( 'WhatsApp', 'babel-directory' ); ?></label>
                    <input type="text" id="babel_whatsapp" name="babel_whatsapp" value="<?php echo esc_attr( $whatsapp ); ?>" placeholder="Ej: +56987654321" />
                    <p class="babel-field-desc"><?php esc_html_e( 'Número directo para chat comercial (sin espacios).', 'babel-directory' ); ?></p>
                </div>

                <div class="babel-field-group babel-grid-span-4">
                    <label for="babel_email"><?php esc_html_e( 'Email Comercial', 'babel-directory' ); ?></label>
                    <input type="email" id="babel_email" name="babel_email" value="<?php echo esc_attr( $email ); ?>" placeholder="Ej: contacto@empresa.cl" />
                    <p class="babel-field-desc"><?php esc_html_e( 'Correo para solicitudes de clientes.', 'babel-directory' ); ?></p>
                </div>

                <!-- FILA 3 (Geolocalización): Dirección (span 6), Maps Link (span 6) -->
                <div class="babel-field-group babel-grid-span-6">
                    <label for="babel_address"><?php esc_html_e( 'Dirección Física', 'babel-directory' ); ?></label>
                    <input type="text" id="babel_address" name="babel_address" value="<?php echo esc_attr( $address ); ?>" placeholder="Ej: Av. Providencia 1234, Oficina 501" />
                    <p class="babel-field-desc"><?php esc_html_e( 'Ubicación completa del local/oficina.', 'babel-directory' ); ?></p>
                </div>

                <div class="babel-field-group babel-grid-span-6">
                    <label for="babel_maps"><?php esc_html_e( 'Enlace de Google Maps', 'babel-directory' ); ?></label>
                    <input type="url" id="babel_maps" name="babel_maps" value="<?php echo esc_url( $maps ); ?>" placeholder="Ej: https://maps.app.goo.gl/..." />
                    <p class="babel-field-desc"><?php esc_html_e( 'Enlace directo para abrir la ubicación en Google Maps.', 'babel-directory' ); ?></p>
                </div>

                <!-- FILA 4 (Coordenadas y Web): Latitud (span 3), Longitud (span 3), Sitio Web (span 6) -->
                <div class="babel-field-group babel-grid-span-3">
                    <label for="babel_lat"><?php esc_html_e( 'Latitud GPS', 'babel-directory' ); ?></label>
                    <input type="text" id="babel_lat" name="babel_lat" value="<?php echo esc_attr( $lat ); ?>" placeholder="Ej: -33.4372" />
                    <p class="babel-field-desc"><?php esc_html_e( 'Latitud de geolocalización.', 'babel-directory' ); ?></p>
                </div>

                <div class="babel-field-group babel-grid-span-3">
                    <label for="babel_lng"><?php esc_html_e( 'Longitud GPS', 'babel-directory' ); ?></label>
                    <input type="text" id="babel_lng" name="babel_lng" value="<?php echo esc_attr( $lng ); ?>" placeholder="Ej: -70.6506" />
                    <p class="babel-field-desc"><?php esc_html_e( 'Longitud de geolocalización.', 'babel-directory' ); ?></p>
                </div>

                <div class="babel-field-group babel-grid-span-6">
                    <label for="babel_website"><?php esc_html_e( 'Sitio Web', 'babel-directory' ); ?></label>
                    <input type="url" id="babel_website" name="babel_website" value="<?php echo esc_url( $website ); ?>" placeholder="Ej: https://www.negocio.cl" />
                    <p class="babel-field-desc"><?php esc_html_e( 'URL oficial de la empresa.', 'babel-directory' ); ?></p>
                </div>

                <!-- FILA 5 (Redes Sociales): Instagram (span 4), Facebook (span 4), LinkedIn (span 4) -->
                <div class="babel-field-group babel-grid-span-4">
                    <label for="babel_instagram"><?php esc_html_e( 'Instagram', 'babel-directory' ); ?></label>
                    <input type="url" id="babel_instagram" name="babel_instagram" value="<?php echo esc_url( $instagram ); ?>" placeholder="Ej: https://instagram.com/perfil" />
                </div>

                <div class="babel-field-group babel-grid-span-4">
                    <label for="babel_facebook"><?php esc_html_e( 'Facebook', 'babel-directory' ); ?></label>
                    <input type="url" id="babel_facebook" name="babel_facebook" value="<?php echo esc_url( $facebook ); ?>" placeholder="Ej: https://facebook.com/pagina" />
                </div>

                <div class="babel-field-group babel-grid-span-4">
                    <label for="babel_linkedin"><?php esc_html_e( 'LinkedIn', 'babel-directory' ); ?></label>
                    <input type="url" id="babel_linkedin" name="babel_linkedin" value="<?php echo esc_url( $linkedin ); ?>" placeholder="Ej: https://linkedin.com/company/empresa" />
                </div>

                <!-- FILA 6 (Estados): Verified & Featured (span 12) -->
                <div class="babel-grid-span-12">
                    <div class="babel-states-container">
                        <label class="babel-state-checkbox">
                            <input type="checkbox" id="babel_verified" name="babel_verified" value="1" <?php checked( $verified, '1' ); ?> />
                            <span>✨ <?php esc_html_e( 'Negocio Verificado', 'babel-directory' ); ?></span>
                        </label>

                        <label class="babel-state-checkbox">
                            <input type="checkbox" id="babel_featured" name="babel_featured" value="1" <?php checked( $featured, '1' ); ?> />
                            <span>🔥 <?php esc_html_e( 'Destacar Negocio', 'babel-directory' ); ?></span>
                        </label>
                    </div>
                </div>

                <!-- FILA 7 (Módulos Complejos): Horarios (span 6), Galería (span 6) -->
                <div class="babel-field-group babel-grid-span-6">
                    <label><?php esc_html_e( 'Horarios de Atención', 'babel-directory' ); ?></label>
                    <div class="babel-hours-container">
                        <?php foreach ( $days_of_week as $day ) : 
                            $day_open   = isset( $hours[ $day ]['open'] ) ? $hours[ $day ]['open'] : '09:00';
                            $day_close  = isset( $hours[ $day ]['close'] ) ? $hours[ $day ]['close'] : '18:00';
                            $day_closed = isset( $hours[ $day ]['closed'] ) && $hours[ $day ]['closed'];
                            ?>
                            <div class="babel-hours-row">
                                <span class="babel-hours-day"><?php echo esc_html( $day ); ?></span>
                                <div class="babel-hours-inputs">
                                    <input type="time" name="babel_hours[<?php echo esc_attr( $day ); ?>][open]" value="<?php echo esc_attr( $day_open ); ?>" />
                                    <span><?php esc_html_e( 'a', 'babel-directory' ); ?></span>
                                    <input type="time" name="babel_hours[<?php echo esc_attr( $day ); ?>][close]" value="<?php echo esc_attr( $day_close ); ?>" />
                                </div>
                                <label class="babel-hours-closed-label">
                                    <input type="checkbox" class="babel-hours-closed-checkbox" name="babel_hours[<?php echo esc_attr( $day ); ?>][closed]" value="1" <?php checked( $day_closed, true ); ?> />
                                    <span><?php esc_html_e( 'Cerrado', 'babel-directory' ); ?></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="babel-field-group babel-grid-span-6 babel-gallery-container">
                    <label><?php esc_html_e( 'Galería de Fotos Múltiple', 'babel-directory' ); ?></label>
                    <input type="hidden" id="babel_gallery" name="babel_gallery" value="<?php echo esc_attr( $gallery ); ?>" />
                    <div class="babel-gallery-grid" id="babel-gallery-grid">
                        <?php foreach ( $gallery_ids as $img_id ) : 
                            $img_url = wp_get_attachment_image_url( $img_id, 'thumbnail' );
                            if ( ! $img_url ) {
                                continue;
                            }
                            ?>
                            <div class="babel-gallery-item" data-id="<?php echo esc_attr( $img_id ); ?>">
                                <img src="<?php echo esc_url( $img_url ); ?>" alt="Thumbnail" />
                                <button type="button" class="babel-remove-gallery-item">&times;</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="button babel-btn-primary" id="babel-add-gallery-btn" style="align-self: flex-start;">
                        📸 <?php esc_html_e( 'Añadir Fotos', 'babel-directory' ); ?>
                    </button>
                </div>

            </div>
        </div>

        <script>
            jQuery(document).ready(function($) {
                // --- MANEJO DE LOGOTIPO / IMAGEN DESTACADA ---
                var logoFrame;
                $('#babel-select-logo-btn').on('click', function(e) {
                    e.preventDefault();
                    if (logoFrame) {
                        logoFrame.open();
                        return;
                    }
                    logoFrame = wp.media({
                        title: 'Seleccionar Logotipo o Imagen Principal',
                        button: {
                            text: 'Usar como Imagen Principal'
                        },
                        multiple: false
                    });
                    logoFrame.on('select', function() {
                        var attachment = logoFrame.state().get('selection').first().toJSON();
                        $('#babel_logo_id').val(attachment.id);
                        var imageUrl = (attachment.sizes && attachment.sizes.thumbnail) ? attachment.sizes.thumbnail.url : attachment.url;
                        $('#babel-logo-preview').html('<img src="' + imageUrl + '" />');
                        $('#babel-remove-logo-btn').show();
                    });
                    logoFrame.open();
                });

                $('#babel-remove-logo-btn').on('click', function(e) {
                    e.preventDefault();
                    $('#babel_logo_id').val('0');
                    $('#babel-logo-preview').html('<span class="babel-media-placeholder-icon">🏢</span>');
                    $(this).hide();
                });

                // --- MANEJO DE GALERÍA DE FOTOS ---
                var galleryFrame;
                $('#babel-add-gallery-btn').on('click', function(e) {
                    e.preventDefault();
                    if (galleryFrame) {
                        galleryFrame.open();
                        return;
                    }
                    galleryFrame = wp.media({
                        title: 'Añadir Fotos a la Galería',
                        button: {
                            text: 'Añadir a la Galería'
                        },
                        multiple: true
                    });
                    galleryFrame.on('select', function() {
                        var selection = galleryFrame.state().get('selection');
                        var currentIds = $('#babel_gallery').val() ? $('#babel_gallery').val().split(',') : [];
                        
                        selection.each(function(attachment) {
                            attachment = attachment.toJSON();
                            if (currentIds.indexOf(attachment.id.toString()) === -1) {
                                currentIds.push(attachment.id);
                                var imgUrl = (attachment.sizes && attachment.sizes.thumbnail) ? attachment.sizes.thumbnail.url : attachment.url;
                                $('#babel-gallery-grid').append(
                                    '<div class="babel-gallery-item" data-id="' + attachment.id + '">' +
                                        '<img src="' + imgUrl + '" />' +
                                        '<button type="button" class="babel-remove-gallery-item">&times;</button>' +
                                    '</div>'
                                );
                            }
                        });
                        $('#babel_gallery').val(currentIds.join(','));
                    });
                    galleryFrame.open();
                });

                $(document).on('click', '.babel-remove-gallery-item', function(e) {
                    e.preventDefault();
                    var $item = $(this).closest('.babel-gallery-item');
                    var idToRemove = $item.data('id').toString();
                    $item.remove();
                    
                    var currentIds = $('#babel_gallery').val() ? $('#babel_gallery').val().split(',') : [];
                    var index = currentIds.indexOf(idToRemove);
                    if (index > -1) {
                        currentIds.splice(index, 1);
                    }
                    $('#babel_gallery').val(currentIds.join(','));
                });

                // Habilitar ordenación por Drag-and-Drop (Sortable UI)
                var $galleryGrid = $('#babel-gallery-grid');
                if ($galleryGrid.length && $.fn.sortable) {
                    $galleryGrid.sortable({
                        items: '.babel-gallery-item',
                        cursor: 'move',
                        opacity: 0.8,
                        update: function(event, ui) {
                            var ids = [];
                            $galleryGrid.find('.babel-gallery-item').each(function() {
                                ids.push($(this).data('id'));
                            });
                            $('#babel_gallery').val(ids.join(','));
                        }
                    });
                }

                // --- MANEJO DE SECCIÓN DE HORARIOS (DESACTIVAR AL MARCAR CERRADO) ---
                $('.babel-hours-closed-checkbox').each(function() {
                    var $row = $(this).closest('.babel-hours-row');
                    if ($(this).is(':checked')) {
                        $row.find('input[type="time"]').prop('disabled', true).css('opacity', '0.5');
                    }
                });

                $(document).on('change', '.babel-hours-closed-checkbox', function() {
                    var $row = $(this).closest('.babel-hours-row');
                    if ($(this).is(':checked')) {
                        $row.find('input[type="time"]').prop('disabled', true).css('opacity', '0.5');
                    } else {
                        $row.find('input[type="time"]').prop('disabled', false).css('opacity', '1');
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * Obtiene y renderiza recursivamente las opciones jerárquicas de una taxonomía.
     *
     * @param string $taxonomy    Nombre de la taxonomía.
     * @param int    $parent      ID del término padre.
     * @param int    $depth       Nivel de anidamiento actual.
     * @param int    $selected_id ID del término seleccionado.
     */
    private function render_hierarchical_category_options( $taxonomy, $parent = 0, $depth = 0, $selected_id = 0 ) {
        $terms = get_terms( array(
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
            'parent'     => $parent,
        ) );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return;
        }

        foreach ( $terms as $term ) {
            $prefix = str_repeat( '&nbsp;&nbsp;&nbsp;&mdash;&nbsp;', $depth );
            $selected = ( $term->term_id === $selected_id ) ? ' selected' : '';
            echo '<option value="' . esc_attr( $term->term_id ) . '"' . $selected . '>' . $prefix . esc_html( $term->name ) . '</option>';
            $this->render_hierarchical_category_options( $taxonomy, $term->term_id, $depth + 1, $selected_id );
        }
    }

    /**
     * Guarda y sanitiza de forma segura los metadatos en la base de datos de WordPress.
     *
     * @param int     $post_id ID del post que se está guardando.
     * @param WP_Post $post    Objeto del post que se está guardando.
     */
    public function save_business_meta( $post_id, $post ) {
        // 1. Validar el token de seguridad (Nonce)
        if ( ! isset( $_POST['babel_business_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['babel_business_meta_box_nonce'], 'babel_business_meta_box_nonce_action' ) ) {
            return;
        }

        // 2. Verificar que no sea un guardado automático (Autosave)
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // 3. Verificar permisos del usuario actual
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // 4. Sanitizar y guardar cada campo personalizado de forma individual

        // Teléfono
        if ( isset( $_POST['babel_phone'] ) ) {
            update_post_meta( $post_id, '_babel_phone', sanitize_text_field( wp_unslash( $_POST['babel_phone'] ) ) );
        }

        // WhatsApp
        if ( isset( $_POST['babel_whatsapp'] ) ) {
            $whatsapp = sanitize_text_field( wp_unslash( $_POST['babel_whatsapp'] ) );
            $whatsapp = preg_replace( '/[^0-9+]/', '', $whatsapp ); // Permitir sólo números y '+'
            update_post_meta( $post_id, '_babel_whatsapp', $whatsapp );
        }

        // Email Comercial
        if ( isset( $_POST['babel_email'] ) ) {
            update_post_meta( $post_id, '_babel_email', sanitize_email( wp_unslash( $_POST['babel_email'] ) ) );
        }

        // Dirección Física
        if ( isset( $_POST['babel_address'] ) ) {
            update_post_meta( $post_id, '_babel_address', sanitize_text_field( wp_unslash( $_POST['babel_address'] ) ) );
        }

        // Enlace de Google Maps y duplicación por retrocompatibilidad
        if ( isset( $_POST['babel_maps'] ) ) {
            $maps = esc_url_raw( wp_unslash( $_POST['babel_maps'] ) );
            update_post_meta( $post_id, '_babel_maps', $maps );
            update_post_meta( $post_id, '_babel_gmaps', $maps );
        }

        // Coordenadas Lat/Lng y duplicados
        if ( isset( $_POST['babel_lat'] ) ) {
            $lat = sanitize_text_field( wp_unslash( $_POST['babel_lat'] ) );
            update_post_meta( $post_id, '_babel_lat', $lat );
            update_post_meta( $post_id, '_babel_latitude', $lat );
        }
        if ( isset( $_POST['babel_lng'] ) ) {
            $lng = sanitize_text_field( wp_unslash( $_POST['babel_lng'] ) );
            update_post_meta( $post_id, '_babel_lng', $lng );
            update_post_meta( $post_id, '_babel_longitude', $lng );
        }

        // Redes Sociales y Web
        if ( isset( $_POST['babel_website'] ) ) {
            update_post_meta( $post_id, '_babel_website', esc_url_raw( wp_unslash( $_POST['babel_website'] ) ) );
        }
        if ( isset( $_POST['babel_instagram'] ) ) {
            update_post_meta( $post_id, '_babel_instagram', esc_url_raw( wp_unslash( $_POST['babel_instagram'] ) ) );
        }
        if ( isset( $_POST['babel_facebook'] ) ) {
            update_post_meta( $post_id, '_babel_facebook', esc_url_raw( wp_unslash( $_POST['babel_facebook'] ) ) );
        }
        if ( isset( $_POST['babel_linkedin'] ) ) {
            update_post_meta( $post_id, '_babel_linkedin', esc_url_raw( wp_unslash( $_POST['babel_linkedin'] ) ) );
        }

        // Categoría Principal
        if ( isset( $_POST['babel_category_id'] ) ) {
            $cat_id = intval( $_POST['babel_category_id'] );
            if ( $cat_id > 0 ) {
                wp_set_object_terms( $post_id, array( $cat_id ), 'babel_category' );
            } else {
                wp_set_object_terms( $post_id, array(), 'babel_category' );
            }
        }

        // Imagen Principal / Logotipo (Post Thumbnail / Destacada)
        if ( isset( $_POST['babel_logo_id'] ) ) {
            $logo_id = intval( $_POST['babel_logo_id'] );
            if ( $logo_id > 0 ) {
                set_post_thumbnail( $post_id, $logo_id );
            } else {
                delete_post_thumbnail( $post_id );
            }
        }

        // Galería de fotos
        if ( isset( $_POST['babel_gallery'] ) ) {
            update_post_meta( $post_id, '_babel_gallery', sanitize_text_field( wp_unslash( $_POST['babel_gallery'] ) ) );
        }

        // Horarios tipo rueda en JSON
        if ( isset( $_POST['babel_hours'] ) && is_array( $_POST['babel_hours'] ) ) {
            $hours_data = array();
            foreach ( $_POST['babel_hours'] as $day => $data ) {
                $hours_data[ $day ] = array(
                    'open'   => isset( $data['open'] ) ? sanitize_text_field( $data['open'] ) : '',
                    'close'  => isset( $data['close'] ) ? sanitize_text_field( $data['close'] ) : '',
                    'closed' => isset( $data['closed'] ) ? true : false,
                );
            }
            update_post_meta( $post_id, '_babel_hours', wp_json_encode( $hours_data ) );
        }

        // Estados
        $verified = isset( $_POST['babel_verified'] ) ? '1' : '0';
        update_post_meta( $post_id, '_babel_verified', $verified );
        update_post_meta( $post_id, '_babel_is_verified', $verified ); // Duplicación para compatibilidad

        $featured = isset( $_POST['babel_featured'] ) ? '1' : '0';
        update_post_meta( $post_id, '_babel_featured', $featured );
        update_post_meta( $post_id, '_babel_is_featured', $featured ); // Duplicación para compatibilidad
    }
}
