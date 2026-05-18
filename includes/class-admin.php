<?php
/**
 * Panel de Administración y Configuración Integral (Babel_Directory_Admin)
 * v7.0.0 — Hito 10: Configuración Administrativa Integral (ID de Layout de Divi & Settings API)
 *
 * @package Babel_Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}

class Babel_Directory_Admin {

    /**
     * Constructor de la clase.
     * Registra los ganchos de administración y de la Settings API.
     */
    public function __construct() {
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
    }

    /**
     * Registra los ajustes, secciones y campos usando la Settings API de WordPress.
     */
    public function register_settings() {
        register_setting(
            'babel_directory_settings_group',
            'babel_divi_grid_layout_id',
            array(
                'type'              => 'integer',
                'sanitize_callback' => 'intval',
                'default'           => 0,
            )
        );

        add_settings_section(
            'babel_admin_main_section',
            __( 'Configuración de Integración con Divi 5', 'babel-directory' ),
            array( $this, 'render_section_description' ),
            'bd-panel'
        );

        add_settings_field(
            'babel_divi_grid_layout_id',
            __( 'ID del Layout de Rejilla Divi (Divi Grid Layout ID)', 'babel-directory' ),
            array( $this, 'render_divi_layout_id_field' ),
            'bd-panel',
            'babel_admin_main_section'
        );
    }

    /**
     * Renderiza la descripción de la sección principal.
     */
    public function render_section_description() {
        echo '<p>' . esc_html__( 'Configure las opciones principales de integración de Babel Directory con el maquetador Divi y otros motores del sitio.', 'babel-directory' ) . '</p>';
    }

    /**
     * Renderiza el input para configurar el ID del Layout de Divi.
     */
    public function render_divi_layout_id_field() {
        $value = get_option( 'babel_divi_grid_layout_id', 0 );
        ?>
        <input 
            type="number" 
            name="babel_divi_grid_layout_id" 
            id="babel_divi_grid_layout_id" 
            value="<?php echo esc_attr( $value ); ?>" 
            class="regular-text" 
            min="0"
            step="1"
        />
        <p class="description">
            <?php esc_html_e( 'Ingrese el ID del Layout guardado en la biblioteca de Divi que se utilizará para renderizar las rejillas de resultados de negocios.', 'babel-directory' ); ?>
        </p>
        <?php
    }

    /**
     * Registra la página de administración del plugin.
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'Panel Babel', 'babel-directory' ),
            __( 'Panel Babel', 'babel-directory' ),
            'manage_options',
            'bd-panel',
            array( $this, 'render_admin_page' ),
            'dashicons-layout',
            3
        );
    }

    /**
     * Renderiza el formulario de administración principal y la sección técnica de diagnóstico.
     */
    public function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields( 'babel_directory_settings_group' );
                do_settings_sections( 'bd-panel' );
                submit_button();
                ?>
            </form>

            <?php
            // ==========================================================================
            // SECCIÓN DE DIAGNÓSTICO Y ESTADO DEL SISTEMA (EXTENSIÓN HITO 10)
            // ==========================================================================
            
            // 1. Obtención de métricas de control
            $posts_count = wp_count_posts( 'babel_business' );
            $total_published_wp = isset( $posts_count->publish ) ? intval( $posts_count->publish ) : 0;

            global $wpdb;
            $table_name = $wpdb->prefix . 'bd_search_index';
            $table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) === $table_name;

            $total_indexed = 0;
            if ( $table_exists ) {
                $total_indexed = intval( $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" ) );
            }

            // 2. Verificación del Layout de Divi
            $layout_id = intval( get_option( 'babel_divi_grid_layout_id', 0 ) );
            $layout_status = '';
            $layout_badge_class = '';

            if ( $layout_id <= 0 ) {
                $layout_status = __( 'No configurado', 'babel-directory' );
                $layout_badge_class = 'babel-badge-warning';
            } else {
                $post_status = get_post_status( $layout_id );
                $post_type = get_post_type( $layout_id );
                if ( $post_status && 'et_pb_layout' === $post_type ) {
                    $layout_status = sprintf( __( 'Válido (ID: %d - %s)', 'babel-directory' ), $layout_id, ucfirst( $post_status ) );
                    $layout_badge_class = 'babel-badge-success';
                } else {
                    $layout_status = sprintf( __( 'Inexistente o Inválido (ID: %d)', 'babel-directory' ), $layout_id );
                    $layout_badge_class = 'babel-badge-danger';
                }
            }
            ?>
            <div class="babel-diagnostic-panel">
                <h2 class="babel-diagnostic-title">
                    <span class="dashicons dashicons-shield-alt"></span>
                    <?php esc_html_e( 'Diagnóstico y Estado del Sistema', 'babel-directory' ); ?>
                </h2>
                <p class="babel-diagnostic-subtitle">
                    <?php esc_html_e( 'Supervisión en tiempo real de la integridad de la base de datos y la integración con Divi.', 'babel-directory' ); ?>
                </p>

                <div class="babel-diagnostic-grid">
                    <!-- Tarjeta 1: Motor de Búsqueda Rápida -->
                    <div class="babel-diagnostic-card">
                        <div class="babel-card-header">
                            <span class="dashicons dashicons-database"></span>
                            <h3><?php esc_html_e( 'Índice de Búsqueda', 'babel-directory' ); ?></h3>
                        </div>
                        <div class="babel-card-body">
                            <div class="babel-metric-item">
                                <span class="babel-metric-label"><?php esc_html_e( 'Estado de Tabla:', 'babel-directory' ); ?></span>
                                <span class="babel-badge <?php echo $table_exists ? 'babel-badge-success' : 'babel-badge-danger'; ?>">
                                    <?php echo $table_exists ? esc_html__( 'OK (Física)', 'babel-directory' ) : esc_html__( 'No encontrada', 'babel-directory' ); ?>
                                </span>
                            </div>
                            <div class="babel-metric-item">
                                <span class="babel-metric-label"><?php esc_html_e( 'Tabla Física:', 'babel-directory' ); ?></span>
                                <code class="babel-code-inline"><?php echo esc_html( $table_name ); ?></code>
                            </div>
                        </div>
                    </div>

                    <!-- Tarjeta 2: Negocios e Indexación -->
                    <div class="babel-diagnostic-card">
                        <div class="babel-card-header">
                            <span class="dashicons dashicons-admin-plugins"></span>
                            <h3><?php esc_html_e( 'Sincronización de Datos', 'babel-directory' ); ?></h3>
                        </div>
                        <div class="babel-card-body">
                            <div class="babel-metric-item">
                                <span class="babel-metric-label"><?php esc_html_e( 'Negocios en WordPress:', 'babel-directory' ); ?></span>
                                <span class="babel-metric-value"><?php echo esc_html( $total_published_wp ); ?></span>
                            </div>
                            <div class="babel-metric-item">
                                <span class="babel-metric-label"><?php esc_html_e( 'Negocios en Índice:', 'babel-directory' ); ?></span>
                                <span class="babel-metric-value"><?php echo esc_html( $total_indexed ); ?></span>
                            </div>
                            <?php if ( $table_exists && $total_published_wp !== $total_indexed ) : ?>
                                <div class="babel-alert-inline babel-alert-warning">
                                    <span class="dashicons dashicons-warning"></span>
                                    <?php esc_html_e( 'Existe un desfase en el índice. Se recomienda ejecutar re-indexación masiva.', 'babel-directory' ); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Tarjeta 3: Integración de Layout de Divi -->
                    <div class="babel-diagnostic-card">
                        <div class="babel-card-header">
                            <span class="dashicons dashicons-admin-appearance"></span>
                            <h3><?php esc_html_e( 'Layout de Divi 5', 'babel-directory' ); ?></h3>
                        </div>
                        <div class="babel-card-body">
                            <div class="babel-metric-item">
                                <span class="babel-metric-label"><?php esc_html_e( 'Estado del Layout:', 'babel-directory' ); ?></span>
                                <span class="babel-badge <?php echo esc_attr( $layout_badge_class ); ?>">
                                    <?php echo esc_html( $layout_status ); ?>
                                </span>
                            </div>
                            <div class="babel-metric-item">
                                <span class="babel-metric-label"><?php esc_html_e( 'ID Configurado:', 'babel-directory' ); ?></span>
                                <span class="babel-metric-value"><?php echo $layout_id > 0 ? esc_html( $layout_id ) : esc_html__( 'Ninguno', 'babel-directory' ); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <style>
                .babel-diagnostic-panel {
                    margin-top: 40px;
                    background: #ffffff;
                    border: 1px solid #e2e8f0;
                    border-radius: 12px;
                    padding: 24px;
                    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                    max-width: 1000px;
                }
                .babel-diagnostic-title {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    font-size: 20px;
                    font-weight: 600;
                    color: #1e293b;
                    margin: 0 0 4px 0 !important;
                }
                .babel-diagnostic-title .dashicons {
                    font-size: 24px;
                    width: 24px;
                    height: 24px;
                    color: #6366f1;
                }
                .babel-diagnostic-subtitle {
                    font-size: 14px;
                    color: #64748b;
                    margin: 0 0 24px 0 !important;
                }
                .babel-diagnostic-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                    gap: 20px;
                }
                .babel-diagnostic-card {
                    background: #f8fafc;
                    border: 1px solid #f1f5f9;
                    border-radius: 8px;
                    padding: 20px;
                    transition: transform 0.2s ease, box-shadow 0.2s ease;
                }
                .babel-diagnostic-card:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
                }
                .babel-card-header {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    margin-bottom: 16px;
                    border-bottom: 1px solid #e2e8f0;
                    padding-bottom: 10px;
                }
                .babel-card-header .dashicons {
                    color: #4f46e5;
                }
                .babel-card-header h3 {
                    font-size: 15px;
                    font-weight: 600;
                    color: #334155;
                    margin: 0 !important;
                }
                .babel-metric-item {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 12px;
                    font-size: 13px;
                }
                .babel-metric-label {
                    color: #64748b;
                    font-weight: 500;
                }
                .babel-metric-value {
                    color: #1e293b;
                    font-weight: 600;
                }
                .babel-code-inline {
                    background: #e2e8f0;
                    color: #0f172a;
                    padding: 2px 6px;
                    border-radius: 4px;
                    font-size: 12px;
                    font-family: monospace;
                }
                .babel-badge {
                    display: inline-block;
                    padding: 4px 8px;
                    border-radius: 9999px;
                    font-size: 11px;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.05em;
                }
                .babel-badge-success {
                    background: #dcfce7;
                    color: #15803d;
                }
                .babel-badge-warning {
                    background: #fef9c3;
                    color: #a16207;
                }
                .babel-badge-danger {
                    background: #fee2e2;
                    color: #b91c1c;
                }
                .babel-alert-inline {
                    display: flex;
                    align-items: flex-start;
                    gap: 6px;
                    background: #fffbeb;
                    border: 1px solid #fef3c7;
                    border-radius: 6px;
                    padding: 8px 12px;
                    font-size: 12px;
                    color: #b45309;
                    margin-top: 12px;
                }
                .babel-alert-inline .dashicons {
                    font-size: 16px;
                    width: 16px;
                    height: 16px;
                    color: #d97706;
                }
            </style>
            <?php
        }
    }
