<?php
namespace Babel\Directory;

/**
 * Gestión de Assets y Shortcodes Públicos (Babel_Directory_Assets)
 * v7.0.0 — Hito 9: Buscador de Frontend, Carga Inteligente y Shortcodes Semánticos.
 *
 * @package Babel_Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}
class Assets {

    /**
     * Constructor de la clase.
     * Registra los ganchos de encolado y shortcodes.
     */
    public function __construct() {
        // Registrar assets públicos en el frontend
        add_action( 'wp_enqueue_scripts', array( $this, 'register_public_assets' ) );

        // CRÍTICO Divi 5: Encolar babel-public-css GLOBALMENTE aquí,
        // no dentro del callback del shortcode. Divi 5 ejecuta los shortcodes
        // DESPUÉS de wp_head, por lo que cualquier wp_enqueue_style() dentro
        // de un shortcode llega demasiado tarde y el CSS nunca aparece en <head>.
        add_action( 'wp_enqueue_scripts', array( $this, 'force_enqueue_babel_css' ), 99 );

        // Registrar los shortcodes oficiales del buscador
        add_shortcode( 'babel_search_form', array( $this, 'render_search_form' ) );
        add_shortcode( 'babel_results', array( $this, 'render_results_container' ) );
    }

    /**
     * Registra los scripts y estilos públicos del buscador.
     * No se encolan globalmente, sino que se registran para encolado inteligente.
     */
    public function register_public_assets() {
        // Encolar fuentes del sistema de diseño Stitch globalmente
        wp_enqueue_style(
            'babel-google-fonts',
            'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Montserrat:wght@500;600;700&family=Playfair+Display:wght@600;700&display=swap',
            array(),
            null
        );

        wp_enqueue_style(
            'babel-material-symbols',
            'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap',
            array(),
            null
        );

        // Registrar la hoja de estilos pública con cache-busting físico en el nombre del archivo
        wp_register_style(
            'babel-public-css',
            BD_URL . 'assets/css/babel-public-v717.css',
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
     * Fuerza el encolado de babel-public-css GLOBALMENTE en wp_enqueue_scripts
     * con prioridad 99 (después de que Divi registra sus propios estilos).
     * Esto garantiza que el stylesheet llegue al <head> incluso en Divi 5,
     * donde los shortcodes se renderizan después del cierre de wp_head.
     */
    public function force_enqueue_babel_css() {
        wp_enqueue_style( 'babel-public-css' );
        wp_enqueue_script( 'babel-public-js' );
    }

    public function render_search_form( $atts ) {
        return do_shortcode( "[bd_filter_bar show_results='false']" );
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
