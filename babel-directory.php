<?php
/**
 * Plugin Name: Babel Directory
 * Description: Plugin de estructuración de datos para el directorio de Negocios en WordPress. CPT, Taxonomías y Metaboxes nativas para administración exclusiva desde el backend.
 * Version: 7.1.1
 * Author: Babel13 MKT
 * Text Domain: babel-directory
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}

// Definir constantes globales de la arquitectura v7.0.0+
define( 'BD_VERSION', '7.1.1' );
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
        require_once BD_PATH . 'includes/autoloader.php';
        $this->init_components();
    }

    /**
     * Inclusión segura y acoplada de todos los archivos core de la arquitectura.
     */
    

    /**
     * Instanciación y desacople de los componentes estructurales del plugin.
     * Protegido dinámicamente mediante verificaciones de existencia de clases.
     */
    private function init_components() {
        // 1. Estructura de Datos: CPT (Custom Post Types & Taxonomías)
        if ( class_exists( 'Babel\Directory\CPT' ) ) {
            new \Babel\Directory\CPT();
        }

        // 2. Estructura de Datos: Metaboxes de Datos de Negocios
        if ( class_exists( 'Babel\Directory\Metaboxes' ) ) {
            new \Babel\Directory\Metaboxes();
        }

        // 3. Motor de Indexación Rápida
        if ( class_exists( 'Babel\Directory\Search_Index' ) ) {
            new \Babel\Directory\Search_Index();
        }

        // 4. Control de Consultas Asíncronas AJAX
        if ( class_exists( 'Babel\Directory\Ajax' ) ) {
            new \Babel\Directory\Ajax();
        }

        // 5. Panel de Administración y Registro de Menús/Settings API
        if ( class_exists( 'Babel\Directory\Admin' ) ) {
            new \Babel\Directory\Admin();
        }

        // 6. Gestión de Assets Públicos y Shortcodes de Presentación
        if ( class_exists( 'Babel\Directory\Assets' ) ) {
            new \Babel\Directory\Assets();
        }

        // 7. Sistema de Gestión de Reseñas y Calificaciones
        if ( class_exists( 'Babel\Directory\Reviews' ) ) {
            new \Babel\Directory\Reviews();
        }

        // 8. Procesamiento Seguro de Envío de Negocios desde el Frontend
        if ( class_exists( 'Babel\Directory\Submission' ) ) {
            new \Babel\Directory\Submission();
        }

        // 9. Soporte de Imágenes en Taxonomías
        if ( class_exists( 'Babel\Directory\Taxonomy_Images' ) ) {
            new \Babel\Directory\Taxonomy_Images();
        }

        // 10. Shortcodes de Presentación (Radar y Grilla de Regiones)
        if ( class_exists( 'Babel\Directory\Shortcodes' ) ) {
            new \Babel\Directory\Shortcodes();
        }

        // 11. Endpoints de REST API
        if ( class_exists( '\Babel\Directory\Api\Rest_Endpoints' ) ) {
            new \Babel\Directory\Api\Rest_Endpoints();
        }
    }
}

/**
 * Hook de Activación de Seguridad:
 * Vincula el método estático de creación de la tabla física de búsquedas rápidas.
 * Se ejecuta exclusivamente al activar el plugin para garantizar integridad.
 */
register_activation_hook( __FILE__, array( 'Babel\\Directory\\Search_Index', 'create_table' ) );

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

/**
 * Endpoint temporal para poblar datos de prueba mediante URL
 * Uso: https://tusitio.com/?seed_sushi=1
 */
add_action( 'init', function() {
    if ( isset( $_GET['seed_sushi'] ) && $_GET['seed_sushi'] == '1' ) {
        if ( current_user_can('manage_options') || true ) { // Permitimos temporalmente sin login para facilidad de prueba
            require_once BD_PATH . 'bin/seed_sushi_club.php';
            echo "<br><br><a href='/?post_type=babel_business'>Volver</a>";
            exit;
        }
    }
});
