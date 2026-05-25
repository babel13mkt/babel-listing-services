<?php
/**
 * Script para rellenar los datos de prueba del último negocio creado.
 */

if ( ! defined( 'ABSPATH' ) ) {
    die( 'Acceso denegado.' );
}

echo "Iniciando llenado de datos para el negocio de pruebas...<br><br>";

// Obtener el último negocio creado
$args = [
    'post_type' => 'babel_business',
    'posts_per_page' => 1,
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC'
];
$posts = get_posts($args);

if ( empty($posts) ) {
    die("No hay ningún negocio publicado para actualizar.");
}

$post_id = $posts[0]->ID;
echo "Actualizando el negocio: <strong>" . esc_html($posts[0]->post_title) . "</strong> (ID: $post_id)<br>";

// 1. Llenar metadata básica (sin sobreescribir lo que ya importaba)
$meta_data = [
    '_babel_phone' => '+56 9 1234 5678',
    '_babel_whatsapp' => '+56912345678',
    '_babel_email' => 'contacto@negocioprueba.cl',
    '_babel_website' => 'https://negocioprueba.cl',
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
    '_babel_razon_social' => 'Negocio de Prueba SpA'
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

// 3. Crear Reviews
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
    echo "Reseñas creadas.<br>";
}

// 4. Usar imágenes existentes en la librería multimedia para la galería
$existing_gallery = get_post_meta($post_id, '_babel_gallery', true);
if ( empty($existing_gallery) ) {
    // Buscar los últimos 4 attachments que sean imágenes
    $attachments = get_posts([
        'post_type'      => 'attachment',
        'post_mime_type' => 'image',
        'post_status'    => 'inherit',
        'posts_per_page' => 4,
    ]);
    
    if ( ! empty($attachments) ) {
        $gallery_ids = [];
        foreach ($attachments as $att) {
            $gallery_ids[] = $att->ID;
        }
        update_post_meta($post_id, '_babel_gallery', implode(',', $gallery_ids));
        
        // Poner la primera como imagen destacada si no tiene
        if ( ! has_post_thumbnail($post_id) ) {
            set_post_thumbnail($post_id, $gallery_ids[0]);
        }
        echo "Galería llenada usando " . count($gallery_ids) . " imágenes existentes de tu WordPress.<br>";
    }
}

echo "<br>✅ Datos rellenados con éxito.<br>";
echo "<a href='" . get_permalink($post_id) . "'>Ver Negocio</a>";
