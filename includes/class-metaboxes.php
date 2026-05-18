<?php
/**
 * Clase para el manejo de Metaboxes y Custom Fields nativos
 *
 * @package Babel_Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}

class Babel_Directory_Metaboxes {

    /**
     * Constructor de la clase de metaboxes.
     * Enlaza los hooks de WordPress para renderizado y guardado.
     */
    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_business_meta_box' ) );
        add_action( 'save_post_babel_business', array( $this, 'save_business_meta' ), 10, 2 );
    }

    /**
     * Registra la metabox nativa para el post type 'babel_business'.
     */
    public function add_business_meta_box() {
        add_meta_box(
            'babel_business_details',
            __( 'Información de Contacto y Detalles del Negocio', 'babel-directory' ),
            array( $this, 'render_business_meta_box' ),
            'babel_business',
            'normal',
            'high'
        );
    }

    /**
     * Renderiza los campos de la metabox en el backend de WordPress.
     *
     * @param WP_Post $post El objeto del post actual.
     */
    public function render_business_meta_box( $post ) {
        // Generar token de seguridad (Nonce)
        wp_nonce_field( 'babel_business_meta_box_nonce_action', 'babel_business_meta_box_nonce' );

        // Recuperar los valores guardados actualmente
        $phone       = get_post_meta( $post->ID, '_babel_phone', true );
        $whatsapp    = get_post_meta( $post->ID, '_babel_whatsapp', true );
        $address     = get_post_meta( $post->ID, '_babel_address', true );
        $gmaps       = get_post_meta( $post->ID, '_babel_gmaps', true );
        $hours       = get_post_meta( $post->ID, '_babel_hours', true );
        $latitude    = get_post_meta( $post->ID, '_babel_latitude', true );
        $longitude   = get_post_meta( $post->ID, '_babel_longitude', true );
        $is_verified = get_post_meta( $post->ID, '_babel_is_verified', true );
        $is_featured = get_post_meta( $post->ID, '_babel_is_featured', true );
        ?>
        <style>
            .babel-meta-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
            }
            .babel-meta-table th {
                width: 20%;
                text-align: left;
                padding: 12px 10px;
                font-weight: 600;
                color: #1d2327;
                vertical-align: top;
            }
            .babel-meta-table td {
                padding: 8px 10px;
                vertical-align: top;
            }
            .babel-meta-table input[type="text"],
            .babel-meta-table input[type="url"],
            .babel-meta-table textarea {
                width: 100%;
                box-sizing: border-box;
                border-radius: 4px;
                border: 1px solid #8c8f94;
                padding: 8px 10px;
                font-size: 14px;
                line-height: 1.5;
                transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
            }
            .babel-meta-table input[type="text"]:focus,
            .babel-meta-table input[type="url"]:focus,
            .babel-meta-table textarea:focus {
                border-color: #2271b1;
                box-shadow: 0 0 0 1px #2271b1;
                outline: 2px solid transparent;
            }
            .babel-meta-desc {
                margin: 4px 0 0;
                font-size: 12px;
                font-style: italic;
                color: #646970;
            }
        </style>

        <table class="babel-meta-table">
            <tbody>
                <!-- Campo Teléfono -->
                <tr>
                    <th>
                        <label for="babel_phone"><?php esc_html_e( 'Teléfono de Contacto', 'babel-directory' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="babel_phone" name="babel_phone" value="<?php echo esc_attr( $phone ); ?>" placeholder="<?php esc_attr_e( 'Ej: +56 9 1234 5678 o 2 2345 6789', 'babel-directory' ); ?>" />
                        <p class="babel-meta-desc"><?php esc_html_e( 'Teléfono comercial de atención general.', 'babel-directory' ); ?></p>
                    </td>
                </tr>

                <!-- Campo WhatsApp -->
                <tr>
                    <th>
                        <label for="babel_whatsapp"><?php esc_html_e( 'WhatsApp', 'babel-directory' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="babel_whatsapp" name="babel_whatsapp" value="<?php echo esc_attr( $whatsapp ); ?>" placeholder="<?php esc_attr_e( 'Ej: +56912345678', 'babel-directory' ); ?>" />
                        <p class="babel-meta-desc"><?php esc_html_e( 'Número telefónico en formato internacional para enlace directo de WhatsApp (sin espacios ni guiones).', 'babel-directory' ); ?></p>
                    </td>
                </tr>

                <!-- Campo Dirección Física -->
                <tr>
                    <th>
                        <label for="babel_address"><?php esc_html_e( 'Dirección Física', 'babel-directory' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="babel_address" name="babel_address" value="<?php echo esc_attr( $address ); ?>" placeholder="<?php esc_attr_e( 'Ej: Av. Providencia 1234, Oficina 501, Providencia', 'babel-directory' ); ?>" />
                        <p class="babel-meta-desc"><?php esc_html_e( 'Dirección comercial completa para los clientes.', 'babel-directory' ); ?></p>
                    </td>
                </tr>

                <!-- Enlace Google Maps -->
                <tr>
                    <th>
                        <label for="babel_gmaps"><?php esc_html_e( 'Enlace de Google Maps', 'babel-directory' ); ?></label>
                    </th>
                    <td>
                        <input type="url" id="babel_gmaps" name="babel_gmaps" value="<?php echo esc_url( $gmaps ); ?>" placeholder="<?php esc_attr_e( 'Ej: https://maps.app.goo.gl/... o https://google.com/maps/...', 'babel-directory' ); ?>" />
                        <p class="babel-meta-desc"><?php esc_html_e( 'URL directa de la ubicación en Google Maps.', 'babel-directory' ); ?></p>
                    </td>
                </tr>

                <!-- Horarios de Atención -->
                <tr>
                    <th>
                        <label for="babel_hours"><?php esc_html_e( 'Horarios de Atención', 'babel-directory' ); ?></label>
                    </th>
                    <td>
                        <textarea id="babel_hours" name="babel_hours" rows="4" placeholder="<?php esc_attr_e( "Ej:\nLunes a Viernes: 09:00 a 18:00\nSábados: 10:00 a 14:00\nDomingos: Cerrado", 'babel-directory' ); ?>"><?php echo esc_textarea( $hours ); ?></textarea>
                        <p class="babel-meta-desc"><?php esc_html_e( 'Detalla los días y horarios comerciales de atención al público.', 'babel-directory' ); ?></p>
                    </td>
                </tr>

                <!-- Latitud GPS -->
                <tr>
                    <th>
                        <label for="babel_latitude"><?php esc_html_e( 'Latitud GPS', 'babel-directory' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="babel_latitude" name="babel_latitude" value="<?php echo esc_attr( $latitude ); ?>" placeholder="<?php esc_attr_e( 'Ej: -33.4372', 'babel-directory' ); ?>" />
                        <p class="babel-meta-desc"><?php esc_html_e( 'Coordenada de latitud para geolocalización (radar de proximidad).', 'babel-directory' ); ?></p>
                    </td>
                </tr>

                <!-- Longitud GPS -->
                <tr>
                    <th>
                        <label for="babel_longitude"><?php esc_html_e( 'Longitud GPS', 'babel-directory' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="babel_longitude" name="babel_longitude" value="<?php echo esc_attr( $longitude ); ?>" placeholder="<?php esc_attr_e( 'Ej: -70.6506', 'babel-directory' ); ?>" />
                        <p class="babel-meta-desc"><?php esc_html_e( 'Coordenada de longitud para geolocalización (radar de proximidad).', 'babel-directory' ); ?></p>
                    </td>
                </tr>

                <!-- Estados Especiales (Verificado y Destacado) -->
                <tr>
                    <th>
                        <?php esc_html_e( 'Estados Especiales', 'babel-directory' ); ?>
                    </th>
                    <td>
                        <label style="margin-right: 20px; display: inline-flex; align-items: center; cursor: pointer;">
                            <input type="checkbox" id="babel_is_verified" name="babel_is_verified" value="1" <?php checked( $is_verified, '1' ); ?> style="margin-right: 6px;" />
                            <strong><?php esc_html_e( 'Negocio Verificado', 'babel-directory' ); ?></strong>
                        </label>
                        
                        <label style="display: inline-flex; align-items: center; cursor: pointer;">
                            <input type="checkbox" id="babel_is_featured" name="babel_is_featured" value="1" <?php checked( $is_featured, '1' ); ?> style="margin-right: 6px;" />
                            <strong><?php esc_html_e( 'Destacar Negocio', 'babel-directory' ); ?></strong>
                        </label>
                        <p class="babel-meta-desc"><?php esc_html_e( 'Marcar si el negocio está verificado o si debe tener prioridad de posicionamiento (Destacado).', 'babel-directory' ); ?></p>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    /**
     * Guarda y sanitiza de forma segura los metadatos en la base de datos de WordPress.
     *
     * @param int     $post_id ID del post que se está guardando.
     * @param WP_Post $post    Objeto del post que se está guardando.
     */
    public function save_business_meta( $post_id, $post ) {
        // 1. Validar el token de seguridad (Nonce)
        if ( ! isset( $_POST['babel_business_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['babel_business_meta_box_nonce'], 'babel_business_meta_box_nonce_action' ) ) {
            return;
        }

        // 2. Verificar que no sea un guardado automático (Autosave)
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // 3. Verificar permisos del usuario actual
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // 4. Sanitizar y guardar cada campo personalizado de forma individual

        // Teléfono
        if ( isset( $_POST['babel_phone'] ) ) {
            $phone = sanitize_text_field( wp_unslash( $_POST['babel_phone'] ) );
            update_post_meta( $post_id, '_babel_phone', $phone );
        } else {
            delete_post_meta( $post_id, '_babel_phone' );
        }

        // WhatsApp
        if ( isset( $_POST['babel_whatsapp'] ) ) {
            $whatsapp = sanitize_text_field( wp_unslash( $_POST['babel_whatsapp'] ) );
            $whatsapp = preg_replace( '/[^0-9+]/', '', $whatsapp ); // Permitir sólo números y el símbolo '+'
            update_post_meta( $post_id, '_babel_whatsapp', $whatsapp );
        } else {
            delete_post_meta( $post_id, '_babel_whatsapp' );
        }

        // Dirección Física
        if ( isset( $_POST['babel_address'] ) ) {
            $address = sanitize_text_field( wp_unslash( $_POST['babel_address'] ) );
            update_post_meta( $post_id, '_babel_address', $address );
        } else {
            delete_post_meta( $post_id, '_babel_address' );
        }

        // Enlace de Google Maps
        if ( isset( $_POST['babel_gmaps'] ) ) {
            $gmaps = esc_url_raw( wp_unslash( $_POST['babel_gmaps'] ) );
            update_post_meta( $post_id, '_babel_gmaps', $gmaps );
        } else {
            delete_post_meta( $post_id, '_babel_gmaps' );
        }

        // Horarios
        if ( isset( $_POST['babel_hours'] ) ) {
            $hours = sanitize_textarea_field( wp_unslash( $_POST['babel_hours'] ) );
            update_post_meta( $post_id, '_babel_hours', $hours );
        } else {
            delete_post_meta( $post_id, '_babel_hours' );
        }

        // Latitud
        if ( isset( $_POST['babel_latitude'] ) ) {
            $latitude = sanitize_text_field( wp_unslash( $_POST['babel_latitude'] ) );
            update_post_meta( $post_id, '_babel_latitude', $latitude );
        } else {
            delete_post_meta( $post_id, '_babel_latitude' );
        }

        // Longitud
        if ( isset( $_POST['babel_longitude'] ) ) {
            $longitude = sanitize_text_field( wp_unslash( $_POST['babel_longitude'] ) );
            update_post_meta( $post_id, '_babel_longitude', $longitude );
        } else {
            delete_post_meta( $post_id, '_babel_longitude' );
        }

        // Verificado (Checkbox)
        $is_verified = isset( $_POST['babel_is_verified'] ) ? '1' : '0';
        update_post_meta( $post_id, '_babel_is_verified', $is_verified );

        // Destacado (Checkbox)
        $is_featured = isset( $_POST['babel_is_featured'] ) ? '1' : '0';
        update_post_meta( $post_id, '_babel_is_featured', $is_featured );
    }
}
