<?php
namespace Babel\Directory;

/**
 * Panel de Administración y Configuración Integral SPA (Babel_Directory_Admin)
 * v8.0.0 — Hito 11: Single Page Application "Soy de Chile"
 *
 * @package Babel_Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}

class Admin {

    /**
     * Constructor de la clase.
     * Registra los ganchos de administración y de la Settings API.
     */
    public function __construct() {
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        
        // AJAX Endpoints SPA
        add_action( 'wp_ajax_sdc_save_settings', array( $this, 'ajax_save_settings' ) );
        add_action( 'wp_ajax_sdc_save_business', array( $this, 'ajax_save_business' ) );
    }

    /**
     * Registra los ajustes, secciones y campos usando la Settings API de WordPress.
     */
    public function register_settings() {
        register_setting(
            'babel_directory_settings_group',
            'babel_google_client_id',
            array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            )
        );

        add_settings_section(
            'babel_google_section',
            __( 'Integración con Google Identity Services', 'babel-directory' ),
            array( $this, 'render_google_section_description' ),
            'bd-settings'
        );

        add_settings_field(
            'babel_google_client_id',
            __( 'Google Client ID', 'babel-directory' ),
            array( $this, 'render_google_client_id_field' ),
            'bd-settings',
            'babel_google_section'
        );
    }

    public function render_google_section_description() {
        echo '<p class="sdc-text-muted" style="margin-bottom:12px;">';
        esc_html_e( 'Configura tu Google OAuth 2.0 Client ID para habilitar el login con Google en el formulario de publicación.', 'babel-directory' );
        echo ' <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener" style="color:var(--sdc-blue);text-decoration:none;">';
        esc_html_e( 'Obtener Client ID &rarr;', 'babel-directory' );
        echo '</a></p>';
    }

    public function render_google_client_id_field() {
        $value = get_option( 'babel_google_client_id', '' );
        echo '<input type="text" name="babel_google_client_id" id="babel_google_client_id" value="' . esc_attr( $value ) . '" class="sdc-input" placeholder="123456789-abc...apps.googleusercontent.com" />';
    }

    /**
     * Guardado de configuración vía AJAX para la SPA.
     */
    public function ajax_save_settings() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permisos insuficientes.' );
        }
        
        if ( isset( $_POST['babel_google_client_id'] ) ) {
            update_option( 'babel_google_client_id', sanitize_text_field( wp_unslash( $_POST['babel_google_client_id'] ) ) );
        }
        
        wp_send_json_success( 'Guardado correctamente.' );
    }

    /**
     * Guarda los datos del Editor de Negocios de la SPA
     */
    public function ajax_save_business() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( 'Permisos insuficientes.' );
        }

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        $title   = isset( $_POST['biz_name'] ) ? sanitize_text_field( wp_unslash( $_POST['biz_name'] ) ) : 'Nuevo Negocio';
        $content = isset( $_POST['biz_desc'] ) ? sanitize_textarea_field( wp_unslash( $_POST['biz_desc'] ) ) : '';

        if ( $post_id ) {
            wp_update_post( array(
                'ID'           => $post_id,
                'post_title'   => $title,
                'post_content' => $content,
            ) );
        } else {
            $post_id = wp_insert_post( array(
                'post_type'    => 'babel_business',
                'post_title'   => $title,
                'post_content' => $content,
                'post_status'  => 'publish', // Autopublicar desde la SPA del admin
            ) );
        }

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( 'Error al crear el negocio.' );
        }

        // Mapeo de campos de la UI a los Meta Keys del post
        $meta_fields = array(
            'biz_logo_id'      => array( '_bd_logo_id', '_thumbnail_id' ),
            'biz_whatsapp'     => array( '_babel_whatsapp', '_bd_whatsapp' ),
            'biz_phone'        => array( '_babel_phone', '_bd_telefono' ),
            'biz_email'        => array( '_babel_email', '_bd_email' ),
            'biz_website'      => array( '_babel_website', '_bd_sitio_web', '_bd_web' ),
            'biz_instagram'    => array( '_babel_instagram' ),
            'biz_facebook'     => array( '_babel_facebook' ),
            'biz_tiktok'       => array( '_babel_tiktok' ),
            'biz_linkedin'     => array( '_babel_linkedin' ),
            'biz_rut'          => array( '_babel_rut', '_bd_rut' ),
            'biz_razon_social' => array( '_babel_razon_social', '_bd_razon_social' ),
            'biz_giro'         => array( '_babel_giro', '_bd_giro' ),
            'biz_rep_legal'    => array( '_babel_rep_legal', '_bd_rep_legal' ),
            'biz_gallery'      => array( '_babel_gallery', '_bd_galeria' ),
            'biz_address'      => array( '_babel_address', '_bd_direccion' ),
            'biz_lat'          => array( '_babel_lat', '_babel_latitude', '_bd_latitud' ),
            'biz_lng'          => array( '_babel_lng', '_babel_longitude', '_bd_longitud' ),
            'biz_wifi'         => array( '_babel_wifi', '_bd_wifi' ),
            'biz_parking'      => array( '_babel_parking', '_bd_estacionamiento' ),
            'biz_pet_friendly' => array( '_babel_pet_friendly' ),
            'biz_delivery'     => array( '_babel_delivery' )
        );

        foreach ( $meta_fields as $post_key => $meta_keys ) {
            if ( isset( $_POST[ $post_key ] ) ) {
                $val = wp_unslash( $_POST[ $post_key ] );
                
                // Sanitización básica
                if ( in_array( $post_key, array('biz_website', 'biz_instagram', 'biz_facebook', 'biz_tiktok', 'biz_linkedin') ) ) {
                    $val = esc_url_raw( $val );
                } elseif ( $post_key === 'biz_email' ) {
                    $val = sanitize_email( $val );
                } else {
                    $val = sanitize_text_field( $val );
                }

                foreach ( $meta_keys as $meta_key ) {
                    update_post_meta( $post_id, $meta_key, $val );
                }
            }
        }

        // Disparar un hook personalizado para que otros módulos (como el motor de búsqueda)
        // sepan que el negocio y TODOS sus metadatos ya están listos en la BD.
        do_action( 'bd_after_business_saved', $post_id );

        wp_send_json_success( array( 'post_id' => $post_id ) );
    }


    /**
     * Registra la página de administración única de la SPA.
     */
    public function add_admin_menu() {
        // Menú principal único
        add_menu_page(
            __( 'Soy de Chile', 'babel-directory' ),
            __( 'Soy de Chile', 'babel-directory' ),
            'manage_options',
            'bd-panel',
            array( $this, 'render_spa_page' ),
            'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#ffffff"><path d="M12 2L2 22h20L12 2z"/></svg>'), // Placeholder flag icon
            3
        );
        
        // Removemos los submenús redundantes (el CPT 'babel_business' sigue registrado y accesible por URL directa si hiciera falta, pero priorizamos la SPA).
    }

    /**
     * Renderiza el contenedor principal y estructura HTML de la SPA.
     */
    public function render_spa_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Obtención de métricas rápidas
        $posts_count = wp_count_posts( 'babel_business' );
        $total_published = isset( $posts_count->publish ) ? intval( $posts_count->publish ) : 0;
        $total_pending = isset( $posts_count->pending ) ? intval( $posts_count->pending ) : 0;
        ?>
        <div id="babel-admin-app">
            
            <!-- Header SPA -->
            <header class="sdc-header">
                <div class="sdc-header-logo">
                    <!-- Bandera simplificada por CSS/Emoji o logo -->
                    🇨🇱 Soy de Chile
                    <span class="sdc-header-title">Admin Dashboard</span>
                </div>
                <div class="sdc-header-actions">
                    <span class="sdc-text-muted" style="color:rgba(255,255,255,0.7); font-size: 13px;">v8.0.0</span>
                </div>
            </header>

            <!-- Menú de Navegación por Pestañas -->
            <nav class="sdc-tabs-nav">
                <button class="sdc-tab-btn active" data-target="sdc-tab-dashboard">
                    <span class="dashicons dashicons-dashboard"></span> Dashboard
                </button>
                <button class="sdc-tab-btn" data-target="sdc-tab-negocios">
                    <span class="dashicons dashicons-store"></span> Editor de Negocios
                </button>
                <button class="sdc-tab-btn" data-target="sdc-tab-configuracion">
                    <span class="dashicons dashicons-admin-settings"></span> Configuración
                </button>
                <button class="sdc-tab-btn" data-target="sdc-tab-guia">
                    <span class="dashicons dashicons-editor-code"></span> Guía de Shortcodes
                </button>
            </nav>

            <!-- 1. Contenido: Dashboard -->
            <div id="sdc-tab-dashboard" class="sdc-tab-content active">
                <h2 style="margin-top:0;">Dashboard</h2>
                
                <div class="sdc-grid">
                    <div class="sdc-card sdc-card-primary">
                        <div class="sdc-metric-title">Negocios Totales (Publicados)</div>
                        <div class="sdc-metric-value"><?php echo esc_html( $total_published ); ?></div>
                    </div>
                    <div class="sdc-card sdc-card-danger">
                        <div class="sdc-metric-title">Aprobaciones Pendientes</div>
                        <div class="sdc-metric-value"><?php echo esc_html( $total_pending ); ?></div>
                    </div>
                </div>

                <h3>Negocios Pendientes de Aprobación</h3>
                <div class="sdc-table-wrapper">
                    <table class="sdc-table">
                        <thead>
                            <tr>
                                <th>Nombre del Negocio</th>
                                <th>Estado</th>
                                <th>Acciones Rápidas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( $total_pending > 0 ) : ?>
                                <!-- Aquí iría el loop de post pendientes. Estático por ahora para estructura. -->
                                <tr>
                                    <td>Ejemplo Restaurante</td>
                                    <td>Pendiente</td>
                                    <td>
                                        <div class="sdc-flex sdc-gap-2">
                                            <button class="sdc-btn sdc-btn-primary">Aprobar</button>
                                            <button class="sdc-btn sdc-btn-danger">Rechazar</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php else : ?>
                                <tr>
                                    <td colspan="3" style="text-align:center; padding: 24px; color: var(--sdc-text-muted);">No hay negocios pendientes de aprobación.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 2. Contenido: Editor de Negocios (Contenedor para la inyección de Metaboxes) -->
            <div id="sdc-tab-negocios" class="sdc-tab-content">
                <h2 style="margin-top:0;">Editor Inmersivo de Negocios</h2>
                <div class="sdc-card">
                    <?php 
                    // Aquí se invoca la nueva clase de Metaboxes refactorizada 
                    // para renderizar el formulario dentro de la SPA.
                    \Babel\Directory\Metaboxes::render_spa_editor(); 
                    ?>
                </div>
            </div>

            <!-- 3. Contenido: Configuración -->
            <div id="sdc-tab-configuracion" class="sdc-tab-content">
                <h2 style="margin-top:0;">Configuración de Soy de Chile</h2>
                <div class="sdc-card">
                    <form id="sdc-settings-form">
                        <?php
                        // Renderiza los campos de configuración registrados
                        do_settings_fields( 'bd-settings', 'babel_google_section' );
                        ?>
                        <div style="margin-top: 24px;">
                            <button type="submit" class="sdc-btn sdc-btn-primary">Guardar Configuración</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 4. Contenido: Guía de Shortcodes -->
            <div id="sdc-tab-guia" class="sdc-tab-content">
                <h2 style="margin-top:0;">Guía Completa de Shortcodes</h2>
                <p class="sdc-text-muted" style="margin-bottom:20px; font-size:14px;">Utiliza estos códigos cortos en las páginas de tu sitio o constructores visuales (como Divi o Gutenberg) para inyectar las diferentes secciones del directorio de forma dinámica.</p>
                
                <h3 style="border-bottom:1px solid var(--sdc-border); padding-bottom:8px; margin-top:24px; color:var(--sdc-blue);">1. Formularios e Interacción</h3>
                <div class="sdc-grid">
                    <div class="sdc-card">
                        <h4 style="margin-top:0; color:#111827;">Formulario de Carga Frontend</h4>
                        <p class="sdc-text-muted" style="font-size:13px; line-height:1.5;">Muestra el portal de registro para nuevos negocios. Incluye validación de login con Google, selección de región, horarios semanales, carga de fotos y selección de comodidades. Los registros quedan pendientes de aprobación.</p>
                        <code style="display:block; padding:8px; background:#F3F4F6; border-radius:4px; margin-top:8px; font-weight:bold; color:#d97706;">[babel_submission_form]</code>
                    </div>
                </div>

                <h3 style="border-bottom:1px solid var(--sdc-border); padding-bottom:8px; margin-top:24px; color:var(--sdc-blue);">2. Buscadores e Integración de Filtros</h3>
                <div class="sdc-grid">
                    <div class="sdc-card">
                        <h4 style="margin-top:0; color:#111827;">Buscador Universal AJAX (Recomendado)</h4>
                        <p class="sdc-text-muted" style="font-size:13px; line-height:1.5;">Barra de filtros unificada que permite buscar negocios por texto libre, filtrar por región y geolocalizar por proximidad GPS (botón radar). Carga los resultados en caliente sin recargar la página.</p>
                        <code style="display:block; padding:8px; background:#F3F4F6; border-radius:4px; margin-top:8px; font-weight:bold; color:#10b981;">[bd_filter_bar show_results="yes"]</code>
                    </div>

                    <div class="sdc-card">
                        <h4 style="margin-top:0; color:#111827;">Buscadores Alternativos (Compatibilidad)</h4>
                        <p class="sdc-text-muted" style="font-size:13px; line-height:1.5;">Códigos heredados que mapean automáticamente al buscador unificado para mantener la compatibilidad en bases de datos antiguas.</p>
                        <code style="display:block; padding:8px; background:#F3F4F6; border-radius:4px; margin-top:8px; font-size:11px; color:#6b7280;">[babel_search_form] o [babel_radar_search]</code>
                    </div>
                </div>

                <h3 style="border-bottom:1px solid var(--sdc-border); padding-bottom:8px; margin-top:24px; color:var(--sdc-blue);">3. Páginas de Destino y Vistas</h3>
                <div class="sdc-grid">
                    <div class="sdc-card">
                        <h4 style="margin-top:0; color:#111827;">Ficha/Perfil de Negocio</h4>
                        <p class="sdc-text-muted" style="font-size:13px; line-height:1.5;">Renderiza el perfil completo del comercio en su página individual (galería, contacto directo, botón de WhatsApp, mapa interactivo Leaflet.js, horarios y valoraciones). Si no se le pasa un ID, detecta automáticamente el negocio actual.</p>
                        <code style="display:block; padding:8px; background:#F3F4F6; border-radius:4px; margin-top:8px; font-weight:bold; color:#2563eb;">[bd_business_profile]</code>
                    </div>

                    <div class="sdc-card">
                        <h4 style="margin-top:0; color:#111827;">Plantilla de Región</h4>
                        <p class="sdc-text-muted" style="font-size:13px; line-height:1.5;">Genera el layout completo de una página de región: incluye un banner Hero de la región, contador de locales, buscador interno y botones de filtrado rápido por categoría.</p>
                        <code style="display:block; padding:8px; background:#F3F4F6; border-radius:4px; margin-top:8px; font-weight:bold; color:#2563eb;">[bd_region_template region="auto"]</code>
                    </div>

                    <div class="sdc-card">
                        <h4 style="margin-top:0; color:#111827;">Grilla / Listado de Resultados</h4>
                        <p class="sdc-text-muted" style="font-size:13px; line-height:1.5;">Muestra los negocios publicados en formato de tarjetas responsivas (diseño premium con ratings, badges de verificado/destacado y precio). Cuenta con paginación integrada.</p>
                        <code style="display:block; padding:8px; background:#F3F4F6; border-radius:4px; margin-top:8px; font-weight:bold; color:#2563eb;">[bd_archive_loop]</code>
                    </div>
                </div>

                <h3 style="border-bottom:1px solid var(--sdc-border); padding-bottom:8px; margin-top:24px; color:var(--sdc-blue);">4. Navegación, Portada y Enlaces SEO</h3>
                <div class="sdc-grid">
                    <div class="sdc-card">
                        <h4 style="margin-top:0; color:#111827;">Grilla de Regiones Populares</h4>
                        <p class="sdc-text-muted" style="font-size:13px; line-height:1.5;">Dibuja una cuadricula responsiva con las imágenes y cantidad de locales registrados de cada región de Chile, ordenadas geográficamente.</p>
                        <code style="display:block; padding:8px; background:#F3F4F6; border-radius:4px; margin-top:8px; font-weight:bold; color:#4f46e5;">[bd_popular_regions columns="4" rows="4"]</code>
                    </div>

                    <div class="sdc-card">
                        <h4 style="margin-top:0; color:#111827;">Grilla de Categorías Populares</h4>
                        <p class="sdc-text-muted" style="font-size:13px; line-height:1.5;">Renderiza una grilla visual de las categorías con mayor cantidad de negocios, utilizando imágenes de fondo u opcionalmente fallbacks elegantes.</p>
                        <code style="display:block; padding:8px; background:#F3F4F6; border-radius:4px; margin-top:8px; font-weight:bold; color:#4f46e5;">[bd_popular_categories columns="4" rows="4"]</code>
                    </div>

                    <div class="sdc-card">
                        <h4 style="margin-top:0; color:#111827;">Enlaces de Regiones (Footer)</h4>
                        <p class="sdc-text-muted" style="font-size:13px; line-height:1.5;">Lista ordenada de regiones para situar en el pie de página, ideal para estructuración de enlaces de navegación y SEO indexable.</p>
                        <code style="display:block; padding:8px; background:#F3F4F6; border-radius:4px; margin-top:8px; font-weight:bold; color:#4f46e5;">[bd_footer_regions columns="2" rows="8"]</code>
                    </div>

                    <div class="sdc-card">
                        <h4 style="margin-top:0; color:#111827;">Enlaces de Categorías (Footer)</h4>
                        <p class="sdc-text-muted" style="font-size:13px; line-height:1.5;">Lista ordenada de las categorías principales para situar en el pie de página, atrayendo visitas de nicho por buscadores web.</p>
                        <code style="display:block; padding:8px; background:#F3F4F6; border-radius:4px; margin-top:8px; font-weight:bold; color:#4f46e5;">[bd_footer_categories columns="3" rows="8"]</code>
                    </div>
                </div>
            </div>

        </div> <!-- Cierre #babel-admin-app -->
        <?php
    }
}
