<?php
/**
 * Script de importación y asociación de imágenes de regiones para Babel Directory.
 * Ejecutar vía WP-CLI: wp eval-file bin/import_regions.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    die( 'Acceso denegado.' );
}

// Asegurar que las funciones de administración de imágenes estén cargadas
require_once( ABSPATH . 'wp-admin/includes/image.php' );
require_once( ABSPATH . 'wp-admin/includes/file.php' );
require_once( ABSPATH . 'wp-admin/includes/media.php' );

$mapping = array(
    'arica'         => 'arica_parinacota.png',
    'tarapaca'      => 'tarapaca.png',
    'tarapacá'      => 'tarapaca.png',
    'antofagasta'   => 'antofagasta.png',
    'atacama'       => 'atacama.png',
    'coquimbo'      => 'coquimbo.png',
    'valparaiso'    => 'valparaiso.png',
    'valparaíso'    => 'valparaiso.png',
    'higgins'       => 'ohiggins.png',
    'maule'         => 'maule.png',
    'biobio'        => 'biobio.png',
    'bío'           => 'biobio.png',
    'araucania'     => 'araucania.png',
    'araucanía'     => 'araucania.png',
    'lagos'         => 'los_lagos.png',
    'aysen'         => 'aysen.png',
    'aysén'         => 'aysen.png',
    'magallanes'    => 'magallanes.png',
    'metropolitana' => 'metropolitana.png',
    'rios'          => 'los_rios.png',
    'ríos'          => 'los_rios.png',
    'nuble'         => 'nuble.png',
    'ñuble'         => 'nuble.png',
);

$terms = get_terms( array(
    'taxonomy'   => 'babel_region',
    'hide_empty' => false,
    'parent'     => 0,
) );

if ( is_wp_error( $terms ) || empty( $terms ) ) {
    echo "Error: No se encontraron términos en la taxonomía babel_region.\n";
    exit( 1 );
}

echo "Iniciando importación de imágenes para " . count( $terms ) . " regiones...\n";

foreach ( $terms as $term ) {
    // Evitar duplicar si ya tiene una imagen asociada válida en la BD
    $existing_image_id = get_term_meta( $term->term_id, 'bd_term_image_id', true );
    if ( ! empty( $existing_image_id ) && wp_get_attachment_url( $existing_image_id ) ) {
        echo "⏭️  Saltando: '{$term->name}' ya tiene una imagen asociada (Media ID: {$existing_image_id})\n";
        continue;
    }

    $matched_file = '';
    $term_name_lower = mb_strtolower( $term->name, 'UTF-8' );
    foreach ( $mapping as $key => $file ) {
        $key_lower = mb_strtolower( $key, 'UTF-8' );
        if ( mb_strpos( $term_name_lower, $key_lower ) !== false ) {
            $matched_file = $file;
            break;
        }
    }

    if ( empty( $matched_file ) ) {
        echo "⚠️  No se encontró coincidencia de imagen para el término: '{$term->name}'\n";
        continue;
    }

    $file_path = dirname( __DIR__ ) . '/assets/images/regiones/' . $matched_file;

    if ( ! file_exists( $file_path ) ) {
        echo "❌ Archivo no existe: {$file_path}\n";
        continue;
    }

    // Copiar el archivo al directorio temporal de WordPress uploads para su importación
    $upload_dir = wp_upload_dir();
    if ( ! empty( $upload_dir['error'] ) ) {
        echo "❌ Error en directorio de subidas: {$upload_dir['error']}\n";
        continue;
    }

    $filename = basename( $file_path );
    $target_path = $upload_dir['path'] . '/' . $filename;

    // Si ya existe un archivo con el mismo nombre, le concatenamos un hash único para evitar colisiones
    if ( file_exists( $target_path ) ) {
        $name_part = pathinfo( $filename, PATHINFO_FILENAME );
        $ext_part  = pathinfo( $filename, PATHINFO_EXTENSION );
        $filename  = $name_part . '_' . time() . '.' . $ext_part;
        $target_path = $upload_dir['path'] . '/' . $filename;
    }

    if ( ! copy( $file_path, $target_path ) ) {
        echo "❌ No se pudo copiar el archivo al directorio de destino: {$target_path}\n";
        continue;
    }

    // Preparar el attachment
    $file_mime = mime_content_type( $target_path );
    $attachment = array(
        'post_mime_type' => $file_mime,
        'post_title'     => sanitize_file_name( pathinfo( $filename, PATHINFO_FILENAME ) ),
        'post_content'   => '',
        'post_status'    => 'inherit'
    );

    // Insertar el attachment
    $attach_id = wp_insert_attachment( $attachment, $target_path );

    if ( is_wp_error( $attach_id ) || ! $attach_id ) {
        echo "❌ Error al insertar el attachment para '{$term->name}': " . ( is_wp_error( $attach_id ) ? $attach_id->get_error_message() : 'ID inválido' ) . "\n";
        unlink( $target_path );
        continue;
    }

    // Generar y actualizar los metadatos de la imagen
    $attach_data = wp_generate_attachment_metadata( $attach_id, $target_path );
    wp_update_attachment_metadata( $attach_id, $attach_data );

    // Asociar el attachment al término de la región
    $result = update_term_meta( $term->term_id, 'bd_term_image_id', $attach_id );

    if ( is_wp_error( $result ) ) {
        echo "❌ Error al actualizar el meta del término para '{$term->name}': " . $result->get_error_message() . "\n";
    } else {
        echo "✅ Asociado: '{$term->name}' -> '{$matched_file}' (Media ID: {$attach_id})\n";
    }
}

echo "Proceso finalizado.\n";
