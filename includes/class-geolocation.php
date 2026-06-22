<?php
namespace Babel\Directory;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Manejador de Geolocalización Pasiva (Capa 1)
 * Auto-detecta la región del usuario vía IP y guarda la preferencia en una Cookie (24hs)
 * para pre-llenar los resultados del directorio sin mostrar popups intrusivos.
 */
class Geolocation {

    public function __construct() {
        // En vez de template_redirect (que muere con la caché), exponemos un endpoint AJAX
        add_action( 'wp_ajax_nopriv_babel_geolocate_me', array( $this, 'ajax_geolocate_me' ) );
        add_action( 'wp_ajax_babel_geolocate_me', array( $this, 'ajax_geolocate_me' ) );
    }

    public function ajax_geolocate_me() {
        // Nonce verification
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'babel_geolocate_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Nonce inválido' ) );
        }

        // Verificamos si ya hay cookie para no abusar de la API
        if ( isset( $_COOKIE['babel_user_region_slug'] ) && ! empty( $_COOKIE['babel_user_region_slug'] ) ) {
            wp_send_json_success( array( 'region' => $_COOKIE['babel_user_region_slug'] ) );
        }

        // Si la cookie ya existe, ya localizamos al usuario, no hacer nada para ahorrar recursos.
        if ( isset( $_COOKIE['babel_user_region_slug'] ) && ! empty( $_COOKIE['babel_user_region_slug'] ) ) {
            return;
        }

        $ip = $this->get_user_ip();
        
        // Evitar consumo de API en entornos locales
        if ( $ip === '127.0.0.1' || $ip === '::1' ) {
            wp_send_json_error( array( 'message' => 'Local IP' ) );
        }

        $region_slug = $this->resolve_ip_to_region( $ip );

        if ( $region_slug ) {
            $this->set_region_cookie( $region_slug );
            wp_send_json_success( array( 'region' => $region_slug ) );
        } else {
            $this->set_region_cookie( 'unknown' );
            wp_send_json_error( array( 'message' => 'Not found or not Chile' ) );
        }
    }

    private function get_user_ip() {
        $ip = '';
        // Soporte nativo para Cloudflare Tunnels (AR1/MORDOR)
        if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ip = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] )[0];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return trim( $ip );
    }

    private function resolve_ip_to_region( $ip ) {
        // Llamada a API ultra-rápida y gratuita sin auth.
        $url = 'https://ip-api.com/json/' . $ip . '?lang=es&fields=status,regionName,countryCode';
        $response = wp_remote_get( $url, array( 'timeout' => 3 ) ); // Timeout ultra corto para no bloquear el LCP

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( ! $data || ! isset( $data['status'] ) || $data['status'] !== 'success' ) {
            return false;
        }

        // Si el usuario no está en Chile (Ej: VPN, bots, extranjeros), abortamos.
        if ( isset( $data['countryCode'] ) && $data['countryCode'] !== 'CL' ) {
            return false;
        }

        $region_name = isset( $data['regionName'] ) ? strtolower( $data['regionName'] ) : '';

        if ( empty( $region_name ) ) {
            return false;
        }

        return $this->fuzzy_match_region( $region_name );
    }

    private function fuzzy_match_region( $name ) {
        // Mapeo seguro contra los slugs de las taxonomías de WordPress (babel_region)
        if ( strpos( $name, 'metropol' ) !== false || strpos( $name, 'santiago' ) !== false ) return 'metropolitana';
        if ( strpos( $name, 'valparaiso' ) !== false || strpos( $name, 'valparaíso' ) !== false ) return 'valparaiso';
        if ( strpos( $name, 'biobio' ) !== false || strpos( $name, 'bío bío' ) !== false || strpos( $name, 'bio-bio' ) !== false ) return 'biobio';
        if ( strpos( $name, 'araucan' ) !== false ) return 'araucania';
        if ( strpos( $name, 'coquimbo' ) !== false ) return 'coquimbo';
        if ( strpos( $name, 'los lagos' ) !== false ) return 'los-lagos';
        if ( strpos( $name, 'antofagasta' ) !== false ) return 'antofagasta';
        if ( strpos( $name, 'maule' ) !== false ) return 'maule';
        if ( strpos( $name, 'los rios' ) !== false || strpos( $name, 'los ríos' ) !== false ) return 'los-rios';
        if ( strpos( $name, 'tarapaca' ) !== false || strpos( $name, 'tarapacá' ) !== false ) return 'tarapaca';
        if ( strpos( $name, 'atacama' ) !== false ) return 'atacama';
        if ( strpos( $name, 'magallanes' ) !== false ) return 'magallanes';
        if ( strpos( $name, 'aysen' ) !== false || strpos( $name, 'aysén' ) !== false ) return 'aysen';
        if ( strpos( $name, 'arica' ) !== false || strpos( $name, 'parinacota' ) !== false ) return 'arica';
        if ( strpos( $name, 'nuble' ) !== false || strpos( $name, 'ñuble' ) !== false ) return 'nuble';
        if ( strpos( $name, 'ohiggins' ) !== false || strpos( $name, "o'higgins" ) !== false ) return 'ohiggins';

        return false;
    }

    private function set_region_cookie( $slug ) {
        // La cookie dura 24 horas y es accesible por PHP y JS (HttpOnly = false para poder leer en JS si hiciese falta)
        setcookie( 'babel_user_region_slug', $slug, time() + 86400, '/', '', true, true );
    }
}
