<?php
namespace Babel\Directory;

/**
 * Motor de Indexación y Base de Datos para Búsquedas Rápidas
 * Fase 2 - Paso 2.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Evitar acceso directo
}
class Search_Index {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'bd_search_index';

        // Hooks de sincronización automática
        add_action( 'save_post_babel_business', array( $this, 'sync_business_to_index' ), 10, 3 );
        add_action( 'save_post_bd_institution', array( $this, 'sync_institution_to_index' ), 10, 3 );
        add_action( 'delete_post', array( $this, 'delete_from_index' ) );
        
        // Hook personalizado para sincronizar DESPUÉS de que se hayan guardado todos los metadatos en AJAX
        add_action( 'bd_after_business_saved', array( $this, 'sync_after_ajax_save' ) );
    }

    /**
     * Sincroniza desde el hook personalizado de la SPA
     */
    public function sync_after_ajax_save( $post_id ) {
        $this->sync_business_to_index( $post_id, get_post( $post_id ), true );
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
            post_type varchar(20) DEFAULT 'babel_business',
            category_id bigint(20) UNSIGNED DEFAULT 0,
            region_id bigint(20) UNSIGNED DEFAULT 0,
            latitude decimal(10,8) DEFAULT NULL,
            longitude decimal(11,8) DEFAULT NULL,
            rating_avg decimal(3,2) DEFAULT 0.00,
            is_verified tinyint(1) DEFAULT 0,
            is_featured tinyint(1) DEFAULT 0,
            featured_expires DATETIME DEFAULT NULL,
            PRIMARY KEY (post_id),
            KEY post_type (post_type),
            KEY category_id (category_id),
            KEY region_id (region_id),
            KEY coords (latitude, longitude),
            KEY sorting (is_featured, rating_avg),
            KEY featured_expires (featured_expires)
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
            $this->delete_from_index( $post_id );
            return;
        }

        // 1. Obtener Taxonomías (Primer término asignado)
        $categories = wp_get_post_terms( $post_id, 'babel_category', array( 'fields' => 'ids' ) );
        $regions    = wp_get_post_terms( $post_id, 'babel_region', array( 'fields' => 'ids' ) );

        $category_id = ! empty( $categories ) && ! \is_wp_error( $categories ) ? intval( $categories[0] ) : 0;
        $region_id   = ! empty( $regions ) && ! \is_wp_error( $regions ) ? intval( $regions[0] ) : 0;

        // 2. Obtener y sanitizar Coordenadas desde Meta (tratados estrictamente como floats)
        // Soporte de múltiples llaves para compatibilidad con distintos flujos de guardado
        $lat = get_post_meta( $post_id, '_babel_latitude', true );
        if ( empty( $lat ) ) {
            $lat = get_post_meta( $post_id, '_babel_lat', true );
        }
        if ( empty( $lat ) ) {
            $lat = get_post_meta( $post_id, '_bd_latitud', true );
        }
        $lng = get_post_meta( $post_id, '_babel_longitude', true );
        if ( empty( $lng ) ) {
            $lng = get_post_meta( $post_id, '_babel_lng', true );
        }
        if ( empty( $lng ) ) {
            $lng = get_post_meta( $post_id, '_bd_longitud', true );
        }

        $latitude  = ( $lat !== '' && $lat !== false ) ? filter_var( $lat, FILTER_VALIDATE_FLOAT ) : null;
        $longitude = ( $lng !== '' && $lng !== false ) ? filter_var( $lng, FILTER_VALIDATE_FLOAT ) : null;

        // 3. Obtener Flags de Control e Impacto Visual
        $is_verified = get_post_meta( $post_id, '_babel_is_verified', true ) ? 1 : 0;
        $is_featured = get_post_meta( $post_id, '_babel_is_featured', true ) ? 1 : 0;

        // Featured v2: calcular is_featured dinámicamente desde fecha de expiración
        $featured_expires = get_post_meta( $post_id, '_babel_featured_expires', true );
        if ( ! empty( $featured_expires ) ) {
            $expires_ts = strtotime( $featured_expires );
            $now_ts     = current_time( 'timestamp' );
            // Si el featured expiró, forzar is_featured = 0 (salvo Premium, se maneja en class-featured-listings)
            if ( $expires_ts > 0 && $expires_ts < $now_ts ) {
                $is_featured = 0;
            } else {
                $is_featured = 1;
            }
        }

        // El promedio de reviews por ahora inicia en 0.00 hasta integrar class-reviews
        $rating_avg  = get_post_meta( $post_id, '_babel_rating_avg', true );
        $rating_avg  = ( $rating_avg !== '' ) ? filter_var( $rating_avg, FILTER_VALIDATE_FLOAT ) : 0.00;

        // Formatear featured_expires para DATETIME de MySQL
        $featured_expires_db = null;
        if ( ! empty( $featured_expires ) ) {
            $featured_expires_db = date( 'Y-m-d H:i:s', strtotime( $featured_expires ) );
        }

        // 4. Inserción/Reemplazo Atómico en la BD
        $wpdb->replace(
            $this->table_name,
            array(
                'post_id'          => $post_id,
                'post_type'        => 'babel_business',
                'category_id'      => $category_id,
                'region_id'        => $region_id,
                'latitude'         => $latitude,
                'longitude'        => $longitude,
                'rating_avg'       => $rating_avg,
                'is_verified'      => $is_verified,
                'is_featured'      => $is_featured,
                'featured_expires' => $featured_expires_db,
            ),
            array( '%d', '%s', '%d', '%d', '%f', '%f', '%f', '%d', '%d', '%s' )
        );
    }

    /**
     * Sincroniza al guardar/actualizar un CPT bd_institution
     */
    public function sync_institution_to_index( $post_id, $post, $update ) {
        global $wpdb;

        // Evitar ejecuciones duplicadas en revisiones o autosaves
        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
            return;
        }

        // Asegurar que el estado del post sea 'publish' (no indexar borradores)
        if ( $post->post_status !== 'publish' ) {
            $this->delete_from_index( $post_id );
            return;
        }

        // 1. Obtener Taxonomías
        $categories = wp_get_post_terms( $post_id, 'babel_category', array( 'fields' => 'ids' ) );
        $regions    = wp_get_post_terms( $post_id, 'babel_region', array( 'fields' => 'ids' ) );

        $category_id = ! empty( $categories ) && ! \is_wp_error( $categories ) ? intval( $categories[0] ) : 0;
        $region_id   = ! empty( $regions ) && ! \is_wp_error( $regions ) ? intval( $regions[0] ) : 0;

        // 2. Obtener Coordenadas
        $lat = get_post_meta( $post_id, '_babel_latitude', true );
        if ( empty( $lat ) ) {
            $lat = get_post_meta( $post_id, '_babel_lat', true );
        }
        $lng = get_post_meta( $post_id, '_babel_longitude', true );
        if ( empty( $lng ) ) {
            $lng = get_post_meta( $post_id, '_babel_lng', true );
        }

        $latitude  = ( $lat !== '' ) ? filter_var( $lat, FILTER_VALIDATE_FLOAT ) : null;
        $longitude = ( $lng !== '' ) ? filter_var( $lng, FILTER_VALIDATE_FLOAT ) : null;

        // 3. Flags
        $is_verified = get_post_meta( $post_id, '_babel_is_verified', true ) ? 1 : 0;
        $is_featured = get_post_meta( $post_id, '_babel_is_featured', true ) ? 1 : 0;

        // 4. Inserción/Reemplazo Atómico
        $wpdb->replace(
            $this->table_name,
            array(
                'post_id'          => $post_id,
                'post_type'        => 'bd_institution',
                'category_id'      => $category_id,
                'region_id'        => $region_id,
                'latitude'         => $latitude,
                'longitude'        => $longitude,
                'rating_avg'       => 0.00,
                'is_verified'      => $is_verified,
                'is_featured'      => $is_featured,
                'featured_expires' => null,
            ),
            array( '%d', '%s', '%d', '%d', '%f', '%f', '%f', '%d', '%d', '%s' )
        );
    }

    /**
     * Limpieza Inmediata de Registros Eliminados (soporta ambos CPTs)
     */
    public function delete_from_index( $post_id ) {
        global $wpdb;
        
        $post_type = get_post_type( $post_id );
        if ( in_array( $post_type, array( 'babel_business', 'bd_institution' ), true ) ) {
            $wpdb->delete( $this->table_name, array( 'post_id' => $post_id ), array( '%d' ) );
        }
    }

    /**
     * Indexador Masivo (migración para CLI o Panel de Control)
     * Indexa tanto negocios como instituciones.
     */
    public function bulk_index_all() {
        global $wpdb;

        $post_types = array( 'babel_business', 'bd_institution' );
        $count = 0;

        foreach ( $post_types as $pt ) {
            $args = array(
                'post_type'      => $pt,
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'fields'         => 'ids'
            );

            $query = new \WP_Query( $args );

            if ( $query->have_posts() ) {
                foreach ( $query->posts as $post_id ) {
                    $post = get_post( $post_id );
                    if ( 'bd_institution' === $pt ) {
                        $this->sync_institution_to_index( $post_id, $post, true );
                    } else {
                        $this->sync_business_to_index( $post_id, $post, true );
                    }
                    $count++;
                }
            }
            wp_reset_postdata();
        }

        return $count;
    }

    /**
     * Migración: agrega columna featured_expires si no existe (actualización in-place).
     * Se ejecuta en la activación del plugin o vía CLI.
     */
    public static function maybe_upgrade_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bd_search_index';

        // Verificar si la columna ya existe
        $column_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND COLUMN_NAME = 'featured_expires'",
                $table_name
            )
        );

        if ( ! $column_exists ) {
            $wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN featured_expires DATETIME DEFAULT NULL AFTER is_featured, ADD INDEX idx_featured_expires (featured_expires)" );
        }
    }

    /**
     * @deprecated Usar bulk_index_all() en su lugar
     */
    public function bulk_index_all_businesses() {
        return $this->bulk_index_all();
    }
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
    class CLI_Command {

        /**
         * Re-indexa de forma masiva todos los negocios de la plataforma.
         *
         * ## EXAMPLES
         *
         *     wp babel-directory index
         */
        public function index( $args, $assoc_args ) {
            \WP_CLI::log( 'Iniciando indexación masiva de negocios...' );
            global $wpdb;

            // Asegurarse de que la tabla de indexación rápida exista
            Search_Index::create_table();

            // Limpiar la tabla de registros obsoletos para asegurar integridad
            $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}bd_search_index" );

            $indexer = new Search_Index();
            $count   = $indexer->bulk_index_all();

            \WP_CLI::success( "Se indexaron {$count} elementos (negocios + instituciones) correctamente." );
        }
    }
    \WP_CLI::add_command( 'babel-directory', __NAMESPACE__ . '\CLI_Command' );
}
