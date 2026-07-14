<?php
namespace Babel\Directory\Geo;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Índice Geoespacial Limpio (Clean Slate)
 *
 * Tabla: wp_babel_geo_index
 * Tier 1: Regiones (taxonomy babel_region, post_id negativo)
 * Tier 2: Negocios/Instituciones (post_id positivo)
 *
 * Elimina duplicación de lógica Haversine en AJAX v1 + REST v1.
 */

class Index {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'babel_geo_index';

        // Hooks de sincronización automática
        add_action( 'save_post_babel_business', [ $this, 'sync_business' ], 10, 3 );
        add_action( 'save_post_bd_institution', [ $this, 'sync_institution' ], 10, 3 );
        add_action( 'delete_post', [ $this, 'delete_from_index' ] );
    }

    /**
     * Crea la tabla en activación del plugin.
     */
    public static function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'babel_geo_index';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            post_id bigint(20) NOT NULL,
            tier tinyint(1) NOT NULL DEFAULT 2,
            latitude decimal(10,8) DEFAULT NULL,
            longitude decimal(11,8) DEFAULT NULL,
            region_id bigint(20) UNSIGNED DEFAULT 0,
            category_id bigint(20) UNSIGNED DEFAULT 0,
            rating_avg decimal(3,2) DEFAULT 0.00,
            is_verified tinyint(1) DEFAULT 0,
            is_featured tinyint(1) DEFAULT 0,
            PRIMARY KEY (post_id, tier),
            KEY tier (tier),
            KEY region_id (region_id),
            KEY category_id (category_id),
            KEY coords (latitude, longitude),
            KEY sorting (tier, is_featured, rating_avg)
        ) ENGINE=InnoDB $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Sincroniza un negocio (tier=2) al índice.
     */
    public function sync_business( $post_id, $post, $update ) {
        $this->sync_entity( $post_id, $post, 2 );
    }

    /**
     * Sincroniza una institución (tier=2) al índice.
     */
    public function sync_institution( $post_id, $post, $update ) {
        $this->sync_entity( $post_id, $post, 2 );
    }

    /**
     * Lógica común de sincronización para tier=2 (negocios e instituciones).
     */
    private function sync_entity( $post_id, $post, $tier ) {
        global $wpdb;

        // Evitar revisiones y autosaves
        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
            return;
        }

        // Solo indexar publicados
        if ( $post->post_status !== 'publish' ) {
            $this->delete_from_index( $post_id );
            return;
        }

        // Taxonomías (primer término asignado)
        $categories = wp_get_post_terms( $post_id, 'babel_category', [ 'fields' => 'ids' ] );
        $regions    = wp_get_post_terms( $post_id, 'babel_region', [ 'fields' => 'ids' ] );

        $category_id = ! empty( $categories ) && ! is_wp_error( $categories ) ? intval( $categories[0] ) : 0;
        $region_id   = ! empty( $regions ) && ! is_wp_error( $regions ) ? intval( $regions[0] ) : 0;

        // Coordenadas (soporta múltiples meta keys legacy)
        $lat = get_post_meta( $post_id, '_babel_latitude', true );
        if ( empty( $lat ) ) { $lat = get_post_meta( $post_id, '_babel_lat', true ); }
        if ( empty( $lat ) ) { $lat = get_post_meta( $post_id, '_bd_latitud', true ); }

        $lng = get_post_meta( $post_id, '_babel_longitude', true );
        if ( empty( $lng ) ) { $lng = get_post_meta( $post_id, '_babel_lng', true ); }
        if ( empty( $lng ) ) { $lng = get_post_meta( $post_id, '_bd_longitud', true ); }

        $latitude  = ( $lat !== '' && $lat !== false ) ? filter_var( $lat, FILTER_VALIDATE_FLOAT ) : null;
        $longitude = ( $lng !== '' && $lng !== false ) ? filter_var( $lng, FILTER_VALIDATE_FLOAT ) : null;

        // Flags
        $is_verified = get_post_meta( $post_id, '_babel_is_verified', true ) ? 1 : 0;
        $is_featured = get_post_meta( $post_id, '_babel_is_featured', true ) ? 1 : 0;

        // Rating
        $rating_avg = get_post_meta( $post_id, '_babel_rating_avg', true );
        $rating_avg = ( $rating_avg !== '' ) ? filter_var( $rating_avg, FILTER_VALIDATE_FLOAT ) : 0.00;

        // Upsert atómico
        $wpdb->replace(
            $this->table_name,
            [
                'post_id'      => $post_id,
                'tier'         => $tier,
                'latitude'     => $latitude,
                'longitude'    => $longitude,
                'region_id'    => $region_id,
                'category_id'  => $category_id,
                'rating_avg'   => $rating_avg,
                'is_verified'  => $is_verified,
                'is_featured'  => $is_featured,
            ],
            [ '%d', '%d', '%f', '%f', '%d', '%d', '%f', '%d', '%d' ]
        );
    }

    /**
     * Elimina del índice al borrar el post.
     */
    public function delete_from_index( $post_id ) {
        global $wpdb;
        $post_type = get_post_type( $post_id );
        if ( in_array( $post_type, [ 'babel_business', 'bd_institution' ], true ) ) {
            $wpdb->delete( $this->table_name, [ 'post_id' => $post_id ], [ '%d' ] );
        }
    }

    /**
     * Indexación masiva (WP-CLI o panel admin).
     */
    public function bulk_index_all() {
        global $wpdb;
        $post_types = [ 'babel_business', 'bd_institution' ];
        $count = 0;

        foreach ( $post_types as $pt ) {
            $args = [
                'post_type'      => $pt,
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'fields'         => 'ids',
            ];

            $query = new \WP_Query( $args );
            if ( $query->have_posts() ) {
                foreach ( $query->posts as $post_id ) {
                    $post = get_post( $post_id );
                    $this->sync_entity( $post_id, $post, 2 );
                    $count++;
                }
            }
            wp_reset_postdata();
        }

        return $count;
    }
}

// Registro en WP-CLI
if ( defined( 'WP_CLI' ) && WP_CLI ) {
    \WP_CLI::add_command( 'babel-directory geo-index', function( $args, $assoc_args ) {
        global $wpdb;
        $index = new \Babel\Directory\Geo\Index();
        \Babel\Directory\Geo\Index::create_table();
        $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}babel_geo_index" );
        $count = $index->bulk_index_all();
        \WP_CLI::success( "Se indexaron {$count} elementos (negocios + instituciones) en wp_babel_geo_index." );
    } );
}