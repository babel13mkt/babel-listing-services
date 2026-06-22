<?php
namespace Babel\Directory;

/**
 * Comandos de WP-CLI para administración asíncrona y mantenimiento masivo.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {

    class CLI {

        /**
         * Re-indexa de forma masiva todos los negocios de la plataforma.
         *
         * ## EXAMPLES
         *
         *     wp babel-directory index-rebuild
         */
        public function index_rebuild( $args, $assoc_args ) {
            \WP_CLI::log( 'Iniciando reconstrucción de índices de Babel Directory...' );
            global $wpdb;

            if ( ! class_exists( 'Babel\Directory\Search_Index' ) ) {
                \WP_CLI::error( 'El módulo Search_Index no está activo.' );
            }

            Search_Index::create_table();
            $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}bd_search_index" );

            $indexer = new Search_Index();
            $count   = $indexer->bulk_index_all_businesses();

            \WP_CLI::success( "Se indexaron {$count} negocios correctamente." );
        }

        /**
         * Purga la memoria caché (Transients) del directorio.
         *
         * ## EXAMPLES
         *
         *     wp babel-directory clear-cache
         */
        public function clear_cache( $args, $assoc_args ) {
            \WP_CLI::log( 'Purgando memoria caché de búsquedas...' );
            
            if ( class_exists( 'Babel\Directory\Cache' ) ) {
                $cache = new Cache();
                $cache->clear_all_transients();
                \WP_CLI::success( 'Caché eliminada con éxito.' );
            } else {
                \WP_CLI::error( 'El módulo de Caché no está activo.' );
            }
        }
    }

    \WP_CLI::add_command( 'babel-directory', __NAMESPACE__ . '\CLI' );
}
