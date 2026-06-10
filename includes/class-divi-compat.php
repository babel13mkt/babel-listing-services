<?php
namespace Babel\Directory;

/**
 * PROTOCOLO DE COMPATIBILIDAD DE TEMAS — BABEL DIRECTORY
 * ========================================================
 * 
 * REGLA DE ORO: NUNCA llames wp_enqueue_style() o wp_enqueue_script()
 * dentro de un callback de shortcode. En Divi 5 (y Elementor, Avada, Beaver),
 * los shortcodes se renderizan DESPUÉS de wp_head(), por lo que los assets 
 * encolados en esa fase NUNCA llegan al <head>.
 * 
 * REGLA PARA NUEVOS SHORTCODES:
 * 1. Registrar assets SIEMPRE en wp_enqueue_scripts (via class-assets.php)
 * 2. Si el shortcode usa clases CSS nuevas con flexbox/grid, agregar sus 
 *    overrides en el método get_compat_css() de ESTA clase.
 * 3. Usar el atributo data-babel-{componente}="true" en el wrapper HTML
 *    del shortcode para anclaje de selectores de alta especificidad.
 * 
 * REGLA DE ESPECIFICIDAD:
 * Siempre escribir CSS en 3 capas:
 *   Capa 1: .mi-clase-babel { ... }                          (0,1,0)
 *   Capa 2: #mi-form [data-babel-componente="true"] { ... }  (1,1,0)  
 *   Capa 3: #et-main-area #mi-form [data-babel-...] { ... }  (2,1,0)
 * 
 * COMPATIBILIDAD VERIFICADA CON:
 *   - Divi 5.x (via class-divi-compat.php)
 *   - WordPress Gutenberg (nativo, sin overrides necesarios)
 *   - Temas clásicos sin framework CSS (nativo)
 * 
 * PENDIENTE DE IMPLEMENTAR:
 *   - Elementor Pro (crear class-elementor-compat.php con misma arquitectura)
 *   - Beaver Builder (idem)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Divi_Compat {

    public function __construct() {
        if ( ! $this->is_divi_active() ) {
            return;
        }

        if ( ! defined( 'BD_DIVI_COMPAT_ACTIVE' ) ) {
            define( 'BD_DIVI_COMPAT_ACTIVE', true );
        }

        // Si el Divi Builder está activo, pausar el renderizado de shortcodes pesados
        if ( $this->is_divi_builder_active() ) {
            add_action( 'init', [ $this, 'register_dummy_shortcodes' ], 999 );
        }

        add_action( 'wp_enqueue_scripts', [ $this, 'force_enqueue_assets' ], 99 );
        add_action( 'wp_enqueue_scripts', [ $this, 'inject_compat_css' ], 100 );
        add_action( 'wp_head', [ $this, 'remove_divi_input_styles' ], 999 );
    }

    private function is_divi_active() {
        $is_divi_theme = function_exists( 'wp_get_theme' ) && 'Divi' === wp_get_theme()->get( 'Name' );
        $is_divi_builder = defined( 'ET_CORE_VERSION' );
        $has_divi_functions = function_exists( 'et_divi_fonts_url' );

        return $is_divi_theme || $is_divi_builder || $has_divi_functions;
    }

    private function is_divi_builder_active() {
        return ( isset( $_GET['et_fb'] ) && '1' === $_GET['et_fb'] ) || 
               ( isset( $_POST['action'] ) && 'et_fb_retrieve_builder_data' === $_POST['action'] ) || 
               ( function_exists( 'et_core_is_fb_enabled' ) && et_core_is_fb_enabled() );
    }

    public function register_dummy_shortcodes() {
        $dummy_render = function( $atts, $content, $tag ) {
            return '<div style="padding:20px;background:#f5f5f5;border:2px dashed #ccc;text-align:center;color:#666;">[' . esc_html( $tag ) . ': Renderizado pausado en Divi Builder]</div>';
        };

        $tags = [
            'babel_region_grid',
            'bd_footer_regions',
            'bd_footer_categories',
            'bd_archive_loop',
            'bd_region_template',
            'bd_business_profile',
            'bd_filter_bar'
        ];

        foreach ( $tags as $tag ) {
            add_shortcode( $tag, $dummy_render );
        }
    }

    public function force_enqueue_assets() {
        wp_enqueue_style( 'babel-public-css' );
    }

    public function inject_compat_css() {
        wp_add_inline_style( 'babel-public-css', $this->get_compat_css() );
    }

    public function remove_divi_input_styles() {
        echo '<style id="bd-divi-compat-overrides">' . "\n" . $this->get_compat_css() . "\n" . '</style>';
    }

    private function get_compat_css() {
        return '
/* ================================================================
   BABEL DIRECTORY — Capa de Compatibilidad Divi 5
   Generado por: Babel\Directory\Divi_Compat
   Principio: Especificidad escalonada en 3 capas.
   NO editar directamente. Modificar get_compat_css() en class-divi-compat.php
   ================================================================ */

