<?php
namespace Babel\Directory;

/**
 * Clase para el manejo del CPT 'bd_institution' (Instituciones).
 * Registra el post type, metaboxes específicos y lógica de indexación.
 *
 * @package Babel_Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}

class Institution {

    /**
     * Constructor de la clase Institution.
     * Registra los ganchos de inicialización.
     */
    public function __construct() {
        // El CPT se registra desde class-cpt.php, aquí solo añadimos metaboxes y hooks de guardado
        add_action( 'add_meta_boxes', array( $this, 'add_institution_meta_boxes' ) );
        add_action( 'save_post_bd_institution', array( $this, 'save_institution_meta' ), 10, 2 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    /**
     * Registra las metaboxes específicas para el CPT 'bd_institution'.
     */
    public function add_institution_meta_boxes() {
        add_meta_box(
            'bd_institution_data_panel',
            __( 'Datos de la Institución', 'babel-directory' ),
            array( $this, 'render_institution_panel' ),
            'bd_institution',
            'normal',
            'high'
        );
    }

    /**
     * Encola los assets de administración específicos para instituciones.
     *
     * @param string $hook Identificador de la página actual del panel de administración.
     */
    public function enqueue_admin_assets( $hook ) {
        $screen = get_current_screen();
        if ( $screen && 'bd_institution' === $screen->post_type ) {
            wp_enqueue_style( 'babel-admin' );
        }
    }

    /**
     * Renderiza los campos de la metabox de instituciones en el backend.
     *
     * @param WP_Post $post El objeto del post actual.
     */
    public function render_institution_panel( $post ) {
        // Nonce de seguridad
        wp_nonce_field( 'bd_institution_meta_box_nonce_action', 'bd_institution_meta_box_nonce' );

        // Recuperar valores guardados
        $tipo_institucion = get_post_meta( $post->ID, '_bd_institucion_tipo', true );
        $nivel_educativo  = get_post_meta( $post->ID, '_bd_institucion_nivel_educativo', true );
        $dependencia      = get_post_meta( $post->ID, '_bd_institucion_dependencia', true );
        $codigo_rbd       = get_post_meta( $post->ID, '_bd_institucion_codigo_rbd', true );
        $horario_atencion = get_post_meta( $post->ID, '_bd_institucion_horario', true );

        // Deserializar horario si es JSON
        $horario = array();
        if ( ! empty( $horario_atencion ) ) {
            if ( is_array( $horario_atencion ) ) {
                $horario = $horario_atencion;
            } else {
                $horario = json_decode( $horario_atencion, true );
            }
        }
        if ( ! is_array( $horario ) ) {
            $horario = array();
        }

        $days_of_week = array( 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo' );
        ?>
        <style>
            /* Estilos para el panel de Instituciones - BEM CSS */
            .bd-institution-wrapper {
                background: transparent;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                color: #334155;
                box-sizing: border-box;
                padding: 0;
            }

            .bd-institution-grid {
                display: grid;
                grid-template-columns: repeat(12, 1fr);
                gap: 16px;
            }

            .bd-institution-grid--span-12 { grid-column: span 12; }
            .bd-institution-grid--span-6 { grid-column: span 6; }
            .bd-institution-grid--span-4 { grid-column: span 4; }
            .bd-institution-grid--span-3 { grid-column: span 3; }

            .bd-institution-field {
                display: flex;
                flex-direction: column;
                gap: 6px;
            }

            .bd-institution-field label {
                font-weight: 600;
                font-size: 13px;
                color: #1e293b;
            }

            .bd-institution-field input[type="text"],
            .bd-institution-field input[type="time"],
            .bd-institution-field select,
            .bd-institution-field textarea {
                width: 100%;
                border: 1px solid #cbd5e1;
                border-radius: 6px;
                padding: 8px 12px;
                font-size: 13px;
                color: #334155;
                background-color: #f8fafc;
                box-sizing: border-box;
                transition: all 0.2s ease;
            }

            .bd-institution-field input:focus,
            .bd-institution-field select:focus,
            .bd-institution-field textarea:focus {
                background-color: #ffffff;
                border-color: #219ebc;
                box-shadow: 0 0 0 3px rgba(33, 158, 188, 0.15);
                outline: none;
            }

            .bd-institution-section-title {
                font-size: 14px;
                font-weight: 700;
                color: #0f172a;
                margin: 16px 0 8px 0;
                padding-bottom: 8px;
                border-bottom: 1px solid #e2e8f0;
            }

            .bd-institution-schedule {
                display: grid;
                grid-template-columns: 100px 1fr 1fr;
                gap: 8px;
                align-items: center;
            }

            .bd-institution-schedule label {
                font-size: 12px;
                font-weight: 500;
                color: #475569;
            }

            .bd-institution-badge {
                display: inline-block;
                padding: 2px 8px;
                border-radius: 4px;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
            }

            .bd-institution-badge--escuela { background: #dbeafe; color: #1e40af; }
            .bd-institution-badge--universidad { background: #f3e8ff; color: #6b21a8; }
            .bd-institution-badge--banco { background: #dcfce7; color: #166534; }
            .bd-institution-badge--organismo { background: #fef3c7; color: #92400e; }
        </style>

        <div class="bd-institution-wrapper">
            <!-- Tipo de Institución y Dependencia -->
            <div class="bd-institution-grid">
                <div class="bd-institution-field bd-institution-grid--span-4">
                    <label for="bd_institucion_tipo"><?php _e( 'Tipo de Institución', 'babel-directory' ); ?></label>
                    <select id="bd_institucion_tipo" name="bd_institucion_tipo">
                        <option value=""><?php _e( 'Seleccionar tipo...', 'babel-directory' ); ?></option>
                        <option value="escuela" <?php selected( $tipo_institucion, 'escuela' ); ?>><?php _e( 'Escuela', 'babel-directory' ); ?></option>
                        <option value="universidad" <?php selected( $tipo_institucion, 'universidad' ); ?>><?php _e( 'Universidad', 'babel-directory' ); ?></option>
                        <option value="banco" <?php selected( $tipo_institucion, 'banco' ); ?>><?php _e( 'Banco', 'babel-directory' ); ?></option>
                        <option value="organismo" <?php selected( $tipo_institucion, 'organismo' ); ?>><?php _e( 'Organismo', 'babel-directory' ); ?></option>
                    </select>
                </div>

                <div class="bd-institution-field bd-institution-grid--span-4">
                    <label for="bd_institucion_dependencia"><?php _e( 'Dependencia', 'babel-directory' ); ?></label>
                    <select id="bd_institucion_dependencia" name="bd_institucion_dependencia">
                        <option value=""><?php _e( 'Seleccionar dependencia...', 'babel-directory' ); ?></option>
                        <option value="municipal" <?php selected( $dependencia, 'municipal' ); ?>><?php _e( 'Municipal', 'babel-directory' ); ?></option>
                        <option value="subvencionada" <?php selected( $dependencia, 'subvencionada' ); ?>><?php _e( 'Subvencionada', 'babel-directory' ); ?></option>
                        <option value="privada" <?php selected( $dependencia, 'privada' ); ?>><?php _e( 'Privada', 'babel-directory' ); ?></option>
                    </select>
                </div>

                <div class="bd-institution-field bd-institution-grid--span-4">
                    <label for="bd_institucion_nivel_educativo"><?php _e( 'Nivel Educativo', 'babel-directory' ); ?></label>
                    <select id="bd_institucion_nivel_educativo" name="bd_institucion_nivel_educativo">
                        <option value=""><?php _e( 'No aplica', 'babel-directory' ); ?></option>
                        <option value="preescolar" <?php selected( $nivel_educativo, 'preescolar' ); ?>><?php _e( 'Preescolar', 'babel-directory' ); ?></option>
                        <option value="basica" <?php selected( $nivel_educativo, 'basica' ); ?>><?php _e( 'Educación Básica', 'babel-directory' ); ?></option>
                        <option value="media" <?php selected( $nivel_educativo, 'media' ); ?>><?php _e( 'Educación Media', 'babel-directory' ); ?></option>
                        <option value="superior" <?php selected( $nivel_educativo, 'superior' ); ?>><?php _e( 'Educación Superior', 'babel-directory' ); ?></option>
                        <option value="tecnica" <?php selected( $nivel_educativo, 'tecnica' ); ?>><?php _e( 'Educación Técnica', 'babel-directory' ); ?></option>
                        <option value="formacion" <?php selected( $nivel_educativo, 'formacion' ); ?>><?php _e( 'Formación / Capacitación', 'babel-directory' ); ?></option>
                    </select>
                </div>
            </div>

            <!-- Código RBD -->
            <div class="bd-institution-grid" style="margin-top: 16px;">
                <div class="bd-institution-field bd-institution-grid--span-4">
                    <label for="bd_institucion_codigo_rbd"><?php _e( 'Código RBD (Rol de Base de Datos)', 'babel-directory' ); ?></label>
                    <input type="text" id="bd_institucion_codigo_rbd" name="bd_institucion_codigo_rbd"
                           value="<?php echo esc_attr( $codigo_rbd ); ?>"
                           placeholder="<?php _e( 'Ej: 1234-5', 'babel-directory' ); ?>" />
                    <small style="color: #64748b;"><?php _e( 'Código asignado por el MINEDUC. Solo aplica a establecimientos educacionales.', 'babel-directory' ); ?></small>
                </div>
            </div>

            <!-- Horario de Atención -->
            <div class="bd-institution-section-title"><?php _e( 'Horario de Atención', 'babel-directory' ); ?></div>
            <div class="bd-institution-grid">
                <?php foreach ( $days_of_week as $day ) : 
                    $day_key = strtolower( $day );
                    $day_key = str_replace( 
                        array( 'á', 'é', 'í', 'ó', 'ú' ), 
                        array( 'a', 'e', 'i', 'o', 'u' ), 
                        $day_key 
                    );
                    $hora_apertura = isset( $horario[ $day_key ]['apertura'] ) ? $horario[ $day_key ]['apertura'] : '';
                    $hora_cierre   = isset( $horario[ $day_key ]['cierre'] ) ? $horario[ $day_key ]['cierre'] : '';
                ?>
                <div class="bd-institution-field bd-institution-grid--span-12">
                    <div class="bd-institution-schedule">
                        <label><?php echo esc_html( $day ); ?></label>
                        <input type="time" name="bd_institucion_horario[<?php echo esc_attr( $day_key ); ?>][apertura]"
                               value="<?php echo esc_attr( $hora_apertura ); ?>"
                               placeholder="<?php _e( 'Apertura', 'babel-directory' ); ?>" />
                        <input type="time" name="bd_institucion_horario[<?php echo esc_attr( $day_key ); ?>][cierre]"
                               value="<?php echo esc_attr( $hora_cierre ); ?>"
                               placeholder="<?php _e( 'Cierre', 'babel-directory' ); ?>" />
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Guarda los metadatos específicos de la institución.
     *
     * @param int     $post_id ID del post que se guarda.
     * @param WP_Post $post    Objeto del post.
     */
    public function save_institution_meta( $post_id, $post ) {
        // Verificar nonce
        if ( ! isset( $_POST['bd_institution_meta_box_nonce'] ) ) {
            return;
        }
        if ( ! wp_verify_nonce( $_POST['bd_institution_meta_box_nonce'], 'bd_institution_meta_box_nonce_action' ) ) {
            return;
        }

        // Evitar autosaves y revisiones
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        // Verificar permisos
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Lista de campos simples a sanitizar
        $simple_fields = array(
            '_bd_institucion_tipo'            => 'bd_institucion_tipo',
            '_bd_institucion_nivel_educativo' => 'bd_institucion_nivel_educativo',
            '_bd_institucion_dependencia'     => 'bd_institucion_dependencia',
            '_bd_institucion_codigo_rbd'      => 'bd_institucion_codigo_rbd',
        );

        foreach ( $simple_fields as $meta_key => $field_name ) {
            if ( isset( $_POST[ $field_name ] ) ) {
                update_post_meta( $post_id, $meta_key, sanitize_text_field( $_POST[ $field_name ] ) );
            } else {
                delete_post_meta( $post_id, $meta_key );
            }
        }

        // Horario de Atención (array sanitizado)
        if ( isset( $_POST['bd_institucion_horario'] ) && is_array( $_POST['bd_institucion_horario'] ) ) {
            $horario_sanitized = array();
            $days_validos = array( 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo' );
            
            foreach ( $_POST['bd_institucion_horario'] as $day => $times ) {
                if ( in_array( $day, $days_validos, true ) ) {
                    $horario_sanitized[ $day ] = array(
                        'apertura' => isset( $times['apertura'] ) ? sanitize_text_field( $times['apertura'] ) : '',
                        'cierre'   => isset( $times['cierre'] ) ? sanitize_text_field( $times['cierre'] ) : '',
                    );
                }
            }
            update_post_meta( $post_id, '_bd_institucion_horario', wp_slash( wp_json_encode( $horario_sanitized ) ) );
        } else {
            delete_post_meta( $post_id, '_bd_institucion_horario' );
        }
    }

    /**
     * Obtiene el label legible de un tipo de institución.
     *
     * @param string $tipo Slug del tipo de institución.
     * @return string Label legible.
     */
    public static function get_tipo_label( $tipo ) {
        $labels = array(
            'escuela'     => __( 'Escuela', 'babel-directory' ),
            'universidad' => __( 'Universidad', 'babel-directory' ),
            'banco'       => __( 'Banco', 'babel-directory' ),
            'organismo'   => __( 'Organismo', 'babel-directory' ),
        );
        return isset( $labels[ $tipo ] ) ? $labels[ $tipo ] : $tipo;
    }

    /**
     * Obtiene el label legible de una dependencia.
     *
     * @param string $dependencia Slug de la dependencia.
     * @return string Label legible.
     */
    public static function get_dependencia_label( $dependencia ) {
        $labels = array(
            'municipal'    => __( 'Municipal', 'babel-directory' ),
            'subvencionada' => __( 'Subvencionada', 'babel-directory' ),
            'privada'      => __( 'Privada', 'babel-directory' ),
        );
        return isset( $labels[ $dependencia ] ) ? $labels[ $dependencia ] : $dependencia;
    }

    /**
     * Obtiene el label legible de un nivel educativo.
     *
     * @param string $nivel Slug del nivel educativo.
     * @return string Label legible.
     */
    public static function get_nivel_label( $nivel ) {
        $labels = array(
            'preescolar' => __( 'Preescolar', 'babel-directory' ),
            'basica'     => __( 'Educación Básica', 'babel-directory' ),
            'media'      => __( 'Educación Media', 'babel-directory' ),
            'superior'   => __( 'Educación Superior', 'babel-directory' ),
            'tecnica'    => __( 'Educación Técnica', 'babel-directory' ),
            'formacion'  => __( 'Formación / Capacitación', 'babel-directory' ),
        );
        return isset( $labels[ $nivel ] ) ? $labels[ $nivel ] : $nivel;
    }
}
