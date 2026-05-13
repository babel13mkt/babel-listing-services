<?php
/**
 * Gestión de Assets (BD_Assets)
 * v3.3.0 — Hito 14: App Mode y Media Enqueue.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BD_Assets {

    public function __construct() {
        add_action( 'wp_enqueue_scripts',    array( $this, 'enqueue_frontend_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    public function enqueue_frontend_assets() {
        // CSS Principal (Design System bd-)
        wp_enqueue_style(
            'bd-main-style',
            BD_URL . 'assets/css/style.css',
            array(),
            BD_VERSION
        );

        // Google Fonts (Premium: Inter)
        wp_enqueue_style(
            'bd-fonts',
            'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap',
            array(),
            null
        );

        // FontAwesome
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
            array(),
            '6.0.0'
        );

        // Leaflet (Maps)
        if ( is_post_type_archive( 'directorio_negocio' ) || is_tax( 'directorio_categoria' ) || is_tax( 'directorio_region' ) || is_singular( 'directorio_negocio' ) ) {
            wp_enqueue_style( 'leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4' );
            wp_enqueue_script( 'leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true );
        }

        // AJAX Filters Script
        if ( ! is_singular( 'directorio_negocio' ) ) {
            wp_enqueue_script(
                'bd-filters',
                BD_URL . 'assets/js/filters.js',
                array( 'jquery' ),
                BD_VERSION,
                true
            );
        }

        // Single Listing Script
        if ( is_singular( 'directorio_negocio' ) ) {
            wp_enqueue_script(
                'bd-single-js',
                BD_URL . 'assets/js/single-listing.js',
                array( 'jquery', 'leaflet-js' ),
                BD_VERSION,
                true
            );
        }

        // Registro de Script Core para Localización (Fix Hito 5)
        wp_register_script( 'bd-core-vars', '', array(), BD_VERSION, true );
        wp_enqueue_script( 'bd-core-vars' );

        wp_localize_script( 'bd-core-vars', 'bd_vars', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'bd_ajax_nonce' ),
        ) );
    }

    /**
     * Admin CSS — se carga en todo el CPT + páginas del submenú bd-*
     */
    public function enqueue_admin_assets( $hook ) {
        $screen  = get_current_screen();
        $bd_cpt  = $screen && $screen->post_type === 'directorio_negocio';
        $bd_page = isset( $_GET['page'] ) && strpos( sanitize_key( wp_unslash( $_GET['page'] ) ), 'bd-' ) === 0;

        if ( ! $bd_cpt && ! $bd_page ) {
            return;
        }

        // Cargar Multimedia de WP para nuestra interfaz
        if ( $bd_cpt || $bd_page ) {
            wp_enqueue_media();
        }

        wp_enqueue_style(
            'bd-admin-style',
            BD_URL . 'assets/css/admin.css',
            array(),
            BD_VERSION
        );
    }
}
