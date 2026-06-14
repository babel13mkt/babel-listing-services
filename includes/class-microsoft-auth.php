<?php
namespace Babel\Directory;

/**
 * Microsoft Authentication Handler (Babel_Directory_Microsoft_Auth)
 * v8.1.0 — Hito 21: Login con Microsoft (Hotmail/Outlook)
 *
 * Verifica el Access Token contra Microsoft Graph, crea o recupera un usuario WordPress
 * y establece la cookie de sesión sin redireccionamientos.
 *
 * @package Babel_Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Microsoft_Auth {

    public function __construct() {
        add_action( 'wp_ajax_nopriv_babel_microsoft_login', array( $this, 'handle_microsoft_login' ) );
        add_action( 'wp_ajax_babel_microsoft_login', array( $this, 'handle_microsoft_login' ) );
    }

    /**
     * Maneja el login con Microsoft via AJAX.
     * Recibe el Access Token del cliente, verifica con Graph API y autentica al usuario.
     */
    public function handle_microsoft_login() {
        // Verificar nonce básico de seguridad
        if ( ! check_ajax_referer( 'babel_microsoft_login_nonce', 'security', false ) ) {
            wp_send_json_error( array( 'message' => 'Solicitud no autorizada.' ) );
            return;
        }

        $access_token = isset( $_POST['id_token'] ) ? sanitize_text_field( wp_unslash( $_POST['id_token'] ) ) : '';
        if ( empty( $access_token ) ) {
            wp_send_json_error( array( 'message' => 'Token no proporcionado.' ) );
            return;
        }

        // Obtener el Client ID configurado
        $client_id = get_option( 'babel_microsoft_client_id', '' );
        if ( empty( $client_id ) ) {
            wp_send_json_error( array( 'message' => 'El Microsoft Client ID no está configurado. Contacta al administrador.' ) );
            return;
        }

        // Verificar token con Microsoft Graph API
        $response = wp_remote_get(
            'https://graph.microsoft.com/v1.0/me',
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $access_token,
                    'Accept'        => 'application/json',
                ),
                'timeout' => 10,
            )
        );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => 'Error al conectar con Microsoft. Intenta nuevamente.' ) );
            return;
        }

        $http_code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 200 !== intval( $http_code ) || empty( $body ) || isset( $body['error'] ) ) {
            wp_send_json_error( array( 'message' => 'Token de Microsoft inválido o expirado.' ) );
            return;
        }

        // El email puede venir en mail o userPrincipalName
        $email = '';
        if ( ! empty( $body['mail'] ) ) {
            $email = sanitize_email( $body['mail'] );
        } elseif ( ! empty( $body['userPrincipalName'] ) && strpos( $body['userPrincipalName'], '@' ) !== false ) {
            $email = sanitize_email( $body['userPrincipalName'] );
        }

        if ( empty( $email ) ) {
            wp_send_json_error( array( 'message' => 'No se pudo obtener el correo electrónico de tu cuenta Microsoft.' ) );
            return;
        }

        $name = isset( $body['displayName'] ) ? sanitize_text_field( $body['displayName'] ) : $email;
        $ms_id = isset( $body['id'] ) ? sanitize_text_field( $body['id'] ) : '';

        // Intentar obtener la foto de perfil (Opcional)
        $avatar = ''; // Para Microsoft, obtener la foto requiere otro request a /me/photo/$value, lo omitimos para mantenerlo rápido.

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

            $first_name = isset( $body['givenName'] ) ? sanitize_text_field( $body['givenName'] ) : '';
            $last_name = isset( $body['surname'] ) ? sanitize_text_field( $body['surname'] ) : '';

            wp_update_user( array(
                'ID'           => $user_id,
                'display_name' => $name,
                'first_name'   => $first_name,
                'last_name'    => $last_name,
                'role'         => 'subscriber',
            ) );

            update_user_meta( $user_id, '_babel_microsoft_id', $ms_id );
            
            $user = get_user_by( 'id', $user_id );
        }

        // Establecer sesión WordPress
        wp_set_current_user( $user->ID );
        wp_set_auth_cookie( $user->ID, true );

        wp_send_json_success( array(
            'message' => '¡Bienvenido, ' . esc_html( $user->display_name ) . '!',
            'user'    => array(
                'name'   => $user->display_name,
                'email'  => $user->user_email,
            ),
        ) );
    }
}
