<?php
/**
 * Plugin Name: Babel Directory
 * Description: Plugin de estructuración de datos para el directorio de Negocios en WordPress. CPT, Taxonomías y Metaboxes nativas para administración exclusiva desde el backend.
 * Version: 7.0.8
 * Author: Babel13 MKT
 * Text Domain: babel-directory
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}

// Definir constantes globales de la arquitectura v7.0.0+
define( 'BD_VERSION', '7.0.8' );
define( 'BD_PATH', plugin_dir_path( __FILE__ ) );
define( 'BD_URL', plugin_dir_url( __FILE__ ) );

/**
 * Clase principal de inicialización: Babel_Directory_Core (Pattern Singleton).
 * Coordina la carga segura de la arquitectura desacoplada del plugin.
 */
class Babel_Directory_Core {

    /**
     * Instancia única de la clase.
     *
     * @var Babel_Directory_Core|null
     */
    private static $instance = null;

    /**
     * Retorna la instancia única del plugin de forma segura.
     *
     * @return Babel_Directory_Core
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado de seguridad para prevenir instanciación externa.
     */
    private function __construct() {
        $this->includes();
        $this->init_components();
    }

    /**
     * Inclusión segura y acoplada de todos los archivos core de la arquitectura.
     */
    private function includes() {
        // Estructura de Datos (CPT y Metaboxes)
        require_once BD_PATH . 'includes/class-cpt.php';
        require_once BD_PATH . 'includes/class-metaboxes.php';

        // Componentes de Lógica, Procesamiento y Calificaciones
        require_once BD_PATH . 'includes/class-search-index.php';
        require_once BD_PATH . 'includes/class-reviews.php';
        require_once BD_PATH . 'includes/class-submission.php';
        require_once BD_PATH . 'includes/class-ajax.php';
        require_once BD_PATH . 'includes/class-admin.php';
        require_once BD_PATH . 'includes/class-assets.php';
        require_once BD_PATH . 'includes/class-taxonomy-images.php';
        require_once BD_PATH . 'includes/class-shortcodes.php';
    }

    /**
     * Instanciación y desacople de los componentes estructurales del plugin.
     * Protegido dinámicamente mediante verificaciones de existencia de clases.
     */
    private function init_components() {
        // 1. Estructura de Datos: CPT (Custom Post Types & Taxonomías)
        if ( class_exists( 'Babel_Directory_CPT' ) ) {
            new Babel_Directory_CPT();
        }

        // 2. Estructura de Datos: Metaboxes de Datos de Negocios
        if ( class_exists( 'Babel_Directory_Metaboxes' ) ) {
            new Babel_Directory_Metaboxes();
        }

        // 3. Motor de Indexación Rápida
        if ( class_exists( 'Babel_Directory_Search_Index' ) ) {
            new Babel_Directory_Search_Index();
        }

        // 4. Control de Consultas Asíncronas AJAX
        if ( class_exists( 'Babel_Directory_Ajax' ) ) {
            new Babel_Directory_Ajax();
        }

        // 5. Panel de Administración y Registro de Menús/Settings API
        if ( class_exists( 'Babel_Directory_Admin' ) ) {
            new Babel_Directory_Admin();
        }

        // 6. Gestión de Assets Públicos y Shortcodes de Presentación
        if ( class_exists( 'Babel_Directory_Assets' ) ) {
            new Babel_Directory_Assets();
        }

        // 7. Sistema de Gestión de Reseñas y Calificaciones
        if ( class_exists( 'Babel_Directory_Reviews' ) ) {
            new Babel_Directory_Reviews();
        }

        // 8. Procesamiento Seguro de Envío de Negocios desde el Frontend
        if ( class_exists( 'Babel_Directory_Submission' ) ) {
            new Babel_Directory_Submission();
        }

        // 9. Soporte de Imágenes en Taxonomías
        if ( class_exists( 'BD_Taxonomy_Images' ) ) {
            new BD_Taxonomy_Images();
        }

        // 10. Shortcodes de Presentación (Radar y Grilla de Regiones)
        if ( class_exists( 'Babel_Directory_Shortcodes' ) ) {
            new Babel_Directory_Shortcodes();
        }
    }
}

/**
 * Hook de Activación de Seguridad:
 * Vincula el método estático de creación de la tabla física de búsquedas rápidas.
 * Se ejecuta exclusivamente al activar el plugin para garantizar integridad.
 */
register_activation_hook( __FILE__, array( 'Babel_Directory_Search_Index', 'create_table' ) );

/**
 * Inicializa y retorna la instancia principal de la arquitectura del plugin.
 *
 * @return Babel_Directory_Core
 */
function babel_directory_init() {
    return Babel_Directory_Core::get_instance();
}

// Arrancar el plugin de forma segura
babel_directory_init();
