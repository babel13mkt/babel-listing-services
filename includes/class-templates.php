<?php
/**
 * Cargador de Plantillas (BD_Templates)
 * v3.1 — Override de templates single, archive y taxonomy.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BD_Templates {

    public function __construct() {
        add_filter( 'template_include', array( $this, 'template_loader' ) );
    }

    public function template_loader( $template ) {
        if ( is_singular( 'directorio_negocio' ) ) {
            $plugin_template = BD_PATH . 'templates/single-directorio_negocio.php';
            if ( file_exists( $plugin_template ) ) {
                return $plugin_template;
            }
        }

        if ( is_post_type_archive( 'directorio_negocio' ) ) {
            $plugin_template = BD_PATH . 'templates/archive-directorio_negocio.php';
            if ( file_exists( $plugin_template ) ) {
                return $plugin_template;
            }
        }

        if ( is_tax( 'directorio_categoria' ) ) {
            $plugin_template = BD_PATH . 'templates/taxonomy-directorio_categoria.php';
            if ( file_exists( $plugin_template ) ) {
                return $plugin_template;
            }
        }

        if ( is_tax( 'directorio_region' ) ) {
            $plugin_template = BD_PATH . 'templates/taxonomy-directorio_region.php';
            if ( file_exists( $plugin_template ) ) {
                return $plugin_template;
            }
        }

        return $template;
    }
}
