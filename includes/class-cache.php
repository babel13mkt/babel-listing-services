<?php
namespace Babel\Directory;

/**
 * Gestor de Caché y Transients
 * Optimiza consultas pesadas de bases de datos
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Cache {

    public function __construct() {
        // Limpiar caché globalmente cuando se edita un negocio para mantener datos frescos
        add_action( 'save_post_babel_business', array( $this, 'clear_all_transients' ) );
    }

    /**
     * Obtiene un resultado cacheado usando Transients.
     */
    public static function get_transient( $key ) {
        return get_transient( 'bd_' . $key );
    }

    /**
     * Guarda un resultado en caché.
     */
    public static function set_transient( $key, $value, $expiration = 3600 ) {
        set_transient( 'bd_' . $key, $value, $expiration );
    }

    /**
     * Limpia la caché.
     */
    public function clear_all_transients() {
        global $wpdb;
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_bd\_%'" );
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_timeout\_bd\_%'" );
    }
}
