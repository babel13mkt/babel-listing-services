<?php
/**
 * Motor de Indexación Personalizada (MySQL High Performance)
 * v3.6 — Hito 6
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BD_Search_Index {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'bd_search_index';

        // Sincronización automática
        add_action( 'save_post_directorio_negocio', array( $this, 'sync_post_to_index' ), 20, 3 );
        add_action( 'delete_post', array( $this, 'remove_post_from_index' ) );
    }

    /**
     * Crear tabla de búsqueda
     */
    public function create_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $this->table_name (
            post_id bigint(20) UNSIGNED NOT NULL,
            category_id bigint(20) UNSIGNED DEFAULT 0,
            region_id bigint(20) UNSIGNED DEFAULT 0,
            latitude decimal(10,8) DEFAULT NULL,
            longitude decimal(11,8) DEFAULT NULL,
            rating_avg decimal(3,2) DEFAULT 0.00,
            rating_count int(11) DEFAULT 0,
            is_verified tinyint(1) DEFAULT 0,
            is_featured tinyint(1) DEFAULT 0,
            PRIMARY KEY  (post_id),
            KEY category_id (category_id),
            KEY region_id (region_id),
            KEY coords (latitude, longitude)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Sincronizar un post con el índice
     */
    public function sync_post_to_index( $post_id, $post, $update ) {
        // Evitar revisiones y autosaves
        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
            return;
        }

        // Verificar post type
        if ( 'directorio_negocio' !== get_post_type( $post_id ) ) {
            return;
        }

        global $wpdb;

        // Obtener Meta
        $lat = get_post_meta( $post_id, '_bd_latitud', true );
        $lng = get_post_meta( $post_id, '_bd_longitud', true );
        $rating = get_post_meta( $post_id, '_bd_reputacion', true );
        $is_verified = get_post_meta( $post_id, '_bd_verificado', true );
        $is_featured = get_post_meta( $post_id, '_bd_destacado', true );

        // Obtener Taxonomías
        $categories = wp_get_post_terms( $post_id, 'directorio_categoria', array( 'fields' => 'ids' ) );
        $regions = wp_get_post_terms( $post_id, 'directorio_region', array( 'fields' => 'ids' ) );

        $cat_id = ! empty( $categories ) && ! is_wp_error( $categories ) ? $categories[0] : 0;
        $reg_id = ! empty( $regions ) && ! is_wp_error( $regions ) ? $regions[0] : 0;

        // Limpiar data
        $data = array(
            'post_id'      => $post_id,
            'category_id'  => intval( $cat_id ),
            'region_id'    => intval( $reg_id ),
            'latitude'     => ! empty( $lat ) ? floatval( $lat ) : null,
            'longitude'    => ! empty( $lng ) ? floatval( $lng ) : null,
            'rating_avg'   => ! empty( $rating ) ? floatval( $rating ) : 0.00,
            'is_verified'  => ( '1' === $is_verified ) ? 1 : 0,
            'is_featured'  => ( '1' === $is_featured ) ? 1 : 0
        );

        $wpdb->replace( $this->table_name, $data );
    }

    /**
     * Eliminar un post del índice
     */
    public function remove_post_from_index( $post_id ) {
        global $wpdb;
        $wpdb->delete( $this->table_name, array( 'post_id' => $post_id ) );
    }
}
