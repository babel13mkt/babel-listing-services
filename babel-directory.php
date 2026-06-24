<?php
/**
 * Plugin Name: Babel Directory
 * Description: Plugin de estructuración de datos para el directorio de Negocios en WordPress. CPT, Taxonomías y Metaboxes nativas para administración exclusiva desde el backend.
 * Version: 8.2.0
 * Author: Babel13 MKT
 * Text Domain: babel-directory
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}

// Definir constantes globales de la arquitectura v7.2.0+
define( 'BD_VERSION', '8.2.0' );
define( 'BD_PATH', plugin_dir_path( __FILE__ ) );
// FIX: Forzar HTTPS en la URL del plugin.
// Detrás de Cloudflare el servidor no ve HTTPS, plugin_dir_url() genera http://
// y los navegadores bloquean silenciosamente los assets como "mixed content".
define( 'BD_URL', ( defined('WP_DEBUG') && WP_DEBUG ) ? plugin_dir_url( __FILE__ ) : set_url_scheme( plugin_dir_url( __FILE__ ), 'https' ) );

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

        // 1.5 Estructura de Datos: CPT de Instituciones (Escuelas, Universidades, Bancos, Organismos)
        if ( class_exists( 'Babel\Directory\Institution' ) ) {
            new \Babel\Directory\Institution();
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

        // 6.5 Panel de Control Frontend (Client Portal)
        if ( class_exists( 'Babel\Directory\Frontend_Dashboard' ) ) {
            new \Babel\Directory\Frontend_Dashboard();
        }

        // 6.6 Integración con WooCommerce para Planes
        if ( class_exists( 'Babel\Directory\WooCommerce_Integration' ) ) {
            new \Babel\Directory\WooCommerce_Integration();
        }

        // 7. Sistema de Gestión de Reseñas y Calificaciones
        if ( class_exists( 'Babel\Directory\Reviews' ) ) {
            new \Babel\Directory\Reviews();
        }

        // 8. Procesamiento Seguro de Envío de Negocios desde el Frontend
        if ( class_exists( 'Babel\Directory\Submission' ) ) {
            new \Babel\Directory\Submission();
        }

        // 8.5 Formulario Multi-step de Auto-registro Frontend
        if ( class_exists( 'Babel\Directory\Frontend_Registration' ) ) {
            new \Babel\Directory\Frontend_Registration();
        }

        // 12. Autenticación con Google Identity Services
        if ( class_exists( 'Babel\Directory\Google_Auth' ) ) {
            new \Babel\Directory\Google_Auth();
        }

        // 12.1 Autenticación con Microsoft
        if ( class_exists( 'Babel\Directory\Microsoft_Auth' ) ) {
            new \Babel\Directory\Microsoft_Auth();
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
        if ( class_exists( 'Babel\Directory\Api\Rest_Endpoints' ) ) {
            new \Babel\Directory\Api\Rest_Endpoints();
        }

        // 13. Capa de Compatibilidad con Temas Opinionados (Divi 5, Elementor, etc.)
        if ( class_exists( 'Babel\Directory\Divi_Compat' ) ) {
            new \Babel\Directory\Divi_Compat();
        }

        // 14. Integración de Pagos y Webhooks
        if ( class_exists( 'Babel\Directory\Payments' ) ) {
            new \Babel\Directory\Payments();
        }

        // 15. Geolocalización Pasiva (IP Detección)
        if ( class_exists( 'Babel\Directory\Geolocation' ) ) {
            new \Babel\Directory\Geolocation();
        }


        // 16. API de Publicidad y Redireccionamiento de Clics
        if ( class_exists( 'Babel\Directory\Ads_API' ) ) {
            new \Babel\Directory\Ads_API();
        }

        // 16.5 Lógica de SEO y Schema Markup
        if ( class_exists( 'Babel\Directory\SEO' ) ) {
            new \Babel\Directory\SEO();
        }

        // 17. Enrutamiento de Plantillas Autónomas (Fallback Divi/FSE)
        add_filter( 'template_include', function( $template ) {
            if ( is_tax( 'babel_region' ) || is_tax( 'babel_category' ) ) {
                $plugin_template = BD_PATH . 'templates/taxonomy-babel_region.php';
                if ( file_exists( $plugin_template ) ) return $plugin_template;
            }
            if ( is_singular( 'babel_business' ) ) {
                $plugin_template = BD_PATH . 'templates/single-babel_business.php';
                if ( file_exists( $plugin_template ) ) return $plugin_template;
            }
            if ( is_singular( 'bd_institution' ) ) {
                $plugin_template = BD_PATH . 'templates/single-bd_institution.php';
                if ( file_exists( $plugin_template ) ) return $plugin_template;
            }
            return $template;
        }, 99 );
    }
}

/**
 * Hook de Activación de Seguridad:
 * Vincula el método estático de creación de la tabla física de búsquedas rápidas.
 * Se ejecuta exclusivamente al activar el plugin para garantizar integridad.
 */
register_activation_hook( __FILE__, array( 'Babel\Directory\Search_Index', 'create_table' ) );

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
 * Configuración SMTP para wp_mail.
 */
add_action( 'phpmailer_init', function( $phpmailer ) {
    $phpmailer->isSMTP();
    $phpmailer->Host       = defined('BD_SMTP_HOST') ? BD_SMTP_HOST : 'mail.soydechile.cl';
    $phpmailer->SMTPAuth   = defined('BD_SMTP_USER') && defined('BD_SMTP_PASS');
    $phpmailer->Port       = defined('BD_SMTP_PORT') ? BD_SMTP_PORT : 587;
    $phpmailer->Username   = defined('BD_SMTP_USER') ? BD_SMTP_USER : '';
    $phpmailer->Password   = defined('BD_SMTP_PASS') ? BD_SMTP_PASS : '';
    $phpmailer->SMTPSecure = defined('BD_SMTP_SECURE') ? BD_SMTP_SECURE : 'tls';
    $phpmailer->From       = defined('BD_SMTP_FROM') ? BD_SMTP_FROM : 'contacto@soydechile.cl';
    $phpmailer->FromName   = defined('BD_SMTP_FROM_NAME') ? BD_SMTP_FROM_NAME : 'Soy de Chile';
} );
