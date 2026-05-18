<?php
/**
 * Gestión de Assets y Shortcodes Públicos (Babel_Directory_Assets)
 * v7.0.0 — Hito 9: Buscador de Frontend, Carga Inteligente y Shortcodes Semánticos.
 *
 * @package Babel_Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}

class Babel_Directory_Assets {

    /**
     * Constructor de la clase.
     * Registra los ganchos de encolado y shortcodes.
     */
    public function __construct() {
        // Registrar assets públicos en el frontend
        add_action( 'wp_enqueue_scripts', array( $this, 'register_public_assets' ) );

        // Registrar los shortcodes oficiales del buscador
        add_shortcode( 'babel_search_form', array( $this, 'render_search_form' ) );
        add_shortcode( 'babel_results', array( $this, 'render_results_container' ) );
    }

    /**
     * Registra los scripts y estilos públicos del buscador.
     * No se encolan globalmente, sino que se registran para encolado inteligente.
     */
    public function register_public_assets() {
        // Registrar la hoja de estilos pública
        wp_register_style(
            'babel-public-css',
            BD_URL . 'assets/css/babel-public.css',
            array(),
            BD_VERSION
        );

        // Registrar el script de control público (Vanilla JS / Modern React-friendly)
        wp_register_script(
            'babel-public-js',
            BD_URL . 'assets/js/babel-public.js',
            array(),
            BD_VERSION,
            true // Cargar en el footer de forma asíncrona
        );

        // Pasar variables de forma segura desde el backend a JavaScript
        wp_localize_script( 'babel-public-js', 'babel_vars', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'babel_search_nonce' ),
        ) );
    }

    /**
     * Callback para el shortcode [babel_search_form].
     * Renderiza el formulario de búsqueda pura y semántica para integración con Divi 5.
     *
     * @return string HTML renderizado del formulario.
     */
    public function render_search_form() {
        // Encolar los assets en caliente solo si este shortcode es renderizado
        wp_enqueue_style( 'babel-public-css' );
        wp_enqueue_script( 'babel-public-js' );

        ob_start();
        ?>
        <form id="babel-search-form" class="babel-search-form-wrapper" method="post" action="">
            
            <!-- Campo de Texto para Búsqueda Libre -->
            <div class="babel-search-field babel-search-keyword-wrapper">
                <label for="babel-search-keyword" class="screen-reader-text"><?php esc_html_e( 'Búsqueda libre', 'babel-directory' ); ?></label>
                <input 
                    type="text" 
                    name="keyword" 
                    id="babel-search-keyword" 
                    placeholder="<?php esc_attr_e( '¿Qué estás buscando? (ej. Restaurante, Abogado)', 'babel-directory' ); ?>" 
                    value="" 
                />
            </div>

            <!-- Selector de Categorías Dinámico (Jerárquico) -->
            <div class="babel-search-field babel-search-category-wrapper">
                <label for="babel-search-category" class="screen-reader-text"><?php esc_html_e( 'Categoría', 'babel-directory' ); ?></label>
                <select name="category" id="babel-search-category">
                    <option value=""><?php esc_html_e( 'Todas las Categorías', 'babel-directory' ); ?></option>
                    <?php
                    // Helper recursivo para renderizar opciones jerárquicas con escape de seguridad
                    $render_hierarchy = function( $parent_id, $taxonomy, $depth = 0 ) use ( &$render_hierarchy ) {
                        $terms = get_terms( array(
                            'taxonomy'   => $taxonomy,
                            'parent'     => $parent_id,
                            'hide_empty' => false,
                            'hierarchy'  => true,
                        ) );

                        if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                            foreach ( $terms as $term ) {
                                $slug  = esc_attr( $term->slug );
                                $class = ( $depth === 0 ) ? 'babel-opt-parent' : 'babel-opt-child';
                                
                                // Generar sangría visual (2 espacios duros &nbsp; por nivel de profundidad)
                                $indent = str_repeat( '&nbsp;&nbsp;', $depth );
                                
                                $name = $term->name;
                                if ( $taxonomy === 'babel_region' ) {
                                    $name = $this->format_term_name( $name );
                                }
                                $name = esc_html( $name );

                                echo '<option value="' . $slug . '" class="' . $class . '">' . $indent . $name . '</option>';

                                // Llamada recursiva para obtener los términos hijos directos de este término
                                $render_hierarchy( $term->term_id, $taxonomy, $depth + 1 );
                            }
                        }
                    };

                    // Renderizar jerarquía de categorías
                    $render_hierarchy( 0, 'babel_category' );
                    ?>
                </select>
            </div>

            <!-- Selector de Regiones/Comunas Dinámico (Jerárquico) -->
            <div class="babel-search-field babel-search-region-wrapper">
                <label for="babel-search-region" class="screen-reader-text"><?php esc_html_e( 'Región / Comuna', 'babel-directory' ); ?></label>
                <select name="region" id="babel-search-region">
                    <option value=""><?php esc_html_e( 'Todo Chile', 'babel-directory' ); ?></option>
                    <?php
                    // Renderizar jerarquía de regiones
                    $render_hierarchy( 0, 'babel_region' );
                    ?>
                </select>
            </div>

            <!-- Botón de Envío -->
            <div class="babel-search-submit-wrapper">
                <button type="submit" id="babel-search-submit" class="babel-search-submit-btn">
                    <?php esc_html_e( 'Buscar', 'babel-directory' ); ?>
                </button>
            </div>
        </form>
        <?php
        return ob_get_clean();
    }

    /**
     * Callback para el shortcode [babel_results].
     * Renderiza el contenedor estructural puro donde se inyectarán los resultados vía AJAX.
     *
     * @return string Contenedor HTML estructural.
     */
    public function render_results_container() {
        // Encolar los assets en caliente por si el formulario no está en la misma página
        wp_enqueue_style( 'babel-public-css' );
        wp_enqueue_script( 'babel-public-js' );

        return '<div id="babel-directory-results" class="babel-results-container"></div>';
    }

    /**
     * Helper para formatear nombres de términos que contienen prefijos romanos (ej: REG-X).
     *
     * @param string $name Nombre original del término.
     * @return string Nombre formateado de forma amigable.
     */
    private function format_term_name( $name ) {
        if ( strpos( $name, 'REG-' ) === 0 ) {
            if ( preg_match( '/^REG-([IVXLCDM]+)\s+(.*)$/', $name, $matches ) ) {
                return $matches[1] . ' REG - ' . $matches[2];
            }
        }
        return $name;
    }
}
