<?php
/**
 * Importador Autónomo de JSON para Babel Directory (WP-CLI)
 * 
 * Uso: wp eval-file babel-auto-importer.php path/to/archivo.json
 */

if ( php_sapi_name() !== 'cli' || ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    die( 'Este script solo puede ejecutarse mediante WP-CLI.' );
}

$args = WP_CLI::get_runner()->arguments;
// WP-CLI eval-file strips the first argument (the script itself), so the next arguments are in $args.
$json_file = isset($args[0]) ? $args[0] : '';

if ( empty( $json_file ) || ! file_exists( $json_file ) ) {
    WP_CLI::error( "Debe proporcionar un archivo JSON válido. Uso: wp eval-file babel-auto-importer.php archivo.json" );
}

$json_data = file_get_contents( $json_file );
$records = json_decode( $json_data, true );

if ( ! is_array( $records ) ) {
    WP_CLI::error( "El archivo JSON no tiene un formato válido o no es un array de objetos." );
}

WP_CLI::log( "Iniciando importación de " . count( $records ) . " registros..." );

// Helper function to upload image from URL
function bd_auto_sideload_image( $url, $post_id, $desc = null ) {
    if ( empty( $url ) ) return false;
    
    // Check if the URL is valid
    if ( filter_var($url, FILTER_VALIDATE_URL) === false ) {
        return false;
    }

    require_once( ABSPATH . 'wp-admin/includes/media.php' );
    require_once( ABSPATH . 'wp-admin/includes/file.php' );
    require_once( ABSPATH . 'wp-admin/includes/image.php' );

    $attachment_id = media_sideload_image( $url, $post_id, $desc, 'id' );

    if ( is_wp_error( $attachment_id ) ) {
        WP_CLI::warning( "Error al descargar imagen: " . $url . " - " . $attachment_id->get_error_message() );
        return false;
    }

    return $attachment_id;
}

$imported_count = 0;

foreach ( $records as $record ) {
    $nombre      = isset( $record['nombre'] ) ? trim( $record['nombre'] ) : '';
    if ( empty( $nombre ) ) {
        continue;
    }

    // Check if it already exists to avoid duplicates
    $existing = get_page_by_title( $nombre, OBJECT, 'bd_institution' );
    if ( ! $existing ) {
        $existing = get_page_by_title( $nombre, OBJECT, 'babel_business' );
    }

    if ( $existing ) {
        WP_CLI::log( "Saltando: '{$nombre}' (Ya existe)." );
        continue;
    }

    $categoria   = isset( $record['categoria'] ) ? trim( $record['categoria'] ) : '';
    $region      = isset( $record['region'] ) ? trim( $record['region'] ) : '';
    $telefono    = isset( $record['telefono'] ) ? trim( $record['telefono'] ) : '';
    $direccion   = isset( $record['direccion'] ) ? trim( $record['direccion'] ) : '';
    $website     = isset( $record['website'] ) ? trim( $record['website'] ) : '';
    $descripcion = isset( $record['descripcion'] ) ? trim( $record['descripcion'] ) : '';
    $imagen_url  = isset( $record['imagen_destacada'] ) ? trim( $record['imagen_destacada'] ) : '';
    $servicios   = isset( $record['servicios'] ) ? $record['servicios'] : array();

    // Determinar Post Type
    $is_institution = in_array( strtolower($categoria), ['municipalidad', 'municipalidades', 'salud', 'educacion', 'educación', 'seguridad', 'gobierno'] );
    $post_type = $is_institution ? 'bd_institution' : 'babel_business';

    // Insertar Post
    $post_data = array(
        'post_title'   => sanitize_text_field( $nombre ),
        'post_content' => wp_kses_post( $descripcion ),
        'post_status'  => 'publish',
        'post_type'    => $post_type,
    );

    $post_id = wp_insert_post( $post_data );

    if ( is_wp_error( $post_id ) ) {
        WP_CLI::warning( "Error al crear: {$nombre}" );
        continue;
    }

    // Guardar Metadatos Unificados (compatibles con class-metaboxes.php y class-institution.php)
    if ( ! empty( $telefono ) ) {
        update_post_meta( $post_id, '_babel_phone', sanitize_text_field( $telefono ) );
        update_post_meta( $post_id, '_bd_telefono', sanitize_text_field( $telefono ) ); // Retro-compat
    }
    if ( ! empty( $direccion ) ) {
        update_post_meta( $post_id, '_babel_address', sanitize_text_field( $direccion ) );
        update_post_meta( $post_id, '_bd_direccion', sanitize_text_field( $direccion ) );
    }
    if ( ! empty( $website ) ) {
        update_post_meta( $post_id, '_babel_website', sanitize_url( $website ) );
        update_post_meta( $post_id, '_bd_sitio_web', sanitize_url( $website ) );
    }
    if ( $is_institution ) {
        update_post_meta( $post_id, '_babel_is_institution', 'yes' );
    }

    // Gestionar Categoría (babel_category)
    if ( ! empty( $categoria ) ) {
        $term = term_exists( $categoria, 'babel_category' );
        if ( ! $term ) {
            $term = wp_insert_term( $categoria, 'babel_category' );
        }
        if ( ! is_wp_error( $term ) ) {
            wp_set_object_terms( $post_id, (int) $term['term_id'], 'babel_category' );
        }
    }

    // Gestionar Región (bd_region)
    if ( ! empty( $region ) ) {
        $term_region = term_exists( $region, 'bd_region' );
        if ( ! $term_region ) {
            $term_region = wp_insert_term( $region, 'bd_region' );
        }
        if ( ! is_wp_error( $term_region ) ) {
            wp_set_object_terms( $post_id, (int) $term_region['term_id'], 'bd_region' );
        }
    }

    // Gestionar Servicios / Tags Dinámicos
    if ( ! empty( $servicios ) && is_array( $servicios ) ) {
        $servicios_sanitizados = array_map( 'sanitize_text_field', $servicios );
        update_post_meta( $post_id, '_babel_biz_tags', implode( ',', $servicios_sanitizados ) );
    } elseif ( ! empty( $servicios ) && is_string( $servicios ) ) {
        update_post_meta( $post_id, '_babel_biz_tags', sanitize_text_field( $servicios ) );
    }

    // Imagen Destacada
    if ( ! empty( $imagen_url ) ) {
        WP_CLI::log( " - Descargando imagen: {$imagen_url}" );
        $attach_id = bd_auto_sideload_image( $imagen_url, $post_id, $nombre );
        if ( $attach_id ) {
            set_post_thumbnail( $post_id, $attach_id );
            update_post_meta( $post_id, '_bd_logo_id', $attach_id );
        }
    }

    WP_CLI::success( "Importado: {$nombre}" );
    $imported_count++;
}

WP_CLI::success( "Proceso completado. Registros importados exitosamente: {$imported_count}" );

// Mover archivo para no reprocesar
$done_dir = dirname($json_file) . '/procesados';
if ( ! is_dir( $done_dir ) ) {
    mkdir( $done_dir, 0755, true );
}
rename( $json_file, $done_dir . '/' . basename($json_file) );
WP_CLI::log( "Archivo movido a: " . $done_dir );
