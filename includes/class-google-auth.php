<?php
namespace Babel\Directory;

/**
 * Google Identity Services Authentication Handler (Babel_Directory_Google_Auth)
 * v7.2.0 — Hito 20: Login con Google + Publicación de Negocios desde el Frontend.
 *
 * Verifica el JWT de Google Identity Services, crea o recupera un usuario WordPress
 * y establece la cookie de sesión sin redireccionamientos.
 *
 * @package Babel_Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Google_Auth {

    public function __construct() {
        add_action( 'wp_ajax_nopriv_babel_google_login', array( $this, 'handle_google_login' ) );
        add_action( 'wp_ajax_babel_google_login', array( $this, 'handle_google_login' ) );
    }

    /**
     * Maneja el login con Google Identity Services via AJAX.
     * Recibe el JWT del cliente, lo verifica contra Google y autentica al usuario.
     */
    public function handle_google_login() {
        // Verificar nonce básico de seguridad
        if ( ! check_ajax_referer( 'babel_google_login_nonce', 'security', false ) ) {
            wp_send_json_error( array( 'message' => 'Solicitud no autorizada.' ) );
            return;
        }

        $credential = isset( $_POST['credential'] ) ? sanitize_text_field( wp_unslash( $_POST['credential'] ) ) : '';
        if ( empty( $credential ) ) {
            wp_send_json_error( array( 'message' => 'Token no proporcionado.' ) );
            return;
        }

        // Obtener el Client ID configurado
        $client_id = get_option( 'babel_google_client_id', '' );
        if ( empty( $client_id ) ) {
            wp_send_json_error( array( 'message' => 'El Google Client ID no está configurado. Contacta al administrador.' ) );
            return;
        }

        // Verificar token con Google tokeninfo endpoint
        $response = wp_remote_get(
            'https://oauth2.googleapis.com/tokeninfo?id_token=' . rawurlencode( $credential ),
            array( 'timeout' => 10 )
        );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => 'Error al verificar con Google. Intenta nuevamente.' ) );
            return;
        }

        $http_code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== intval( $http_code ) ) {
            wp_send_json_error( array( 'message' => 'Token de Google inválido o expirado.' ) );
            return;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        // Validar que el token sea para nuestro Client ID y tenga email verificado
        if (
            empty( $body['email'] ) ||
            empty( $body['email_verified'] ) ||
            'true' !== $body['email_verified'] ||
            ! isset( $body['aud'] ) ||
            $body['aud'] !== $client_id
        ) {
            wp_send_json_error( array( 'message' => 'Token inválido: audiencia no coincide o email no verificado.' ) );
            return;
        }

        $email  = sanitize_email( $body['email'] );
        $name   = isset( $body['name'] ) ? sanitize_text_field( $body['name'] ) : $email;
        $avatar = isset( $body['picture'] ) ? esc_url_raw( $body['picture'] ) : '';

        // Buscar o crear usuario WordPress
        $user = get_user_by( 'email', $email );

        if ( ! $user ) {
            // Crear nuevo usuario con rol subscriber
            $username = sanitize_user( str_replace( '@', '_at_', $email ), true );
            // Asegurar username único
            if ( username_exists( $username ) ) {
                $username = $username . '_' . wp_rand( 100, 999 );
            }

            $user_id = wp_create_user( $username, wp_generate_password( 24, true, true ), $email );

            if ( is_wp_error( $user_id ) ) {
                wp_send_json_error( array( 'message' => 'Error al crear la cuenta: ' . $user_id->get_error_message() ) );
                return;
            }

            wp_update_user( array(
                'ID'           => $user_id,
                'display_name' => $name,
                'first_name'   => isset( $body['given_name'] ) ? sanitize_text_field( $body['given_name'] ) : '',
                'last_name'    => isset( $body['family_name'] ) ? sanitize_text_field( $body['family_name'] ) : '',
                'role'         => 'subscriber',
            ) );

            update_user_meta( $user_id, '_babel_google_avatar', $avatar );
            update_user_meta( $user_id, '_babel_google_sub', sanitize_text_field( $body['sub'] ?? '' ) );

            $user = get_user_by( 'id', $user_id );
        } else {
            // Actualizar avatar si cambió
            if ( $avatar ) {
                update_user_meta( $user->ID, '_babel_google_avatar', $avatar );
            }
        }

        // Establecer sesión WordPress
        wp_set_current_user( $user->ID );
        wp_set_auth_cookie( $user->ID, true );

        wp_send_json_success( array(
            'message' => '¡Bienvenido, ' . esc_html( $user->display_name ) . '!',
            'user'    => array(
                'name'   => $user->display_name,
                'email'  => $user->user_email,
                'avatar' => get_user_meta( $user->ID, '_babel_google_avatar', true ) ?: $avatar,
            ),
        ) );
    }
}
