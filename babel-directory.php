<?php
/**
 * Plugin Name: Babel Directory
 * Description: Sistema de directorio premium inspirado en ListingHive para el mercado chileno.
 * Version: 3.4.0
 * Author: Babel13 MKT
 * Text Domain: babel-directory
 */

if ( ! defined( "ABSPATH" ) ) {
    exit;
}

// Definir constantes globales
define( "BD_VERSION", "3.4.0" );
define( "BD_PATH", plugin_dir_path( __FILE__ ) );
define( "BD_URL", plugin_dir_url( __FILE__ ) );

/**
 * Clase Maestra del Directorio (BD_Core)
 */
class BD_Core {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->includes();
        $this->init_components();
        $this->hooks();
        
        // Hook de activación
        register_activation_hook( __FILE__, array( $this, "activate" ) );
    }

    private function includes() {
        require_once BD_PATH . "includes/class-cpt.php";
        require_once BD_PATH . "includes/class-metaboxes.php";
        require_once BD_PATH . "includes/class-assets.php";
        require_once BD_PATH . "includes/class-frontend.php";
        require_once BD_PATH . "includes/class-ajax.php";
        require_once BD_PATH . "includes/class-templates.php";
        require_once BD_PATH . "includes/class-reviews.php";
        require_once BD_PATH . "includes/class-dashboard.php";
        require_once BD_PATH . "includes/class-search-index.php";
        require_once BD_PATH . "includes/class-submission.php";
        require_once BD_PATH . "includes/class-taxonomy-images.php";
    }

    private function init_components() {
        new BD_CPT();
        new BD_Metaboxes();
        new BD_Assets();
        new BD_Frontend();
        new BD_AJAX();
        new BD_Templates();
        new BD_Reviews();
        new BD_Dashboard();
        new BD_Search_Index();
        new BD_Submission();
        new BD_Taxonomy_Images();
    }

    private function hooks() {
        /**
         * Hito 15.7: Ordenar regiones alfabéticamente por nombre real,
         * ignorando el prefijo REG-N para la comparación.
         */
        add_filter("get_terms", function($terms, $taxonomies) {
            if (in_array("directorio_region", (array)$taxonomies) && is_array($terms) && !empty($terms)) {
                usort($terms, function($a, $b) {
                    if (!isset($a->name) || !isset($b->name)) return 0;
                    // Limpiamos el prefijo REG-X para comparar
                    $nameA = preg_replace("/^REG-[IVXLMCD]+\s+/", "", $a->name);
                    $nameB = preg_replace("/^REG-[IVXLMCD]+\s+/", "", $b->name);
                    return strcasecmp($nameA, $nameB);
                });
            }
            return $terms;
        }, 10, 2);
    }

    public function activate() {
        $cpt = new BD_CPT();
        $cpt->register_listing_cpt();
        $cpt->register_listing_taxonomy();
        $index = new BD_Search_Index();
        $index->create_table();
        new BD_Submission();
        new BD_Taxonomy_Images();
        flush_rewrite_rules();
    }
}

// Iniciar motor
function bd_run() {
    return BD_Core::get_instance();
}
bd_run();
