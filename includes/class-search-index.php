<?php
/**
 * Motor de Indexación y Base de Datos para Búsquedas Rápidas
 * Fase 2 - Paso 2.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Evitar acceso directo
}

class Babel_Directory_Search_Index {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'bd_search_index';

        // Hooks de sincronización automática
        add_action( 'save_post_babel_business', array( $this, 'sync_business_to_index' ), 10, 3 );
        add_action( 'delete_post', array( $this, 'delete_business_from_index' ) );
    }

    /**
     * MICRO-PASO 1: Creación de la Tabla Personalizada (Se ejecuta en la activación del plugin)
     */
    public static function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bd_search_index';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            post_id bigint(20) UNSIGNED NOT NULL,
            category_id bigint(20) UNSIGNED DEFAULT 0,
            region_id bigint(20) UNSIGNED DEFAULT 0,
            latitude decimal(10,8) DEFAULT NULL,
            longitude decimal(11,8) DEFAULT NULL,
            rating_avg decimal(3,2) DEFAULT 0.00,
            is_verified tinyint(1) DEFAULT 0,
            is_featured tinyint(1) DEFAULT 0,
            PRIMARY KEY (post_id),
            KEY category_id (category_id),
            KEY region_id (region_id),
            KEY coords (latitude, longitude),
            KEY sorting (is_featured, rating_avg)
        ) ENGINE=InnoDB $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * MICRO-PASO 2: Sincronización Atómica al Guardar/Actualizar un CPT babel_business
     */
    public function sync_business_to_index( $post_id, $post, $update ) {
        global $wpdb;

        // Evitar ejecuciones duplicadas en revisiones o autosaves
        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
            return;
        }

        // Asegurar que el estado del post sea 'publish' (no indexar borradores)
        if ( $post->post_status !== 'publish' ) {
            $this->delete_business_from_index( $post_id );
            return;
        }

        // 1. Obtener Taxonomías (Primer término asignado)
        $categories = wp_get_post_terms( $post_id, 'babel_category', array( 'fields' => 'ids' ) );
        $regions    = wp_get_post_terms( $post_id, 'babel_region', array( 'fields' => 'ids' ) );

        $category_id = ! empty( $categories ) && ! is_wp_error( $categories ) ? intval( $categories[0] ) : 0;
        $region_id   = ! empty( $regions ) && ! is_wp_error( $regions ) ? intval( $regions[0] ) : 0;

        // 2. Obtener y sanitizar Coordenadas desde Meta (tratados estrictamente como floats)
        $lat = get_post_meta( $post_id, '_babel_latitude', true ); // Asegura mapear tus llaves correctas
        $lng = get_post_meta( $post_id, '_babel_longitude', true );

        $latitude  = ( $lat !== '' ) ? filter_var( $lat, FILTER_VALIDATE_FLOAT ) : null;
        $longitude = ( $lng !== '' ) ? filter_var( $lng, FILTER_VALIDATE_FLOAT ) : null;

        // 3. Obtener Flags de Control e Impacto Visual
        $is_verified = get_post_meta( $post_id, '_babel_is_verified', true ) ? 1 : 0;
        $is_featured = get_post_meta( $post_id, '_babel_is_featured', true ) ? 1 : 0;
        
        // El promedio de reviews por ahora inicia en 0.00 hasta integrar class-reviews
        $rating_avg  = get_post_meta( $post_id, '_babel_rating_avg', true );
        $rating_avg  = ( $rating_avg !== '' ) ? filter_var( $rating_avg, FILTER_VALIDATE_FLOAT ) : 0.00;

        // 4. Inserción/Reemplazo Atómico en la BD
        $wpdb->replace(
            $this->table_name,
            array(
                'post_id'     => $post_id,
                'category_id' => $category_id,
                'region_id'   => $region_id,
                'latitude'    => $latitude,
                'longitude'   => $longitude,
                'rating_avg'  => $rating_avg,
                'is_verified' => $is_verified,
                'is_featured' => $is_featured,
            ),
            array( '%d', '%d', '%d', '%f', '%f', '%f', '%d', '%d' )
        );
    }

    /**
     * MICRO-PASO 3: Limpieza Inmediata de Registros Eliminados
     */
    public function delete_business_from_index( $post_id ) {
        global $wpdb;
        
        // Verificar si el post eliminado pertenece a nuestro CPT antes de ejecutar la Query
        if ( get_post_type( $post_id ) === 'babel_business' ) {
            $wpdb->delete( $this->table_name, array( 'post_id' => $post_id ), array( '%d' ) );
        }
    }

    /**
     * MICRO-PASO 4: Indexador Masivo (Herramienta de migración para CLI o Panel de Control)
     */
    public function bulk_index_all_businesses() {
        global $wpdb;

        $args = array(
            'post_type'      => 'babel_business',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'fields'         => 'ids'
        );

        $query = new WP_Query( $args );
        $count = 0;

        if ( $query->have_posts() ) {
            foreach ( $query->posts as $post_id ) {
                $post = get_post( $post_id );
                $this->sync_business_to_index( $post_id, $post, true );
                $count++;
            }
        }

        return $count;
    }
}

/**
 * Soporte de WP-CLI para indexación masiva nativa del directorio.
 */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
    class Babel_Directory_CLI_Command {

        /**
         * Re-indexa de forma masiva todos los negocios de la plataforma.
         *
         * ## EXAMPLES
         *
         *     wp babel-directory index
         */
        public function index( $args, $assoc_args ) {
            WP_CLI::log( 'Iniciando indexación masiva de negocios...' );
            global $wpdb;

            // Asegurarse de que la tabla de indexación rápida exista
            Babel_Directory_Search_Index::create_table();

            // Limpiar la tabla de registros obsoletos para asegurar integridad
            $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}bd_search_index" );

            $indexer = new Babel_Directory_Search_Index();
            $count   = $indexer->bulk_index_all_businesses();

            WP_CLI::success( "Se indexaron {$count} negocios correctamente." );
        }
    }
    WP_CLI::add_command( 'babel-directory', 'Babel_Directory_CLI_Command' );
}
