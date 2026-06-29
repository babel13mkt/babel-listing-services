<?php
namespace Babel\Directory;

/**
 * Motor Lógico de Autenticación por Magic Link (Sin Contraseñas)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Magic_Link {

    public function __construct() {
        // Manejadores AJAX para solicitar el enlace
        add_action( 'wp_ajax_nopriv_babel_request_magic_link', array( $this, 'request_magic_link' ) );
        add_action( 'wp_ajax_babel_request_magic_link', array( $this, 'request_magic_link' ) );

        // Interceptor para procesar el clic en el enlace mágico
        add_action( 'init', array( $this, 'verify_magic_link' ) );
    }

    /**
     * AJAX: Genera el token y envía el correo.
     */
    public function request_magic_link() {
        check_ajax_referer( 'babel_magic_link_nonce', 'security' );

        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

        if ( ! is_email( $email ) ) {
            wp_send_json_error( array( 'message' => 'Correo electrónico inválido.' ) );
        }

        // Generar token seguro
        $token = wp_generate_password( 32, false );
        
        // El transient expirará en 15 minutos. Usamos el email como key (hasheado por seguridad de longitud)
        $transient_key = 'bd_magic_' . md5( $email );
        
        // Si ya hay un token pendiente, lo sobreescribimos (rate limiting implícito por la expiración)
        set_transient( $transient_key, $token, 15 * MINUTE_IN_SECONDS );

        // Construir la URL mágica con token hasheado (no exponer token plano en URL)
        $token_hash = wp_hash( $token );
        $magic_url = add_query_arg( array(
            'babel_magic_login' => $token_hash,
            'email'             => rawurlencode( $email )
        ), home_url( '/' ) );

        // Configuración del correo
        $site_name = get_bloginfo( 'name' );
        $subject   = 'Tu enlace de acceso seguro a ' . $site_name;
        
        $message  = "Hola,\n\n";
        $message .= "Has solicitado un enlace mágico para iniciar sesión en {$site_name}. Haz clic en el siguiente enlace para acceder directamente (sin contraseña):\n\n";
        $message .= $magic_url . "\n\n";
        $message .= "Este enlace expirará en 15 minutos.\n";
        $message .= "Si no solicitaste este enlace, puedes ignorar este correo.\n\n";
        $message .= "Saludos,\nEl equipo de {$site_name}.";

        $headers = array('Content-Type: text/plain; charset=UTF-8');

        $mail_sent = wp_mail( $email, $subject, $message, $headers );

        if ( $mail_sent ) {
            wp_send_json_success( array( 'message' => 'Enlace enviado correctamente.' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Error al enviar el correo. Revisa la configuración SMTP.' ) );
        }
    }

    /**
     * Interceptor: Valida el token al cargar la página e inicia sesión.
     */
    public function verify_magic_link() {
        if ( isset( $_GET['babel_magic_login'] ) && isset( $_GET['email'] ) ) {
            
            $token_from_url = sanitize_text_field( wp_unslash( $_GET['babel_magic_login'] ) );
            $email_from_url = sanitize_email( wp_unslash( $_GET['email'] ) );

            if ( empty( $token_from_url ) || ! is_email( $email_from_url ) ) {
                wp_die( 'Enlace mágico inválido o corrupto.' );
            }

            $transient_key = 'bd_magic_' . md5( $email_from_url );
            $saved_token   = get_transient( $transient_key );

            // SECURITY: Comparar usando hash (token plano NUNCA va en URL)
            // El URL contiene wp_hash($token), verificamos hasheando el token guardado
            $saved_token_hash = wp_hash( $saved_token );
            if ( $saved_token && hash_equals( $saved_token_hash, $token_from_url ) ) {
                
                // Borrar el token para que sea de un solo uso
                delete_transient( $transient_key );

                // Buscar usuario por correo
                $user = get_user_by( 'email', $email_from_url );

                // Si no existe, lo creamos
                if ( ! $user ) {
                    $random_password = wp_generate_password( 12, false );
                    $username = sanitize_user( current( explode( '@', $email_from_url ) ), true );
                    
                    // Si el username ya existe, le agregamos números aleatorios seguros
                    if ( username_exists( $username ) ) {
                        $username .= wp_rand( 100000, 999999 );
                    }

                    $user_id = wp_create_user( $username, $random_password, $email_from_url );

                    if ( is_wp_error( $user_id ) ) {
                        wp_die( 'Hubo un error al crear tu cuenta. Por favor contacta a soporte.' );
                    }

                    $user = get_user_by( 'id', $user_id );
                }

                // Autenticar al usuario
                wp_set_current_user( $user->ID );
                wp_set_auth_cookie( $user->ID, true );
                do_action( 'wp_login', $user->user_login, $user );

                // Redirigir al dashboard (Client Portal)
                wp_safe_redirect( home_url( '/mi-cuenta/' ) );
                exit;

            } else {
                wp_die( 'El enlace mágico ha expirado o ya fue utilizado. Por favor, solicita uno nuevo.' );
            }
        }
    }
}
