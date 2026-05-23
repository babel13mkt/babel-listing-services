<?php
/**
 * Controladores REST API nativa.
 * Reemplaza al viejo admin-ajax.php para mejor rendimiento en SPA.
 */

namespace Babel\Directory\Api;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Rest_Endpoints {

    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        // GET / POST /wp-json/babel/v1/search
        register_rest_route( 'babel/v1', '/search', array(
            'methods'             => \WP_REST_Server::CREATABLE, // Acepta POST (usamos POST en fetch)
            'callback'            => array( $this, 'handle_search' ),
            'permission_callback' => '__return_true', // Validación de nonce manejada internamente
        ) );
    }

    public function handle_search( \WP_REST_Request $request ) {
        // Simular variables POST para no romper el motor AJAX existente de momento
        $params = $request->get_params();
        foreach ( $params as $key => $value ) {
            $_POST[ $key ] = $value;
        }
        
        if ( class_exists( '\Babel\Directory\Ajax' ) ) {
            $ajax_handler = new \Babel\Directory\Ajax();
            
            // Atrapamos la salida (wp_send_json_success hace die, así que el REST devolverá eso directo)
            // En el futuro, Ajax->filter_listings() debería retornar el array en lugar de hacer die().
            $ajax_handler->filter_listings();
        }
        
        return new \WP_REST_Response( array( 'error' => 'Motor de búsqueda no disponible.' ), 500 );
    }
}
