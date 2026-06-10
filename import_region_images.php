<?php
// Script to import region images and link them to babel_region terms
$base_path = '/home/soydechile/public_html/wp-content/plugins/babel-directory/assets/images/regiones/';

$regions_map = [
    25 => 'arica_parinacota.png',
    26 => 'tarapaca.png',
    27 => 'antofagasta.png',
    20 => 'atacama.png',
    28 => 'coquimbo.png',
    19 => 'valparaiso.png',
    18 => 'metropolitana.png',
    29 => 'ohiggins.png',
    30 => 'maule.png',
    31 => 'nuble.png',
    32 => 'biobio.png',
    33 => 'araucania.png',
    34 => 'los_rios.png',
    35 => 'los_lagos.png',
    36 => 'aysen.png',
    37 => 'magallanes.png'
];

foreach ($regions_map as $term_id => $filename) {
    $file_path = $base_path . $filename;
    
    if (file_exists($file_path)) {
        // Run wp media import
        $cmd = "wp media import " . escapeshellarg($file_path) . " --title=" . escapeshellarg("Region " . $term_id) . " --porcelain --allow-root --path=/home/soydechile/public_html";
        $attachment_id = trim(shell_exec($cmd));
        
        if (is_numeric($attachment_id)) {
            update_term_meta($term_id, 'bd_term_image_id', $attachment_id);
            echo "Term {$term_id} updated with image ID {$attachment_id}\n";
        } else {
            echo "Failed to import image for term {$term_id}\n";
        }
    } else {
        echo "File not found: {$file_path}\n";
    }
}
echo "Done.\n";
