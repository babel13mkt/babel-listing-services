<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Clase para el Dashboard administrativo del plugin
 * v5.0.0 — Rediseño Premium: Centralización y Layout Centrado (1200px)
 */
class BD_Dashboard {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_panel_menu' ), 5 );
        add_action( 'admin_init', array( $this, 'handle_moderation_actions' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    /**
     * Encolar assets para el Dashboard
     */
    public function enqueue_admin_assets( $hook ) {
        // Encolar solo en páginas del plugin
        if ( strpos( $hook, 'bd-' ) === false && $hook !== 'toplevel_page_bd-panel' ) {
            return;
        }

        // Cargar estilos base del admin
        wp_enqueue_style( 'bd-admin-style', BD_URL . 'assets/css/admin.css', array(), BD_VERSION );

        // Si es la página de Alta Premium, cargar también los estilos del formulario
        if ( $hook === 'panel_page_bd-alta-premium' ) {
            $submission = new BD_Submission();
            $submission->enqueue_assets( true );
        }
    }

    /**
     * Centraliza todos los menús bajo "Panel"
     */
    public function register_panel_menu() {
        add_menu_page(
            'Panel Babel',
            'Panel',
            'manage_options',
            'bd-panel',
            array( $this, 'render_panel_page' ),
            'dashicons-layout',
            3
        );

        add_submenu_page(
            'bd-panel',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'bd-panel',
            array( $this, 'render_panel_page' )
        );

        add_submenu_page(
            'bd-panel',
            'Ver Negocios',
            'Negocios',
            'manage_options',
            'edit.php?post_type=directorio_negocio'
        );

        add_submenu_page(
            'bd-panel',
            'Shortcodes',
            'Shortcodes',
            'manage_options',
            'bd-shortcodes',
            array( $this, 'render_shortcodes_page' )
        );

        /*
        add_submenu_page(
            'bd-panel',
            'Gestión de Reservas',
            'Reservas',
            'manage_options',
        );
        */

        add_submenu_page(
            'bd-panel',
            'Moderación',
            'Moderación',
            'manage_options',
            'bd-moderation',
            array( $this, 'render_moderation_page' )
        );

        add_submenu_page(
            'bd-panel',
            'Alta Premium',
            'Alta Premium',
            'manage_options',
            'bd-alta-premium',
            array( $this, 'render_new_business_form' )
        );
    }

    private function get_stats() {
        $counts = wp_count_posts( 'directorio_negocio' );
        $reported = get_comments( array(
            'meta_key'   => '_bd_reported',
            'meta_value' => '1',
            'count'      => true
        ) );

        return array(
            'published'        => $counts->publish,
            'pending'          => $counts->pending,
            'reported'         => $reported
            // 'pending_bookings' => 0 // Placeholder
        );
    }

    public function render_panel_page() {
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['bd_details_metabox_nonce'] ) ) {
            $this->handle_integrated_save();
        }

        $action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : 'stats';
        
        if ( $action === 'new' ) {
            $this->render_new_business_form();
            return;
        }

        $s = $this->get_stats();
        ?>
        <div class="bd-app-container">
            
            <!-- Cabecera de Aplicación -->
            <div class="bd-app-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:40px;">
                <div>
                    <h1 style="font-size:32px; font-weight:800; margin:0; color:var(--bda-text);">Dashboard</h1>
                    <p style="margin:4px 0 0; color:var(--bda-text-soft);">Gestión centralizada de tu directorio</p>
                </div>
                <div class="bd-app-header-actions">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=bd-alta-premium' ) ); ?>"
                       class="bd-save-button" style="text-decoration:none; display:inline-flex; align-items:center; gap:8px; background:var(--bda-accent); color:#fff; padding:12px 24px; border-radius:8px; font-weight:700;">
                       <span style="font-size:20px;">+</span> Nuevo Negocio
                    </a>
                </div>
            </div>

            <!-- Stats Grid Premium -->
            <div class="bd-stats-grid" style="display:grid; grid-template-columns:repeat(4, 1fr); gap:20px; margin-bottom:40px;">
                <div class="bd-card" style="padding:20px; margin-bottom:0; cursor:pointer;" onclick="location.href='edit.php?post_type=directorio_negocio'">
                    <div style="font-size:12px; font-weight:700; color:var(--bda-text-soft); text-transform:uppercase; letter-spacing:0.5px;">Publicados</div>
                    <div style="font-size:36px; font-weight:800; color:var(--bda-accent); margin:8px 0;"><?php echo esc_html( $s['published'] ); ?></div>
                    <div style="font-size:12px; color:var(--bda-text-soft);">Negocios activos 🏪</div>
                </div>
                <div class="bd-card" style="padding:20px; margin-bottom:0; cursor:pointer;" onclick="location.href='edit.php?post_type=directorio_negocio&post_status=pending'">
                    <div style="font-size:12px; font-weight:700; color:var(--bda-text-soft); text-transform:uppercase; letter-spacing:0.5px;">Pendientes</div>
                    <div style="font-size:36px; font-weight:800; color:var(--bda-warning); margin:8px 0;"><?php echo esc_html( $s['pending'] ); ?></div>
                    <div style="font-size:12px; color:var(--bda-text-soft);">Esperando revisión ⏳</div>
                </div>
                <?php /* ?>
                <div class="bd-card" style="padding:20px; margin-bottom:0; cursor:pointer;">
                    <div style="font-size:12px; font-weight:700; color:var(--bda-text-soft); text-transform:uppercase; letter-spacing:0.5px;">Reservas</div>
                    <div style="font-size:36px; font-weight:800; color:var(--bda-success); margin:8px 0;"><?php echo esc_html( $s['pending_bookings'] ); ?></div>
                    <div style="font-size:12px; color:var(--bda-text-soft);">Próximas citas 📅</div>
                </div>
                <?php */ ?>
                <div class="bd-card" style="padding:20px; margin-bottom:0; cursor:pointer;" onclick="location.href='admin.php?page=bd-moderation'">
                    <div style="font-size:12px; font-weight:700; color:var(--bda-text-soft); text-transform:uppercase; letter-spacing:0.5px;">Reportes</div>
                    <div style="font-size:36px; font-weight:800; color:var(--bda-danger); margin:8px 0;"><?php echo esc_html( $s['reported'] ); ?></div>
                    <div style="font-size:12px; color:var(--bda-text-soft);">Reseñas marcadas 🛡️</div>
                </div>
            </div>

            <!-- Listado de Pendientes -->
            <div class="bd-card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
                    <h2 class="bd-card-title" style="margin-bottom:0;">⏳ Negocios pendientes de aprobación</h2>
                    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=directorio_negocio&post_status=pending' ) ); ?>" style="text-decoration:none; font-size:13px; font-weight:600; color:var(--bda-accent);">Ver todos →</a>
                </div>

                <?php
                $pending_posts = get_posts( array(
                    'post_type'      => 'directorio_negocio',
                    'post_status'    => 'pending',
                    'posts_per_page' => 5,
                ) );

                if ( empty( $pending_posts ) ) : ?>
                    <div style="text-align:center; padding:40px; background:#f9fbfc; border-radius:8px;">
                        <span style="font-size:40px; display:block; margin-bottom:12px;">✅</span>
                        <p style="color:var(--bda-text-soft); font-weight:600;">No hay negocios esperando aprobación.</p>
                    </div>
                <?php else : ?>
                    <div style="overflow-x:auto;">
                        <table class="bd-table" style="width:100%; border-collapse:collapse;">
                            <thead>
                                <tr style="text-align:left; border-bottom:1px solid var(--bda-border);">
                                    <th style="padding:12px 0; color:var(--bda-text-soft); font-size:12px; text-transform:uppercase;">Negocio</th>
                                    <th style="padding:12px 0; color:var(--bda-text-soft); font-size:12px; text-transform:uppercase;">Ubicación</th>
                                    <th style="padding:12px 0; color:var(--bda-text-soft); font-size:12px; text-transform:uppercase; text-align:right;">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $pending_posts as $p ) : ?>
                                    <tr style="border-bottom:1px solid #f1f5f9;">
                                        <td style="padding:16px 0;">
                                            <div style="font-weight:700; color:var(--bda-text);"><?php echo esc_html($p->post_title); ?></div>
                                            <div style="font-size:12px; color:var(--bda-text-soft);"><?php echo get_the_date('', $p->ID); ?></div>
                                        </td>
                                        <td style="padding:16px 0;">
                                            <div style="font-size:13px;"><?php echo esc_html(get_post_meta($p->ID, '_bd_direccion', true) ?: 'Sin dirección'); ?></div>
                                        </td>
                                        <td style="padding:16px 0; text-align:right;">
                                            <a href="<?php echo esc_url(admin_url('post.php?post='.$p->ID.'&action=edit')); ?>" class="bd-action-btn view" style="text-decoration:none; background:#fff; border:1px solid var(--bda-border); padding:6px 12px; border-radius:6px; font-size:12px; font-weight:600;">Revisar</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    public function render_new_business_form() {
        ?>
        <div class="bd-app-container">
            <div style="margin-bottom:20px;">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=bd-panel' ) ); ?>" style="text-decoration:none; color:var(--bda-text-soft); font-weight:600; font-size:13px;">← Volver al Dashboard</a>
            </div>
            
            <div class="bd-card" style="padding: 40px;">
                <div class="bd-app-header" style="margin-bottom:30px;">
                    <h1 style="font-size:28px; font-weight:800; margin:0; color:var(--bda-text);">Alta de Negocio Premium</h1>
                    <p style="margin:4px 0 0; color:var(--bda-text-soft);">Interfaz de entrada de alta fidelidad conectada a la red de soberanía.</p>
                </div>

                <?php
                $submission = new BD_Submission();
                echo $submission->render_form( true ); // Pasamos true para modo admin
                ?>
            </div>
        </div>
        <style>
            /* Ajustes para el formulario dentro del admin */
            .bd-form-wrapper { max-width: 100% !important; padding: 0 !important; box-shadow: none !important; background: transparent !important; }
            #wpbody-content { background: #f0f2f5; }
        </style>
        <?php
    }

