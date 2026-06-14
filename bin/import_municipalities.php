<?php
/**
 * Script de importación de municipalidades para Soy de Chile
 * Ejecutar con: wp eval-file bin/import_municipalities.php
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

$count = 0;

foreach ($data['regiones'] as $region_data) {
    if (!isset($region_data['comunas'])) continue;
    $region_name = trim($region_data['region']);
    
    foreach ($region_data['comunas'] as $comuna_name_str) {
        $comuna_name = trim($comuna_name_str);
        // Generar nombre automático
        $muni_name = "Ilustre Municipalidad de " . $comuna_name;

        // Verificar existencia por título
        $existing = get_page_by_title($muni_name, OBJECT, 'babel_business');
        if ($existing) {
            WP_CLI::line("Municipalidad existente: $muni_name");
            continue;
        }

        // Crear post
        $post_data = array(
            'post_title'   => $muni_name,
            'post_status'  => 'publish',
            'post_type'    => 'babel_business',
        );

        $post_id = wp_insert_post($post_data);
        if (is_wp_error($post_id) || !$post_id) {
            WP_CLI::warning("Error insertando $muni_name");
            continue;
        }

        // Flag institucional crítico
        update_post_meta($post_id, '_babel_is_institution', '1');
        
        // Asignar Taxonomías
        $terms_to_assign = array();
        
        // 1. Región
        $region_term = get_term_by('name', $region_name, 'babel_region');
        if (!$region_term) {
            $region_term = get_term_by('slug', sanitize_title($region_name), 'babel_region');
        }
        
        if ($region_term) {
            $terms_to_assign[] = (int) $region_term->term_id;
            
            // 2. Comuna (que sea hija de esta región)
            $comuna_term = term_exists($comuna_name, 'babel_region', $region_term->term_id);
            if ($comuna_term) {
                $terms_to_assign[] = (int) $comuna_term['term_id'];
            }
        }

        if (!empty($terms_to_assign)) {
            wp_set_object_terms($post_id, $terms_to_assign, 'babel_region', false);
        }

        WP_CLI::success("Municipalidad creada e indexada: $muni_name");
        $count++;
    }
}

WP_CLI::success("Proceso de municipalidades completado. Instituciones nuevas insertadas: $count");
