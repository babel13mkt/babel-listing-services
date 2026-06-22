<?php
namespace Babel\Directory;

/**
 * Middleware de Seguridad y Rate Limiting
 * Protege los endpoints públicos (AJAX/REST) contra abusos y DDoS a nivel de aplicación.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Security {

    public function __construct() {
        // Inicialización si se requieren hooks
    }

    /**
     * Valida el Rate Limit por IP usando Transients.
     * @param string $action Nombre de la acción a limitar.
     * @param int $max_requests Máximo número de peticiones.
     * @param int $window_seconds Ventana de tiempo en segundos.
     * @return bool True si está permitido, False si está bloqueado por abuso.
     */
    public static function check_rate_limit( $action = 'general', $max_requests = 30, $window_seconds = 60 ) {
        $ip = self::get_client_ip();
        if ( ! $ip ) {
            return false;
        }
        
        $transient_key = 'bd_rl_' . md5( $ip . '_' . $action );
        $requests = get_transient( $transient_key );
        
        if ( false === $requests ) {
            set_transient( $transient_key, 1, $window_seconds );
            return true;
        }
        
        if ( (int) $requests >= $max_requests ) {
            return false;
        }
        
        set_transient( $transient_key, (int) $requests + 1, $window_seconds );
        return true;
    }

    /**
     * Obtiene la IP real del cliente superando proxies como Cloudflare.
     */
    public static function get_client_ip() {
        if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ips = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
            return trim( $ips[0] );
        } else {
            return isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
        }
    }
}