    private function handle_integrated_save() {
        if ( ! current_user_can( 'edit_posts' ) ) return;
        check_admin_referer( 'bd_details_metabox', 'bd_details_metabox_nonce' );

        $post_id = wp_insert_post( array(
            'post_title'  => isset( $_POST['post_title'] ) ? sanitize_text_field( $_POST['post_title'] ) : 'Sin nombre',
            'post_type'   => 'directorio_negocio',
            'post_status' => isset( $_POST['post_status'] ) ? sanitize_text_field( $_POST['post_status'] ) : 'pending',
        ) );

        if ( $post_id && ! is_wp_error( $post_id ) ) {
            $metaboxes = new BD_Metaboxes();
            $metaboxes->save_metabox( $post_id );
            wp_redirect( admin_url( 'admin.php?page=bd-panel&message=success' ) );
            exit;
        }
    }

    public function handle_moderation_actions() {}

    public function render_moderation_page() {
        // Implementar layout centralizado aquí también...
    }


    public function render_shortcodes_page() {
        ?>
        <div class="bd-app-container">
            <div class="bd-app-header" style="margin-bottom:40px;">
                <h1 style="font-size:32px; font-weight:800; margin:0; color:var(--bda-text);">Diccionario de Shortcodes</h1>
                <p style="margin:4px 0 0; color:var(--bda-text-soft);">Copia y pega estos códigos en tu maquetador (Divi 5) usando módulos de Texto o Código.</p>
            </div>

            <div class="bd-card" style="margin-bottom:24px;">
                <h2 style="font-size:18px; font-weight:700; margin-top:0;">🔍 Buscador de la Home</h2>
                <p style="color:var(--bda-text-soft);">Renderiza el buscador horizontal con radar de cercanía.</p>
                <div style="background:#f1f5f9; padding:12px; border-radius:6px; font-family:monospace; color:#1e293b; font-weight:600; display:inline-block; border:1px solid #cbd5e1;">
                    [sdc_buscador]
                </div>
            </div>

            <div class="bd-card" style="margin-bottom:24px;">
                <h2 style="font-size:18px; font-weight:700; margin-top:0;">📝 Formulario de Carga (Frontend)</h2>
                <p style="color:var(--bda-text-soft);">Renderiza el formulario multi-step para dar de alta nuevos negocios desde la web pública.</p>
                <div style="background:#f1f5f9; padding:12px; border-radius:6px; font-family:monospace; color:#1e293b; font-weight:600; display:inline-block; border:1px solid #cbd5e1;">
                    [bd_nuevo_negocio]
                </div>
            </div>

            <div class="bd-card" style="margin-bottom:24px;">
                <h2 style="font-size:18px; font-weight:700; margin-top:0;">🎛️ Barra de Filtros AJAX</h2>
                <p style="color:var(--bda-text-soft);">Barra horizontal de filtros (Keyword, Categoría, Región, Cerca de mí). Solo funciona en las páginas de archivo o resultados de búsqueda.</p>
                <div style="background:#f1f5f9; padding:12px; border-radius:6px; font-family:monospace; color:#1e293b; font-weight:600; display:inline-block; border:1px solid #cbd5e1;">
                    [bd_filter_bar]
                </div>
            </div>

            <div class="bd-card" style="margin-bottom:24px;">
                <h2 style="font-size:18px; font-weight:700; margin-top:0;">📦 Grillas Dinámicas (Categorías y Regiones)</h2>
                <p style="color:var(--bda-text-soft);">Muestra un grid estilizado de términos. Ideal para la portada.</p>
                
                <h4 style="margin:16px 0 8px;">Atributos soportados:</h4>
                <ul style="list-style-type:disc; padding-left:20px; color:var(--bda-text-soft); margin-bottom:16px;">
                    <li><strong>type</strong>: <code>category</code> o <code>region</code> (Por defecto: category)</li>
                    <li><strong>limit</strong>: Número máximo a mostrar. <code>0</code> para mostrar todos. (Por defecto: 0)</li>
                    <li><strong>columns</strong>: Columnas en escritorio: <code>2</code>, <code>3</code>, o <code>4</code>. (Por defecto: 4)</li>
                </ul>

                <div style="margin-bottom: 12px;">
                    <strong>Ejemplo 1: Todas las regiones (4 columnas)</strong>
                    <div style="background:#f1f5f9; padding:12px; border-radius:6px; font-family:monospace; color:#1e293b; font-weight:600; display:block; border:1px solid #cbd5e1; margin-top:4px;">
                        [bd_grid type="region"]
                    </div>
                </div>

                <div style="margin-bottom: 12px;">
                    <strong>Ejemplo 2: Top 8 categorías (4 columnas)</strong>
                    <div style="background:#f1f5f9; padding:12px; border-radius:6px; font-family:monospace; color:#1e293b; font-weight:600; display:block; border:1px solid #cbd5e1; margin-top:4px;">
                        [bd_grid type="category" limit="8" columns="4"]
                    </div>
                </div>

                <div>
                    <strong>Ejemplo 3: 6 regiones en 3 columnas</strong>
                    <div style="background:#f1f5f9; padding:12px; border-radius:6px; font-family:monospace; color:#1e293b; font-weight:600; display:block; border:1px solid #cbd5e1; margin-top:4px;">
                        [bd_grid type="region" limit="6" columns="3"]
                    </div>
                </div>
            </div>

            <div class="bd-card" style="margin-bottom:24px;">
                <h2 style="font-size:18px; font-weight:700; margin-top:0;">📂 Listados para Footer (Regiones y Categorías)</h2>
                <p style="color:var(--bda-text-soft);">Shortcodes independientes y optimizados para el pie de página, formateados en columnas mediante CSS nativo.</p>
                
                <h3 style="font-size:15px; font-weight:700; margin:16px 0 8px; color:var(--bda-text);">1. Footer Regiones: <code>[bd_footer_regions]</code></h3>
                <p style="color:var(--bda-text-soft);">Muestra un listado de las regiones principales ordenadas alfabéticamente (removiendo automáticamente prefijos romanos como "XV - REG -").</p>
                <h4 style="margin:8px 0 4px; font-size:13px;">Atributos soportados:</h4>
                <ul style="list-style-type:disc; padding-left:20px; color:var(--bda-text-soft); margin-bottom:12px;">
                    <li><strong>columns</strong>: Número de columnas (Por defecto: <code>2</code>)</li>
                    <li><strong>rows</strong>: Límite de filas por columna (Por defecto: <code>8</code>)</li>
                    <li><strong>orderby</strong>: Campo de ordenación (Por defecto: <code>name</code>)</li>
                    <li><strong>order</strong>: Dirección (<code>ASC</code> o <code>DESC</code>, Por defecto: <code>ASC</code>)</li>
                </ul>
                <div style="margin-bottom:16px;">
                    <strong>Ejemplo de uso:</strong>
                    <div style="background:#f1f5f9; padding:12px; border-radius:6px; font-family:monospace; color:#1e293b; font-weight:600; display:block; border:1px solid #cbd5e1; margin-top:4px;">
                        [bd_footer_regions columns="2" rows="8"]
                    </div>
                </div>

                <h3 style="font-size:15px; font-weight:700; margin:16px 0 8px; color:var(--bda-text);">2. Footer Categorías: <code>[bd_footer_categories]</code></h3>
                <p style="color:var(--bda-text-soft);">Muestra un listado de las categorías principales.</p>
                <h4 style="margin:8px 0 4px; font-size:13px;">Atributos soportados:</h4>
                <ul style="list-style-type:disc; padding-left:20px; color:var(--bda-text-soft); margin-bottom:12px;">
                    <li><strong>columns</strong>: Número de columnas (Por defecto: <code>3</code>)</li>
                    <li><strong>rows</strong>: Límite de filas por columna (Por defecto: <code>8</code>)</li>
                    <li><strong>parent</strong>: ID de la categoría padre. Usar <code>0</code> para principales, o <code>any</code> para todas. (Por defecto: <code>0</code>)</li>
                    <li><strong>orderby</strong>: Campo de ordenación (Por defecto: <code>name</code>)</li>
                    <li><strong>order</strong>: Dirección (<code>ASC</code> o <code>DESC</code>, Por defecto: <code>ASC</code>)</li>
                </ul>
                <div>
                    <strong>Ejemplo de uso:</strong>
                    <div style="background:#f1f5f9; padding:12px; border-radius:6px; font-family:monospace; color:#1e293b; font-weight:600; display:block; border:1px solid #cbd5e1; margin-top:4px;">
                        [bd_footer_categories columns="3" rows="8"]
                    </div>
                </div>
            </div>

            <div class="bd-card" style="margin-bottom:24px;">
                <h2 style="font-size:18px; font-weight:700; margin-top:0;">🔄 Loop de Resultados de Archivo (Divi Theme Builder)</h2>
                <p style="color:var(--bda-text-soft);">Muestra de forma dinámica los negocios y la paginación correspondientes a la Región o Categoría actual cuando el usuario navega por el directorio. Diseñado para integrarse en las plantillas del <strong>Generador de Temas de Divi (Theme Builder)</strong>.</p>
                <div style="margin-bottom:16px;">
                    <strong>Ejemplo de aplicación (en plantilla de Divi):</strong>
                    <div style="background:#f8fafc; padding:12px; border-radius:6px; font-family:monospace; color:#0f172a; font-size:13px; line-height:1.5; border:1px solid #e2e8f0; margin-top:4px;">
                        &lt;!-- Colocar primero el buscador y luego el listado de resultados --&gt;<br>
                        [bd_filter_bar]<br>
                        [bd_archive_loop]
                    </div>
                </div>
                <div>
                    <strong>Shortcode:</strong>
                    <div style="background:#f1f5f9; padding:12px; border-radius:6px; font-family:monospace; color:#1e293b; font-weight:600; display:inline-block; border:1px solid #cbd5e1; margin-top:4px;">
                        [bd_archive_loop]
                    </div>
                </div>
            </div>

        </div>
        <?php
    }
}

