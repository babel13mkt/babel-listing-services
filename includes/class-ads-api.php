<?php
namespace Babel\Directory;

/**
 * Clase para el manejo de la API REST y el tracking seguro de clics en banners.
 *
 * @package Babel_Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}

class Ads_API {

    /**
     * Constructor de la clase Ads_API.
     * Enlaza el gancho para registrar las rutas de la REST API.
     */
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Registra las rutas de la REST API para los anuncios.
     */
    public function register_routes() {
        register_rest_route( 'babel/v1', '/ads/click', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'handle_click_redirect' ),
            'permission_callback' => '__return_true', // Público
            'args'                => array(
                'ad_id' => array(
                    'required'          => true,
                    'sanitize_callback' => 'absint',
                ),
            ),
        ) );
    }

    /**
     * Maneja el clic en un anuncio, incrementa el contador y redirige.
     *
     * @param \WP_REST_Request $request Objeto de la petición REST.
     */
    public function handle_click_redirect( $request ) {
        $ad_id = $request->get_param( 'ad_id' );

        // Verificar que el post existe y es del tipo correcto
        if ( ! $ad_id || 'bd_ad_banner' !== get_post_type( $ad_id ) ) {
            return new \WP_Error( 'invalid_ad', __( 'El anuncio especificado no es válido.', 'babel-directory' ), array( 'status' => 404 ) );
        }

        // Incrementar el contador de clics
        $clicks = (int) get_post_meta( $ad_id, '_bd_ad_clicks', true );
        $clicks++;
        update_post_meta( $ad_id, '_bd_ad_clicks', $clicks );

        // Obtener el enlace de destino
        $redirect_url = get_post_meta( $ad_id, '_bd_ad_link', true );

        if ( empty( $redirect_url ) ) {
            $redirect_url = home_url( '/' ); // Redirigir a la home si no hay link
        }

        // Ejecutar la redirección segura
        wp_redirect( esc_url_raw( $redirect_url ), 302, 'Babel-Directory-Ad-Tracker' );
        exit;
    }
}
