<?php
/**
 * Autoloader PSR-4 para Babel Directory.
 * Elimina la necesidad de usar require_once y prepara el terreno para arquitecturas desacopladas.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

spl_autoload_register(function ($class) {
    // Prefix base del namespace
    $prefix = 'Babel\\Directory\\';

    // Directorio base donde viven las clases (ahora usamos 'includes/')
    $base_dir = BD_PATH . 'includes/';

    // Validar si la clase usa nuestro namespace
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Obtener la porción del nombre de clase que va después del prefijo
    $relative_class = substr($class, $len);

    // Separar en partes por si hay sub-namespaces (Ej. Api\Rest_Endpoints)
    $class_parts = explode('\\', $relative_class);
    
    // El último elemento es el nombre del archivo / clase
    $class_name = array_pop($class_parts);
    
    // Convención de WP: prefijo 'class-' y nombres en lowercase con guiones
    // Ej: Search_Index -> class-search-index.php
    $file_name = 'class-' . strtolower(str_replace('_', '-', $class_name)) . '.php';
    
    // Si hay sub-namespaces, convertimos a subcarpetas en lowercase
    $folder_path = '';
    if (!empty($class_parts)) {
        $folder_path = strtolower(implode('/', $class_parts)) . '/';
    }

    $file = $base_dir . $folder_path . $file_name;

    if (file_exists($file)) {
        require $file;
    }
});
