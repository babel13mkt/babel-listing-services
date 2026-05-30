<?php
/**
 * Migración masiva de meta keys viejas (_bd_*) a nuevas (_babel_*)
 * Ejecución vía: wp eval-file bin/migrate_metas.php
 */

if ( 'cli' !== php_sapi_name() ) {
    die( 'Este script solo puede ejecutarse mediante WP-CLI.' );
}

WP_CLI::line( 'Iniciando migración de meta keys...' );

// Mapa de migración de claves
$meta_map = array(
    '_bd_telefono'        => '_babel_phone',
    '_bd_whatsapp'        => '_babel_whatsapp',
    '_bd_latitud'         => '_babel_lat',
    '_bd_longitud'        => '_babel_lng',
    '_bd_direccion'       => '_babel_address',
    '_bd_galeria'         => '_babel_gallery',
    '_bd_wifi'            => '_babel_attr_wifi',
    '_bd_estacionamiento' => '_babel_attr_parking',
    '_bd_delivery'        => '_babel_attr_delivery',
    '_bd_accesibilidad'   => '_babel_attr_accesibilidad',
);

$paged = 1;
$posts_per_page = 100;
$total_updated = 0;

while ( true ) {
    $args = array(
        'post_type'      => 'babel_business',
        'post_status'    => 'any',
        'posts_per_page' => $posts_per_page,
        'paged'          => $paged,
        'fields'         => 'ids',
    );

    $query = new WP_Query( $args );
    $post_ids = $query->posts;

    if ( empty( $post_ids ) ) {
        break;
    }

    foreach ( $post_ids as $post_id ) {
        $updated_for_post = false;
        
        foreach ( $meta_map as $old_key => $new_key ) {
            // Verificar si existe el valor viejo
            $old_val = get_post_meta( $post_id, $old_key, true );
            
            if ( $old_val !== '' ) {
                // Solo actualizar si el nuevo no existe, para no pisar datos nuevos si alguien ya editó
                $new_val = get_post_meta( $post_id, $new_key, true );
                if ( $new_val === '' ) {
                    update_post_meta( $post_id, $new_key, $old_val );
                    $updated_for_post = true;
                }
            }
        }
        
        if ( $updated_for_post ) {
            $total_updated++;
        }
    }

    $paged++;
}

WP_CLI::success( "Migración completada. Se actualizaron metas para $total_updated negocios." );
