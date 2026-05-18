<?php
/**
 * Script de Auditoría de Rendimiento y Estrés (WP-CLI / Standalone)
 * v7.0.0 — Paso 3.3: Diagnóstico de Latencia, Consumo de Memoria e Integridad SQL.
 *
 * Modo de uso:
 * Standalone: php bin/audit-performance.php [keyword] [category_slug] [region_slug]
 * WP-CLI:     wp eval-file bin/audit-performance.php [keyword] [category_slug] [region_slug]
 */

// 1. INICIALIZAR EL ENTORNO DE WORDPRESS (Soporte Standalone/CLI)
if ( ! defined( 'ABSPATH' ) ) {
    $wp_load_paths = array(
        dirname( __DIR__, 4 ) . '/wp-load.php',
        dirname( __DIR__, 3 ) . '/wp-load.php',
        dirname( __DIR__, 2 ) . '/wp-load.php',
        dirname( __DIR__ ) . '/wp-load.php',
        'wp-load.php'
    );
    foreach ( $wp_load_paths as $path ) {
        if ( file_exists( $path ) ) {
            define( 'WP_USE_THEMES', false );
            require_once $path;
            break;
        }
    }
}

if ( ! defined( 'ABSPATH' ) ) {
    fwrite( STDERR, "Error Crítico: No se pudo cargar el archivo wp-load.php. Asegúrese de colocar este script dentro de la jerarquía de directorios de WordPress.\n" );
    exit( 1 );
}

// Activar registro de consultas SQL si es posible
if ( ! defined( 'SAVEQUERIES' ) ) {
    define( 'SAVEQUERIES', true );
}

// Helper para impresión estilizada compatible con CLI y terminales estándar
function bd_print_line( $message, $type = 'info' ) {
    $ansi_colors = array(
        'success' => "\033[32m[✓]\033[0m",
        'warning' => "\033[33m[!]\033[0m",
        'error'   => "\033[31m[✗]\033[0m",
        'info'    => "\033[36m[•]\033[0m",
        'header'  => "\033[1;35m",
        'reset'   => "\033[0m"
    );

    if ( class_exists( 'WP_CLI' ) ) {
        switch ( $type ) {
            case 'success':
                WP_CLI::success( $message );
                break;
            case 'warning':
                WP_CLI::warning( $message );
                break;
            case 'error':
                WP_CLI::error( $message, false );
                break;
            default:
                WP_CLI::line( $message );
                break;
        }
    } else {
        if ( $type === 'header' ) {
            echo $ansi_colors['header'] . $message . $ansi_colors['reset'] . "\n";
        } else {
            echo $ansi_colors[ $type ] . " " . $message . "\n";
        }
    }
}

// 2. PARSEAR ARGUMENTOS DE ENTRADA (Filtrando flags de WP-CLI)
global $argv;
$filtered_args = array();
if ( isset( $argv ) && is_array( $argv ) ) {
    foreach ( $argv as $arg ) {
        if ( strpos( $arg, '--' ) === 0 ) {
            continue;
        }
        if ( preg_match( '/\.php$/', $arg ) ) {
            continue;
        }
        if ( preg_match( '/(wp|wp-cli\.phar)$/', $arg ) ) {
            continue;
        }
        if ( in_array( $arg, array( 'eval-file', 'wp' ), true ) ) {
            continue;
        }
        $filtered_args[] = $arg;
    }
}

$test_keyword = isset( $filtered_args[0] ) ? sanitize_text_field( $filtered_args[0] ) : '';
$test_cat     = isset( $filtered_args[1] ) ? sanitize_text_field( $filtered_args[1] ) : '';
$test_region  = isset( $filtered_args[2] ) ? sanitize_text_field( $filtered_args[2] ) : '';

bd_print_line( "==========================================================", 'header' );
bd_print_line( "   AUDITORÍA DE ESTRÉS Y RENDIMIENTO — BABEL DIRECTORY    ", 'header' );
bd_print_line( "==========================================================", 'header' );
bd_print_line( "Simulando parámetros de entrada:" );
bd_print_line( " - Keyword:   " . ( $test_keyword ?: '(Ninguna)' ) );
bd_print_line( " - Categoría: " . ( $test_cat ?: '(Todas)' ) );
bd_print_line( " - Región:    " . ( $test_region ?: '(Todo Chile)' ) );
bd_print_line( "----------------------------------------------------------" );

// Verificar que el plugin babel-directory esté activo
if ( ! class_exists( 'BD_AJAX' ) ) {
    bd_print_line( "Error: La clase BD_AJAX no está disponible. Asegúrese de que el plugin babel-directory esté activo.", 'error' );
    exit( 1 );
}

// Preparar los inputs simulados en $_POST para el controlador
$_POST['nonce']    = wp_create_nonce( 'babel_search_nonce' );
$_POST['keyword']  = $test_keyword;
$_POST['category'] = $test_cat;
$_POST['region']   = $test_region;
$_POST['paged']    = 1;
$_POST['sort']     = 'featured';

// Forzar que wp_doing_ajax() devuelva true para que wp_send_json llame a wp_die() en vez de die()
add_filter( 'wp_doing_ajax', '__return_true' );

// Configurar filtro para capturar wp_die y evitar la terminación abrupta del script
add_filter( 'wp_die_ajax_handler', function() {
    return function( $message ) {
        // Capturar la respuesta enviada en lugar de terminar con wp_die()
        throw new RuntimeException( $message );
    };
} );

