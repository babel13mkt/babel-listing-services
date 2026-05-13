<?php
/**
 * Metaboxes del Directorio (BD_Metaboxes)
 * v5.0.0 — Rediseño Premium: Dashboard Centralizado y Media Uploader Frame.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BD_Metaboxes {

    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'register_metaboxes' ) );
        add_action( 'save_post',      array( $this, 'save_metabox' ) );
        add_action( 'admin_footer', array( $this, 'inline_js' ) );
        add_action( 'admin_head',   array( $this, 'inject_app_mode_css' ) );
        add_filter( 'wp_insert_post_data', array( $this, 'capture_manual_title' ), 10, 2 );
    }

    public function inject_app_mode_css() {
        $screen = get_current_screen();
        $is_cpt = $screen && $screen->post_type === 'directorio_negocio';
        $is_panel = isset($_GET['page']) && $_GET['page'] === 'bd-panel';

        if ( $is_cpt || $is_panel ) {
            // Solo mantenemos inyecciones dinámicas si fueran necesarias.
            // La mayoría del CSS ya vive en admin.css
            echo '<style>
                #wpbody-content { background: #f8fafc !important; }
            </style>';
        }
    }

    public function capture_manual_title( $data, $postarr ) {
        if ( isset( $postarr['post_type'] ) && $postarr['post_type'] === 'directorio_negocio' ) {
            if ( isset( $_POST['post_title'] ) ) {
                $data['post_title'] = sanitize_text_field( $_POST['post_title'] );
            }
        }
        return $data;
    }

    public function register_metaboxes() {
        add_meta_box(
            'bd_details_metabox',
            'Editor Premium',
            array( $this, 'render_metabox' ),
            'directorio_negocio',
            'normal',
            'high'
        );
    }

    public function render_metabox( $post ) {
        wp_nonce_field( 'bd_save_meta', 'bd_nonce' );

        $f = array(
            'direccion'       => get_post_meta( $post->ID, '_bd_direccion', true ),
            'telefono'        => get_post_meta( $post->ID, '_bd_telefono', true ),
            'whatsapp'        => get_post_meta( $post->ID, '_bd_whatsapp', true ),
            'email'           => get_post_meta( $post->ID, '_bd_email', true ),
            'sitio_web'       => get_post_meta( $post->ID, '_bd_sitio_web', true ),
            'horario'         => get_post_meta( $post->ID, '_bd_horario', true ),
            'logo_id'         => get_post_meta( $post->ID, '_bd_logo_id', true ),
            'maps_embed'      => get_post_meta( $post->ID, '_bd_maps_embed', true ),
            'latitud'         => get_post_meta( $post->ID, '_bd_latitud', true ),
            'longitud'        => get_post_meta( $post->ID, '_bd_longitud', true ),
            'verificado'      => get_post_meta( $post->ID, '_bd_verificado', true ),
            'destacado'       => get_post_meta( $post->ID, '_bd_destacado', true ),
        );
        ?>
        <div class="bd-app-container">
            
            <!-- Header Unificado -->
            <div class="bd-app-header" style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:30px;">
                <div style="flex:1;">
                    <span style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--bda-text-soft); letter-spacing:1px;">Modernizar Directorio</span>
                    <input type="text" name="post_title" value="<?php echo esc_attr($post->post_title); ?>" 
                           placeholder="Nombre del Comercio..." 
                           style="border:none; background:transparent; font-size:32px; font-weight:800; padding:0; width:100%; outline:none; color:var(--bda-text);">
                </div>
                <div style="display:flex; gap:12px;">
                    <?php if($post->ID > 0): ?>
                    <a href="<?php echo get_permalink($post->ID); ?>" target="_blank" class="bd-action-btn" style="background:#fff; border:1px solid var(--bda-border); text-decoration:none;">👁️ Previsualizar</a>
                    <?php endif; ?>
                    <button type="button" class="bd-save-button" id="bd-app-save" style="background:var(--bda-accent); color:#fff; border:none; padding:12px 24px; border-radius:8px; font-weight:700; cursor:pointer;">💾 Guardar Cambios</button>
                </div>
            </div>

            <div class="bd-app-layout">
                
                <!-- COLUMNA PRINCIPAL -->
                <div class="bd-main-content">
                    
                    <!-- CAJA 1: INFORMACIÓN BÁSICA -->
                    <div class="bd-card">
                        <h2 class="bd-card-title">📝 Información General</h2>
                        <div class="bd-form-grid">
                            <div class="bd-meta-field bd-full-width">
                                <label class="bd-meta-label">Sitio Web</label>
                                <input type="url" name="bd_sitio_web" value="<?php echo esc_attr($f['sitio_web']); ?>" class="bd-meta-input" placeholder="https://ejemplo.cl">
                            </div>
                            <div class="bd-meta-field">
                                <label class="bd-meta-label">Email de Contacto</label>
                                <input type="email" name="bd_email" value="<?php echo esc_attr($f['email']); ?>" class="bd-meta-input" placeholder="hola@comercio.cl">
                            </div>
                            <div class="bd-meta-field">
                                <label class="bd-meta-label">Teléfono / WhatsApp</label>
                                <input type="tel" name="bd_telefono" value="<?php echo esc_attr($f['telefono']); ?>" class="bd-meta-input" placeholder="+56 9 ...">
                            </div>
                            <div class="bd-meta-field bd-full-width">
                                <label class="bd-meta-label">Horario</label>
                                <textarea name="bd_horario" class="bd-meta-textarea" style="height:60px;" placeholder="Ej: Lun-Vie 09:00 - 19:00"><?php echo esc_textarea($f['horario']); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- CAJA 2: UBICACIÓN -->
                    <div class="bd-card">
                        <h2 class="bd-card-title">📍 Ubicación y Mapa</h2>
                        <div class="bd-form-grid">
                            <div class="bd-meta-field bd-full-width">
                                <label class="bd-meta-label">Dirección Completa</label>
                                <input type="text" name="bd_direccion" value="<?php echo esc_attr($f['direccion']); ?>" class="bd-meta-input" placeholder="Av. Siempre Viva 742, Springfield">
                            </div>
                            
                            <!-- Selectores Taxonomía Reutilizados -->
                            <?php $this->render_tax_selectors($post); ?>

                            <div class="bd-meta-field bd-full-width">
                                <label class="bd-meta-label">Mapa Embed (Iframe)</label>
                                <textarea name="bd_maps_embed" class="bd-meta-textarea" style="height:80px;" placeholder="Pega aquí el código <iframe...> de Google Maps"><?php echo esc_textarea($f['maps_embed']); ?></textarea>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- SIDEBAR -->
                <div class="bd-sidebar-content">
                    
                    <!-- CAJA 3: LOGO Y ESTADO -->
                    <div class="bd-card">
                        <h2 class="bd-card-title">🖼️ Identidad</h2>
                        <label class="bd-meta-label" style="margin-bottom:8px; display:block;">Logo del Negocio</label>
                        <?php $this->render_media_uploader('bd_logo_id', $f['logo_id']); ?>
                        
                        <div class="bd-section-divider" style="margin:24px 0 16px; border-top:1px solid var(--bda-border); padding-top:16px;">Configuración</div>
                        
                        <div class="bd-meta-field">
                            <label class="bd-meta-label">Estado</label>
                            <div class="bd-meta-select-wrap">
                                <select name="post_status" class="bd-meta-select">
                                    <option value="publish" <?php selected($post->post_status, 'publish'); ?>>✅ Publicado</option>
                                    <option value="pending" <?php selected($post->post_status, 'pending'); ?>>⏳ Pendiente</option>
                                    <option value="draft"   <?php selected($post->post_status, 'draft');   ?>>📝 Borrador</option>
                                </select>
                            </div>
                        </div>

                        <div style="margin-top:20px; display:flex; flex-direction:column; gap:10px;">
                            <label class="bd-toggle-item <?php echo $f['verificado'] ? 'checked' : ''; ?>" style="padding:10px; border-radius:8px; border:1px solid var(--bda-border); display:flex; align-items:center; gap:10px; cursor:pointer;">
                                <input type="checkbox" name="bd_verificado" value="1" <?php checked($f['verificado'], '1'); ?>>
                                <span>Verificado</span>
                            </label>
                            <label class="bd-toggle-item <?php echo $f['destacado'] ? 'checked' : ''; ?>" style="padding:10px; border-radius:8px; border:1px solid var(--bda-border); display:flex; align-items:center; gap:10px; cursor:pointer;">
                                <input type="checkbox" name="bd_destacado" value="1" <?php checked($f['destacado'], '1'); ?>>
                                <span>Destacado ⭐</span>
                            </label>
                        </div>
                    </div>

                    <!-- CAJA EXTRA: IMAGEN DESTACADA (Portada) -->
                    <div class="bd-card">
                        <h2 class="bd-card-title">📸 Foto de Portada</h2>
                        <?php 
                        $thumb_id = get_post_thumbnail_id($post->ID);
                        $this->render_media_uploader('_thumbnail_id', $thumb_id); 
                        ?>
                    </div>

                </div>

            </div>
        </div>
        <?php
    }

    private function render_media_uploader($name, $current_id) {
        $img_url = $current_id ? wp_get_attachment_url($current_id) : '';
        ?>
        <div class="bd-media-uploader" data-target="<?php echo esc_attr($name); ?>">
            <div class="bd-image-frame bd-open-media">
                <?php if($img_url): ?>
                    <img src="<?php echo esc_url($img_url); ?>" class="bd-preview-img">
                <?php else: ?>
                    <div class="bd-image-placeholder">
                        <span style="font-size:24px; display:block; margin-bottom:8px;">📁</span>
                        <span>Seleccionar archivo</span>
                    </div>
                <?php endif; ?>
            </div>
            <input type="hidden" name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($current_id); ?>">
            <?php if($img_url): ?>
                <button type="button" class="bd-remove-media" style="background:none; border:none; color:var(--bda-danger); font-size:11px; margin-top:8px; cursor:pointer;">✕ Quitar imagen</button>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_tax_selectors($post) {
        // Reutilizamos la lógica de taxonomías que ya teníamos pero ajustada al nuevo grid
        $all_regions  = get_terms( array( 'taxonomy' => 'directorio_region', 'hide_empty' => false ) );
        $post_regions = wp_get_post_terms( $post->ID, 'directorio_region', array( 'fields' => 'ids' ) );
        $selected_region = 0; $selected_comuna = 0;
        foreach($post_regions as $rid) {
            $t = get_term($rid);
            if($t->parent === 0) $selected_region = $rid; else $selected_comuna = $rid;
        }
        ?>
        <div class="bd-meta-field">
            <label class="bd-meta-label">Región</label>
            <div class="bd-meta-select-wrap">
                <select id="bd_tax_region" name="bd_tax_region" class="bd-meta-select">
                    <option value="0">Seleccionar Región</option>
                    <?php foreach($all_regions as $r) if($r->parent === 0): ?>
                        <option value="<?php echo $r->term_id; ?>" <?php selected($selected_region, $r->term_id); ?>><?php echo $r->name; ?></option>
                    <?php endif; ?>
                </select>
            </div>
        </div>
        <div class="bd-meta-field">
            <label class="bd-meta-label">Comuna</label>
            <div class="bd-meta-select-wrap">
                <select id="bd_tax_comuna" name="bd_tax_comuna" class="bd-meta-select">
                    <option value="0">Primero elige región</option>
                    <?php foreach($all_regions as $r) if($r->parent !== 0): ?>
                        <option value="<?php echo $r->term_id; ?>" data-parent="<?php echo $r->parent; ?>" <?php selected($selected_comuna, $r->term_id); ?>><?php echo $r->name; ?></option>
                    <?php endif; ?>
                </select>
            </div>
        </div>
        <?php
    }

    public function save_metabox( $post_id ) {
        if ( ! isset( $_POST['bd_nonce'] ) || ! wp_verify_nonce( $_POST['bd_nonce'], 'bd_save_meta' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $fields = array(
            'direccion', 'telefono', 'email', 'sitio_web', 'horario', 
            'logo_id', 'maps_embed', 'latitud', 'longitud'
        );
        foreach($fields as $field) {
            if(isset($_POST['bd_'.$field])) {
                update_post_meta($post_id, '_bd_'.$field, sanitize_text_field($_POST['bd_'.$field]));
            }
        }

        // Toggles
        update_post_meta($post_id, '_bd_verificado', isset($_POST['bd_verificado']) ? '1' : '0');
        update_post_meta($post_id, '_bd_destacado',  isset($_POST['bd_destacado'])  ? '1' : '0');

        // Taxonomías
        $regions = array_filter(array( (int)$_POST['bd_tax_region'], (int)$_POST['bd_tax_comuna'] ));
        if(!empty($regions)) wp_set_post_terms($post_id, $regions, 'directorio_region');
        
        // Thumbnail ID (Portada)
        if(isset($_POST['_thumbnail_id'])) {
            set_post_thumbnail($post_id, (int)$_POST['_thumbnail_id']);
        }
    }

    public function inline_js() {
        ?>
        <script>
        (function($) {
            $(document).ready(function() {
                // Media Uploader Frame
                $('.bd-app-container').on('click', '.bd-open-media', function(e) {
                    e.preventDefault();
                    var $container = $(this).closest('.bd-media-uploader');
                    var $target = $('#' + $container.data('target'));
                    var $frame = wp.media({
                        title: 'Seleccionar Archivo',
                        multiple: false,
                        library: { type: 'image' }
                    }).on('select', function() {
                        var attachment = $frame.state().get('selection').first().toJSON();
                        $target.val(attachment.id);
                        $container.find('.bd-image-frame').html('<img src="' + attachment.url + '">');
                        if($container.find('.bd-remove-media').length === 0) {
                            $container.append('<button type="button" class="bd-remove-media" style="background:none; border:none; color:var(--bda-danger); font-size:11px; margin-top:8px; cursor:pointer;">✕ Quitar imagen</button>');
                        }
                    }).open();
                });

                $('.bd-app-container').on('click', '.bd-remove-media', function() {
                    var $container = $(this).closest('.bd-media-uploader');
                    $('#' + $container.data('target')).val('');
                    $container.find('.bd-image-frame').html('<div class="bd-image-placeholder"><span style="font-size:24px; display:block; margin-bottom:8px;">📁</span><span>Seleccionar archivo</span></div>');
                    $(this).remove();
                });

                // Cascada Región -> Comuna
                $('#bd_tax_region').on('change', function() {
                    var rid = $(this).val();
                    $('#bd_tax_comuna option').each(function() {
                        if($(this).val() == 0) return;
                        $(this).toggle($(this).data('parent') == rid);
                    });
                    if($('#bd_tax_comuna option:selected').is(':hidden')) $('#bd_tax_comuna').val(0);
                }).trigger('change');

                // Guardado
                $('#bd-app-save').on('click', function() {
                    var $publish = $('#publish');
                    if($publish.length) $publish.click(); else $(this).closest('form').submit();
                });
            });
        })(jQuery);
        </script>
        <?php
    }
}
