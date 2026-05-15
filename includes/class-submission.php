<?php
/**
 * BD_Submission — Formulario Frontend de Alta de Negocios
 * Hito 13: Formulario multistep frontend sin dependencia de wp-admin ni Divi.
 * v1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BD_Submission {

    public function __construct() {
        add_shortcode( 'bd_nuevo_negocio', array( $this, 'render_form' ) );
        add_action( 'wp_ajax_bd_submit_negocio',        array( $this, 'handle_submission' ) );
        add_action( 'wp_ajax_nopriv_bd_submit_negocio', array( $this, 'handle_submission' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    /**
     * Encolar assets solo en páginas con el shortcode.
     */
    public function enqueue_assets() {
        global $post;
        if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, 'bd_nuevo_negocio' ) ) {
            return;
        }
        wp_enqueue_style(
            'bd-form-style',
            BD_URL . 'assets/css/form.css',
            array(),
            BD_VERSION
        );
        wp_enqueue_script(
            'bd-form-submission',
            BD_URL . 'assets/js/form-submission.js',
            array(),
            BD_VERSION,
            true
        );
        wp_localize_script( 'bd-form-submission', 'bdFormConfig', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'bd_submission_nonce' ),
            'strings' => array(
                'sending'  => __( 'Enviando...', 'babel-directory' ),
                'success'  => __( '¡Tu negocio fue enviado! Lo revisaremos pronto.', 'babel-directory' ),
                'error'    => __( 'Hubo un error. Por favor intentá de nuevo.', 'babel-directory' ),
                'required' => __( 'Este campo es obligatorio.', 'babel-directory' ),
            ),
        ) );
    }

    /**
     * Renderizar el formulario via shortcode [bd_nuevo_negocio].
     */
    public function render_form( $is_admin = false ) {
        $categorias = get_terms( array(
            'taxonomy'   => 'directorio_categoria',
            'parent'     => 0,
            'hide_empty' => false,
            'orderby'    => 'name',
        ) );
        $regiones = get_terms( array(
            'taxonomy'   => 'directorio_region',
            'parent'     => 0,
            'hide_empty' => false,
            'orderby'    => 'name',
        ) );
        ob_start();
        include BD_PATH . 'templates/form-nuevo-negocio.php';
        return ob_get_clean();
    }

    /**
     * Procesar envío AJAX del formulario.
     */
    public function handle_submission() {

        // 1. Verificar nonce
        if ( ! check_ajax_referer( 'bd_submission_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Error de seguridad. Recargá la página.', 'babel-directory' ) ) );
        }

        // 2. Honeypot — fallo silencioso para bots
        if ( ! empty( $_POST['bd_hp_field'] ) ) {
            wp_send_json_success( array( 'message' => __( '¡Negocio enviado con éxito!', 'babel-directory' ) ) );
        }

        // 3. Validar campos obligatorios
        $nombre = sanitize_text_field( wp_unslash( $_POST['bd_nombre'] ?? '' ) );
        if ( empty( $nombre ) ) {
            wp_send_json_error( array( 'message' => __( 'El nombre del negocio es obligatorio.', 'babel-directory' ) ) );
        }

        $descripcion  = wp_kses_post( wp_unslash( $_POST['bd_descripcion'] ?? '' ) );
        $categoria_id = intval( $_POST['bd_categoria'] ?? 0 );
        $region_id    = intval( $_POST['bd_region'] ?? 0 );

        if ( empty( $descripcion ) ) {
            wp_send_json_error( array( 'message' => __( 'La descripción es obligatoria.', 'babel-directory' ) ) );
        }

        // 4. Sanitizar el resto de campos
        $direccion       = sanitize_text_field( wp_unslash( $_POST['bd_direccion']   ?? '' ) );
        $telefono        = sanitize_text_field( wp_unslash( $_POST['bd_telefono']    ?? '' ) );
        $whatsapp        = sanitize_text_field( wp_unslash( $_POST['bd_whatsapp']    ?? '' ) );
        $email           = sanitize_email(      wp_unslash( $_POST['bd_email']       ?? '' ) );
        $sitio_web       = esc_url_raw(         wp_unslash( $_POST['bd_sitio_web']   ?? '' ) );
        $horario         = sanitize_textarea_field( wp_unslash( $_POST['bd_horario'] ?? '' ) );
        $latitud         = floatval( $_POST['bd_latitud']  ?? 0 );
        $longitud        = floatval( $_POST['bd_longitud'] ?? 0 );

        // Mapa Embed (Iframe)
        $maps_embed      = wp_kses( wp_unslash( $_POST['bd_maps_embed'] ?? '' ), array(
            'iframe' => array(
                'src'             => true,
                'width'           => true,
                'height'          => true,
                'frameborder'     => true,
                'style'           => true,
                'allowfullscreen' => true,
                'loading'         => true,
                'referrerpolicy'  => true,
            ),
        ) );

        // Paso 3
        $rango_precio    = sanitize_text_field( wp_unslash( $_POST['bd_rango_precio'] ?? '' ) );
        $wifi            = ! empty( $_POST['bd_wifi'] )            ? '1' : '0';
        $estacionamiento = ! empty( $_POST['bd_estacionamiento'] ) ? '1' : '0';
        $delivery        = ! empty( $_POST['bd_delivery'] )        ? '1' : '0';
        $reservas        = ! empty( $_POST['bd_reservas'] )        ? '1' : '0';
        $accesibilidad   = ! empty( $_POST['bd_accesibilidad'] )   ? '1' : '0';

        // 5. Crear el post
        $is_admin = current_user_can( 'manage_options' ) && ! empty( $_POST['bd_admin_mode'] );
        $status   = ( $is_admin && ! empty( $_POST['bd_publicar_inmediato'] ) ) ? 'publish' : 'pending';

        $post_id = wp_insert_post( array(
            'post_title'   => $nombre,
            'post_content' => $descripcion,
            'post_status'  => $status,
            'post_type'    => 'directorio_negocio',
            'post_author'  => is_user_logged_in() ? get_current_user_id() : 1,
        ), true );

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( array( 'message' => $post_id->get_error_message() ) );
        }

        // 6. Procesar Archivos (Multimedia)
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/media.php' );

        // Logo
        if ( ! empty( $_FILES['bd_logo']['name'] ) ) {
            $logo_id = media_handle_upload( 'bd_logo', $post_id );
            if ( ! is_wp_error( $logo_id ) ) {
                update_post_meta( $post_id, '_bd_logo_id', $logo_id );
            }
        }

        // Portada (Featured Image)
        if ( ! empty( $_FILES['bd_cover']['name'] ) ) {
            $cover_id = media_handle_upload( 'bd_cover', $post_id );
            if ( ! is_wp_error( $cover_id ) ) {
                set_post_thumbnail( $post_id, $cover_id );
            }
        }

        // Galería (Múltiple)
        if ( ! empty( $_FILES['bd_gallery']['name'][0] ) ) {
            $gallery_ids = array();
            $files = $_FILES['bd_gallery'];
            foreach ( $files['name'] as $key => $value ) {
                if ( $files['name'][$key] ) {
                    $file = array(
                        'name'     => $files['name'][$key],
                        'type'     => $files['type'][$key],
                        'tmp_name' => $files['tmp_name'][$key],
                        'error'    => $files['error'][$key],
                        'size'     => $files['size'][$key],
                    );
                    $_FILES['bd_gallery_single'] = $file;
                    $attachment_id = media_handle_upload( 'bd_gallery_single', $post_id );
                    if ( ! is_wp_error( $attachment_id ) ) {
                        $gallery_ids[] = $attachment_id;
                    }
                }
            }
            if ( ! empty( $gallery_ids ) ) {
                update_post_meta( $post_id, '_bd_galeria', implode( ',', $gallery_ids ) );
            }
        }

        // 7. Guardar meta — usando las mismas keys que class-metaboxes.php
        $meta = array(
            '_bd_direccion'       => $direccion,
            '_bd_telefono'        => $telefono,
            '_bd_whatsapp'        => $whatsapp,
            '_bd_email'           => $email,
            '_bd_sitio_web'       => $sitio_web,
            '_bd_horario'         => $horario,
            '_bd_latitud'         => $latitud ? $latitud : '',
            '_bd_longitud'        => $longitud ? $longitud : '',
            '_bd_maps_embed'      => $maps_embed,
            '_bd_rango_precio'    => $rango_precio,
            '_bd_wifi'            => $wifi,
            '_bd_estacionamiento' => $estacionamiento,
            '_bd_delivery'        => $delivery,
            '_bd_reservas'        => $reservas,
            '_bd_accesibilidad'   => $accesibilidad,
            '_bd_verificado'      => '0',
            '_bd_destacado'       => '0',
            '_bd_reputacion'      => '0.0',
        );
        foreach ( $meta as $key => $value ) {
            update_post_meta( $post_id, $key, $value );
        }

        // 7.5 Meta de Reseña Premium (Sovereign)
        if ( $is_admin ) {
            $admin_stars  = intval( $_POST['bd_admin_review_stars'] ?? 0 );
            $admin_review = sanitize_textarea_field( wp_unslash( $_POST['bd_admin_review_text'] ?? '' ) );
            
            if ( $admin_stars > 0 ) {
                update_post_meta( $post_id, '_bd_admin_review_stars', $admin_stars );
                update_post_meta( $post_id, '_bd_admin_review_text',  $admin_review );
                update_post_meta( $post_id, '_bd_verificado', '1' ); // Autoverificado si lo hace el admin
                
                // Actualizar reputación inicial si hay reseña admin
                update_post_meta( $post_id, '_bd_reputacion', floatval( $admin_stars ) );
                update_post_meta( $post_id, '_bd_review_count', 1 );
            }
        }

        // 8. Asignar taxonomías
        if ( $categoria_id ) {
            wp_set_object_terms( $post_id, $categoria_id, 'directorio_categoria' );
        }
        if ( $region_id ) {
            wp_set_object_terms( $post_id, $region_id, 'directorio_region' );
        }

        // 9. Notificar al admin por email
        $admin_email = get_option( 'admin_email' );
        $site_name   = get_bloginfo( 'name' );
        /* translators: 1: site name, 2: business name */
        $subject = sprintf( __( '[%1$s] Nuevo negocio pendiente: %2$s', 'babel-directory' ), $site_name, $nombre );
        $body    = sprintf(
            /* translators: 1: business name, 2: contact info, 3: admin URL */
            __( "Nuevo negocio para revisión:\n\nNombre: %1\$s\nContacto: %2\$s\n\nRevisar: %3\$s", 'babel-directory' ),
            $nombre,
            $email ? $email : $telefono,
            admin_url( 'post.php?post=' . $post_id . '&action=edit' )
        );
        wp_mail( $admin_email, $subject, $body );

        wp_send_json_success( array(
            'message' => __( '¡Tu negocio fue enviado con éxito! Lo revisaremos y publicaremos pronto.', 'babel-directory' ),
        ) );
    }
}