// Obtener métricas iniciales del sistema
global $wpdb;
$initial_queries = $wpdb->num_queries;
$initial_queries_list = isset( $wpdb->queries ) ? $wpdb->queries : array();

$start_time = microtime( true );
$start_mem  = memory_get_usage();

bd_print_line( "Ejecutando simulador interno de loop de búsqueda AJAX..." );

$ajax_handler = new BD_AJAX();
$exception_triggered = false;
$output_response = '';

// 3. CAPTURA Y AUDITORÍA EN TIEMPO REAL
ob_start();
try {
    $ajax_handler->filter_listings();
} catch ( RuntimeException $e ) {
    $exception_triggered = true;
    $output_response = $e->getMessage();
} catch ( Exception $e ) {
    bd_print_line( "Excepción inesperada: " . $e->getMessage(), 'error' );
}
$raw_output = ob_get_clean();

// Si no se disparó la excepción pero hubo salida en búfer, intentamos interpretarla
if ( empty( $output_response ) && ! empty( $raw_output ) ) {
    $output_response = $raw_output;
}

$end_time = microtime( true );
$peak_mem = memory_get_peak_usage();

// Calcular métricas
$total_time_ms  = round( ( $end_time - $start_time ) * 1000, 2 );
$memory_peak_mb = round( $peak_mem / 1024 / 1024, 2 );
$final_queries  = $wpdb->num_queries;
$queries_run    = $final_queries - $initial_queries;

// 4. PRESENTACIÓN DE RESULTADOS
bd_print_line( "==========================================================", 'header' );
bd_print_line( "                  MÉTRICAS DE RENDIMIENTO                 ", 'header' );
bd_print_line( "==========================================================", 'header' );

if ( $total_time_ms < 150 ) {
    bd_print_line( "Tiempo de Respuesta:  {$total_time_ms} ms (Óptimo / Ultra-veloz)", 'success' );
} elseif ( $total_time_ms < 400 ) {
    bd_print_line( "Tiempo de Respuesta:  {$total_time_ms} ms (Aceptable)", 'warning' );
} else {
    bd_print_line( "Tiempo de Respuesta:  {$total_time_ms} ms (Alta Latencia - Requiere Optimización)", 'error' );
}

bd_print_line( "Consumo de Memoria Pico: {$memory_peak_mb} MB" );
bd_print_line( "Consultas SQL ejecutadas: {$queries_run}" );

// 5. DIAGNÓSTICO DE COMPORTAMIENTO N+1
bd_print_line( "----------------------------------------------------------" );
bd_print_line( "Análisis y Diagnóstico de Base de Datos:" );

if ( $queries_run <= 5 ) {
    bd_print_line( "Excelente: No hay indicios de consultas N+1. La indexación y el cache operan eficientemente.", 'success' );
} elseif ( $queries_run <= 12 ) {
    bd_print_line( "Advertencia: El conteo de queries es moderado. Compruebe la carga de layouts dinámicos.", 'warning' );
} else {
    bd_print_line( "CRÍTICO: Posible bucle de consultas SQL N+1 detectado al renderizar layouts individuales de Divi Library en cada iteración.", 'error' );
}

// Imprimir consultas si SAVEQUERIES está activo
if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES && isset( $wpdb->queries ) ) {
    bd_print_line( "Listado detallado de consultas ejecutadas en esta sesión:", 'info' );
    $current_queries = array_slice( $wpdb->queries, $initial_queries );
    foreach ( $current_queries as $index => $q ) {
        $q_num = $index + 1;
        // La estructura de $wpdb->queries es [query_string, execution_time, stack_trace]
        $latency = isset( $q[1] ) ? round( $q[1] * 1000, 2 ) : 0;
        echo "   [SQL #{$q_num}] Latencia: {$latency}ms | Query: " . trim( preg_replace( '/\s+/', ' ', $q[0] ) ) . "\n";
    }
}

// 6. ANÁLISIS DE LA RESPUESTA OBTENIDA
bd_print_line( "----------------------------------------------------------" );
bd_print_line( "Análisis de Payload Inyectado:" );

$decoded_response = json_decode( $output_response, true );
if ( $decoded_response ) {
    if ( isset( $decoded_response['success'] ) && $decoded_response['success'] ) {
        $count = isset( $decoded_response['data']['count'] ) ? intval( $decoded_response['data']['count'] ) : 0;
        bd_print_line( "Estado de Búsqueda: Éxito (JSON Success)", 'success' );
        bd_print_line( "Registros coincidentes devueltos: {$count}" );
    } else {
        $error_msg = isset( $decoded_response['data'] ) ? $decoded_response['data'] : 'Error desconocido';
        bd_print_line( "Estado de Búsqueda: Error del Controlador. Mensaje: '{$error_msg}'", 'error' );
    }
} else {
    bd_print_line( "Advertencia: No se pudo decodificar una respuesta JSON válida o el payload está vacío.", 'warning' );
    if ( ! empty( $output_response ) ) {
        echo "   [Contenido de respuesta]: " . substr( strip_tags( $output_response ), 0, 500 ) . "...\n";
    }
}

bd_print_line( "==========================================================", 'header' );
bd_print_line( "Auditoría finalizada con éxito.", 'success' );
bd_print_line( "==========================================================", 'header' );
