<?php
/**
 * Gestión de Assets y Shortcodes Públicos (Babel_Directory_Assets)
 * v7.0.0 — Hito 9: Buscador de Frontend, Carga Inteligente y Shortcodes Semánticos.
 *
 * @package Babel_Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}

namespace Babel\Directory;


class Assets {

    /**
     * Constructor de la clase.
     * Registra los ganchos de encolado y shortcodes.
     */
    public function __construct() {
        // Registrar assets públicos en el frontend
        add_action( 'wp_enqueue_scripts', array( $this, 'register_public_assets' ) );

        // Registrar los shortcodes oficiales del buscador
        add_shortcode( 'babel_search_form', array( $this, 'render_search_form' ) );
        add_shortcode( 'babel_results', array( $this, 'render_results_container' ) );
    }

    /**
     * Registra los scripts y estilos públicos del buscador.
     * No se encolan globalmente, sino que se registran para encolado inteligente.
     */
    public function register_public_assets() {
        // Registrar la hoja de estilos pública
        wp_register_style(
            'babel-public-css',
            BD_URL . 'assets/css/babel-public.css',
            array(),
            BD_VERSION
        );

        // Registrar el script de control público (Vanilla JS / Modern React-friendly)
        wp_register_script( 
            'babel-public-js', 
            BD_URL . 'assets/js/babel-public.js', 
            array(), 
            BD_VERSION, 
            true 
        );

        // Pasar variables de forma segura desde el backend a JavaScript
        wp_localize_script( 'babel-public-js', 'babel_vars', array(
            'ajax_url'         => admin_url( 'admin-ajax.php' ), // Para retrocompatibilidad
            'rest_url'         => esc_url_raw( rest_url( 'babel/v1/search' ) ), // Nueva API REST
            'nonce'            => wp_create_nonce( 'babel_search_nonce' ),
            'submission_nonce' => wp_create_nonce( 'babel_submission_nonce' ),
            'ajaxUrl'          => admin_url( 'admin-ajax.php' ), // alias para form-submission.js
        ) );
    }

    /**
     * Callback para el shortcode [babel_search_form].
     * Renderiza el formulario de búsqueda pura y semántica para integración con Divi 5.
     *
     * @return string HTML renderizado del formulario.
     */
    public function render_search_form( $atts ) {
        $atts = shortcode_atts( array(
            'action' => home_url( '/buscar/' ),
        ), $atts, 'babel_search_form' );

        // Encolar los assets en caliente solo si este shortcode es renderizado
        wp_enqueue_style( 'babel-public-css' );
        wp_enqueue_script( 'babel-public-js' );

        ob_start();
        ?>
        <form id="babel-search-form" class="babel-search-form-wrapper" method="get" action="<?php echo esc_url( $atts['action'] ); ?>">
            
            <!-- Campo de Texto para Búsqueda Única e Inteligente -->
            <div class="babel-search-field babel-search-keyword-wrapper">
                <label for="babel-search-keyword" class="screen-reader-text"><?php esc_html_e( 'Búsqueda inteligente', 'babel-directory' ); ?></label>
                <div class="babel-input-icon-wrapper">
                    <svg class="babel-input-icon" viewBox="0 0 24 24" width="18" height="18">
                        <path fill="currentColor" d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
                    </svg>
                    <input 
                        type="text" 
                        name="keyword" 
                        id="babel-search-keyword" 
                        placeholder="<?php esc_attr_e( '¿Qué buscas y dónde? (ej. Sushi, Abogado, Providencia...)', 'babel-directory' ); ?>" 
                        value="" 
                    />
                </div>
            </div>

            <!-- Radar GPS de Proximidad Integrado de Forma Discreta -->
            <div class="babel-search-field babel-search-radar-wrapper" style="flex: 0 0 auto; min-width: auto; max-width: 60px;">
                <div class="babel-radar-control-group">
                    <button type="button" id="babel-geo-btn" class="babel-radar-btn" title="<?php esc_attr_e( 'Buscar cerca de mí (GPS)', 'babel-directory' ); ?>">
                        <svg class="radar-svg" viewBox="0 0 24 24" width="18" height="18">
                            <path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17.93c-3.95-.49-7-3.54-7.49-7.49H9c.46 2.28 2.22 4.04 4.5 4.5v2.99zm1-2.99c2.28-.46 4.04-2.22 4.5-4.5h3.43c-.49 3.95-3.54 7-7.49 7.49v-2.99zm5.93-8.94H17.5c-.46-2.28-2.22-4.04-4.5-4.5V2.07c3.95.49 7 3.54 7.49 7.49zM11 2.07v2.99C8.72 5.52 6.96 7.28 6.5 9.56H3.07c.49-3.95 3.54-7 7.49-7.49z"/>
                        </svg>
                        <span class="radar-ripple"></span>
                    </button>
                </div>
                <input type="hidden" id="babel-search-lat" name="lat" value="" />
                <input type="hidden" id="babel-search-lng" name="lng" value="" />
                <input type="hidden" id="babel-search-radius" name="radius" value="25" />
            </div>

            <!-- Botón de Envío -->
            <div class="babel-search-submit-wrapper">
                <button type="submit" id="babel-search-submit" class="babel-search-submit-btn">
                    <?php esc_html_e( 'Buscar', 'babel-directory' ); ?>
                </button>
            </div>
        </form>
        <?php
        return ob_get_clean();
    }

    /**
     * Callback para el shortcode [babel_results].
     * Renderiza el contenedor estructural puro donde se inyectarán los resultados vía AJAX.
     *
     * @return string Contenedor HTML estructural.
     */
    public function render_results_container() {
        // Encolar los assets en caliente por si el formulario no está en la misma página
        wp_enqueue_style( 'babel-public-css' );
        wp_enqueue_script( 'babel-public-js' );

        return '<div id="babel-directory-results" class="babel-results-container"></div>';
    }

}
