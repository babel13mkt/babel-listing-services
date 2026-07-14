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
     * Normaliza parámetros de búsqueda para generar cache keys determinísticas.
     * Elimina ruido (nonce, action, valores vacíos) y ordena por clave.
     *
     * @param array $params Parámetros crudos ($_POST, $_GET, etc.)
     * @return array Parámetros normalizados y ordenados
     */
    public static function normalize_params( array $params ): array {
        // Claves que no afectan el resultado de búsqueda (ruido de WP AJAX/REST)
        $ignore = [ 'action', 'nonce', '_wpnonce', 'security', 'is_ajax', 'append', 'lang' ];

        $filtered = [];
        foreach ( $params as $key => $value ) {
            if ( in_array( $key, $ignore, true ) ) {
                continue;
            }
            // Tratar '0', '0.0', 0 como valores válidos; descartar vacíos/null
            if ( $value === '' || $value === null || $value === false ) {
                continue;
            }
            if ( is_array( $value ) ) {
                $filtered[ $key ] = self::normalize_params( $value );
            } elseif ( is_numeric( $value ) && ! is_int( $value ) ) {
                // Normalizar floats a 6 decimales para evitar ruido de precisión
                $filtered[ $key ] = round( (float) $value, 6 );
            } else {
                $filtered[ $key ] = $value;
            }
        }

        // Ordenar por clave para garantizar keys estables independientemente del orden de llegada
        ksort( $filtered );
        return $filtered;
    }

    /**
     * Genera una cache key determinística para una búsqueda.
     *
     * @param string $prefix Contexto (ej: 'ajax_search', 'rest_v1')
     * @param array  $params Parámetros de la búsqueda
     * @return string
     */
    public static function key( string $prefix, array $params ): string {
        $normalized = self::normalize_params( $params );
        return 'bd_' . $prefix . '_' . md5( wp_json_encode( $normalized ) );
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
