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

        // Registrar assets del admin SPA (Soy de Chile)
        add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_assets' ) );

        // CRÍTICO Divi 5: Encolar babel-public-css GLOBALMENTE aquí,
        // no dentro del callback del shortcode. Divi 5 ejecuta los shortcodes
        // DESPUÉS de wp_head, por lo que cualquier wp_enqueue_style() dentro
        // de un shortcode llega demasiado tarde y el CSS nunca aparece en <head>.
        add_action( 'wp_enqueue_scripts', array( $this, 'force_enqueue_babel_css' ), 99 );

        // Shortcodes legacy eliminados: babel_search_form, babel_results
        // Usar exclusivamente [bd_filter_bar show_results="yes"]
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

        // Registrar Leaflet.js para mapas
        wp_register_style(
            'leaflet-css',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            array(),
            '1.9.4'
        );

        wp_register_script(
            'leaflet-js',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
            array(),
            '1.9.4',
            true
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

        wp_register_style(
            'babel-global-auth-css',
            BD_URL . 'assets/css/babel-global-auth.css',
            array(),
            BD_VERSION
        );

        wp_register_script(
            'babel-global-auth-js',
            BD_URL . 'assets/js/babel-global-auth.js',
            array('jquery'),
            BD_VERSION,
            true
        );

        wp_register_script(
            'babel-geolocation-js',
            BD_URL . 'assets/js/babel-geolocation.js',
            array(),
            BD_VERSION,
            true
        );
        
        $client_id = get_option( 'babel_google_client_id', '' );
        if ( ! empty( $client_id ) ) {
            wp_register_script( 'google-gsi-client-global', 'https://accounts.google.com/gsi/client', array(), null, false );
        }
        $ms_client_id = get_option( 'babel_microsoft_client_id', '' );
        if ( ! empty( $ms_client_id ) ) {
            wp_register_script( 'msal-browser', 'https://alcdn.msauth.net/browser/2.30.0/js/msal-browser.min.js', array(), null, false );
        }

        $recaptcha_site_key = get_option( 'babel_recaptcha_site_key', '' );
        
        // Pasar variables de forma segura desde el backend a JavaScript
        wp_localize_script( 'babel-public-js', 'babel_vars', array(
            'ajax_url'         => admin_url( 'admin-ajax.php' ), // Para retrocompatibilidad
            'rest_url'         => esc_url_raw( rest_url( 'babel/v1/search' ) ), // Nueva API REST
            'nonce'            => wp_create_nonce( 'babel_search_nonce' ),
            'submission_nonce' => wp_create_nonce( 'babel_submission_nonce' ),
            'ajaxUrl'          => admin_url( 'admin-ajax.php' ), // alias para form-submission.js
            'google_login_nonce' => wp_create_nonce( 'babel_google_login_nonce' ),
            'microsoft_login_nonce' => wp_create_nonce( 'babel_microsoft_login_nonce' ),
            'recaptcha_site_key' => esc_js( $recaptcha_site_key ),
        ) );
    }

    /**
     * Registra los scripts y estilos para el panel de administración SPA "Soy de Chile".
     */
    public function register_admin_assets( $hook ) {
        // Solo cargar en nuestras páginas del plugin o CPT
        if ( strpos( $hook, 'bd-panel' ) === false && $hook !== 'post.php' && $hook !== 'post-new.php' ) {
            return;
        }

        wp_enqueue_style(
            'babel-admin-css',
            BD_URL . 'assets/css/babel-admin.css',
            array(),
            BD_VERSION
        );

        wp_enqueue_script(
            'babel-admin-js',
            BD_URL . 'assets/js/babel-admin.js',
            array(),
            BD_VERSION,
            true
        );

        // Pass ajaxurl and nonce to the admin JS
        wp_localize_script( 'babel-admin-js', 'sdc_admin_vars', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'sdc_quick_action_nonce' ),
            'admin_nonce' => wp_create_nonce( 'babel_admin_nonce' ),
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
        
        wp_enqueue_style( 'babel-global-auth-css' );
        wp_enqueue_script( 'babel-global-auth-js' );
        wp_enqueue_script( 'babel-geolocation-js' );
        
        $client_id = get_option( 'babel_google_client_id', '' );
        if ( ! empty( $client_id ) && ! is_user_logged_in() ) {
            wp_enqueue_script( 'google-gsi-client-global' );
        }
        
        $recaptcha_site_key = get_option( 'babel_recaptcha_site_key', '' );
        if ( ! empty( $recaptcha_site_key ) ) {
            wp_enqueue_script( 'google-recaptcha-v3-global', 'https://www.google.com/recaptcha/api.js?render=' . esc_attr( $recaptcha_site_key ), array(), null, false );
        }
    }

}
