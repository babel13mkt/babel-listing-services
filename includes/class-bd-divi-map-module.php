<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Módulo de Divi Builder para Babel Directory Map.
 */
class Babel_Directory_Divi_Map_Module extends ET_Builder_Module {

    public $slug       = 'bd_divi_map';
    public $vb_support = 'on';

    public function init() {
        $this->name = esc_html__( 'Babel Map Directory', 'babel-directory' );
        $this->settings_modal_toggles = array(
            'general' => array(
                'toggles' => array(
                    'main_content' => esc_html__( 'Configuración Principal', 'babel-directory' ),
                ),
            ),
        );
    }

    public function get_fields() {
        return array(
            'default_city' => array(
                'label'           => esc_html__( 'Ciudad por Defecto', 'babel-directory' ),
                'type'            => 'text',
                'option_category' => 'basic_option',
                'description'     => esc_html__( 'Ciudad a mostrar al cargar. Deja "Todas" para mapa global.', 'babel-directory' ),
                'default'         => 'Todas',
                'toggle_slug'     => 'main_content',
            ),
            'accent_color' => array(
                'label'           => esc_html__( 'Color de Acento', 'babel-directory' ),
                'type'            => 'color-alpha',
                'option_category' => 'configuration',
                'description'     => esc_html__( 'Color principal para botones y resaltados.', 'babel-directory' ),
                'default'         => '#6366F1',
                'toggle_slug'     => 'main_content',
            ),
        );
    }

    public function render( $unprocessed_props, $content, $render_slug ) {
        $city   = $this->props['default_city'];
        $accent = $this->props['accent_color'];

        $config = wp_json_encode([
            'defaultCity' => $city,
            'accentColor' => $accent
        ]);

        return sprintf(
            '<div id="babel-native-wrapper" data-config="%s"></div>',
            esc_attr( $config )
        );
    }
}
