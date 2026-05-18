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

        ob_start();

        // Mostrar notificaciones si existen
        if ( ! empty( $this->notices ) ) {
            foreach ( $this->notices as $notice ) {
                $class = $notice['type'] === 'success' ? 'babel-notice-success' : 'babel-notice-error';
                echo '<div class="babel-notice ' . esc_attr( $class ) . '">';
                echo esc_html( $notice['message'] );
                echo '</div>';
            }
        }
        ?>
        <div class="babel-submission-form-container">
            <form action="" method="post" enctype="multipart/form-data" class="babel-submission-form">
                
                <?php wp_nonce_field( 'babel_submit_listing', 'babel_submission_nonce' ); ?>

                <!-- Título del negocio -->
                <div class="babel-form-group">
                    <label for="babel_title">
                        <?php esc_html_e( 'Nombre del Negocio *', 'babel-directory' ); ?>
                    </label>
                    <input type="text" id="babel_title" name="babel_title" required placeholder="<?php esc_attr_e( 'Ej: Cafetería Central', 'babel-directory' ); ?>" value="<?php echo isset( $_POST['babel_title'] ) ? esc_attr( wp_unslash( $_POST['babel_title'] ) ) : ''; ?>" />
                </div>

                <!-- Descripción -->
                <div class="babel-form-group">
                    <label for="babel_description">
                        <?php esc_html_e( 'Descripción del Negocio *', 'babel-directory' ); ?>
                    </label>
                    <textarea id="babel_description" name="babel_description" required rows="5" placeholder="<?php esc_attr_e( 'Describe los servicios, productos y horarios de tu negocio...', 'babel-directory' ); ?>"><?php echo isset( $_POST['babel_description'] ) ? esc_textarea( wp_unslash( $_POST['babel_description'] ) ) : ''; ?></textarea>
                </div>

                <div class="babel-form-row">
                    <!-- Categoría -->
                    <div class="babel-form-group">
                        <label for="babel_category">
                            <?php esc_html_e( 'Categoría *', 'babel-directory' ); ?>
                        </label>
                        <select id="babel_category" name="babel_category" required>
                            <option value=""><?php esc_html_e( '-- Selecciona una Categoría --', 'babel-directory' ); ?></option>
                            <?php $this->render_taxonomy_options( 'babel_category' ); ?>
                        </select>
                    </div>

                    <!-- Región/Comuna -->
                    <div class="babel-form-group">
                        <label for="babel_region">
                            <?php esc_html_e( 'Región / Comuna *', 'babel-directory' ); ?>
                        </label>
                        <select id="babel_region" name="babel_region" required>
                            <option value=""><?php esc_html_e( '-- Selecciona una Región/Comuna --', 'babel-directory' ); ?></option>
                            <?php $this->render_taxonomy_options( 'babel_region' ); ?>
                        </select>
                    </div>
                </div>

                <!-- Dirección -->
                <div class="babel-form-group">
                    <label for="babel_address">
                        <?php esc_html_e( 'Dirección Comercial *', 'babel-directory' ); ?>
                    </label>
                    <input type="text" id="babel_address" name="babel_address" required placeholder="<?php esc_attr_e( 'Ej: Av. Providencia 1234, Oficina 501', 'babel-directory' ); ?>" value="<?php echo isset( $_POST['babel_address'] ) ? esc_attr( wp_unslash( $_POST['babel_address'] ) ) : ''; ?>" />
                </div>

                <div class="babel-form-row">
                    <!-- Teléfono -->
                    <div class="babel-form-group">
                        <label for="babel_phone">
                            <?php esc_html_e( 'Teléfono de Contacto *', 'babel-directory' ); ?>
                        </label>
                        <input type="text" id="babel_phone" name="babel_phone" required placeholder="<?php esc_attr_e( 'Ej: +56 9 1234 5678', 'babel-directory' ); ?>" value="<?php echo isset( $_POST['babel_phone'] ) ? esc_attr( wp_unslash( $_POST['babel_phone'] ) ) : ''; ?>" />
                    </div>

                    <!-- WhatsApp -->
                    <div class="babel-form-group">
                        <label for="babel_whatsapp">
                            <?php esc_html_e( 'WhatsApp Comercial', 'babel-directory' ); ?>
                        </label>
                        <input type="text" id="babel_whatsapp" name="babel_whatsapp" placeholder="<?php esc_attr_e( 'Ej: +56 9 8765 4321', 'babel-directory' ); ?>" value="<?php echo isset( $_POST['babel_whatsapp'] ) ? esc_attr( wp_unslash( $_POST['babel_whatsapp'] ) ) : ''; ?>" />
                    </div>
                </div>

                <!-- Logotipo / Imagen Destacada -->
                <div class="babel-form-group">
                    <label for="babel_logo">
                        <?php esc_html_e( 'Logotipo del Negocio (Opcional)', 'babel-directory' ); ?>
                    </label>
                    <span>
                        <?php esc_html_e( 'Formatos permitidos: JPG, PNG o WEBP de forma exclusiva.', 'babel-directory' ); ?>
                    </span>
                    <input type="file" id="babel_logo" name="babel_logo" accept="image/jpeg,image/png,image/webp" />
                </div>

                <!-- Botón de Envío -->
                <button type="submit" name="babel_submit_action">
                    <?php esc_html_e( 'Enviar Negocio a Moderación', 'babel-directory' ); ?>
                </button>

            </form>
        </div>
        <?php
        return ob_get_clean();
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
        // Evaluar si se presionó el botón y si el Nonce de seguridad es válido
        if ( ! isset( $_POST['babel_submit_action'] ) ) {
            return;
        }

        if ( ! isset( $_POST['babel_submission_nonce'] ) || ! wp_verify_nonce( $_POST['babel_submission_nonce'], 'babel_submit_listing' ) ) {
            $this->notices[] = array(
                'type'    => 'error',
                'message' => esc_html__( 'Error de seguridad. Por favor, recarga la página e inténtalo nuevamente.', 'babel-directory' ),
            );
            return;
        }

        // Capturar y sanitizar campos obligatorios de texto y selectores
        $title       = isset( $_POST['babel_title'] ) ? sanitize_text_field( wp_unslash( $_POST['babel_title'] ) ) : '';
        $description = isset( $_POST['babel_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['babel_description'] ) ) : '';
        $category_id = isset( $_POST['babel_category'] ) ? intval( $_POST['babel_category'] ) : 0;
        $region_id   = isset( $_POST['babel_region'] ) ? intval( $_POST['babel_region'] ) : 0;
        $address     = isset( $_POST['babel_address'] ) ? sanitize_text_field( wp_unslash( $_POST['babel_address'] ) ) : '';
        $phone       = isset( $_POST['babel_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['babel_phone'] ) ) : '';
        $whatsapp    = isset( $_POST['babel_whatsapp'] ) ? sanitize_text_field( wp_unslash( $_POST['babel_whatsapp'] ) ) : '';

        // Validar que los campos obligatorios no estén vacíos
        if ( empty( $title ) || empty( $description ) || empty( $category_id ) || empty( $region_id ) || empty( $address ) || empty( $phone ) ) {
            $this->notices[] = array(
                'type'    => 'error',
                'message' => esc_html__( 'Por favor, completa todos los campos obligatorios marcados con asterisco (*).', 'babel-directory' ),
            );
            return;
        }

        // Crear el nuevo negocio en estado 'pending' (para moderación)
        $post_data = array(
            'post_title'   => $title,
            'post_content' => $description,
            'post_status'  => 'pending',
            'post_type'    => 'babel_business',
        );

        $post_id = wp_insert_post( $post_data );

        if ( is_wp_error( $post_id ) || ! $post_id ) {
            $this->notices[] = array(
                'type'    => 'error',
                'message' => esc_html__( 'Ocurrió un error al procesar tu solicitud de registro. Inténtalo más tarde.', 'babel-directory' ),
            );
            return;
        }

        // Vincular los términos de taxonomías correspondientes
        wp_set_object_terms( $post_id, array( $category_id ), 'babel_category' );
        wp_set_object_terms( $post_id, array( $region_id ), 'babel_region' );

        // Almacenar metadatos del negocio
        update_post_meta( $post_id, '_babel_address', $address );
        update_post_meta( $post_id, '_babel_phone', $phone );
        update_post_meta( $post_id, '_babel_whatsapp', $whatsapp );

        // Procesamiento Seguro del Logotipo (Media Upload)
        if ( isset( $_FILES['babel_logo'] ) && ! empty( $_FILES['babel_logo']['name'] ) ) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';

            // Restringir dinámicamente los mime types permitidos
            add_filter( 'upload_mimes', array( $this, 'restrict_submission_mimes' ) );

            // Subir archivo de forma nativa y segura
            $attachment_id = media_handle_upload( 'babel_logo', $post_id );

            // Remover el filtro de mime types inmediatamente para no afectar otras subidas
            remove_filter( 'upload_mimes', array( $this, 'restrict_submission_mimes' ) );

            if ( ! is_wp_error( $attachment_id ) ) {
                // Asignar el logotipo como imagen destacada del negocio
                set_post_thumbnail( $post_id, $attachment_id );
            } else {
                $this->notices[] = array(
                    'type'    => 'error',
                    'message' => sprintf( esc_html__( 'El negocio fue registrado pero el logotipo no pudo subirse: %s', 'babel-directory' ), $attachment_id->get_error_message() ),
                );
                // Si la imagen falla, no detenemos el éxito del post
            }
        }

        // Registrar mensaje de éxito
        $this->notices[] = array(
            'type'    => 'success',
            'message' => esc_html__( '¡Tu negocio ha sido registrado con éxito! Está en proceso de revisión por nuestro equipo de moderación.', 'babel-directory' ),
        );

        // Limpiar $_POST para evitar rellenar el formulario de nuevo
        $_POST = array();
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
