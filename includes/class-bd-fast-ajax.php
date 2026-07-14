<?php
namespace Babel\Directory;

/**
 * Optimizador de Rendimiento: Fast AJAX
 * Inspirado en arquitecturas de alto rendimiento.
 * Apaga plugins innecesarios cuando se detectan llamadas AJAX críticas del Directorio (ej. bd_filter_listings)
 * para reducir la memoria y el tiempo de respuesta.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Fast_AJAX {

    public static function init() {
        // Nos enganchamos en plugins_loaded de forma muy temprana (antes de cargar el resto) o justo al cargar el plugin
        // Sin embargo, DOING_AJAX no está definido en mu-plugins o carga temprana de options.
        // Pero el filtro 'option_active_plugins' sí se ejecuta cuando WP lee las opciones para arrancar los plugins.
        // Por lo tanto, registramos el filtro incondicionalmente en la carga global de nuestro plugin, 
        // pero dentro del callback validaremos si es AJAX.
        add_filter( 'option_active_plugins', array( __CLASS__, 'filter_active_plugins' ), 1, 1 );
    }

    public static function filter_active_plugins( $plugins ) {
        if ( ! is_array( $plugins ) ) {
            return $plugins;
        }

        // Detectar si es una llamada a admin-ajax.php para bd_filter_listings
        $is_ajax = defined('DOING_AJAX') ? DOING_AJAX : ( isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'admin-ajax.php') !== false );
        if ( ! $is_ajax ) {
            return $plugins;
        }

        $action = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : '';
        $fast_actions = array(
            'bd_filter_listings',
        );

        if ( ! in_array( $action, $fast_actions, true ) ) {
            return $plugins;
        }

        $allowed_plugins = array();
        
        foreach ( $plugins as $plugin ) {
            // 1. Siempre permitimos nuestro plugin
            if ( strpos( $plugin, 'babel-directory' ) !== false ) {
                $allowed_plugins[] = $plugin;
                continue;
            }

            // 2. Permitimos Divi Builder / Elementor por si acaso cargan librerías críticas globales
            if ( strpos( $plugin, 'divi-builder' ) !== false || strpos( $plugin, 'elementor' ) !== false ) {
                $allowed_plugins[] = $plugin;
                continue;
            }
        }

        return $allowed_plugins;
    }
}
