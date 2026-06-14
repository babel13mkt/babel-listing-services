<?php
/**
 * Script de importación de geografía para Soy de Chile (Región -> Comuna)
 * Ejecutar con: wp eval-file bin/import_geography.php
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    die( 'Solo ejecutable via WP-CLI.' );
}

$json_path = dirname(__FILE__) . '/../data/chile_geo_data.json';
if (!file_exists($json_path)) {
    WP_CLI::error("Archivo JSON no encontrado en $json_path");
}

$json_content = file_get_contents($json_path);
$data = json_decode($json_content, true);

if (!$data || !isset($data['regiones'])) {
    WP_CLI::error("JSON inválido o estructura incorrecta.");
}

$count_comunas = 0;

foreach ($data['regiones'] as $region_data) {
    $region_name = trim($region_data['region']);
    
    // Buscar la región existente (en AR1 ya existen las 16)
    $region_term = get_term_by('name', $region_name, 'babel_region');
    
    if (!$region_term) {
        // Intentar búsqueda por slug relajado si el nombre exacto falla (ej. acentos)
        $slug = sanitize_title($region_name);
        $region_term = get_term_by('slug', $slug, 'babel_region');
    }

    if (!$region_term) {
        // Si no existe, la creamos
        $inserted = wp_insert_term($region_name, 'babel_region');
        if (is_wp_error($inserted)) {
            WP_CLI::warning("Error creando región '$region_name': " . $inserted->get_error_message());
            continue;
        }
        $region_term_id = $inserted['term_id'];
        WP_CLI::success("Región creada: $region_name");
    } else {
        $region_term_id = $region_term->term_id;
        WP_CLI::line("Región encontrada: $region_name (ID $region_term_id)");
    }

    if (!isset($region_data['comunas'])) continue;

    foreach ($region_data['comunas'] as $comuna_name_str) {
        $comuna_name = trim($comuna_name_str);
        
        // Evitar duplicados de comunas bajo la misma región
        // Nota: Algunas comunas podrían llamarse igual en distintas regiones (poco común pero posible en teoría), 
        // term_exists busca por nombre y padre opcionalmente
        $exists = term_exists($comuna_name, 'babel_region', $region_term_id);
        
        if ($exists) {
            WP_CLI::line("  - Comuna ya existe: $comuna_name");
            continue;
        }

        $inserted_comuna = wp_insert_term($comuna_name, 'babel_region', array('parent' => $region_term_id));
        if (is_wp_error($inserted_comuna)) {
            WP_CLI::warning("  - Error insertando comuna '$comuna_name': " . $inserted_comuna->get_error_message());
        } else {
            WP_CLI::success("  - Comuna insertada: $comuna_name");
            $count_comunas++;
        }
    }
}

WP_CLI::success("Proceso de geografía completado. Comunas insertadas: $count_comunas");
