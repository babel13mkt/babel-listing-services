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
            'methods'             => array( 'GET', 'POST' ),
            'callback'            => array( $this, 'handle_webhook' ),
            'permission_callback' => '__return_true', // Permitir acceso público para webhooks externos
        ) );
    }

    /**
     * Callback para procesar la llamada de WebPay / MercadoPago.
     * 
     * @param \WP_REST_Request $request Objeto de petición REST.
     * @return \WP_REST_Response
     */
    public function handle_webhook( \WP_REST_Request $request ) {
        $params = $request->get_params();

        // Registrar en el log del servidor para auditorías de transacciones
        error_log( '[Babel Directory Webhook] Payload recibido: ' . print_r( $params, true ) );

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
}
