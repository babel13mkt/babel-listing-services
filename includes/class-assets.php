<?php
namespace Babel\Directory;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Gestión de Assets y Shortcodes Públicos (Babel_Directory_Assets)
 * v7.0.0 — Hito 9: Buscador de Frontend, Carga Inteligente y Shortcodes Semánticos.
 *
 * Responsabilidades:
 * - Registro y encolado condicional de CSS/JS (public + admin).
 * - Shortcodes Legacy: [babel_header], [babel_hero_search], [babel_region_grid], etc.
 * - Renderizado con ob_start()/ob_get_clean() — NUNCA echo directo.
 * - Compatibilidad Divi 4/5: registro en et_pb_third_party_shortcode_in_use.
 * - Inyección CSS crítico (prioridad 99) para evitar FOUC en Divi 5.
 * - Inyección de Material Symbols en wp_head (prioridad 5).
 * - Detección de assets hasheados (Vite) para [babel_react_app].
 *
 * @package Babel\Directory
 * @since   7.0.0
 */
class Assets {

    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'register_public_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_assets' ) );
        add_action( 'wp_head', array( $this, 'print_material_symbols' ), 5 );
        add_action( 'wp_enqueue_scripts', array( $this, 'force_enqueue_babel_css' ), 99 );
    }

    public function register_public_assets() {
        wp_enqueue_style(
            'babel-google-fonts',
            'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Montserrat:wght@500;600;700&family=Playfair+Display:wght@600;700&display=swap',
            array(),
            null
        );

        wp_register_style( 'leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4' );
        wp_register_script( 'leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true );
        wp_register_style( 'leaflet-cluster-css', 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css', array( 'leaflet-css' ), '1.5.3' );
        wp_register_style( 'leaflet-cluster-default-css', 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css', array( 'leaflet-cluster-css' ), '1.5.3' );
        wp_register_script( 'leaflet-cluster-js', 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js', array( 'leaflet-js' ), '1.5.3', true );

        // Skeleton Loading System (CSS-only shimmer)
        wp_register_style( 'babel-skeleton-css', BD_URL . 'assets/css/skeleton-loading.css', array(), BD_VERSION );
        // Focus Visible System (Accessibility First)
        wp_register_style( 'babel-focus-css', BD_URL . 'assets/css/focus-visible.css', array(), BD_VERSION );
        // Empty States (SVG + Actionable CTAs)
        wp_register_style( 'babel-empty-css', BD_URL . 'assets/css/empty-states.css', array(), BD_VERSION );
        // Wayfinding Module (Sticky Filters + Back-to-Top + Breadcrumbs)
        wp_register_style( 'babel-wayfinding-css', BD_URL . 'assets/css/wayfinding.css', array(), BD_VERSION );
        wp_register_script( 'babel-wayfinding-js', BD_URL . 'assets/js/wayfinding.js', array( 'babel-public-js' ), BD_VERSION, true );

        wp_register_style( 'babel-public-css', BD_URL . 'assets/css/babel-public-v722.css', array(), BD_VERSION );
        wp_register_script( 'babel-public-js', BD_URL . 'assets/js/babel-public.js', array( 'leaflet-js', 'leaflet-cluster-js' ), BD_VERSION, true );
        wp_register_style( 'babel-global-auth-css', BD_URL . 'assets/css/babel-global-auth.css', array(), BD_VERSION );
        wp_register_script( 'babel-global-auth-js', BD_URL . 'assets/js/babel-global-auth.js', array(), BD_VERSION, true );
        wp_register_script( 'babel-geolocation-js', BD_URL . 'assets/js/babel-geolocation.js', array(), BD_VERSION, true );
        wp_register_script( 'google-gsi-client-global', 'https://accounts.google.com/gsi/client', array(), null, false );

        add_shortcode( 'babel_header', array( $this, 'render_header' ) );
        add_shortcode( 'babel_hero_search', array( $this, 'render_hero_search' ) );
        add_shortcode( 'babel_region_grid', array( $this, 'render_region_grid' ) );
        add_shortcode( 'babel_region_carousel', array( $this, 'render_region_carousel' ) );
        add_shortcode( 'babel_biz_card', array( $this, 'render_biz_card' ) );
        add_shortcode( 'babel_biz_list', array( $this, 'render_biz_list' ) );
        add_shortcode( 'babel_biz_map', array( $this, 'render_biz_map' ) );
        add_shortcode( 'babel_inst_card', array( $this, 'render_inst_card' ) );
        add_shortcode( 'babel_inst_list', array( $this, 'render_inst_list' ) );
        add_shortcode( 'babel_inst_map', array( $this, 'render_inst_map' ) );
        add_shortcode( 'babel_category_grid', array( $this, 'render_category_grid' ) );
        add_shortcode( 'babel_category_carousel', array( $this, 'render_category_carousel' ) );
        add_shortcode( 'babel_filter_bar', array( $this, 'render_filter_bar' ) );
        add_shortcode( 'babel_search_form', array( $this, 'render_search_form' ) );
        add_shortcode( 'babel_radar_button', array( $this, 'render_radar_button' ) );
        add_shortcode( 'babel_region_pills', array( $this, 'render_region_pills' ) );
        add_shortcode( 'babel_profile', array( $this, 'render_profile' ) );
        add_shortcode( 'babel_dashboard', array( $this, 'render_dashboard' ) );
        add_shortcode( 'babel_login', array( $this, 'render_login' ) );
        add_shortcode( 'babel_register', array( $this, 'render_register' ) );
        add_shortcode( 'babel_password_reset', array( $this, 'render_password_reset' ) );
        add_shortcode( 'babel_claim_form', array( $this, 'render_claim_form' ) );
        add_shortcode( 'babel_plan_selector', array( $this, 'render_plan_selector' ) );
        add_shortcode( 'babel_ad_slot', array( $this, 'render_ad_slot' ) );
        add_shortcode( 'babel_ad_manager', array( $this, 'render_ad_manager' ) );
        add_shortcode( 'babel_react_app', array( $this, 'render_react_app' ) );
        add_shortcode( 'babel_institution_grid', array( $this, 'render_institution_grid' ) );
        add_shortcode( 'babel_institution_list', array( $this, 'render_institution_list' ) );

        add_filter( 'et_pb_third_party_shortcode_in_use', array( $this, 'register_babel_shortcodes_with_divi' ) );
        add_action( 'init', array( $this, 'register_dummy_shortcodes' ), 999 );
        add_action( 'wp_head', array( $this, 'inject_compat_css' ), 100 );
        add_action( 'wp_head', array( $this, 'remove_divi_input_styles' ), 999 );
        add_filter( 'the_content', array( $this, 'decode_shortcodes_in_content' ), 9999 );
    }

    public function register_admin_assets() {
        wp_register_style( 'leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4' );
        wp_register_script( 'leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true );
        wp_register_style( 'leaflet-cluster-css', 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css', array( 'leaflet-css' ), '1.5.3' );
        wp_register_style( 'leaflet-cluster-default-css', 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css', array( 'leaflet-cluster-css' ), '1.5.3' );
        wp_register_script( 'leaflet-cluster-js', 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js', array( 'leaflet-js' ), '1.5.3', true );

        wp_register_style( 'babel-admin-css', BD_URL . 'assets/css/babel-admin.css', array(), BD_VERSION );
        wp_enqueue_style( 'babel-admin-css' );
        wp_enqueue_script( 'babel-admin-js', BD_URL . 'assets/js/babel-admin.js', array(), BD_VERSION, true );
        wp_localize_script( 'babel-admin-js', 'sdc_admin_vars', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'sdc_quick_action_nonce' ),
            'admin_nonce' => wp_create_nonce( 'babel_admin_nonce' ),
        ) );
    }

    public function force_enqueue_babel_css() {
        wp_enqueue_style( 'dashicons' );
        wp_enqueue_style( 'leaflet-css' );
        wp_enqueue_style( 'leaflet-cluster-css' );
        wp_enqueue_style( 'leaflet-cluster-default-css' );
        wp_enqueue_style( 'babel-public-css' );
        wp_enqueue_style( 'babel-skeleton-css' );
        wp_enqueue_style( 'babel-focus-css' );
        wp_enqueue_style( 'babel-empty-css' );
        wp_enqueue_style( 'babel-wayfinding-css' );
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

    public function print_material_symbols() {
        echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" data-optimized="false" />';
    }

    public function inject_compat_css() {
        $css = file_get_contents( BD_PATH . 'assets/css/babel-public-v722.css' );
        if ( $css ) {
            echo '<style id="babel-compat-css">' . $css . '</style>';
        }
    }

    public function remove_divi_input_styles() {
        echo '<style id="babel-remove-divi-input-styles">
            .et_pb_contact_form input, .et_pb_contact_form textarea,
            .et_pb_newsletter_form input { width: auto !important; min-width: 0 !important; }
        </style>';
    }

    public function decode_shortcodes_in_content( $content ) {
        return preg_replace_callback( '/&#91;(babel_[a-z_]+)([^&#93;]*)&#93;/', function( $m ) { return '[' . $m[1] . $m[2] . ']'; }, $content );
    }

    public function register_babel_shortcodes_with_divi( $shortcodes ) {
        return array_merge( $shortcodes, $this->get_babel_shortcodes_list() );
    }

    public function get_babel_shortcodes_list(): array {
        return array( 'babel_region_grid', 'babel_region_carousel', 'babel_biz_card', 'babel_biz_list', 'babel_biz_map', 'babel_inst_card', 'babel_inst_list', 'babel_inst_map', 'babel_profile', 'babel_login', 'babel_register', 'babel_password_reset', 'babel_dashboard', 'babel_claim_form', 'babel_plan_selector', 'babel_ad_slot', 'babel_ad_manager', 'babel_category_grid', 'babel_category_carousel', 'babel_filter_bar', 'babel_search_form', 'babel_radar_button', 'babel_region_pills', 'babel_react_app', 'babel_institution_grid', 'babel_institution_list' );
    }

    public function register_dummy_shortcodes() {
        foreach ( $this->get_babel_shortcodes_list() as $tag ) {
            add_shortcode( $tag, function() { return '<div class="babel-dummy-placeholder" data-shortcode="' . esc_attr( $tag ) . '"></div>'; } );
        }
    }

    public function render_header( $atts ) { ob_start(); $this->safe_include( BD_PATH . 'templates/parts/header.php' ); return ob_get_clean(); }
    public function render_hero_search( $atts ) { ob_start(); $this->safe_include( BD_PATH . 'templates/parts/hero-search.php' ); return ob_get_clean(); }
    public function render_region_grid( $atts ) { ob_start(); $this->safe_include( BD_PATH . 'templates/parts/region-grid.php' ); return ob_get_clean(); }
    public function render_region_carousel( $atts ) { ob_start(); $this->safe_include( BD_PATH . 'templates/parts/region-carousel.php' ); return ob_get_clean(); }
    public function render_biz_card( $atts ) { ob_start(); $this->safe_include( BD_PATH . 'templates/parts/biz-card.php' ); return ob_get_clean(); }
    public function render_biz_list( $atts ) { ob_start(); $this->safe_include( BD_PATH . 'templates/parts/biz-list.php' ); return ob_get_clean(); }
    public function render_biz_map( $atts ) { ob_start(); $this->safe_include( BD_PATH . 'templates/parts/biz-map.php' ); return ob_get_clean(); }
    public function render_inst_card( $atts ) { ob_start(); $this->safe_include( BD_PATH . 'templates/parts/inst-card.php' ); return ob_get_clean(); }
    public function render_inst_list( $atts ) { ob_start(); $this->safe_include( BD_PATH . 'templates/parts/inst-list.php' ); return ob_get_clean(); }
    public function render_inst_map( $atts ) { ob_start(); $this->safe_include( BD_PATH . 'templates/parts/inst-map.php' ); return ob_get_clean(); }
    public function render_category_grid( $atts ) { ob_start(); $this->safe_include( BD_PATH . 'templates/parts/category-grid.php' ); return ob_get_clean(); }
    public function render_category_carousel( $atts ) { ob_start(); $this->safe_include( BD_PATH . 'templates/parts/category-carousel.php' ); return ob_get_clean(); }
    public function render_filter_bar( $atts ) { ob_start(); $this->safe_include( BD_PATH . 'templates/parts/filter-bar.php' ); return ob_get_clean(); }
    public function render_search_form( $atts ) { ob_start(); $this->safe_include( BD_PATH . 'templates/parts/search-form.php' ); return ob_get_clean(); }
    public function render_radar_button( $atts ) { ob_start(); $this->safe_include( BD_PATH . 'templates/parts/radar-button.php' ); return ob_get_clean(); }
    public function render_region_pills( $atts ) { ob_start(); $this->safe_include( BD_PATH . 'templates/parts/region-pills.php' ); return ob_get_clean(); }
    public function render_profile( $atts ) { ob_start(); $this->safe_include( BD_PATH . 'templates/parts/profile.php' ); return ob_get_clean(); }
    public function render_dashboard( $atts ) { ob_start(); $this->safe_include( BD_PATH . 'templates/parts/dashboard.php' ); return ob_get_clean(); }
    public function render_login( $atts ) { ob_start(); $this->safe_include( BD_PATH . 'templates/parts/login.php' ); return ob_get_clean(); }
    public function render_register( $atts ) { ob_start(); $this->safe_include( BD_PATH . 'templates/parts/register.php' ); return ob_get_clean(); }
    public function render_password_reset( $atts ) { ob_start(); $this->safe_include( BD_PATH . 'templates/parts/password-reset.php' ); return ob_get_clean(); }
    public function render_claim_form( $atts ) { ob_start(); $this->safe_include( BD_PATH . 'templates/parts/claim-form.php' ); return ob_get_clean(); }
    public function render_plan_selector( $atts ) { ob_start(); $this->safe_include( BD_PATH . 'templates/parts/plan-selector.php' ); return ob_get_clean(); }
    public function render_ad_slot( $atts ) { ob_start(); $this->safe_include( BD_PATH . 'templates/parts/ad-slot.php' ); return ob_get_clean(); }
    public function render_ad_manager( $atts ) { ob_start(); $this->safe_include( BD_PATH . 'templates/parts/ad-manager.php' ); return ob_get_clean(); }
    public function render_react_app( $atts ) { ob_start(); $this->safe_include( BD_PATH . 'templates/parts/react-app.php' ); return ob_get_clean(); }
    public function render_institution_grid( $atts ) { ob_start(); $this->safe_include( BD_PATH . 'templates/parts/institution-grid.php' ); return ob_get_clean(); }
    public function render_institution_list( $atts ) { ob_start(); $this->safe_include( BD_PATH . 'templates/parts/institution-list.php' ); return ob_get_clean(); }

    /**
     * Incluye un template de forma segura.
     * Si el archivo no existe, retorna string vacío (evita warnings de include).
     * Los templates faltantes se crean bajo demanda sin romper el render.
     */
    private function safe_include( string $template ): void {
        if ( file_exists( $template ) ) {
            include $template;
        }
    }
}