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
        add_filter( 'parent_file', array( $this, 'fix_menu_highlighting' ) );
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

        // 1. Todos los Negocios CPT
        add_submenu_page(
            'bd-panel',
            __( 'Todos los Negocios', 'babel-directory' ),
            __( 'Todos los Negocios', 'babel-directory' ),
            'manage_options',
            'edit.php?post_type=babel_business'
        );

        // 2. Añadir Nuevo Negocio
        add_submenu_page(
            'bd-panel',
            __( 'Añadir Nuevo', 'babel-directory' ),
            __( 'Añadir Nuevo', 'babel-directory' ),
            'manage_options',
            'post-new.php?post_type=babel_business'
        );

        // 3. Categorías de Negocio
        add_submenu_page(
            'bd-panel',
            __( 'Categorías', 'babel-directory' ),
            __( 'Categorías', 'babel-directory' ),
            'manage_options',
            'edit-tags.php?taxonomy=babel_category&post_type=babel_business'
        );

        // 4. Regiones/Comunas
        add_submenu_page(
            'bd-panel',
            __( 'Regiones/Comunas', 'babel-directory' ),
            __( 'Regiones/Comunas', 'babel-directory' ),
            'manage_options',
            'edit-tags.php?taxonomy=babel_region&post_type=babel_business'
        );

        // 5. Guía de Shortcodes
        add_submenu_page(
            'bd-panel',
            __( 'Guía de Shortcodes', 'babel-directory' ),
            __( 'Guía de Shortcodes', 'babel-directory' ),
            'manage_options',
            'bd-shortcode-guide',
            array( $this, 'render_shortcode_guide_page' )
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

        </div> <!-- Cierre de .wrap -->
        <?php
    }

    /**
     * Renderiza la página de la Guía de Shortcodes como elemento del menú de administración.
     */
    public function render_shortcode_guide_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Guía de Shortcodes', 'babel-directory' ); ?></h1>
            
            <div class="babel-shortcode-guide-panel" style="margin-top: 20px;">
                <h2 class="babel-guide-title">
                    <span class="dashicons dashicons-editor-code"></span>
                    <?php esc_html_e( 'Guía de Shortcodes', 'babel-directory' ); ?>
                </h2>
                <p class="babel-guide-subtitle">
                    <?php esc_html_e( 'Copie y pegue estos códigos directamente en Divi para integrar los componentes interactivos.', 'babel-directory' ); ?>
                </p>

                <div class="babel-guide-grid">
                    <!-- Shortcode 1: Buscador de Radar -->
                    <div class="babel-guide-card">
                        <div class="babel-guide-card-header">
                            <h3><?php esc_html_e( 'Buscador de Radar (Hero Section)', 'babel-directory' ); ?></h3>
                        </div>
                        <div class="babel-guide-card-body">
                            <p><?php esc_html_e( 'Renderiza la caja de búsqueda con efecto visual de radar pulsante e integración GPS. Ideal para cabeceras.', 'babel-directory' ); ?></p>
                            <div class="babel-copy-box">
                                <code>[babel_radar_search]</code>
                                <button type="button" class="babel-copy-btn" id="btn-copy-radar" onclick="babelCopyShortcode('[babel_radar_search]', 'btn-copy-radar')">
                                    <span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copiar', 'babel-directory' ); ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Shortcode 2: Grilla de Regiones -->
                    <div class="babel-guide-card">
                        <div class="babel-guide-card-header">
                            <h3><?php esc_html_e( 'Grilla de Regiones / Comunas', 'babel-directory' ); ?></h3>
                        </div>
                        <div class="babel-guide-card-body">
                            <p><?php esc_html_e( 'Muestra las regiones en una cuadrícula responsiva con imágenes. Soporta personalización de columnas y filas.', 'babel-directory' ); ?></p>
                            <div class="babel-copy-box">
                                <code>[babel_region_grid columns="4" rows="2"]</code>
                                <button type="button" class="babel-copy-btn" id="btn-copy-region" onclick="babelCopyShortcode('[babel_region_grid columns=\'4\' rows=\'2\']', 'btn-copy-region')">
                                    <span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copiar', 'babel-directory' ); ?>
                                </button>
                            </div>
                            <div class="babel-guide-params">
                                <span><strong>Parámetros opcionales:</strong></span>
                                <ul>
                                    <li><code>columns="X"</code>: Cantidad de columnas en escritorio (ej. 3, 4).</li>
                                    <li><code>rows="Y"</code>: Cantidad de filas a mostrar (limita los resultados a X * Y).</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Shortcode 3: Formulario de Búsqueda AJAX -->
                    <div class="babel-guide-card">
                        <div class="babel-guide-card-header">
                            <h3><?php esc_html_e( 'Buscador Inteligente AJAX', 'babel-directory' ); ?></h3>
                        </div>
                        <div class="babel-guide-card-body">
                            <p><?php esc_html_e( 'Barra de búsqueda única y moderna con geolocalización por GPS integrada para buscar negocios, categorías y comunas.', 'babel-directory' ); ?></p>
                            <div class="babel-copy-box">
                                <code>[babel_search_form]</code>
                                <button type="button" class="babel-copy-btn" id="btn-copy-search" onclick="babelCopyShortcode('[babel_search_form]', 'btn-copy-search')">
                                    <span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copiar', 'babel-directory' ); ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Shortcode 4: Contenedor de Resultados -->
                    <div class="babel-guide-card">
                        <div class="babel-guide-card-header">
                            <h3><?php esc_html_e( 'Contenedor de Resultados AJAX', 'babel-directory' ); ?></h3>
                        </div>
                        <div class="babel-guide-card-body">
                            <p><?php esc_html_e( 'Sección dinámica donde se cargan los resultados de búsqueda AJAX utilizando el maquetado de la biblioteca Divi.', 'babel-directory' ); ?></p>
                            <div class="babel-copy-box">
                                <code>[babel_results]</code>
                                <button type="button" class="babel-copy-btn" id="btn-copy-results" onclick="babelCopyShortcode('[babel_results]', 'btn-copy-results')">
                                    <span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copiar', 'babel-directory' ); ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Shortcode 5: Barra de Filtros AJAX -->
                    <div class="babel-guide-card">
                        <div class="babel-guide-card-header">
                            <h3><?php esc_html_e( 'Barra de Filtros AJAX', 'babel-directory' ); ?></h3>
                        </div>
                        <div class="babel-guide-card-body">
                            <p><?php esc_html_e( 'Barra horizontal de filtros (Buscador, Categoría, Región/Comuna, Radar Cerca de Mí) que actúa sobre el listado de resultados.', 'babel-directory' ); ?></p>
                            <div class="babel-copy-box">
                                <code>[bd_filter_bar]</code>
                                <button type="button" class="babel-copy-btn" id="btn-copy-filter-bar" onclick="babelCopyShortcode('[bd_filter_bar]', 'btn-copy-filter-bar')">
                                    <span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copiar', 'babel-directory' ); ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Shortcode 6: Loop de Resultados de Archivo -->
                    <div class="babel-guide-card">
                        <div class="babel-guide-card-header">
                            <h3><?php esc_html_e( 'Loop de Resultados de Archivo (Divi Theme Builder)', 'babel-directory' ); ?></h3>
                        </div>
                        <div class="babel-guide-card-body">
                            <p><?php esc_html_e( 'Muestra dinámicamente los negocios y la paginación correspondientes a la Región o Categoría actual. Diseñado para integrarse en plantillas del Generador de Temas de Divi.', 'babel-directory' ); ?></p>
                            <div class="babel-copy-box">
                                <code>[bd_archive_loop]</code>
                                <button type="button" class="babel-copy-btn" id="btn-copy-archive-loop" onclick="babelCopyShortcode('[bd_archive_loop]', 'btn-copy-archive-loop')">
                                    <span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copiar', 'babel-directory' ); ?>
                                </button>
                            </div>
                            <div class="babel-guide-params" style="margin-top: 10px;">
                                <span><strong>Ejemplo de aplicación (en Divi):</strong></span>
                                <div style="background: #e2e8f0; color: #0f172a; padding: 8px; border-radius: 4px; font-family: monospace; font-size: 11px; margin-top: 5px; line-height: 1.4;">
                                    [bd_filter_bar]<br>[bd_archive_loop]
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Shortcode 7: Footer Regiones -->
                    <div class="babel-guide-card">
                        <div class="babel-guide-card-header">
                            <h3><?php esc_html_e( 'Footer Regiones / Comunas', 'babel-directory' ); ?></h3>
                        </div>
                        <div class="babel-guide-card-body">
                            <p><?php esc_html_e( 'Muestra un listado de las regiones principales ordenadas alfabéticamente (removiendo automáticamente prefijos romanos como "XV - REG -"). Ideal para el pie de página.', 'babel-directory' ); ?></p>
                            <div class="babel-copy-box">
                                <code>[bd_footer_regions columns="2" rows="8"]</code>
                                <button type="button" class="babel-copy-btn" id="btn-copy-footer-regions" onclick="babelCopyShortcode('[bd_footer_regions columns=\'2\' rows=\'8\']', 'btn-copy-footer-regions')">
                                    <span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copiar', 'babel-directory' ); ?>
                                </button>
                            </div>
                            <div class="babel-guide-params">
                                <span><strong>Parámetros opcionales:</strong></span>
                                <ul>
                                    <li><code>columns</code>: Número de columnas (Por defecto: <code>2</code>)</li>
                                    <li><code>rows</code>: Límite de filas por columna (Por defecto: <code>8</code>)</li>
                                    <li><code>orderby</code>: Ordenar por (<code>name</code>, <code>count</code>, <code>id</code>. Por defecto: <code>name</code>)</li>
                                    <li><code>order</code>: Dirección (<code>ASC</code> o <code>DESC</code>. Por defecto: <code>ASC</code>)</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Shortcode 8: Footer Categorías -->
                    <div class="babel-guide-card">
                        <div class="babel-guide-card-header">
                            <h3><?php esc_html_e( 'Footer Categorías de Negocio', 'babel-directory' ); ?></h3>
                        </div>
                        <div class="babel-guide-card-body">
                            <p><?php esc_html_e( 'Muestra un listado ordenado de las categorías principales para el pie de página.', 'babel-directory' ); ?></p>
                            <div class="babel-copy-box">
                                <code>[bd_footer_categories columns="3" rows="8"]</code>
                                <button type="button" class="babel-copy-btn" id="btn-copy-footer-cats" onclick="babelCopyShortcode('[bd_footer_categories columns=\'3\' rows=\'8\']', 'btn-copy-footer-cats')">
                                    <span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copiar', 'babel-directory' ); ?>
                                </button>
                            </div>
                            <div class="babel-guide-params">
                                <span><strong>Parámetros opcionales:</strong></span>
                                <ul>
                                    <li><code>columns</code>: Número de columnas (Por defecto: <code>3</code>)</li>
                                    <li><code>rows</code>: Límite de filas por columna (Por defecto: <code>8</code>)</li>
                                    <li><code>parent</code>: ID de categoría padre (<code>0</code> para principales, o <code>any</code>. Por defecto: <code>0</code>)</li>
                                    <li><code>orderby</code>: Ordenar por (<code>name</code>, <code>count</code>. Por defecto: <code>name</code>)</li>
                                    <li><code>order</code>: Dirección (<code>ASC</code> o <code>DESC</code>. Por defecto: <code>ASC</code>)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function babelCopyShortcode(text, buttonId) {
                navigator.clipboard.writeText(text).then(function() {
                    var btn = document.getElementById(buttonId);
                    var originalHtml = btn.innerHTML;
                    btn.innerHTML = '<span class="dashicons dashicons-yes"></span> Copiado';
                    btn.classList.add('copied');
                    setTimeout(function() {
                        btn.innerHTML = originalHtml;
                        btn.classList.remove('copied');
                    }, 2000);
                }).catch(function(err) {
                    console.error('Error al copiar: ', err);
                });
            }
        </script>

        <style>
            .babel-shortcode-guide-panel {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 24px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                max-width: 1000px;
            }
            .babel-guide-title {
                display: flex;
                align-items: center;
                gap: 10px;
                font-size: 20px;
                font-weight: 600;
                color: #1e293b;
                margin: 0 0 4px 0 !important;
            }
            .babel-guide-title .dashicons {
                font-size: 24px;
                width: 24px;
                height: 24px;
                color: #10b981;
            }
            .babel-guide-subtitle {
                font-size: 14px;
                color: #64748b;
                margin: 0 0 24px 0 !important;
            }
            .babel-guide-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(440px, 1fr));
                gap: 20px;
            }
            @media (max-width: 600px) {
                .babel-guide-grid {
                    grid-template-columns: 1fr;
                }
            }
            .babel-guide-card {
                background: #f8fafc;
                border: 1px solid #f1f5f9;
                border-radius: 8px;
                padding: 20px;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
            }
            .babel-guide-card-header h3 {
                font-size: 15px;
                font-weight: 600;
                color: #334155;
                margin: 0 0 10px 0 !important;
            }
            .babel-guide-card-body p {
                font-size: 13px;
                color: #64748b;
                margin: 0 0 15px 0 !important;
                line-height: 1.5;
            }
            .babel-copy-box {
                display: flex;
                align-items: center;
                justify-content: space-between;
                background: #0f172a;
                padding: 8px 12px;
                border-radius: 6px;
                border: 1px solid #1e293b;
                margin-top: 10px;
            }
            .babel-copy-box code {
                color: #38bdf8;
                font-family: monospace;
                font-size: 12px;
                background: transparent !important;
                padding: 0 !important;
            }
            .babel-copy-btn {
                background: #3b82f6;
                color: #ffffff;
                border: none;
                padding: 6px 12px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 11px;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 4px;
                transition: background 0.2s ease;
            }
            .babel-copy-btn:hover {
                background: #2563eb;
            }
            .babel-copy-btn.copied {
                background: #10b981 !important;
            }
            .babel-copy-btn .dashicons {
                font-size: 14px;
                width: 14px;
                height: 14px;
            }
            .babel-guide-params {
                margin-top: 10px;
                font-size: 12px;
                color: #64748b;
            }
            .babel-guide-params ul {
                margin: 5px 0 0 0 !important;
                padding-left: 15px !important;
                list-style-type: disc !important;
            }
            .babel-guide-params li {
                margin-bottom: 4px !important;
            }
            .babel-guide-params code {
                background: #e2e8f0;
                color: #0f172a;
                padding: 1px 4px;
                border-radius: 3px;
                font-size: 11px;
            }
        </style>
        <?php
    }

    /**
     * Corrige el resaltado del menú lateral para que Panel Babel permanezca activo
     * al navegar por el listado, creación, taxonomías de negocios o guía de shortcodes.
     *
     * @param string $parent_file El archivo padre actual.
     * @return string El archivo padre filtrado.
     */
    public function fix_menu_highlighting( $parent_file ) {
        global $current_screen, $plugin_page;
        
        // Si estamos en cualquier pantalla del CPT 'babel_business', sus taxonomías asociadas o la guía de shortcodes
        if ( ( isset( $current_screen->post_type ) && 'babel_business' === $current_screen->post_type ) ||
             ( isset( $plugin_page ) && 'bd-shortcode-guide' === $plugin_page ) ) {
            return 'bd-panel';
        }
        
        return $parent_file;
    }
}