/* ──────────────────────────────────────────────────────────────
   CAPA 1 (especificidad 0,1,0): Clase estándar — funciona sin Divi
   ────────────────────────────────────────────────────────────── */
.babel-filter-bar-inner {
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    flex-wrap: nowrap !important;
}
.sdc-grid-archive {
    display: grid !important;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)) !important;
}
.babel-biz-card {
    display: flex !important;
    flex-direction: column !important;
}
.bd-category-pills {
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: wrap !important;
}

/* ──────────────────────────────────────────────────────────────
   CAPA 2 (especificidad 1,1,0): ID del form + atributo de datos
   ────────────────────────────────────────────────────────────── */
#babel-search-form [data-babel-filter="true"] {
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    flex-wrap: nowrap !important;
    gap: 12px !important;
    width: 100% !important;
    box-sizing: border-box !important;
}
#babel-search-form [data-babel-filter="true"] input,
#babel-search-form [data-babel-filter="true"] input[type="text"],
#babel-search-form [data-babel-filter="true"] input[type="search"],
#babel-search-form [data-babel-filter="true"] select {
    display: block !important;
    height: auto !important;
    width: auto !important;
    float: none !important;
    clear: none !important;
    border: none !important;
    background: transparent !important;
    box-shadow: none !important;
    border-radius: 0 !important;
    -webkit-appearance: none !important;
    appearance: none !important;
    padding: 10px 0 !important;
    margin: 0 !important;
    line-height: normal !important;
}

/* ──────────────────────────────────────────────────────────────
   CAPA 3 (especificidad 2,1,0 a 2,2,1): Nuclear — con IDs de Divi
   ────────────────────────────────────────────────────────────── */
#et-main-area #babel-search-form [data-babel-filter="true"],
#et-boc #babel-search-form [data-babel-filter="true"],
#et_builder_outer_content #babel-search-form [data-babel-filter="true"],
.et_pb_section #babel-search-form [data-babel-filter="true"],
.et_pb_row #babel-search-form [data-babel-filter="true"],
.et_pb_column #babel-search-form [data-babel-filter="true"] {
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    flex-wrap: nowrap !important;
    gap: 12px !important;
    width: 100% !important;
}

#et-main-area #babel-search-form [data-babel-filter="true"] input,
#et-boc #babel-search-form [data-babel-filter="true"] input,
#et_builder_outer_content #babel-search-form [data-babel-filter="true"] input,
.et_pb_section #babel-search-form [data-babel-filter="true"] input,
#et-main-area #babel-search-form [data-babel-filter="true"] select,
#et-boc #babel-search-form [data-babel-filter="true"] select,
#et_builder_outer_content #babel-search-form [data-babel-filter="true"] select,
.et_pb_section #babel-search-form [data-babel-filter="true"] select {
    display: block !important;
    width: auto !important;
    height: auto !important;
    float: none !important;
    clear: none !important;
    border: none !important;
    background: transparent !important;
    box-shadow: none !important;
}

#et-main-area .sdc-grid-archive,
#et-boc .sdc-grid-archive,
.et_pb_section .sdc-grid-archive,
.et_pb_row .sdc-grid-archive {
    display: grid !important;
    width: 100% !important;
}

#et-main-area .babel-biz-card,
#et-boc .babel-biz-card,
.et_pb_section .babel-biz-card {
    display: flex !important;
    flex-direction: column !important;
    height: 100% !important;
}

#et-main-area .bd-category-pills,
#et-boc .bd-category-pills,
.et_pb_section .bd-category-pills {
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: wrap !important;
}

/* ──────────────────────────────────────────────────────────────
   RESPONSIVE: En mobile el buscador SÍ colapsa a columna (intencional)
   ────────────────────────────────────────────────────────────── */
@media (max-width: 768px) {
    .babel-filter-bar-inner,
    #babel-search-form [data-babel-filter="true"],
    #et-main-area #babel-search-form [data-babel-filter="true"],
    #et-boc #babel-search-form [data-babel-filter="true"] {
        flex-direction: column !important;
        align-items: stretch !important;
    }
}';
    }
}
