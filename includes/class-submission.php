<?php
/**
 * Procesamiento Seguro de Envío de Negocios desde el Frontend (Babel_Directory_Submission)
 * v7.0.0 — Hito 10: Formulario de Registro Público de Negocios y Carga de Medios.
 *
 * @package Babel_Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}

class Babel_Directory_Submission {

    /**
     * Almacena mensajes de éxito o error tras el procesamiento del formulario.
     *
     * @var array
     */
    private $notices = array();

    /**
     * Constructor de la clase. Registra el shortcode del formulario.
     */
    public function __construct() {
        add_shortcode( 'babel_submission_form', array( $this, 'render_submission_form' ) );
        add_action( 'wp_loaded', array( $this, 'handle_form_submission' ) );
    }

    /**
     * Renderiza el formulario HTML seguro para envío de negocios.
     *
     * @return string Código HTML del formulario.
     */
    public function render_submission_form() {
        // Encolar los estilos del plugin por seguridad
        wp_enqueue_style( 'babel-public-css' );

        return '<div class="babel-notice babel-notice-info" style="padding: 15px; border-left: 4px solid #3b82f6; background-color: #eff6ff; color: #1e3a8a; border-radius: 4px; font-weight: 500;">' . 
               esc_html__( 'El formulario de registro público desde el frontend se encuentra temporalmente inactivo. Por favor, realiza la carga desde el panel de administración.', 'babel-directory' ) . 
               '</div>';
    }

    /**
     * Obtiene y renderiza jerárquicamente las opciones de un selector de taxonomía.
     *
     * @param string $taxonomy Nombre de la taxonomía.
     */
    private function render_taxonomy_options( $taxonomy ) {
        $parent_terms = get_terms( array(
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
            'parent'     => 0,
        ) );

        if ( is_wp_error( $parent_terms ) || empty( $parent_terms ) ) {
            return;
        }

        $selected_val = isset( $_POST[ $taxonomy ] ) ? intval( $_POST[ $taxonomy ] ) : 0;

        foreach ( $parent_terms as $parent ) {
            $selected = $selected_val === $parent->term_id ? ' selected' : '';
            echo '<option value="' . esc_attr( $parent->term_id ) . '" class="babel-opt-parent"' . $selected . '>' . esc_html( $parent->name ) . '</option>';

            // Obtener e iterar términos hijos directos
            $child_terms = get_terms( array(
                'taxonomy'   => $taxonomy,
                'hide_empty' => false,
                'parent'     => $parent->term_id,
            ) );

            if ( ! is_wp_error( $child_terms ) && ! empty( $child_terms ) ) {
                foreach ( $child_terms as $child ) {
                    $selected_child = $selected_val === $child->term_id ? ' selected' : '';
                    echo '<option value="' . esc_attr( $child->term_id ) . '" class="babel-opt-child"' . $selected_child . '>&nbsp;&nbsp;&nbsp;&mdash;&nbsp;' . esc_html( $child->name ) . '</option>';
                }
            }
        }
    }

    /**
     * Escucha y procesa de forma segura el envío de datos del formulario de registro.
     */
    public function handle_form_submission() {
        // Procesamiento desactivado temporalmente para control estricto de tramos
        return;
    }

    /**
     * Restringe estrictamente los tipos de archivo permitidos para el logotipo.
     *
     * @param array $mimes Mime types existentes.
     * @return array Mime types permitidos.
     */
    public function restrict_submission_mimes( $mimes ) {
        return array(
            'jpg|jpeg' => 'image/jpeg',
            'png'      => 'image/png',
            'webp'     => 'image/webp',
        );
    }
}
