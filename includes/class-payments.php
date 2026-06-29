<?php
namespace Babel\Directory;

/**
 * Procesamiento de Pagos y Webhooks para WebPay/MercadoPago (Babel_Directory_Payments)
 * v1.0.0 — Permite activar el negocio de pending a publish tras confirmación de pago.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Payments {

    /**
     * Constructor de la clase. Registra el endpoint REST de WordPress.
     */
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_webhook_route' ) );
    }

    /**
     * Registra la ruta REST pública para recibir webhooks.
     */
    public function register_webhook_route() {
        register_rest_route( 'babel/v1', '/payments/webhook', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'handle_webhook' ),
            'permission_callback' => array( $this, 'check_webhook_permission' ),
        ) );
    }

    /**
     * Callback para procesar la llamada de WebPay / MercadoPago.
     * 
     * @param \WP_REST_Request $request Objeto de petición REST.
     * @return \WP_REST_Response
     */
    public function handle_webhook( \WP_REST_Request $request ) {
        // --- Signature verification (defense in depth) ---
        $mercadopago_signature = $request->get_header( 'X-Signature' );
        $webpay_signature      = $request->get_header( 'Tbk-Api-Signature' );

        $signature_valid = false;

        if ( ! empty( $mercadopago_signature ) ) {
            $signature_valid = $this->verify_mercadopago_signature( $mercadopago_signature, $request->get_body() );
        } elseif ( ! empty( $webpay_signature ) ) {
            $signature_valid = $this->verify_webpay_signature( $webpay_signature, $request->get_body() );
        }

        if ( ! $signature_valid ) {
            return new \WP_REST_Response( array(
                'success' => false,
                'error'   => 'Invalid or missing webhook signature.',
            ), 401 );
        }

        $params = $request->get_params();

        // SECURITY: Log mínimo sin datos personales (GDPR). Solo IDs.
        $log_id = $params['id'] ?? $params['data']['id'] ?? 'unknown';
        if ( defined( 'BD_DEBUG' ) && BD_DEBUG ) {
            error_log( '[Babel Directory Webhook] Recibido. ID transaccion: ' . sanitize_text_field( $log_id ) );
        }

        $business_id = 0;

        // Intentar extraer el ID del negocio desde distintas llaves estándares de webhook
        if ( ! empty( $params['business_id'] ) ) {
            $business_id = intval( $params['business_id'] );
        } elseif ( ! empty( $params['external_reference'] ) ) {
            $business_id = intval( $params['external_reference'] );
        } elseif ( ! empty( $params['buy_order'] ) ) {
            $business_id = intval( $params['buy_order'] );
        }

        // Si no encontramos un ID de negocio válido en el payload
        if ( ! $business_id ) {
            return new \WP_REST_Response( array( 
                'success' => false, 
                'error'   => 'Business ID not found in payload.' 
            ), 400 );
        }

        // Verificar el estado del pago.
        // Soporta MercadoPago ('approved'), WebPay ('authorized', 'success') y fallbacks
        $status = isset( $params['status'] ) ? sanitize_text_field( $params['status'] ) : '';
        $action = isset( $params['action'] ) ? sanitize_text_field( $params['action'] ) : '';

        $is_success = true; // Por defecto permitimos si no viene especificado (facilidad de pruebas)

        if ( ! empty( $status ) ) {
            $success_statuses = array( 'approved', 'authorized', 'success', 'completed', 'paid' );
            $is_success = in_array( strtolower( $status ), $success_statuses, true );
        } elseif ( ! empty( $action ) ) {
            // MercadoPago envía a veces el tipo de evento en 'action'
            if ( 'payment.created' === $action || 'payment.updated' === $action ) {
                $is_success = true;
            }
        }

        if ( ! $is_success ) {
            return new \WP_REST_Response( array( 
                'success' => false, 
                'message' => 'Payment status is not successful: ' . $status 
            ), 200 );
        }

        // Obtener el post y validar tipo
        $post = get_post( $business_id );
        if ( ! $post || 'babel_business' !== $post->post_type ) {
            return new \WP_REST_Response( array( 
                'success' => false, 
                'error'   => 'Invalid Business ID.' 
            ), 404 );
        }

        // Si el estado es pendiente de moderación/pago, lo publicamos
        if ( 'pending' === $post->post_status ) {
            // Guardar registro del pago en el negocio
            update_post_meta( $business_id, '_babel_payment_status', 'paid' );
            update_post_meta( $business_id, '_babel_payment_gateway_payload', wp_json_encode( $params ) );

            // Cambiar estado del CPT a publicado
            wp_update_post( array(
                'ID'          => $business_id,
                'post_status' => 'publish',
            ) );

            return new \WP_REST_Response( array( 
                'success' => true, 
                'message' => 'Business status updated from pending to publish successfully.' 
            ), 200 );
        }

        return new \WP_REST_Response( array( 
            'success' => true, 
            'message' => 'Business was already published or not in pending state. Current state: ' . $post->post_status 
        ), 200 );
    }

    /**
     * Permission callback: validate HMAC signature before processing the webhook.
     *
     * @param \WP_REST_Request $request The request object.
     * @return bool|\WP_Error True if valid, WP_Error with 401 if invalid.
     */
    public function check_webhook_permission( $request ) {
        // Modo debug para testing: permite webhooks sin signature si BD_WEBHOOK_DEBUG está activo
        if ( defined( 'BD_WEBHOOK_DEBUG' ) && BD_WEBHOOK_DEBUG ) {
            $debug_token = $request->get_header( 'X-Debug-Token' );
            $expected_token = defined( 'BD_WEBHOOK_DEBUG_TOKEN' ) ? BD_WEBHOOK_DEBUG_TOKEN : '';
            if ( ! empty( $debug_token ) && ! empty( $expected_token ) && hash_equals( $expected_token, $debug_token ) ) {
                return true;
            }
        }

        $mercadopago_signature = $request->get_header( 'X-Signature' );
        $webpay_signature      = $request->get_header( 'Tbk-Api-Signature' );

        if ( ! empty( $mercadopago_signature ) ) {
            if ( $this->verify_mercadopago_signature( $mercadopago_signature, $request->get_body() ) ) {
                return true;
            }
        } elseif ( ! empty( $webpay_signature ) ) {
            if ( $this->verify_webpay_signature( $webpay_signature, $request->get_body() ) ) {
                return true;
            }
        }

        return new \WP_Error(
            'rest_forbidden',
            'Invalid or missing webhook signature.',
            array( 'status' => 401 )
        );
    }

    /**
     * Verify MercadoPago webhook signature (X-Signature header).
     *
     * Calcula HMAC-SHA256 del payload con la clave secreta y compara
     * contra el valor recibido via hash_equals() para prevenir timing attacks.
     *
     * @param string $signature_header The X-Signature header value.
     * @param string $payload          The raw request body.
     * @return bool True if signature matches, false otherwise.
     */
    private function verify_mercadopago_signature( $signature_header, $payload ) {
        $secret = get_option( 'babel_mercadopago_webhook_secret', '' );
        if ( empty( $secret ) ) {
            return false;
        }

        $expected = hash_hmac( 'sha256', $payload, $secret );
        return hash_equals( $expected, $signature_header );
    }

    /**
     * Verify WebPay (Transbank) webhook signature (Tbk-Api-Signature header).
     *
     * Calcula HMAC-SHA256 del payload con la clave secreta y compara
     * contra el valor recibido via hash_equals() para prevenir timing attacks.
     *
     * @param string $signature_header The Tbk-Api-Signature header value.
     * @param string $payload          The raw request body.
     * @return bool True if signature matches, false otherwise.
     */
    private function verify_webpay_signature( $signature_header, $payload ) {
        $secret = get_option( 'babel_webpay_webhook_secret', '' );
        if ( empty( $secret ) ) {
            return false;
        }

        $expected = hash_hmac( 'sha256', $payload, $secret );
        return hash_equals( $expected, $signature_header );
    }
}
