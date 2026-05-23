<?php
namespace Babel\Directory;

/**
 * Procesamiento Seguro de Envío de Negocios desde el Frontend (Babel_Directory_Submission)
 * v7.1.0 — Hito 11: Formulario Predictivo AJAX y Mapa Leaflet.
 *
 * @package Babel_Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}
class Submission {

    public function __construct() {
        add_shortcode( 'babel_submission_form', array( $this, 'render_submission_form' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_submission_assets' ) );
        
        // AJAX Endpoints
        add_action( 'wp_ajax_babel_frontend_submission', array( $this, 'handle_ajax_submission' ) );
        add_action( 'wp_ajax_nopriv_babel_frontend_submission', array( $this, 'handle_ajax_submission' ) );
    }

    public function enqueue_submission_assets() {
        // Encolar assets públicos generales
        wp_enqueue_style( 'babel-public-css' );
        wp_enqueue_script( 'babel-public-js' );

        // Encolar Leaflet desde CDN
        wp_enqueue_style( 'leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4' );
        wp_enqueue_script( 'leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true );

        // Script específico del formulario
        wp_enqueue_script( 
            'babel-submission-js', 
            BD_URL . 'assets/js/babel-submission.js', 
            array( 'leaflet-js' ), 
            BD_VERSION, 
            true 
        );
    }

    public function render_submission_form() {
        ob_start();
        ?>
        <div class="babel-submission-wrapper" style="max-width: 600px; margin: 0 auto; background: rgba(255, 255, 255, 0.9); padding: 30px; border-radius: 12px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);">
            <form id="babel-submission-form" enctype="multipart/form-data">
                
                <h3 style="margin-top:0; color:#1e293b; font-size:1.5rem; font-weight:600;">Alta de Comercio</h3>
                
                <div class="babel-form-group" style="margin-bottom: 20px;">
                    <label style="display:block; margin-bottom:8px; font-weight:500; color:#475569;">Nombre del Comercio</label>
                    <input type="text" name="business_name" required placeholder="Ej: Pizzería Don Carlos" style="width:100%; padding:12px; border:1px solid #cbd5e1; border-radius:6px; background:#f8fafc;">
                </div>
                
                <div class="babel-form-group" style="margin-bottom: 20px;">
                    <label style="display:block; margin-bottom:8px; font-weight:500; color:#475569;">Región</label>
                    <select name="business_region" required style="width:100%; padding:12px; border:1px solid #cbd5e1; border-radius:6px; background:#f8fafc;">
                        <option value="">Selecciona una región...</option>
                        <?php $this->render_taxonomy_options( 'babel_region' ); ?>
                    </select>
                </div>

                <div class="babel-form-group" style="margin-bottom: 20px;">
                    <label style="display:block; margin-bottom:8px; font-weight:500; color:#475569;">Dirección Física (Mapa Radar)</label>
                    <div style="display:flex; gap:10px; margin-bottom: 10px;">
                        <input type="text" id="babel_address" name="address" placeholder="Ej: Av. Providencia 1234" style="flex:1; padding:12px; border:1px solid #cbd5e1; border-radius:6px; background:#f8fafc;">
                        <button id="babel-radar-btn" class="babel-btn" style="background:#3b82f6; color:#fff; border:none; padding:12px 20px; border-radius:6px; cursor:pointer; font-weight:500; transition:all 0.3s ease;">Radar (Ubicación Actual)</button>
                    </div>
                    <div id="babel-map-container" style="height:0; transition: height 0.3s ease; border-radius:8px; overflow:hidden; z-index:1;"></div>
                    <input type="hidden" id="babel_lat" name="babel_lat">
                    <input type="hidden" id="babel_lng" name="babel_lng">
                </div>

                <div class="babel-form-group" style="margin-bottom: 20px;">
                    <label style="display:block; margin-bottom:8px; font-weight:500; color:#475569;">Descripción Corta</label>
                    <textarea name="description" rows="3" style="width:100%; padding:12px; border:1px solid #cbd5e1; border-radius:6px; background:#f8fafc;"></textarea>
                </div>

                <div class="babel-form-group" style="margin-bottom: 20px;">
                    <label style="display:block; margin-bottom:8px; font-weight:500; color:#475569;">Foto Principal</label>
                    <input type="file" name="featured_image" accept="image/jpeg,image/png,image/webp" style="width:100%; padding:10px; background:#f1f5f9; border:1px dashed #cbd5e1; border-radius:6px;">
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom: 20px;">
                    <div class="babel-form-group">
                        <label style="display:block; margin-bottom:8px; font-weight:500; color:#475569;">Teléfono (WhatsApp)</label>
                        <input type="tel" name="phone" placeholder="+56 9..." style="width:100%; padding:12px; border:1px solid #cbd5e1; border-radius:6px; background:#f8fafc;">
                    </div>
                    <div class="babel-form-group">
                        <label style="display:block; margin-bottom:8px; font-weight:500; color:#475569;">Email</label>
                        <input type="email" name="email" placeholder="contacto@comercio.cl" style="width:100%; padding:12px; border:1px solid #cbd5e1; border-radius:6px; background:#f8fafc;">
                    </div>
                </div>

                <button type="submit" id="babel-submit-btn" style="width:100%; background:#10b981; color:#fff; border:none; padding:15px; border-radius:6px; font-size:16px; font-weight:600; cursor:pointer; transition:all 0.3s ease;">Publicar Comercio (AJAX)</button>
            </form>
            <div id="babel-response-message"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_taxonomy_options( $taxonomy ) {
        $parent_terms = get_terms( array(
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
            'parent'     => 0,
        ) );

        if ( \is_wp_error( $parent_terms ) || empty( $parent_terms ) ) {
            return;
        }

        foreach ( $parent_terms as $parent ) {
            echo '<option value="' . esc_attr( $parent->term_id ) . '">' . esc_html( $parent->name ) . '</option>';
            
            $child_terms = get_terms( array(
                'taxonomy'   => $taxonomy,
                'hide_empty' => false,
                'parent'     => $parent->term_id,
            ) );

            if ( ! \is_wp_error( $child_terms ) && ! empty( $child_terms ) ) {
                foreach ( $child_terms as $child ) {
                    echo '<option value="' . esc_attr( $child->term_id ) . '">&nbsp;&nbsp;&nbsp;&mdash;&nbsp;' . esc_html( $child->name ) . '</option>';
                }
            }
        }
    }

    public function handle_ajax_submission() {
        check_ajax_referer( 'babel_submission_nonce', 'security' );

        $title = isset( $_POST['business_name'] ) ? sanitize_text_field( wp_unslash( $_POST['business_name'] ) ) : '';
        if ( empty( $title ) ) {
            wp_send_json_error( array( 'message' => 'El nombre del comercio es obligatorio.' ) );
        }

        // Crear Post
        $post_id = wp_insert_post( array(
            'post_title'   => $title,
            'post_content' => isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '',
            'post_status'  => 'pending', // Requiere moderación
            'post_type'    => 'babel_business'
        ) );

        if ( \is_wp_error( $post_id ) || $post_id === 0 ) {
            wp_send_json_error( array( 'message' => 'Error al crear el registro en la base de datos.' ) );
        }

        // Asignar Taxonomía
        if ( ! empty( $_POST['business_region'] ) ) {
            wp_set_object_terms( $post_id, intval( $_POST['business_region'] ), 'babel_region' );
        }

        // Guardar Meta Keys
        $metas = array(
            '_babel_phone'   => 'phone',
            '_babel_email'   => 'email',
            '_babel_address' => 'address',
            '_babel_lat'     => 'babel_lat',
            '_babel_lng'     => 'babel_lng'
        );

        foreach ( $metas as $meta_key => $post_key ) {
            if ( isset( $_POST[ $post_key ] ) ) {
                update_post_meta( $post_id, $meta_key, sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) ) );
            }
        }

        // Manejar Subida de Imagen (Regla Nano Banana)
        if ( ! empty( $_FILES['featured_image']['name'] ) ) {
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            require_once( ABSPATH . 'wp-admin/includes/media.php' );

            $attachment_id = media_handle_upload( 'featured_image', $post_id );
            if ( ! \is_wp_error( $attachment_id ) ) {
                set_post_thumbnail( $post_id, $attachment_id );
            }
        }

        wp_send_json_success( array( 'message' => '¡Éxito! Tu comercio ha sido enviado y está en revisión.' ) );
    }
}
