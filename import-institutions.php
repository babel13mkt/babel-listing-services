<?php
/**
 * Script de importación masiva de instituciones para Babel Directory
 * 
 * Uso desde WP-CLI:
 *   wp eval-file import-institutions.php /ruta/a/instituciones_completas.json
 *
 * Mapea los campos del JSON a los meta fields del CPT bd_institution
 * y también crea/relaciona con babel_business para las que tienen datos de contacto.
 */

if ( php_sapi_name() !== 'cli' || ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    die( 'Este script solo puede ejecutarse mediante WP-CLI.' );
}

$json_file = isset( $args[0] ) ? $args[0] : '';

if ( empty( $json_file ) || ! file_exists( $json_file ) ) {
    WP_CLI::error( "Debe proporcionar un archivo JSON válido. Uso: wp eval-file import-institutions.php archivo.json" );
}

$json_data = file_get_contents( $json_file );
$records  = json_decode( $json_data, true );

if ( ! is_array( $records ) ) {
    WP_CLI::error( "El archivo JSON no tiene un formato válido o no es un array de objetos." );
}

WP_CLI::log( "Iniciando importación de " . count( $records ) . " instituciones..." );

$imported    = 0;
$skipped     = 0;
$errors      = 0;

foreach ( $records as $record ) {
    $nombre = sanitize_text_field( $record['nombre'] ?? '' );
    $comuna = sanitize_text_field( $record['comuna'] ?? '' );
    $region = sanitize_text_field( $record['region'] ?? '' );

    if ( empty( $nombre ) ) {
        $skipped++;
        continue;
    }

    // Verificar si ya existe (por título + comuna)
    $existing = get_posts( array(
        'post_type'      => 'bd_institution',
        'post_status'    => 'any',
        'title'          => $nombre,
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ) );

    if ( ! empty( $existing ) ) {
        // Verificar si es la misma comuna
        $existing_comuna = get_post_meta( $existing[0], '_bd_institucion_comuna', true );
        if ( strtolower( $existing_comuna ) === strtolower( $comuna ) ) {
            $skipped++;
            continue;
        }
    }

    // Crear el post de institución
    $post_data = array(
        'post_title'   => $nombre,
        'post_content' => sanitize_textarea_field( $record['descripcion'] ?? '' ),
        'post_status'  => 'publish',
        'post_type'    => 'bd_institution',
    );

    $post_id = wp_insert_post( $post_data, true );

    if ( is_wp_error( $post_id ) ) {
        WP_CLI::warning( "Error al crear '{$nombre}': " . $post_id->get_error_message() );
        $errors++;
        continue;
    }

    // Mapeo de meta fields
    $meta_map = array(
        '_bd_institucion_tipo'       => 'categoria',
        '_bd_institucion_comuna'     => 'comuna',
        '_bd_institucion_region'     => 'region',
        '_bd_institucion_telefono'   => 'telefono',
        '_bd_institucion_direccion'  => 'direccion',
        '_bd_institucion_web'        => 'website',
        '_bd_institucion_latitud'    => 'lat',
        '_bd_institucion_longitud'   => 'lng',
    );

    foreach ( $meta_map as $meta_key => $json_key ) {
        $value = $record[ $json_key ] ?? '';
        if ( ! empty( $value ) ) {
            update_post_meta( $post_id, $meta_key, sanitize_text_field( $value ) );
        }
    }

    // Marcar como institución verificada (datos oficiales)
    update_post_meta( $post_id, '_bd_institucion_verificada', '1' );

    // Asignar taxonomía de región si existe
    if ( ! empty( $region ) ) {
        $term = term_exists( $region, 'babel_region' );
        if ( ! $term ) {
            $term = wp_insert_term( $region, 'babel_region' );
        }
        if ( ! is_wp_error( $term ) ) {
            wp_set_object_terms( $post_id, intval( $term['term_id'] ), 'babel_region' );
        }
    }

    // Asignar taxonomía de categoría si existe
    $categoria = $record['categoria'] ?? '';
    if ( ! empty( $categoria ) ) {
        $term = term_exists( $categoria, 'babel_category' );
        if ( ! $term ) {
            $term = wp_insert_term( $categoria, 'babel_category' );
        }
        if ( ! is_wp_error( $term ) ) {
            wp_set_object_terms( $post_id, intval( $term['term_id'] ), 'babel_category' );
        }
    }

    $imported++;

    if ( $imported % 100 === 0 ) {
        WP_CLI::log( "Progreso: {$imported}/" . count( $records ) . " importadas..." );
    }
}

WP_CLI::success( "Importación completada:" );
WP_CLI::log( "  Importadas: {$imported}" );
WP_CLI::log( "  Omitidas (ya existían): {$skipped}" );
WP_CLI::log( "  Errores: {$errors}" );
