<?php
namespace Babel\Directory;

/**
 * Panel de Administración y Configuración Integral SPA (Babel_Directory_Admin)
 * v8.0.0 — Hito 11: Single Page Application "Soy de Chile"
 *
 * @package Babel_Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}

class Admin {

    /**
     * Constructor de la clase.
     * Registra los ganchos de administración y de la Settings API.
     */
    public function __construct() {
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        
        // AJAX Endpoints SPA
        add_action( 'wp_ajax_sdc_save_settings', array( $this, 'ajax_save_settings' ) );
        add_action( 'wp_ajax_sdc_save_business', array( $this, 'ajax_save_business' ) );
        add_action( 'wp_ajax_sdc_quick_action', array( $this, 'ajax_quick_action' ) );
        add_action( 'wp_ajax_sdc_fetch_businesses', array( $this, 'ajax_fetch_businesses' ) );
    }

    /**
     * Registra los ajustes, secciones y campos usando la Settings API de WordPress.
     */
    public function register_settings() {
        register_setting(
            'babel_directory_settings_group',
            'babel_google_client_id',
            array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            )
        );

        register_setting(
            'babel_directory_settings_group',
            'babel_microsoft_client_id',
            array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            )
        );

        add_settings_section(
            'babel_google_section',
            __( 'Integración con Google Identity Services', 'babel-directory' ),
            array( $this, 'render_google_section_description' ),
            'bd-settings'
        );

        add_settings_field(
            'babel_google_client_id',
            __( 'Google Client ID', 'babel-directory' ),
            array( $this, 'render_google_client_id_field' ),
            'bd-settings',
            'babel_google_section'
        );

        add_settings_section(
            'babel_microsoft_section',
            __( 'Integración con Microsoft (Hotmail/Outlook)', 'babel-directory' ),
            array( $this, 'render_microsoft_section_description' ),
            'bd-settings'
        );

        add_settings_field(
            'babel_microsoft_client_id',
            __( 'Microsoft Client ID', 'babel-directory' ),
            array( $this, 'render_microsoft_client_id_field' ),
            'bd-settings',
            'babel_microsoft_section'
        );

        // reCAPTCHA v3 Section
        add_settings_section(
            'babel_recaptcha_section',
            __( 'Google reCAPTCHA v3 (Protección Anti-Spam)', 'babel-directory' ),
            array( $this, 'render_recaptcha_section_description' ),
            'bd-settings'
        );

        add_settings_field(
            'babel_recaptcha_site_key',
            __( 'reCAPTCHA Site Key', 'babel-directory' ),
            array( $this, 'render_recaptcha_site_key_field' ),
            'bd-settings',
            'babel_recaptcha_section'
        );

        add_settings_field(
            'babel_recaptcha_secret_key',
            __( 'reCAPTCHA Secret Key', 'babel-directory' ),
            array( $this, 'render_recaptcha_secret_key_field' ),
            'bd-settings',
            'babel_recaptcha_section'
        );

        register_setting(
            'babel_directory_settings_group',
            'babel_recaptcha_site_key',
            array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            )
        );

        register_setting(
            'babel_directory_settings_group',
            'babel_recaptcha_secret_key',
            array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            )
        );
    }

    public function render_google_section_description() {
        echo '<p class="sdc-text-muted" style="margin-bottom:12px;">';
        esc_html_e( 'Configura tu Google OAuth 2.0 Client ID para habilitar el login con Google en el formulario de publicación.', 'babel-directory' );
        echo ' <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener" style="color:var(--sdc-blue);text-decoration:none;">';
        esc_html_e( 'Obtener Client ID &rarr;', 'babel-directory' );
        echo '</a></p>';
    }

    public function render_google_client_id_field() {
        $value = get_option( 'babel_google_client_id', '' );
        echo '<input type="text" name="babel_google_client_id" id="babel_google_client_id" value="' . esc_attr( $value ) . '" class="sdc-input" placeholder="123456789-abc...apps.googleusercontent.com" />';
    }

    public function render_microsoft_section_description() {
        echo '<p class="sdc-text-muted" style="margin-bottom:12px;">';
        esc_html_e( 'Configura tu Microsoft Client ID (Id de Aplicación) para habilitar el login con Hotmail/Outlook/Live.', 'babel-directory' );
        echo '</p>';
    }

    public function render_microsoft_client_id_field() {
        $value = get_option( 'babel_microsoft_client_id', '' );
        echo '<input type="text" name="babel_microsoft_client_id" id="babel_microsoft_client_id" value="' . esc_attr( $value ) . '" class="sdc-input" placeholder="11111111-2222-3333-4444-555555555555" />';
    }

    public function render_recaptcha_section_description() {
        echo '<p class="sdc-text-muted" style="margin-bottom:12px;">';
        esc_html_e( 'Configura tus claves de Google reCAPTCHA v3 para proteger los formularios de envíos de SPAM de forma invisible.', 'babel-directory' );
        echo '</p>';
    }

    public function render_recaptcha_site_key_field() {
        $value = get_option( 'babel_recaptcha_site_key', '' );
        echo '<input type="text" name="babel_recaptcha_site_key" id="babel_recaptcha_site_key" value="' . esc_attr( $value ) . '" class="sdc-input" placeholder="6Lc..." />';
    }

    public function render_recaptcha_secret_key_field() {
        $value = get_option( 'babel_recaptcha_secret_key', '' );
        echo '<input type="password" name="babel_recaptcha_secret_key" id="babel_recaptcha_secret_key" value="' . esc_attr( $value ) . '" class="sdc-input" placeholder="6Lc..." />';
    }

    /**
     * Guardado de configuración vía AJAX para la SPA.
     */
    public function ajax_save_settings() {
        if ( ! check_ajax_referer( 'babel_admin_nonce', 'nonce', false ) ) {
            wp_send_json_error( 'Nonce inválido.' );
            return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permisos insuficientes.' );
            return;
        }
        
        if ( isset( $_POST['babel_google_client_id'] ) ) {
            update_option( 'babel_google_client_id', sanitize_text_field( wp_unslash( $_POST['babel_google_client_id'] ) ) );
        }
        if ( isset( $_POST['babel_microsoft_client_id'] ) ) {
            update_option( 'babel_microsoft_client_id', sanitize_text_field( wp_unslash( $_POST['babel_microsoft_client_id'] ) ) );
        }
        if ( isset( $_POST['babel_recaptcha_site_key'] ) ) {
            update_option( 'babel_recaptcha_site_key', sanitize_text_field( wp_unslash( $_POST['babel_recaptcha_site_key'] ) ) );
        }
        if ( isset( $_POST['babel_recaptcha_secret_key'] ) ) {
            update_option( 'babel_recaptcha_secret_key', sanitize_text_field( wp_unslash( $_POST['babel_recaptcha_secret_key'] ) ) );
        }
        
        wp_send_json_success( 'Guardado correctamente.' );
    }

    /**
     * Guarda los datos del Editor de Negocios de la SPA
     */
    public function ajax_save_business() {
        if ( ! check_ajax_referer( 'babel_admin_nonce', 'nonce', false ) ) {
            wp_send_json_error( 'Nonce invalido' );
        }
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( 'Permisos insuficientes.' );
        }

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        $title   = isset( $_POST['biz_name'] ) ? sanitize_text_field( wp_unslash( $_POST['biz_name'] ) ) : 'Nuevo Negocio';
        $content = isset( $_POST['biz_desc'] ) ? sanitize_textarea_field( wp_unslash( $_POST['biz_desc'] ) ) : '';

        if ( $post_id ) {
            wp_update_post( array(
                'ID'           => $post_id,
                'post_title'   => $title,
                'post_content' => $content,
            ) );
        } else {
            $post_id = wp_insert_post( array(
                'post_type'    => 'babel_business',
                'post_title'   => $title,
                'post_content' => $content,
                'post_status'  => 'publish', // Autopublicar desde la SPA del admin
            ) );
        }

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( 'Error al crear el negocio.' );
        }

        // Mapeo de campos de la UI a los Meta Keys del post
        $meta_fields = array(
            'biz_logo_id'      => array( '_bd_logo_id', '_thumbnail_id' ),
            'biz_whatsapp'     => array( '_babel_whatsapp', '_bd_whatsapp' ),
            'biz_phone'        => array( '_babel_phone', '_bd_telefono' ),
            'biz_email'        => array( '_babel_email', '_bd_email' ),
            'biz_website'      => array( '_babel_website', '_bd_sitio_web', '_bd_web' ),
            'biz_instagram'    => array( '_babel_instagram' ),
            'biz_facebook'     => array( '_babel_facebook' ),
            'biz_tiktok'       => array( '_babel_tiktok' ),
            'biz_linkedin'     => array( '_babel_linkedin' ),
            'biz_rut'          => array( '_babel_rut', '_bd_rut' ),
            'biz_razon_social' => array( '_babel_razon_social', '_bd_razon_social' ),
            'biz_giro'         => array( '_babel_giro', '_bd_giro' ),
            'biz_rep_legal'    => array( '_babel_rep_legal', '_bd_rep_legal' ),
            'biz_gallery'      => array( '_babel_gallery', '_bd_galeria' ),
            'biz_address'      => array( '_babel_address', '_bd_direccion' ),
            'biz_lat'          => array( '_babel_lat', '_babel_latitude', '_bd_latitud' ),
            'biz_lng'          => array( '_babel_lng', '_babel_longitude', '_bd_longitud' ),
            'biz_wifi'         => array( '_babel_wifi', '_bd_wifi' ),
            'biz_parking'      => array( '_babel_parking', '_bd_estacionamiento' ),
            'biz_pet_friendly' => array( '_babel_pet_friendly' ),
            'biz_delivery'     => array( '_babel_delivery' )
        );

        foreach ( $meta_fields as $post_key => $meta_keys ) {
            if ( isset( $_POST[ $post_key ] ) ) {
                $val = wp_unslash( $_POST[ $post_key ] );
                
                // Sanitización básica
                if ( $post_key === 'biz_website' ) {
                    $val = esc_url_raw( $val );
                } elseif ( in_array( $post_key, array('biz_instagram', 'biz_facebook', 'biz_tiktok', 'biz_linkedin') ) ) {
                    $val = sanitize_text_field( $val );
                    // Remove '@' or full url if user pasted it
                    $val = str_replace( array('@', 'https://instagram.com/', 'https://www.instagram.com/', 'https://facebook.com/', 'https://www.facebook.com/', 'https://tiktok.com/', 'https://www.tiktok.com/@', 'https://www.tiktok.com/', 'https://linkedin.com/in/', 'https://www.linkedin.com/in/'), '', $val );
                    $val = trim( $val, '/' );
                } elseif ( $post_key === 'biz_email' ) {
                    $val = sanitize_email( $val );
                } else {
                    $val = sanitize_text_field( $val );
                }

                foreach ( $meta_keys as $meta_key ) {
                    update_post_meta( $post_id, $meta_key, $val );
                }
            }
        }

        // Guardar Categorías
        if ( isset( $_POST['biz_categories'] ) && is_array( $_POST['biz_categories'] ) ) {
            $categories_raw = wp_unslash( $_POST['biz_categories'] );
            $term_ids_or_names = array();
            foreach ( $categories_raw as $cat ) {
                if ( is_numeric( $cat ) ) {
                    $term_ids_or_names[] = intval( $cat );
                } else {
                    $term_ids_or_names[] = sanitize_text_field( $cat );
                }
            }
            wp_set_object_terms( $post_id, $term_ids_or_names, 'babel_category' );
        } else {
            // Si viene vacío, limpiar las categorías
            wp_set_object_terms( $post_id, array(), 'babel_category' );
        }

        // Guardar Horarios Dinámicos
        $final_hours = array();
        $days_of_week = array( 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo' );
        // Pre-llenar todo en "cerrado" por defecto
        foreach ( $days_of_week as $d ) {
            $final_hours[ $d ] = array( 'open' => '', 'close' => '', 'closed' => '1' );
        }

        if ( isset( $_POST['biz_hours_dynamic'] ) && is_array( $_POST['biz_hours_dynamic'] ) ) {
            $dynamic_blocks = wp_unslash( $_POST['biz_hours_dynamic'] );
            foreach ( $dynamic_blocks as $block ) {
                if ( isset( $block['days'] ) && is_array( $block['days'] ) ) {
                    $open_time  = isset( $block['open'] ) ? sanitize_text_field( $block['open'] ) : '';
                    $close_time = isset( $block['close'] ) ? sanitize_text_field( $block['close'] ) : '';
                    foreach ( $block['days'] as $d ) {
                        $d = sanitize_text_field( $d );
                        if ( array_key_exists( $d, $final_hours ) ) {
                            $final_hours[ $d ]['open']   = $open_time;
                            $final_hours[ $d ]['close']  = $close_time;
                            $final_hours[ $d ]['closed'] = ''; // Abierto
                        }
                    }
                }
            }
        }
        update_post_meta( $post_id, '_bd_horarios', $final_hours );
        update_post_meta( $post_id, '_babel_hours', $final_hours );
        // Disparar un hook personalizado para que otros módulos (como el motor de búsqueda)
        // sepan que el negocio y TODOS sus metadatos ya están listos en la BD.
        do_action( 'bd_after_business_saved', $post_id );

        wp_send_json_success( array( 'post_id' => $post_id ) );
    }

    /**
     * Acciones rápidas (Suspender, Borrar) para el dashboard
     */
    public function ajax_quick_action() {
        if ( ! check_ajax_referer( 'sdc_quick_action_nonce', 'nonce', false ) ) {
            wp_send_json_error( 'Seguridad: nonce inválido.' );
            return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permisos insuficientes.' );
            return;
        }
        $q_action = isset( $_POST['q_action'] ) ? sanitize_text_field( $_POST['q_action'] ) : '';
        $post_ids = isset( $_POST['post_ids'] ) ? $_POST['post_ids'] : '';

        if ( ! $post_ids || ! $q_action ) {
            wp_send_json_error( 'Faltan parámetros.' );
        }

        if ( ! is_array( $post_ids ) ) {
            $post_ids = array( intval( $post_ids ) );
        }

        $count = 0;
        foreach ( $post_ids as $pid ) {
            $pid = intval( $pid );
            if ( ! $pid ) continue;

            if ( $q_action === 'trash' ) {
                wp_trash_post( $pid );
                $count++;
            } elseif ( $q_action === 'suspend' ) {
                wp_update_post( array( 'ID' => $pid, 'post_status' => 'draft' ) );
                $count++;
            }
        }

        if ( $count > 0 ) {
            wp_send_json_success( "Acción aplicada a $count negocio(s)." );
        }

        wp_send_json_error( 'Acción desconocida o no se aplicó a ningún negocio.' );
    }

    /**
     * Endpoint AJAX para buscar y paginar negocios
     */
    public function ajax_fetch_businesses() {
        if ( ! check_ajax_referer( 'babel_admin_nonce', 'nonce', false ) ) {
            wp_send_json_error( 'Nonce invalido' );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permisos insuficientes.' );
            return;
        }

        $paged  = isset( $_POST['paged'] ) ? absint( $_POST['paged'] ) : 1;
        $search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';

        $args = array(
            'post_type'      => 'babel_business',
            'post_status'    => array( 'publish', 'pending', 'draft' ),
            'posts_per_page' => 10,
            'paged'          => $paged,
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        if ( ! empty( $search ) ) {
            $args['s'] = $search;
        }

        $query = new \WP_Query( $args );

        $rows_html = $this->render_table_rows_html( $query );
        $pagination_html = $this->render_pagination_html( $query, $paged );

        wp_send_json_success( array(
            'rows'        => $rows_html,
            'pagination'  => $pagination_html,
            'found_posts' => $query->found_posts,
            'max_pages'   => $query->max_num_pages,
        ) );
    }

    /**
     * Renderiza las filas de la tabla de negocios.
     *
     * @param \WP_Query $query Consulta WP_Query.
     * @return string HTML de las filas de la tabla.
     */
    public function render_table_rows_html( $query ) {
        ob_start();
        if ( $query->have_posts() ) :
            while ( $query->have_posts() ) : $query->the_post();
                $post_status = get_post_status();
                if ( $post_status === 'publish' ) {
                    $status_label  = 'Publicado';
                    $badge_bg      = '#dcfce7';
                    $badge_color   = '#166534';
                } elseif ( $post_status === 'pending' ) {
                    $status_label  = 'Pendiente';
                    $badge_bg      = '#fef08a';
                    $badge_color   = '#854d0e';
                } else {
                    $status_label  = 'Borrador';
                    $badge_bg      = '#f1f5f9';
                    $badge_color   = '#64748b';
                }
                $edit_link = admin_url( 'admin.php?page=bd-panel&edit_id=' . get_the_ID() );
                ?>
                <tr>
                    <td><strong><?php echo esc_html( get_the_title() ); ?></strong></td>
                    <td>
                        <span style="background:<?php echo esc_attr( $badge_bg ); ?>; color:<?php echo esc_attr( $badge_color ); ?>; padding:4px 8px; border-radius:12px; font-size:12px; font-weight:600;">
                            <?php echo esc_html( $status_label ); ?>
                        </span>
                    </td>
                    <td style="display: flex; align-items: center;">
                        <input type="checkbox" class="sdc-bulk-select-item" value="<?php echo esc_attr( get_the_ID() ); ?>" style="margin-right:8px;">
                        <a href="<?php echo esc_url( $edit_link ); ?>" class="sdc-btn" style="text-decoration:none; padding:4px 12px; font-size:12px; background:#3b82f6; color:#ffffff;">Editar (SPA)</a>
                        <button type="button" class="sdc-btn sdc-quick-action-btn" data-action="suspend" data-postid="<?php echo esc_attr( get_the_ID() ); ?>" style="padding:4px 12px; font-size:12px; background:#f59e0b; color:#ffffff; margin-left:8px; border:none; cursor:pointer;" title="Pausar negocio (Borrador)">Suspender</button>
                        <button type="button" class="sdc-btn sdc-quick-action-btn" data-action="trash" data-postid="<?php echo esc_attr( get_the_ID() ); ?>" style="padding:4px 12px; font-size:12px; background:#ef4444; color:#ffffff; margin-left:8px; border:none; cursor:pointer;" title="Eliminar negocio">Borrar</button>
                        <a href="<?php echo esc_url( get_permalink() ); ?>" target="_blank" class="sdc-btn" style="text-decoration:none; padding:4px 12px; font-size:12px; background:#f1f5f9; color:#475569; margin-left:8px;">Ver Pág.</a>
                    </td>
                </tr>
            <?php endwhile; wp_reset_postdata(); ?>
        <?php else : ?>
            <tr>
                <td colspan="3" style="text-align:center; padding: 24px; color: var(--sdc-text-muted);">No se encontraron negocios.</td>
            </tr>
        <?php endif;
        return ob_get_clean();
    }

    /**
     * Genera el HTML para los controles de paginación.
     *
     * @param \WP_Query $query Consulta WP_Query.
     * @param int       $paged Página actual.
     * @return string HTML de la paginación.
     */
    public function render_pagination_html( $query, $paged ) {
        $max_pages = $query->max_num_pages;
        if ( $max_pages <= 1 ) {
            return '';
        }

        $html = '<div class="sdc-pagination">';

        // Botón Anterior
        if ( $paged > 1 ) {
            $html .= sprintf( '<button class="sdc-pagination-btn" data-page="%d">&larr; Ant.</button>', $paged - 1 );
        } else {
            $html .= '<button class="sdc-pagination-btn" disabled>&larr; Ant.</button>';
        }

        // Números de Página
        for ( $i = 1; $i <= $max_pages; $i++ ) {
            if ( $i === $paged ) {
                $html .= sprintf( '<span class="sdc-pagination-btn active">%d</span>', $i );
            } else {
                // Mostrar primer/último y páginas cercanas a la actual si hay demasiadas
                if ( $max_pages > 8 ) {
                    if ( $i === 1 || $i === $max_pages || abs( $i - $paged ) <= 2 ) {
                        $html .= sprintf( '<button class="sdc-pagination-btn" data-page="%d">%d</button>', $i, $i );
                    } elseif ( $i === 2 || $i === $max_pages - 1 ) {
                        $html .= '<span class="sdc-pagination-ellipsis">...</span>';
                    }
                } else {
                    $html .= sprintf( '<button class="sdc-pagination-btn" data-page="%d">%d</button>', $i, $i );
                }
            }
        }

        // Botón Siguiente
        if ( $paged < $max_pages ) {
            $html .= sprintf( '<button class="sdc-pagination-btn" data-page="%d">Sig. &rarr;</button>', $paged + 1 );
        } else {
            $html .= '<button class="sdc-pagination-btn" disabled>Sig. &rarr;</button>';
        }

        $html .= '</div>';

        // Evitar elipses dobles seguidas en el renderizado
        $html = str_replace( '<span class="sdc-pagination-ellipsis">...</span><span class="sdc-pagination-ellipsis">...</span>', '<span class="sdc-pagination-ellipsis">...</span>', $html );

        return $html;
    }



    /**
     * Registra la página de administración única de la SPA.
     */
    public function add_admin_menu() {
        // Menú principal único
        add_menu_page(
            __( 'Soy de Chile', 'babel-directory' ),
            __( 'Soy de Chile', 'babel-directory' ),
            'manage_options',
            'bd-panel',
            array( $this, 'render_spa_page' ),
            'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#ffffff"><path d="M12 2L2 22h20L12 2z"/></svg>'), // Placeholder flag icon
            3
        );
        
        // Removemos los submenús redundantes (el CPT 'babel_business' sigue registrado y accesible por URL directa si hiciera falta, pero priorizamos la SPA).
    }

    /**
     * Renderiza el contenedor principal y estructura HTML de la SPA.
     */
    public function render_spa_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Obtención de métricas rápidas
        $posts_count = wp_count_posts( 'babel_business' );
        $total_published = isset( $posts_count->publish ) ? intval( $posts_count->publish ) : 0;
        $total_pending = isset( $posts_count->pending ) ? intval( $posts_count->pending ) : 0;

        $edit_id = isset( $_GET['edit_id'] ) ? intval( $_GET['edit_id'] ) : 0;
        $tab_dashboard_active = $edit_id === 0 ? 'active' : '';
        $tab_editor_active = $edit_id > 0 ? 'active' : '';
        ?>
        <div id="babel-admin-app">
            
            <!-- Header SPA -->
            <header class="sdc-header">
                <div class="sdc-header-logo">
                    <!-- Bandera simplificada por CSS/Emoji o logo -->
                    🇨🇱 Soy de Chile
                    <span class="sdc-header-title">Admin Dashboard</span>
                </div>
                <div class="sdc-header-actions">
                    <span class="sdc-text-muted" style="color:rgba(255,255,255,0.7); font-size: 13px;">v8.0.0</span>
                </div>
            </header>

            <!-- Menú de Navegación por Pestañas -->
            <nav class="sdc-tabs-nav">
                <button class="sdc-tab-btn <?php echo $tab_dashboard_active; ?>" data-target="sdc-tab-dashboard">
                    <span class="dashicons dashicons-dashboard"></span> Dashboard
                </button>
                <button class="sdc-tab-btn <?php echo $tab_editor_active; ?>" data-target="sdc-tab-negocios">
                    <span class="dashicons dashicons-store"></span> Editor de Negocios
                </button>
                <button class="sdc-tab-btn" data-target="sdc-tab-configuracion">
                    <span class="dashicons dashicons-admin-settings"></span> Configuración
                </button>
                <button class="sdc-tab-btn" data-target="sdc-tab-guia">
                    <span class="dashicons dashicons-editor-code"></span> Guía de Shortcodes
                </button>
            </nav>

            <!-- 1. Contenido: Dashboard -->
            <div id="sdc-tab-dashboard" class="sdc-tab-content <?php echo $tab_dashboard_active; ?>">
                <h2 style="margin-top:0;">Dashboard</h2>
                
                <div class="sdc-grid">
                    <div class="sdc-card sdc-card-primary">
                        <div class="sdc-metric-title">Negocios Totales (Publicados)</div>
                        <div class="sdc-metric-value"><?php echo esc_html( $total_published ); ?></div>
                    </div>
                    <div class="sdc-card sdc-card-danger">
                        <div class="sdc-metric-title">Aprobaciones Pendientes</div>
                        <div class="sdc-metric-value"><?php echo esc_html( $total_pending ); ?></div>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; margin-top: 24px; flex-wrap: wrap; gap: 12px;">
                    <h3 style="margin:0;">Negocios Registrados</h3>
                    
                    <div class="sdc-search-wrapper">
                        <span class="dashicons dashicons-search sdc-search-icon"></span>
                        <input type="text" id="sdc-search-input" class="sdc-search-input" placeholder="Buscar negocio por nombre..." autocomplete="off">
                        <span class="dashicons dashicons-update sdc-search-spinner" id="sdc-search-spinner"></span>
                    </div>

                    <div style="display: flex; gap: 8px;">
                        <button type="button" id="sdc-bulk-suspend-btn" class="sdc-btn" style="background:#f59e0b; color:#fff; border:none; padding:6px 16px; border-radius:6px; cursor:pointer;">Suspender Seleccionados</button>
                        <button type="button" id="sdc-bulk-trash-btn" class="sdc-btn" style="background:#ef4444; color:#fff; border:none; padding:6px 16px; border-radius:6px; cursor:pointer;">Borrar Seleccionados</button>
                    </div>
                </div>
                <div class="sdc-table-wrapper">
                    <table class="sdc-table">
                        <thead>
                            <tr>
                                <th>Nombre del Negocio</th>
                                <th>Estado</th>
                                <th>
                                    <input type="checkbox" id="sdc-bulk-select-all" style="margin-right:8px;" title="Seleccionar todos">
                                    Acciones Rápidas
                                </th>
                            </tr>
                        </thead>
                        <tbody id="sdc-table-body">
                            <?php
                            $recent_businesses = new \WP_Query( array(
                                'post_type'      => 'babel_business',
                                'post_status'    => array( 'publish', 'pending', 'draft' ),
                                'posts_per_page' => 10,
                                'orderby'        => 'date',
                                'order'          => 'DESC'
                            ) );
                            echo $this->render_table_rows_html( $recent_businesses );
                            ?>
                        </tbody>
                    </table>
                </div>
                <div id="sdc-pagination-container" class="sdc-pagination-container">
                    <?php echo $this->render_pagination_html( $recent_businesses, 1 ); ?>
                </div>
            </div>

            <!-- 2. Contenido: Editor de Negocios (Contenedor para la inyección de Metaboxes) -->
            <div id="sdc-tab-negocios" class="sdc-tab-content <?php echo $tab_editor_active; ?>">
                <h2 style="margin-top:0;">Editor Inmersivo de Negocios</h2>
                <div class="sdc-card">
                    <?php 
                    // Aquí se invoca la nueva clase de Metaboxes refactorizada 
                    // para renderizar el formulario dentro de la SPA.
                    \Babel\Directory\Metaboxes::render_spa_editor( $edit_id ); 
                    ?>
                </div>
            </div>

            <!-- 3. Contenido: Configuración -->
            <div id="sdc-tab-configuracion" class="sdc-tab-content">
                <h2 style="margin-top:0;">Configuración de Soy de Chile</h2>
                <div class="sdc-card">
                    <form id="sdc-settings-form" action="" onsubmit="return false;">
                        <?php
                        // Campo nonce para AJAX
                        wp_nonce_field( 'babel_admin_nonce', 'nonce' );
                        // Renderiza las secciones y campos
                        do_settings_sections( 'bd-settings' );
                        ?>
                        <div style="margin-top: 24px;">
                            <button type="button" id="sdc-save-settings-btn" class="sdc-btn sdc-btn-primary">Guardar Configuración</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 4. Contenido: Guía de Shortcodes -->
            <div id="sdc-tab-guia" class="sdc-tab-content">
                <div style="background: linear-gradient(135deg, #0039A6 0%, #00256C 100%); color: #ffffff; padding: 24px; border-radius: 12px; margin-bottom: 24px; box-shadow: 0 4px 15px rgba(0, 57, 166, 0.2);">
                    <h2 style="margin:0 0 8px 0; color: #ffffff; font-size: 24px; font-weight: 700; display: flex; align-items: center; gap: 10px;">
                        <span class="dashicons dashicons-editor-code" style="font-size: 28px; width: 28px; height: 28px; line-height: 28px;"></span> 
                        Guía Completa de Shortcodes
                    </h2>
                    <p style="margin: 0; font-size: 14px; opacity: 0.9; line-height: 1.6;">
                        El motor de **Babel Directory** ofrece un catálogo completo de códigos cortos (Shortcodes). Utilízalos en las páginas de tu sitio o dentro de constructores visuales (como **Divi 5** o **Gutenberg**) para incrustar portales, buscadores o bloques de diseño dinámicos.
                    </p>
                </div>

                <div class="sdc-card" style="padding: 24px;">
                    <!-- Sub-pestañas Interactivas -->
                    <div class="sdc-subtabs-nav" style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 24px; border-bottom: 2px solid var(--sdc-border); padding-bottom: 12px;">
                        <button type="button" class="sdc-subtab-btn active" data-subtarget="subtab-formularios" style="display: flex; align-items: center; gap: 8px; background: #fff; border: 1px solid #ddd; padding: 10px 16px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; transition: all 0.2s;">
                            <span class="dashicons dashicons-edit" style="font-size:16px; width:16px; height:16px;"></span> Formularios e Interacción
                        </button>
                        <button type="button" class="sdc-subtab-btn" data-subtarget="subtab-buscadores" style="display: flex; align-items: center; gap: 8px; background: #fff; border: 1px solid #ddd; padding: 10px 16px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; transition: all 0.2s;">
                            <span class="dashicons dashicons-search" style="font-size:16px; width:16px; height:16px;"></span> Buscadores y Filtros
                        </button>
                        <button type="button" class="sdc-subtab-btn" data-subtarget="subtab-vistas" style="display: flex; align-items: center; gap: 8px; background: #fff; border: 1px solid #ddd; padding: 10px 16px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; transition: all 0.2s;">
                            <span class="dashicons dashicons-layout" style="font-size:16px; width:16px; height:16px;"></span> Perfiles y Listados
                        </button>
                        <button type="button" class="sdc-subtab-btn" data-subtarget="subtab-seo" style="display: flex; align-items: center; gap: 8px; background: #fff; border: 1px solid #ddd; padding: 10px 16px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; transition: all 0.2s;">
                            <span class="dashicons dashicons-admin-links" style="font-size:16px; width:16px; height:16px;"></span> Portada y Enlaces (SEO)
                        </button>
                        <button type="button" class="sdc-subtab-btn" data-subtarget="subtab-micro" style="display: flex; align-items: center; gap: 8px; background: #fff; border: 1px solid #ddd; padding: 10px 16px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; transition: all 0.2s;">
                            <span class="dashicons dashicons-welcome-widgets-menus" style="font-size:16px; width:16px; height:16px;"></span> Micro-Shortcodes LTM
                        </button>
                    </div>

                    <!-- 4.1 Formularios -->
                    <div id="subtab-formularios" class="sdc-subtab-content">
                        <div style="display: flex; flex-direction: column; gap: 20px;">
                            
                            <!-- babel_submission_form -->
                            <div style="background: #F9FAFB; border: 1px solid var(--sdc-border); border-radius: 8px; padding: 20px;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 12px;">
                                    <h3 style="margin:0; font-size:18px; color:var(--sdc-blue);">Formulario de Registro y Publicación</h3>
                                    <div style="display:flex; gap:8px;">
                                        <code style="padding: 6px 12px; background: #E0E7FF; color: var(--sdc-blue); border-radius: 6px; font-weight: bold; font-size: 13px;">[babel_submission_form]</code>
                                        <button type="button" id="copy-sub" onclick="copyShortcode('[babel_submission_form]', 'copy-sub')" style="background: #fff; border: 1px solid #ddd; padding: 4px 8px; border-radius: 6px; font-size: 11px; cursor: pointer; font-weight: 600;">📋 Copiar</button>
                                    </div>
                                </div>
                                <p style="margin: 0 0 12px 0; font-size:13.5px; color:var(--sdc-text-muted); line-height: 1.5;">
                                    Dibuja el portal de autogestión y registro para que nuevos comerciantes den de alta sus locales. Incluye soporte para iniciar sesión con Google (Identity Services), carga interactiva de múltiples imágenes, mapa interactivo para marcar la dirección y selector de horarios comerciales. Los comercios nuevos quedan guardados como "Pendientes de aprobación" para la moderación del administrador.
                                </p>
                                <div style="background: #FFFBEB; border-left: 4px solid #F59E0B; padding: 10px 14px; border-radius: 0 6px 6px 0; font-size: 12px; color: #78350F; font-weight: 500;">
                                    <strong>Nota de Configuración:</strong> Requiere que configures tu **Google Client ID** en la pestaña de Configuración para habilitar el inicio de sesión OAuth2.
                                </div>
                            </div>

                            <!-- babel_claim_business -->
                            <div style="background: #F9FAFB; border: 1px solid var(--sdc-border); border-radius: 8px; padding: 20px;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 12px;">
                                    <h3 style="margin:0; font-size:18px; color:var(--sdc-blue);">Botón de Reclamar Negocio</h3>
                                    <div style="display:flex; gap:8px;">
                                        <code style="padding: 6px 12px; background: #E0E7FF; color: var(--sdc-blue); border-radius: 6px; font-weight: bold; font-size: 13px;">[babel_claim_business]</code>
                                        <button type="button" id="copy-claim" onclick="copyShortcode('[babel_claim_business]', 'copy-claim')" style="background: #fff; border: 1px solid #ddd; padding: 4px 8px; border-radius: 6px; font-size: 11px; cursor: pointer; font-weight: 600;">📋 Copiar</button>
                                    </div>
                                </div>
                                <p style="margin: 0; font-size:13.5px; color:var(--sdc-text-muted); line-height: 1.5;">
                                    Inyecta un botón y formulario interactivo para que los verdaderos dueños de un comercio soliciten la propiedad del mismo. Si no están conectados, les pide iniciar sesión. Al enviar la solicitud, el administrador recibe un aviso por correo y el local queda marcado como "Reclamo pendiente". Una vez que el administrador asigna el post al usuario correspondiente, este pasa a ser el autor y dueño oficial.
                                </p>
                            </div>

                            <!-- bd_user_dashboard -->
                            <div style="background: #F9FAFB; border: 1px solid var(--sdc-border); border-radius: 8px; padding: 20px;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 12px;">
                                    <h3 style="margin:0; font-size:18px; color:var(--sdc-blue);">Panel de Control de Usuario</h3>
                                    <div style="display:flex; gap:8px;">
                                        <code style="padding: 6px 12px; background: #E0E7FF; color: var(--sdc-blue); border-radius: 6px; font-weight: bold; font-size: 13px;">[bd_user_dashboard]</code>
                                        <button type="button" id="copy-dash" onclick="copyShortcode('[bd_user_dashboard]', 'copy-dash')" style="background: #fff; border: 1px solid #ddd; padding: 4px 8px; border-radius: 6px; font-size: 11px; cursor: pointer; font-weight: 600;">📋 Copiar</button>
                                    </div>
                                </div>
                                <p style="margin: 0; font-size:13.5px; color:var(--sdc-text-muted); line-height: 1.5;">
                                    Renderiza el panel privado de cara al comercio. Permite a los usuarios registrados auditar el estado de sus publicaciones (Aprobadas / Pendientes), visualizar el número total de visitas registradas en sus perfiles individuales y actualizar de forma instantánea y segura sus teléfonos de WhatsApp sin necesidad de acceder al panel de WordPress de administración estándar.
                                </p>
                            </div>

                            <!-- babel_auth_menu -->
                            <div style="background: #F9FAFB; border: 1px solid var(--sdc-border); border-radius: 8px; padding: 20px;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 12px;">
                                    <h3 style="margin:0; font-size:18px; color:var(--sdc-blue);">Menú de Login Global (Estilo Trivago)</h3>
                                    <div style="display:flex; gap:8px;">
                                        <code style="padding: 6px 12px; background: #E0E7FF; color: var(--sdc-blue); border-radius: 6px; font-weight: bold; font-size: 13px;">[babel_auth_menu]</code>
                                        <button type="button" id="copy-auth-menu" onclick="copyShortcode('[babel_auth_menu]', 'copy-auth-menu')" style="background: #fff; border: 1px solid #ddd; padding: 4px 8px; border-radius: 6px; font-size: 11px; cursor: pointer; font-weight: 600;">📋 Copiar</button>
                                    </div>
                                </div>
                                <p style="margin: 0; font-size:13.5px; color:var(--sdc-text-muted); line-height: 1.5;">
                                    Dibuja el botón dinámico de "Iniciar sesión" con Google para el Encabezado Global de Divi. Si el visitante no está logueado, muestra el botón y abre un Modal emergente en cualquier página. Si ya está logueado, muestra automáticamente su Avatar de Google y un menú desplegable moderno para ir a "Mi Panel" o "Cerrar sesión".
                                </p>
                            </div>

                        </div>
                    </div>

                    <!-- 4.2 Buscadores -->
                    <div id="subtab-buscadores" class="sdc-subtab-content" style="display:none;">
                        <div style="display: flex; flex-direction: column; gap: 20px;">
                            
                            <!-- bd_filter_bar -->
                            <div style="background: #F9FAFB; border: 1px solid var(--sdc-border); border-radius: 8px; padding: 20px;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 12px;">
                                    <h3 style="margin:0; font-size:18px; color:var(--sdc-blue);">Buscador Universal AJAX (Recomendado)</h3>
                                    <div style="display:flex; gap:8px;">
                                        <code style="padding: 6px 12px; background: #E0E7FF; color: var(--sdc-blue); border-radius: 6px; font-weight: bold; font-size: 13px;">[bd_filter_bar]</code>
                                        <button type="button" id="copy-bar" onclick="copyShortcode('[bd_filter_bar show_results=&quot;yes&quot;]', 'copy-bar')" style="background: #fff; border: 1px solid #ddd; padding: 4px 8px; border-radius: 6px; font-size: 11px; cursor: pointer; font-weight: 600;">📋 Copiar Ejemplo</button>
                                    </div>
                                </div>
                                <p style="margin: 0 0 16px 0; font-size:13.5px; color:var(--sdc-text-muted); line-height: 1.5;">
                                    Crea la barra de filtros inteligente integrada. Incluye campo de búsqueda por texto libre y el botón de radar GPS para buscar locales cercanos usando la ubicación del usuario de forma asíncrona. <em>(Nota: El selector de regiones ha sido removido de esta barra por diseño)</em>.
                                </p>
                                
                                <h4 style="margin:0 0 8px 0; font-size:13px; font-weight:600;">Parámetros soportados:</h4>
                                <div class="sdc-table-wrapper" style="margin-bottom: 16px;">
                                    <table class="sdc-table">
                                        <thead>
                                            <tr>
                                                <th style="width:20%;">Parámetro</th>
                                                <th style="width:20%;">Por defecto</th>
                                                <th>Descripción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><code>show_results</code></td>
                                                <td><code>"no"</code></td>
                                                <td>Si se define como <code>"yes"</code>, los resultados se cargarán dinámicamente con AJAX inmediatamente debajo de la barra sin recargar la página. Si es <code>"no"</code>, enviará la consulta a la página de búsqueda predeterminada del sitio.</td>
                                            </tr>
                                            <tr>
                                                <td><code>region</code></td>
                                                <td><code>""</code></td>
                                                <td>Slug de una región (ej: <code>valparaiso</code>) para que aparezca seleccionada por defecto en el menú desplegable.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div style="background: #F3F4F6; padding: 12px; border-radius: 6px;">
                                    <span style="font-size: 12px; font-weight:600; text-transform: uppercase; color: var(--sdc-text-muted); display: block; margin-bottom: 4px;">Uso recomendado:</span>
                                    <code style="font-size:12px; color: #B22217;">[bd_filter_bar show_results="yes"]</code>
                                </div>
                            </div>



                        </div>
                    </div>

                    <!-- 4.3 Vistas y Plantillas -->
                    <div id="subtab-vistas" class="sdc-subtab-content" style="display:none;">
                        <div style="display: flex; flex-direction: column; gap: 20px;">
                            
                            <!-- bd_business_profile -->
                            <div style="background: #F9FAFB; border: 1px solid var(--sdc-border); border-radius: 8px; padding: 20px;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 12px;">
                                    <h3 style="margin:0; font-size:18px; color:var(--sdc-blue);">Perfil Completo de Negocio</h3>
                                    <div style="display:flex; gap:8px;">
                                        <code style="padding: 6px 12px; background: #E0E7FF; color: var(--sdc-blue); border-radius: 6px; font-weight: bold; font-size: 13px;">[bd_business_profile]</code>
                                        <button type="button" id="copy-prof" onclick="copyShortcode('[bd_business_profile]', 'copy-prof')" style="background: #fff; border: 1px solid #ddd; padding: 4px 8px; border-radius: 6px; font-size: 11px; cursor: pointer; font-weight: 600;">📋 Copiar</button>
                                    </div>
                                </div>
                                <p style="margin: 0 0 16px 0; font-size:13.5px; color:var(--sdc-text-muted); line-height: 1.5;">
                                    Renderiza la plantilla premium completa del perfil comercial. Contiene la galería de fotos Bento, mapa interactivo Leaflet.js de OpenStreetMap, contacto, horarios, amenidades y opiniones. Estilizado bajo arquitectura LTM libre de conflictos visuales.
                                </p>
                                
                                <h4 style="margin:0 0 8px 0; font-size:13px; font-weight:600;">Parámetros soportados:</h4>
                                <div class="sdc-table-wrapper" style="margin-bottom:12px;">
                                    <table class="sdc-table">
                                        <thead>
                                            <tr>
                                                <th style="width:20%;">Parámetro</th>
                                                <th style="width:20%;">Por defecto</th>
                                                <th>Descripción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><code>id</code></td>
                                                <td><code>""</code> (Vacío)</td>
                                                <td>ID numérico del negocio. Si se omite, el plugin autodetecta inteligentemente el ID del post actual del loop (ideal para usar en plantillas de post único en Divi Theme Builder o Elementor).</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- bd_region_template -->
                            <div style="background: #F9FAFB; border: 1px solid var(--sdc-border); border-radius: 8px; padding: 20px;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 12px;">
                                    <h3 style="margin:0; font-size:18px; color:var(--sdc-blue);">Plantilla de Portada de Región</h3>
                                    <div style="display:flex; gap:8px;">
                                        <code style="padding: 6px 12px; background: #E0E7FF; color: var(--sdc-blue); border-radius: 6px; font-weight: bold; font-size: 13px;">[bd_region_template]</code>
                                        <button type="button" id="copy-reg-temp" onclick="copyShortcode('[bd_region_template region=&quot;auto&quot;]', 'copy-reg-temp')" style="background: #fff; border: 1px solid #ddd; padding: 4px 8px; border-radius: 6px; font-size: 11px; cursor: pointer; font-weight: 600;">📋 Copiar Ejemplo</button>
                                    </div>
                                </div>
                                <p style="margin: 0 0 16px 0; font-size:13.5px; color:var(--sdc-text-muted); line-height: 1.5;">
                                    Genera una hermosa estructura de destino para una región. Muestra un banner Hero con la foto de fondo cargada en la taxonomía de la región, un titular estilizado, un contador numérico de locales publicados y la barra de filtros unificada configurada por AJAX para listar los locales de esa región.
                                </p>
                                
                                <h4 style="margin:0 0 8px 0; font-size:13px; font-weight:600;">Parámetros soportados:</h4>
                                <div class="sdc-table-wrapper" style="margin-bottom:12px;">
                                    <table class="sdc-table">
                                        <thead>
                                            <tr>
                                                <th style="width:20%;">Parámetro</th>
                                                <th style="width:20%;">Por defecto</th>
                                                <th>Descripción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><code>region</code></td>
                                                <td><code>"auto"</code></td>
                                                <td>Slug de la región a mostrar (ej: <code>"coquimbo"</code>). Si se define como <code>"auto"</code>, autodetecta en qué página de taxonomía <code>babel_region</code> de WordPress se encuentra el visitante de manera dinámica.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- bd_breadcrumbs -->
                            <div style="background: #F9FAFB; border: 1px solid var(--sdc-border); border-radius: 8px; padding: 20px;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 12px;">
                                    <h3 style="margin:0; font-size:18px; color:var(--sdc-blue);">Migas de Pan (Breadcrumbs)</h3>
                                    <div style="display:flex; gap:8px;">
                                        <code style="padding: 6px 12px; background: #E0E7FF; color: var(--sdc-blue); border-radius: 6px; font-weight: bold; font-size: 13px;">[bd_breadcrumbs]</code>
                                        <button type="button" id="copy-bread" onclick="copyShortcode('[bd_breadcrumbs separator=&quot;/&quot;]', 'copy-bread')" style="background: #fff; border: 1px solid #ddd; padding: 4px 8px; border-radius: 6px; font-size: 11px; cursor: pointer; font-weight: 600;">📋 Copiar Ejemplo</button>
                                    </div>
                                </div>
                                <p style="margin: 0 0 16px 0; font-size:13.5px; color:var(--sdc-text-muted); line-height: 1.5;">
                                    Inyecta un sistema de navegación jerárquica automático. Detecta de forma inteligente si el usuario está viendo una Región, una Categoría o el perfil de un Negocio, y genera la ruta correspondiente (Ej. <i>Inicio / Región / Categoría / Negocio</i>).
                                </p>
                                
                                <h4 style="margin:0 0 8px 0; font-size:13px; font-weight:600;">Parámetros soportados:</h4>
                                <div class="sdc-table-wrapper" style="margin-bottom:12px;">
                                    <table class="sdc-table">
                                        <thead>
                                            <tr>
                                                <th style="width:20%;">Parámetro</th>
                                                <th style="width:20%;">Por defecto</th>
                                                <th>Descripción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><code>separator</code></td>
                                                <td><code>"/"</code></td>
                                                <td>El carácter o texto que separa cada nivel jerárquico.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- bd_archive_loop -->
                            <div style="background: #F9FAFB; border: 1px solid var(--sdc-border); border-radius: 8px; padding: 20px;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 12px;">
                                    <h3 style="margin:0; font-size:18px; color:var(--sdc-blue);">Grilla / Bucle de Directorio</h3>
                                    <div style="display:flex; gap:8px;">
                                        <code style="padding: 6px 12px; background: #E0E7FF; color: var(--sdc-blue); border-radius: 6px; font-weight: bold; font-size: 13px;">[bd_archive_loop]</code>
                                        <button type="button" id="copy-loop" onclick="copyShortcode('[bd_archive_loop]', 'copy-loop')" style="background: #fff; border: 1px solid #ddd; padding: 4px 8px; border-radius: 6px; font-size: 11px; cursor: pointer; font-weight: 600;">📋 Copiar</button>
                                    </div>
                                </div>
                                <p style="margin: 0; font-size:13.5px; color:var(--sdc-text-muted); line-height: 1.5;">
                                    Renderiza el listado estándar de tarjetas de negocios con paginación premium. 
                                </p>
                                <div style="margin-top: 12px; background: #FEF2F2; border-left: 4px solid #DC2626; padding: 10px 14px; border-radius: 0 6px 6px 0; font-size: 13px; color: #991B1B;">
                                    <strong>¡ADVERTENCIA CRÍTICA!</strong><br>
                                    Este shortcode debe usarse <strong>ÚNICAMENTE dentro de Plantillas de Archivo</strong> del Divi Theme Builder (ej. Todas las Categorías, Todas las Regiones). <br><strong>NUNCA</strong> lo coloques dentro del contenido de una Página normal (como `/buscar/`), ya que causará una recursión infinita en WordPress provocando un colapso del servidor (Error de Timeout).
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- 4.4 Portada y SEO -->
                    <div id="subtab-seo" class="sdc-subtab-content" style="display:none;">
                        <div style="display: flex; flex-direction: column; gap: 20px;">
                            
                            <!-- bd_popular_regions -->
                            <div style="background: #F9FAFB; border: 1px solid var(--sdc-border); border-radius: 8px; padding: 20px;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 12px;">
                                    <h3 style="margin:0; font-size:18px; color:var(--sdc-blue);">Grilla de Regiones Populares (Portada)</h3>
                                    <div style="display:flex; gap:8px;">
                                        <code style="padding: 6px 12px; background: #E0E7FF; color: var(--sdc-blue); border-radius: 6px; font-weight: bold; font-size: 13px;">[bd_popular_regions]</code>
                                        <button type="button" id="copy-pop-reg" onclick="copyShortcode('[bd_popular_regions columns=&quot;4&quot; rows=&quot;4&quot;]', 'copy-pop-reg')" style="background: #fff; border: 1px solid #ddd; padding: 4px 8px; border-radius: 6px; font-size: 11px; cursor: pointer; font-weight: 600;">📋 Copiar Ejemplo Grid</button>
                                        <button type="button" id="copy-pop-reg-car" onclick="copyShortcode('[bd_popular_regions layout=&quot;carousel&quot;]', 'copy-pop-reg-car')" style="background: #fff; border: 1px solid #ddd; padding: 4px 8px; border-radius: 6px; font-size: 11px; cursor: pointer; font-weight: 600;">📋 Copiar Carrusel</button>
                                        <button type="button" id="copy-pop-reg-car-count" onclick="copyShortcode('[bd_popular_regions layout=&quot;carousel&quot; orderby=&quot;count&quot;]', 'copy-pop-reg-car-count')" style="background: #fff; border: 1px solid #ddd; padding: 4px 8px; border-radius: 6px; font-size: 11px; cursor: pointer; font-weight: 600;">📋 Copiar Carrusel (Count)</button>
                                    </div>
                                </div>
                                <p style="margin: 0 0 16px 0; font-size:13.5px; color:var(--sdc-text-muted); line-height: 1.5;">
                                    Dibuja una grilla visual responsiva de las regiones de Chile para la portada. Cada celda tiene la foto de la región, su nombre y el número total de comercios dados de alta. Se ordenan geográficamente de norte a sur.
                                </p>
                                
                                <h4 style="margin:0 0 8px 0; font-size:13px; font-weight:600;">Parámetros soportados:</h4>
                                <div class="sdc-table-wrapper" style="margin-bottom:12px;">
                                    <table class="sdc-table">
                                        <thead>
                                            <tr>
                                                <th style="width:20%;">Parámetro</th>
                                                <th style="width:20%;">Por defecto</th>
                                                <th>Descripción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><code>columns</code></td>
                                                <td><code>4</code></td>
                                                <td>Cantidad de columnas de la cuadrícula en pantallas de escritorio (cuando el layout es "grid").</td>
                                            </tr>
                                            <tr>
                                                <td><code>rows</code></td>
                                                <td><code>4</code></td>
                                                <td>Filas a renderizar (Ej. 4x4 = 16 regiones máximas).</td>
                                            </tr>
                                            <tr>
                                                <td><code>layout</code></td>
                                                <td><code>"grid"</code></td>
                                                <td>Estilo visual: <code>"grid"</code> (cuadrícula estática) o <code>"carousel"</code> (fila de carrusel con flechas de navegación).</td>
                                            </tr>
                                            <tr>
                                                <td><code>orderby</code></td>
                                                <td><code>"geographic"</code></td>
                                                <td>Orden de los elementos: <code>"geographic"</code> (norte a sur) o <code>"count"</code> (regiones más buscadas/activas por cantidad de locales).</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <span style="font-size:11.5px; color:var(--sdc-text-muted);">* Nota: Tiene el alias <code>[babel_region_grid]</code> para compatibilidad.</span>
                            </div>

                            <!-- bd_popular_categories -->
                            <div style="background: #F9FAFB; border: 1px solid var(--sdc-border); border-radius: 8px; padding: 20px;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 12px;">
                                    <h3 style="margin:0; font-size:18px; color:var(--sdc-blue);">Grilla de Categorías Populares</h3>
                                    <div style="display:flex; gap:8px;">
                                        <code style="padding: 6px 12px; background: #E0E7FF; color: var(--sdc-blue); border-radius: 6px; font-weight: bold; font-size: 13px;">[bd_popular_categories]</code>
                                        <button type="button" id="copy-pop-cat" onclick="copyShortcode('[bd_popular_categories columns=&quot;6&quot; rows=&quot;2&quot;]', 'copy-pop-cat')" style="background: #fff; border: 1px solid #ddd; padding: 4px 8px; border-radius: 6px; font-size: 11px; cursor: pointer; font-weight: 600;">📋 Copiar Ejemplo (6x2)</button>
                                    </div>
                                </div>
                                <p style="margin: 0 0 16px 0; font-size:13.5px; color:var(--sdc-text-muted); line-height: 1.5;">
                                    Renderiza una grilla interactiva de las categorías principales del directorio con mayor volumen de locales publicados en formato visual, ideal para organizar la navegación de la Home.
                                </p>
                                
                                <h4 style="margin:0 0 8px 0; font-size:13px; font-weight:600;">Parámetros soportados:</h4>
                                <div class="sdc-table-wrapper" style="margin-bottom:12px;">
                                    <table class="sdc-table">
                                        <thead>
                                            <tr>
                                                <th style="width:20%;">Parámetro</th>
                                                <th style="width:20%;">Por defecto</th>
                                                <th>Descripción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><code>columns</code></td>
                                                <td><code>4</code></td>
                                                <td>Columnas de tarjetas a mostrar en pantallas grandes.</td>
                                            </tr>
                                            <tr>
                                                <td><code>rows</code></td>
                                                <td><code>4</code></td>
                                                <td>Cantidad de filas de categorías a mostrar.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- bd_footer_regions -->
                            <div style="background: #F9FAFB; border: 1px solid var(--sdc-border); border-radius: 8px; padding: 20px;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 12px;">
                                    <h3 style="margin:0; font-size:18px; color:var(--sdc-blue);">Listado SEO de Regiones (Footer)</h3>
                                    <div style="display:flex; gap:8px;">
                                        <code style="padding: 6px 12px; background: #E0E7FF; color: var(--sdc-blue); border-radius: 6px; font-weight: bold; font-size: 13px;">[bd_footer_regions]</code>
                                        <button type="button" id="copy-foot-reg" onclick="copyShortcode('[bd_footer_regions columns=&quot;2&quot; rows=&quot;8&quot;]', 'copy-foot-reg')" style="background: #fff; border: 1px solid #ddd; padding: 4px 8px; border-radius: 6px; font-size: 11px; cursor: pointer; font-weight: 600;">📋 Copiar Ejemplo</button>
                                    </div>
                                </div>
                                <p style="margin: 0 0 16px 0; font-size:13.5px; color:var(--sdc-text-muted); line-height: 1.5;">
                                    Lista ordenada alfabéticamente para maquetación en el pie de página, distribuyendo enlaces limpios hacia las páginas de región. Ideal para rastreo de buscadores y optimización de enlazado interno SEO.
                                </p>
                                
                                <h4 style="margin:0 0 8px 0; font-size:13px; font-weight:600;">Parámetros soportados:</h4>
                                <div class="sdc-table-wrapper" style="margin-bottom:12px;">
                                    <table class="sdc-table">
                                        <thead>
                                            <tr>
                                                <th style="width:20%;">Parámetro</th>
                                                <th style="width:20%;">Por defecto</th>
                                                <th>Descripción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><code>columns</code></td>
                                                <td><code>2</code></td>
                                                <td>Columnas en las cuales estructurar y dividir el listado de enlaces.</td>
                                            </tr>
                                            <tr>
                                                <td><code>rows</code></td>
                                                <td><code>8</code></td>
                                                <td>Cantidad de enlaces por columna antes de crear otra.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- bd_footer_categories -->
                            <div style="background: #F9FAFB; border: 1px solid var(--sdc-border); border-radius: 8px; padding: 20px;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 12px;">
                                    <h3 style="margin:0; font-size:18px; color:var(--sdc-blue);">Listado SEO de Categorías (Footer)</h3>
                                    <div style="display:flex; gap:8px;">
                                        <code style="padding: 6px 12px; background: #E0E7FF; color: var(--sdc-blue); border-radius: 6px; font-weight: bold; font-size: 13px;">[bd_footer_categories]</code>
                                        <button type="button" id="copy-foot-cat" onclick="copyShortcode('[bd_footer_categories columns=&quot;3&quot; rows=&quot;8&quot; parent=&quot;0&quot;]', 'copy-foot-cat')" style="background: #fff; border: 1px solid #ddd; padding: 4px 8px; border-radius: 6px; font-size: 11px; cursor: pointer; font-weight: 600;">📋 Copiar Ejemplo</button>
                                    </div>
                                </div>
                                <p style="margin: 0 0 16px 0; font-size:13.5px; color:var(--sdc-text-muted); line-height: 1.5;">
                                    Genera una grilla de enlaces directos a las páginas de las categorías del directorio para ubicar en el footer, mejorando la densidad de keywords y la indexabilidad web.
                                </p>
                                
                                <h4 style="margin:0 0 8px 0; font-size:13px; font-weight:600;">Parámetros soportados:</h4>
                                <div class="sdc-table-wrapper" style="margin-bottom:12px;">
                                    <table class="sdc-table">
                                        <thead>
                                            <tr>
                                                <th style="width:20%;">Parámetro</th>
                                                <th style="width:20%;">Por defecto</th>
                                                <th>Descripción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><code>columns</code></td>
                                                <td><code>3</code></td>
                                                <td>Cantidad de columnas de enlaces.</td>
                                            </tr>
                                            <tr>
                                                <td><code>rows</code></td>
                                                <td><code>8</code></td>
                                                <td>Cantidad de categorías listadas por columna.</td>
                                            </tr>
                                            <tr>
                                                <td><code>parent</code></td>
                                                <td><code>0</code></td>
                                                <td>ID de categoría padre de la cual listar (<code>0</code> para mostrar solo las raíces). Define como <code>"any"</code> para listar todas las subcategorías en general.</td>
                                            </tr>
                                            <tr>
                                                <td><code>orderby</code></td>
                                                <td><code>"name"</code></td>
                                                <td>Criterio de orden (<code>"name"</code> para alfabético, <code>"count"</code> para volumen de posts).</td>
                                            </tr>
                                            <tr>
                                                <td><code>order</code></td>
                                                <td><code>"ASC"</code></td>
                                                <td>Sentido (<code>"ASC"</code> o <code>"DESC"</code>).</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- 4.4 React & Nuevos Features -->
                    <div class="sdc-shortcode-item">
                        <div class="sdc-shortcode-header" onclick="this.parentElement.classList.toggle('active')">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 32px; height: 32px; background: #e0e7ff; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--sdc-blue);">
                                    <span class="dashicons dashicons-admin-plugins" style="font-size:16px; width:16px; height:16px;"></span>
                                </div>
                                <div>
                                    <h4 style="margin: 0; font-size: 15px; color: #1f2937; font-weight: 600;">React App & Nuevos Bloques</h4>
                                    <p style="margin: 4px 0 0; font-size: 12.5px; color: #6b7280;">Aplicación Frontend Vite, Destacados y Precios B2B.</p>
                                </div>
                            </div>
                            <span class="dashicons dashicons-arrow-down-alt2 toggle-icon"></span>
                        </div>
                        <div class="sdc-shortcode-body">
                            
                            <!-- babel_react_app -->
                            <div class="sdc-shortcode-variant">
                                <div class="sdc-variant-top">
                                    <div class="sdc-variant-code">
                                        <code>[babel_react_app]</code>
                                        <button type="button" id="copy-react-app" onclick="copyShortcode('[babel_react_app]', 'copy-react-app')" style="background: #fff; border: 1px solid #ddd; padding: 4px 8px; border-radius: 6px; font-size: 11px; cursor: pointer; font-weight: 600;">📋 Copiar</button>
                                    </div>
                                </div>
                                <div class="sdc-variant-desc">
                                    Inyecta la aplicación Frontend completa hecha en React (AI Studio). Muestra el mapa geolocalizado interactivo y los resultados de búsqueda dinámicos. Ideal para la Portada principal de Directorios.
                                </div>
                            </div>

                            <!-- bd_featured_businesses -->
                            <div class="sdc-shortcode-variant">
                                <div class="sdc-variant-top">
                                    <div class="sdc-variant-code">
                                        <code>[bd_featured_businesses]</code>
                                        <button type="button" id="copy-feat-bus" onclick="copyShortcode('[bd_featured_businesses]', 'copy-feat-bus')" style="background: #fff; border: 1px solid #ddd; padding: 4px 8px; border-radius: 6px; font-size: 11px; cursor: pointer; font-weight: 600;">📋 Copiar</button>
                                    </div>
                                </div>
                                <div class="sdc-variant-desc">
                                    Muestra un carrusel o grid de Negocios Destacados (Premium) basado en el nivel de suscripción.
                                </div>
                            </div>

                            <!-- babel_pricing_tables -->
                            <div class="sdc-shortcode-variant">
                                <div class="sdc-variant-top">
                                    <div class="sdc-variant-code">
                                        <code>[babel_pricing_tables]</code>
                                        <button type="button" id="copy-pricing" onclick="copyShortcode('[babel_pricing_tables]', 'copy-pricing')" style="background: #fff; border: 1px solid #ddd; padding: 4px 8px; border-radius: 6px; font-size: 11px; cursor: pointer; font-weight: 600;">📋 Copiar</button>
                                    </div>
                                </div>
                                <div class="sdc-variant-desc">
                                    Muestra las tres tablas de precios (Gratis, Profesional, Premium) del servicio B2B con un diseño moderno.
                                </div>
                            </div>
                            
                            <!-- babel_institutions -->
                            <div class="sdc-shortcode-variant">
                                <div class="sdc-variant-top">
                                    <div class="sdc-variant-code">
                                        <code>[babel_institutions]</code>
                                        <button type="button" id="copy-inst" onclick="copyShortcode('[babel_institutions]', 'copy-inst')" style="background: #fff; border: 1px solid #ddd; padding: 4px 8px; border-radius: 6px; font-size: 11px; cursor: pointer; font-weight: 600;">📋 Copiar</button>
                                    </div>
                                </div>
                                <div class="sdc-variant-desc">
                                    Muestra una botonera/pastillas (Pills) con filtros rápidos de Instituciones (Carabineros, Bomberos, etc). Su alias funcional es <code>[bd_region_institutions_pills]</code>.
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- 4.5 Micro-Shortcodes LTM -->
                    <div id="subtab-micro" class="sdc-subtab-content" style="display:none;">
                        <div style="background: #EEF2F6; border-left: 4px solid var(--sdc-blue); padding: 14px; border-radius: 6px; margin-bottom: 24px; font-size: 13.5px; color: #374151; line-height:1.6;">
                            <span style="font-weight: 700; color: var(--sdc-blue); display:block; margin-bottom: 4px;">🧩 ¿Qué es el Layout LTM Modular?</span>
                            Bajo la arquitectura **LTM (Long Term Maintainability)**, se han descompuesto los bloques del perfil comercial en micro-shortcodes independientes. Esto te permite maquetar la ficha de negocio de forma atómica en **Divi 5** usando módulos de código separados para cada elemento (Ej: la galería en una sección, los horarios en un lateral, etc.), manteniendo un diseño impecable libre de colisiones o cargas de Tailwind externas.
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 20px;">
                            
                            <!-- Explicación de parámetro común -->
                            <div style="background:#FFFDF5; border: 1px solid #FDE68A; padding: 12px 16px; border-radius: 8px; font-size:13px; color:#92400E;">
                                💡 <strong>Parámetro Común Opcional:</strong> Todos los micro-shortcodes descritos a continuación soportan el atributo <code>id</code> (Ej: <code>id="124"</code>). Si lo omites, el shortcode detectará de forma automática el ID del negocio actual del loop de WordPress, facilitando su uso en plantillas dinámicas globales.
                            </div>

                            <!-- bd_business_badges -->
                            <div style="background: #F9FAFB; border: 1px solid var(--sdc-border); border-radius: 8px; padding: 20px;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 12px;">
                                    <h3 style="margin:0; font-size:18px; color:var(--sdc-blue);">Insignias de Estado (Badges)</h3>
                                    <div style="display:flex; gap:8px;">
                                        <code style="padding: 6px 12px; background: #E0E7FF; color: var(--sdc-blue); border-radius: 6px; font-weight: bold; font-size: 13px;">[bd_business_badges]</code>
                                        <button type="button" id="copy-m-badges" onclick="copyShortcode('[bd_business_badges]', 'copy-m-badges')" style="background: #fff; border: 1px solid #ddd; padding: 4px 8px; border-radius: 6px; font-size: 11px; cursor: pointer; font-weight: 600;">📋 Copiar</button>
                                    </div>
                                </div>
                                <p style="margin: 0; font-size:13.5px; color:var(--sdc-text-muted); line-height: 1.5;">
                                    Renderiza las etiquetas dinámicas de estado del negocio: **Verificado** (con un check dorado de autenticidad) y/o **Destacado** (insignia roja estrella), según la configuración del comercio. Si no cuenta con estas marcas, no se renderiza nada para mantener el espacio limpio.
                                </p>
                            </div>

                            <!-- bd_business_gallery -->
                            <div style="background: #F9FAFB; border: 1px solid var(--sdc-border); border-radius: 8px; padding: 20px;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 12px;">
                                    <h3 style="margin:0; font-size:18px; color:var(--sdc-blue);">Galería Bento de Fotos</h3>
                                    <div style="display:flex; gap:8px;">
                                        <code style="padding: 6px 12px; background: #E0E7FF; color: var(--sdc-blue); border-radius: 6px; font-weight: bold; font-size: 13px;">[bd_business_gallery]</code>
                                        <button type="button" id="copy-m-gal" onclick="copyShortcode('[bd_business_gallery]', 'copy-m-gal')" style="background: #fff; border: 1px solid #ddd; padding: 4px 8px; border-radius: 6px; font-size: 11px; cursor: pointer; font-weight: 600;">📋 Copiar</button>
                                    </div>
                                </div>
                                <p style="margin: 0; font-size:13.5px; color:var(--sdc-text-muted); line-height: 1.5;">
                                    Muestra el visor Bento de fotos cargadas del comercio. Cuenta con una imagen destacada amplia y una fila inferior de miniaturas. Soporta interactividad moderna (cambia el visor principal al pasar el cursor o hacer clic sobre las miniaturas) mediante Javascript nativo optimizado.
                                </p>
                            </div>

                            <!-- bd_business_map -->
                            <div style="background: #F9FAFB; border: 1px solid var(--sdc-border); border-radius: 8px; padding: 20px;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 12px;">
                                    <h3 style="margin:0; font-size:18px; color:var(--sdc-blue);">Mapa Interactivo Leaflet.js</h3>
                                    <div style="display:flex; gap:8px;">
                                        <code style="padding: 6px 12px; background: #E0E7FF; color: var(--sdc-blue); border-radius: 6px; font-weight: bold; font-size: 13px;">[bd_business_map]</code>
                                        <button type="button" id="copy-m-map" onclick="copyShortcode('[bd_business_map]', 'copy-m-map')" style="background: #fff; border: 1px solid #ddd; padding: 4px 8px; border-radius: 6px; font-size: 11px; cursor: pointer; font-weight: 600;">📋 Copiar</button>
                                    </div>
                                </div>
                                <p style="margin: 0; font-size:13.5px; color:var(--sdc-text-muted); line-height: 1.5;">
                                    Renderiza el mapa de ubicación del comercio basado en OpenStreetMap utilizando Leaflet.js libre de dependencias pesadas. Muestra un marcador de posición, el texto de la dirección bajo el mapa y un enlace de redirección directa a Google Maps para facilitar la navegación GPS.
                                </p>
                            </div>

                            <!-- bd_business_hours -->
                            <div style="background: #F9FAFB; border: 1px solid var(--sdc-border); border-radius: 8px; padding: 20px;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 12px;">
                                    <h3 style="margin:0; font-size:18px; color:var(--sdc-blue);">Horarios de Apertura</h3>
                                    <div style="display:flex; gap:8px;">
                                        <code style="padding: 6px 12px; background: #E0E7FF; color: var(--sdc-blue); border-radius: 6px; font-weight: bold; font-size: 13px;">[bd_business_hours]</code>
                                        <button type="button" id="copy-m-hours" onclick="copyShortcode('[bd_business_hours]', 'copy-m-hours')" style="background: #fff; border: 1px solid #ddd; padding: 4px 8px; border-radius: 6px; font-size: 11px; cursor: pointer; font-weight: 600;">📋 Copiar</button>
                                    </div>
                                </div>
                                <p style="margin: 0; font-size:13.5px; color:var(--sdc-text-muted); line-height: 1.5;">
                                    Muestra la lista de horarios estructurados para la semana de Lunes a Domingo. Detecta automáticamente el día actual y resalta visualmente esa fila en color dorado con la etiqueta "Hoy" para una lectura rápida por parte del visitante.
                                </p>
                            </div>

                            <!-- bd_business_contact -->
                            <div style="background: #F9FAFB; border: 1px solid var(--sdc-border); border-radius: 8px; padding: 20px;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 12px;">
                                    <h3 style="margin:0; font-size:18px; color:var(--sdc-blue);">Botones de Contacto Directo</h3>
                                    <div style="display:flex; gap:8px;">
                                        <code style="padding: 6px 12px; background: #E0E7FF; color: var(--sdc-blue); border-radius: 6px; font-weight: bold; font-size: 13px;">[bd_business_contact]</code>
                                        <button type="button" id="copy-m-cont" onclick="copyShortcode('[bd_business_contact]', 'copy-m-cont')" style="background: #fff; border: 1px solid #ddd; padding: 4px 8px; border-radius: 6px; font-size: 11px; cursor: pointer; font-weight: 600;">📋 Copiar</button>
                                    </div>
                                </div>
                                <p style="margin: 0; font-size:13.5px; color:var(--sdc-text-muted); line-height: 1.5;">
                                    Renderiza un bloque de botones de acción rápida responsivos con iconos de contacto: **Llamar por teléfono**, **Enviar WhatsApp**, **Enviar Email**, **Visitar sitio Web** y **Visitar perfil de Instagram**. El shortcode filtra la salida de forma inteligente para pintar únicamente los botones que el comercio de verdad tenga configurados.
                                </p>
                            </div>

                        </div>
                    </div>

                </div>

                <!-- Tarjeta de ayuda para constructores visuales -->
                <div class="sdc-card" style="margin-top: 24px; border-left: 5px solid var(--sdc-blue); padding: 20px; background: #F0F4FF;">
                    <h3 style="margin: 0 0 8px 0; font-size:16px; color:var(--sdc-blue); font-weight: 700; display:flex; align-items:center; gap:8px;">
                        <span class="dashicons dashicons-info" style="font-size:20px; width:20px; height:20px;"></span>
                        Integración en Divi 5 y Gutenberg
                    </h3>
                    <p style="margin:0; font-size:13.5px; line-height:1.6; color:#374151;">
                        Para insertar estos shortcodes en **Divi 5**, simplemente añade un módulo de **Código (Code Module)** o un módulo de **Texto** en el maquetador visual de plantillas y pega el shortcode correspondiente. En **Gutenberg (Editor de Bloques)**, utiliza el bloque nativo **Shortcode**. No utilices estilos de Tailwind en el editor, ya que el plugin contiene sus propios estilos BEM desacoplados que renderizan de manera óptima en cualquier resolución.
                    </p>
                </div>
            </div>

            <!-- Script JS interactivo local para las sub-pestañas y el copiado rápido -->
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const subTabBtns = document.querySelectorAll('.sdc-subtab-btn');
                const subTabContents = document.querySelectorAll('.sdc-subtab-content');
                
                subTabBtns.forEach(btn => {
                    btn.addEventListener('click', function() {
                        const targetId = this.getAttribute('data-subtarget');
                        
                        subTabBtns.forEach(b => {
                            b.classList.remove('active');
                            b.style.background = '#fff';
                            b.style.color = '#1f2937';
                            b.style.borderColor = '#ddd';
                        });
                        subTabContents.forEach(c => c.style.display = 'none');
                        
                        this.classList.add('active');
                        this.style.background = 'var(--sdc-blue)';
                        this.style.color = '#fff';
                        this.style.borderColor = 'var(--sdc-blue)';
                        
                        const targetEl = document.getElementById(targetId);
                        if (targetEl) {
                            targetEl.style.display = 'block';
                        }
                    });
                    
                    // Estilos iniciales para el botón activo al cargar
                    if (btn.classList.contains('active')) {
                        btn.style.background = 'var(--sdc-blue)';
                        btn.style.color = '#fff';
                        btn.style.borderColor = 'var(--sdc-blue)';
                    }
                });
                
                // Función de copiado rápido segura
                window.copyShortcode = function(text, btnId) {
                    navigator.clipboard.writeText(text).then(() => {
                        const btn = document.getElementById(btnId);
                        if (btn) {
                            const originalHTML = btn.innerHTML;
                            btn.innerHTML = '✨ ¡Copiado!';
                            btn.style.background = 'var(--sdc-success)';
                            btn.style.color = '#fff';
                            btn.style.borderColor = 'var(--sdc-success)';
                            setTimeout(() => {
                                btn.innerHTML = originalHTML;
                                btn.style.background = '';
                                btn.style.color = '';
                                btn.style.borderColor = '';
                            }, 1500);
                        }
                    }).catch(err => {
                        console.error('Error al copiar: ', err);
                    });
                };
            });
            </script>


        </div> <!-- Cierre #babel-admin-app -->
        <?php
    }
}
