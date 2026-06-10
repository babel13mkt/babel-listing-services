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

        // GET /wp-json/babel/v1/suggestions
        register_rest_route( 'babel/v1', '/suggestions', array(
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => array( $this, 'handle_suggestions' ),
            'permission_callback' => '__return_true',
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

    /**
     * Endpoint rápido de autocompletado (Predictivo)
     */
    public function handle_suggestions( \WP_REST_Request $request ) {
        global $wpdb;

        $q = sanitize_text_field( $request->get_param( 'q' ) );
        if ( empty( $q ) || strlen( $q ) < 2 ) {
            return rest_ensure_response( array( 'success' => true, 'data' => array() ) );
        }

        $like_q = '%' . $wpdb->esc_like( $q ) . '%';
        $results = array();

        // 1. Buscar Categorías (Tienen prioridad en el dropdown)
        $terms_query = $wpdb->prepare( "
            SELECT t.name as label, 'category' as type, t.slug as value 
            FROM {$wpdb->terms} t
            INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
            WHERE tt.taxonomy = 'babel_category' AND t.name LIKE %s
            LIMIT 3
        ", $like_q );
        $cats = $wpdb->get_results( $terms_query, ARRAY_A );
        if ( $cats ) $results = array_merge( $results, $cats );

        // 2. Buscar Regiones/Comunas
        $regions_query = $wpdb->prepare( "
            SELECT t.name as label, 'region' as type, t.slug as value 
            FROM {$wpdb->terms} t
            INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
            WHERE tt.taxonomy = 'babel_region' AND t.name LIKE %s
            LIMIT 2
        ", $like_q );
        $regs = $wpdb->get_results( $regions_query, ARRAY_A );
        if ( $regs ) $results = array_merge( $results, $regs );

        // 3. Buscar Negocios específicos
        $biz_query = $wpdb->prepare( "
            SELECT post_title as label, 'business' as type, post_title as value 
            FROM {$wpdb->posts}
            WHERE post_type = 'babel_business' AND post_status = 'publish' AND post_title LIKE %s
            LIMIT 5
        ", $like_q );
        $bizs = $wpdb->get_results( $biz_query, ARRAY_A );
        if ( $bizs ) $results = array_merge( $results, $bizs );

        return rest_ensure_response( array( 'success' => true, 'data' => $results ) );
    }
}
