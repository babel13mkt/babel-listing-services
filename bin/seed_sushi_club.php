<?php
/**
 * Script para rellenar los datos de prueba de "Sushi Club Santiago"
 * Ejecutar vía WP-CLI: wp eval-file bin/seed_sushi_club.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    die( 'Acceso denegado.' );
}

echo "Iniciando seeding de datos para Sushi Club Santiago...\n";

// Buscar el negocio
$post = get_page_by_title('Sushi Club Santiago', OBJECT, 'babel_business');

if ( ! $post ) {
    // Crear el negocio si no existe
    $post_id = wp_insert_post([
        'post_title' => 'Sushi Club Santiago',
        'post_type' => 'babel_business',
        'post_status' => 'publish',
        'post_content' => 'En Sushi Club Santiago, fusionamos la precisión de la técnica tradicional japonesa con el espíritu vibrante de la vanguardia culinaria. Cada bocado es una obra de arte diseñada para deleitar los sentidos, utilizando solo los ingredientes más frescos seleccionados directamente de los mejores proveedores.

Nuestra propuesta se centra en la exclusividad y la comodidad, ofreciendo un ambiente sofisticado que invita a la calma y al disfrute compartido. Desde nuestra apertura en el corazón de Providencia, nos hemos convertido en el destino predilecto para quienes buscan excelencia gastronómica en cada detalle.'
    ]);
    echo "Negocio creado con ID: $post_id\n";
} else {
    $post_id = $post->ID;
    echo "Negocio encontrado con ID: $post_id\n";
}

// 1. Llenar metadata básica
$meta_data = [
    '_babel_phone' => '+56 9 1234 5678',
    '_babel_whatsapp' => '+56912345678',
    '_babel_email' => 'contacto@sushiclub.cl',
    '_babel_website' => 'https://sushiclub.cl',
    '_babel_address' => 'Avenida Providencia 1234, Santiago, Chile',
    '_babel_instagram' => 'sushiclub_cl',
    '_babel_facebook' => 'sushiclubsantiago',
    '_babel_verified' => '1',
    '_babel_featured' => '1',
    '_babel_lat' => '-33.42628',
    '_babel_lng' => '-70.61200',
    '_babel_parking' => 'yes',
    '_babel_pet_friendly' => 'yes',
    '_babel_wifi' => 'yes',
    '_babel_reservations' => '1',
    '_babel_delivery' => '1',
    '_babel_price_range' => '$$ Moderado',
    '_babel_biz_type' => 'physical',
    '_babel_rut' => '76.123.456-7',
    '_babel_razon_social' => 'Sushi Club SpA'
];

foreach ($meta_data as $key => $value) {
    update_post_meta($post_id, $key, $value);
}

// 2. Horarios
$hours = [
    'monday' => ['open' => '10:00', 'close' => '20:00'],
    'tuesday' => ['open' => '10:00', 'close' => '20:00'],
    'wednesday' => ['open' => '10:00', 'close' => '20:00'],
    'thursday' => ['open' => '10:00', 'close' => '22:00'],
    'friday' => ['open' => '10:00', 'close' => '23:00'],
    'saturday' => ['open' => '12:00', 'close' => '23:00'],
    'sunday' => ['closed' => true]
];
update_post_meta($post_id, '_babel_hours', json_encode($hours));

// 3. Crear Reviews (solo si no existen)
$existing_reviews = get_comments(['post_id' => $post_id, 'type' => 'babel_review']);
if ( empty($existing_reviews) ) {
    $reviews = [
        ['author' => 'María José T.', 'content' => 'Excelente lugar, la atención es increíble y 100% recomendado para visitar en pareja.', 'rating' => 5],
        ['author' => 'Carlos R.', 'content' => 'El mejor sushi de Providencia sin dudas. Ingredientes muy frescos.', 'rating' => 5],
        ['author' => 'Ana P.', 'content' => 'Muy buen ambiente, aunque la música estaba un poco alta.', 'rating' => 4]
    ];
    
    $total_rating = 0;
    foreach ($reviews as $rev) {
        $comment_id = wp_insert_comment([
            'comment_post_ID' => $post_id,
            'comment_author' => $rev['author'],
            'comment_content' => $rev['content'],
            'comment_type' => 'babel_review',
            'comment_approved' => 1
        ]);
        update_comment_meta($comment_id, 'babel_rating', $rev['rating']);
        $total_rating += $rev['rating'];
    }
    
    update_post_meta($post_id, '_babel_rating_avg', round($total_rating / count($reviews), 1));
    update_post_meta($post_id, '_babel_rating_count', count($reviews));
    echo "Reviews creadas.\n";
} else {
    echo "Reviews ya existían.\n";
}

// 4. Descargar imágenes para la galería (si no hay galería)
require_once( ABSPATH . 'wp-admin/includes/image.php' );
require_once( ABSPATH . 'wp-admin/includes/file.php' );
require_once( ABSPATH . 'wp-admin/includes/media.php' );

$existing_gallery = get_post_meta($post_id, '_babel_gallery', true);
if ( empty($existing_gallery) ) {
    echo "Descargando imágenes de prueba para la galería...\n";
    $images = [
        'https://images.unsplash.com/photo-1579871494447-9811cf80d66c?auto=format&fit=crop&w=800&q=80', // Sushi Roll
        'https://images.unsplash.com/photo-1553621042-f6e147245754?auto=format&fit=crop&w=800&q=80', // Omakase
        'https://images.unsplash.com/photo-1583623025817-d180a2221d0a?auto=format&fit=crop&w=800&q=80', // Nigiri
        'https://images.unsplash.com/photo-1611143660185-83407e324021?auto=format&fit=crop&w=800&q=80' // Interior
    ];
    
    $gallery_ids = [];
    foreach ($images as $idx => $url) {
        echo "Descargando imagen " . ($idx + 1) . "...\n";
        $tmp = download_url( $url );
        if ( is_wp_error( $tmp ) ) continue;
        
        $file_array = [
            'name' => 'sushi_test_' . $idx . '.jpg',
            'tmp_name' => $tmp
        ];
        
        $id = media_handle_sideload( $file_array, $post_id );
        if ( ! is_wp_error($id) ) {
            $gallery_ids[] = $id;
        } else {
            @unlink($tmp);
        }
    }
    
    if ( ! empty($gallery_ids) ) {
        update_post_meta($post_id, '_babel_gallery', implode(',', $gallery_ids));
        set_post_thumbnail($post_id, $gallery_ids[0]);
        echo "Galería poblada con " . count($gallery_ids) . " imágenes.\n";
    }
} else {
    echo "La galería ya tiene imágenes.\n";
}

echo "✅ Seeding finalizado con éxito.\n";
