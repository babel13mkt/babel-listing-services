<?php
namespace Babel\Directory;

/**
 * Clase para el manejo de Metaboxes y Custom Fields unificados con estética SaaS moderna.
 * v8.0.0 — Panel Central Compacto por Pestañas, sin Sidebar de Categorías/Imagen y sin editor Gutenberg.
 *
 * @package Babel_Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}
class Metaboxes {

    /**
     * Constructor de la clase de metaboxes.
     * Enlaza los hooks de WordPress para renderizado, guardado y assets del administrador.
     */
    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_business_meta_box' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_ad_banner_meta_box' ) );
        add_action( 'save_post_babel_business', array( $this, 'save_business_meta' ), 10, 2 );
        add_action( 'save_post_bd_ad_banner', array( $this, 'save_ad_banner_meta' ), 10, 2 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    /**
     * Registra la metabox nativa para el post type 'babel_business'.
     */
    public function add_business_meta_box() {
        add_meta_box(
            'babel_business_central_panel',
            __( 'Panel Central de Control de Negocio', 'babel-directory' ),
            array( $this, 'render_central_panel' ),
            'babel_business',
            'normal',
            'high'
        );
    }

    /**
     * Registra la metabox para el post type 'bd_ad_banner'.
     */
    public function add_ad_banner_meta_box() {
        add_meta_box(
            'bd_ad_banner_panel',
            __( 'Ajustes del Anuncio Publicitario', 'babel-directory' ),
            array( $this, 'render_ad_banner_panel' ),
            'bd_ad_banner',
            'normal',
            'high'
        );
    }

    /**
     * Encola los scripts nativos de medios y taxonomías en la pantalla del CPT.
     *
     * @param string $hook Identificador de la página actual del panel de administración.
     */
    public function enqueue_admin_assets( $hook ) {
        $screen = get_current_screen();
        if ( ( $screen && ( 'babel_business' === $screen->post_type || 'bd_ad_banner' === $screen->post_type ) ) || strpos( $hook, 'bd-panel' ) !== false ) {
            wp_enqueue_media();
            wp_enqueue_script( 'jquery-ui-sortable' );
            wp_enqueue_script( 'category' ); // WordPress hierarchical checklist helper
        }
    }

    /**
     * Renderiza los campos de la metabox en el backend de WordPress estructurado por pestañas.
     *
     * @param WP_Post $post El objeto del post actual.
     */
    public function render_central_panel( $post ) {
        // Generar token de seguridad (Nonce)
        wp_nonce_field( 'babel_business_meta_box_nonce_action', 'babel_business_meta_box_nonce' );

        // Nombre y Descripción del negocio (vinculados al post_title y post_content nativos)
        $biz_name = $post->post_title;
        $biz_desc = $post->post_content;

        // Recuperar valores guardados actualmente con fallback seguro a llaves anteriores
        $phone       = get_post_meta( $post->ID, '_babel_phone', true );
        if ( empty( $phone ) ) {
            $phone   = get_post_meta( $post->ID, '_bd_telefono', true );
        }

        $whatsapp    = get_post_meta( $post->ID, '_babel_whatsapp', true );
        if ( empty( $whatsapp ) ) {
            $whatsapp = get_post_meta( $post->ID, '_bd_whatsapp', true );
        }

        $email       = get_post_meta( $post->ID, '_babel_email', true );
        if ( empty( $email ) ) {
            $email   = get_post_meta( $post->ID, '_bd_email', true );
        }

        $address     = get_post_meta( $post->ID, '_babel_address', true );
        if ( empty( $address ) ) {
            $address = get_post_meta( $post->ID, '_bd_direccion', true );
        }
        
        $maps        = get_post_meta( $post->ID, '_babel_maps', true );
        if ( empty( $maps ) ) {
            $maps    = get_post_meta( $post->ID, '_babel_gmaps', true );
        }
        if ( empty( $maps ) ) {
            $maps    = get_post_meta( $post->ID, '_bd_gmaps', true );
        }
        
        $lat         = get_post_meta( $post->ID, '_babel_lat', true );
        if ( empty( $lat ) ) {
            $lat     = get_post_meta( $post->ID, '_babel_latitude', true );
        }
        if ( empty( $lat ) ) {
            $lat     = get_post_meta( $post->ID, '_bd_latitud', true );
        }
        
        $lng         = get_post_meta( $post->ID, '_babel_lng', true );
        if ( empty( $lng ) ) {
            $lng     = get_post_meta( $post->ID, '_babel_longitude', true );
        }
        if ( empty( $lng ) ) {
            $lng     = get_post_meta( $post->ID, '_bd_longitud', true );
        }
        
        $website     = get_post_meta( $post->ID, '_babel_website', true );
        if ( empty( $website ) ) {
            $website = get_post_meta( $post->ID, '_bd_sitio_web', true );
        }
        if ( empty( $website ) ) {
            $website = get_post_meta( $post->ID, '_bd_web', true );
        }

        $instagram   = get_post_meta( $post->ID, '_babel_instagram', true );
        $facebook    = get_post_meta( $post->ID, '_babel_facebook', true );
        $linkedin    = get_post_meta( $post->ID, '_babel_linkedin', true );
        
        $verified    = get_post_meta( $post->ID, '_babel_verified', true );
        if ( $verified === '' ) {
            $verified = get_post_meta( $post->ID, '_babel_is_verified', true );
        }
        if ( $verified === '' ) {
            $verified = get_post_meta( $post->ID, '_bd_verificado', true );
        }
        
        $featured    = get_post_meta( $post->ID, '_babel_featured', true );
        if ( $featured === '' ) {
            $featured = get_post_meta( $post->ID, '_babel_is_featured', true );
        }
        if ( $featured === '' ) {
            $featured = get_post_meta( $post->ID, '_bd_destacado', true );
        }

        $is_institution = get_post_meta( $post->ID, '_babel_is_institution', true );
        if ( $is_institution === '' ) {
            $is_institution = get_post_meta( $post->ID, '_bd_is_institution', true );
        }

        $gallery     = get_post_meta( $post->ID, '_babel_gallery', true );
        if ( empty( $gallery ) ) {
            $gallery = get_post_meta( $post->ID, '_bd_galeria', true );
        }

        $hours_meta  = get_post_meta( $post->ID, '_babel_hours', true );
        $biz_tags    = get_post_meta( $post->ID, '_babel_biz_tags', true );

        // Pre-cargar todas las categorías para el autocomplete JS
        $all_categories = get_terms( array(
            'taxonomy'   => 'babel_category',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ) );
        if ( \is_wp_error( $all_categories ) ) {
            $all_categories = array();
        }
        // Obtener categorías YA asignadas al post
        $assigned_cat_ids = wp_get_object_terms( $post->ID, 'babel_category', array( 'fields' => 'ids' ) );
        if ( \is_wp_error( $assigned_cat_ids ) ) {
            $assigned_cat_ids = array();
        }

        // Configurar los días de la semana y los horarios decodificados
        $days_of_week = array( 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo' );
        $hours = array();
        if ( ! empty( $hours_meta ) ) {
            if ( is_array( $hours_meta ) ) {
                $hours = $hours_meta;
            } else {
                $hours = json_decode( $hours_meta, true );
            }
        }
        if ( ! is_array( $hours ) ) {
            $hours = array();
        }

        // Obtener datos del Logotipo (Imagen destacada)
        $logo_id  = get_post_thumbnail_id( $post->ID );
        if ( ! $logo_id ) {
            $logo_id = get_post_meta( $post->ID, '_bd_logo_id', true );
        }
        $logo_url = '';
        if ( $logo_id ) {
            $logo_url = wp_get_attachment_image_url( $logo_id, 'thumbnail' );
        }

        // Parsear IDs de galería (puede venir como string "1,2,3" o como array)
        $gallery_ids = array();
        if ( ! empty( $gallery ) ) {
            if ( is_array( $gallery ) ) {
                $gallery_ids = array_filter( array_map( 'intval', $gallery ) );
            } else {
                $gallery_ids = explode( ',', (string) $gallery );
                $gallery_ids = array_filter( array_map( 'intval', $gallery_ids ) );
            }
        }

        // --- Fase 2 - Recuperar nuevos valores guardados con fallback seguro a llaves anteriores ---
        $rut = get_post_meta( $post->ID, '_babel_rut', true );
        if ( empty( $rut ) ) {
            $rut = get_post_meta( $post->ID, '_bd_rut', true );
        }

        $razon_social = get_post_meta( $post->ID, '_babel_razon_social', true );
        if ( empty( $razon_social ) ) {
            $razon_social = get_post_meta( $post->ID, '_bd_razon_social', true );
        }

        $nombre_comercial = get_post_meta( $post->ID, '_babel_nombre_comercial', true );
        if ( empty( $nombre_comercial ) ) {
            $nombre_comercial = get_post_meta( $post->ID, '_bd_nombre_comercial', true );
        }

        $giro = get_post_meta( $post->ID, '_babel_giro', true );
        if ( empty( $giro ) ) {
            $giro = get_post_meta( $post->ID, '_bd_giro', true );
        }

        $patente = get_post_meta( $post->ID, '_babel_patente', true );
        if ( empty( $patente ) ) {
            $patente = get_post_meta( $post->ID, '_bd_patente', true );
        }

        $rep_legal = get_post_meta( $post->ID, '_babel_rep_legal', true );
        if ( empty( $rep_legal ) ) {
            $rep_legal = get_post_meta( $post->ID, '_bd_rep_legal', true );
        }

        $founded_year = get_post_meta( $post->ID, '_babel_founded_year', true );
        if ( empty( $founded_year ) ) {
            $founded_year = get_post_meta( $post->ID, '_bd_founded_year', true );
        }

        $tiktok = get_post_meta( $post->ID, '_babel_tiktok', true );
        if ( empty( $tiktok ) ) {
            $tiktok = get_post_meta( $post->ID, '_bd_tiktok', true );
        }

        $youtube_channel = get_post_meta( $post->ID, '_babel_youtube_channel', true );
        if ( empty( $youtube_channel ) ) {
            $youtube_channel = get_post_meta( $post->ID, '_bd_youtube_channel', true );
        }

        $twitter = get_post_meta( $post->ID, '_babel_twitter', true );
        if ( empty( $twitter ) ) {
            $twitter = get_post_meta( $post->ID, '_bd_twitter', true );
        }

        $pinterest = get_post_meta( $post->ID, '_babel_pinterest', true );
        if ( empty( $pinterest ) ) {
            $pinterest = get_post_meta( $post->ID, '_bd_pinterest', true );
        }

        $youtube_url = get_post_meta( $post->ID, '_babel_youtube_url', true );
        if ( empty( $youtube_url ) ) {
            $youtube_url = get_post_meta( $post->ID, '_bd_youtube_url', true );
        }

        $youtube_url_2 = get_post_meta( $post->ID, '_babel_youtube_url_2', true );
        if ( empty( $youtube_url_2 ) ) {
            $youtube_url_2 = get_post_meta( $post->ID, '_bd_youtube_url_2', true );
        }

        $parking = get_post_meta( $post->ID, '_babel_parking', true );
        if ( empty( $parking ) ) {
            $parking = get_post_meta( $post->ID, '_bd_parking', true );
        }

        $pet_friendly = get_post_meta( $post->ID, '_babel_pet_friendly', true );
        if ( empty( $pet_friendly ) ) {
            $pet_friendly = get_post_meta( $post->ID, '_bd_pet_friendly', true );
        }

        $payments_meta = get_post_meta( $post->ID, '_babel_payments', true );
        if ( empty( $payments_meta ) ) {
            $payments_meta = get_post_meta( $post->ID, '_bd_payments', true );
        }
        if ( is_array( $payments_meta ) ) {
            $payments = $payments_meta;
        } else {
            $payments = json_decode( $payments_meta, true );
        }
        if ( ! is_array( $payments ) ) {
            $payments = array();
        }

        $accessibility = get_post_meta( $post->ID, '_babel_accessibility', true );
        if ( empty( $accessibility ) ) {
            $accessibility = get_post_meta( $post->ID, '_bd_accessibility', true );
        }

        $wifi = get_post_meta( $post->ID, '_babel_wifi', true );
        if ( empty( $wifi ) ) {
            $wifi = get_post_meta( $post->ID, '_bd_wifi', true );
        }

        $reservations = get_post_meta( $post->ID, '_babel_reservations', true );
        if ( empty( $reservations ) ) {
            $reservations = get_post_meta( $post->ID, '_bd_reservations', true );
        }

        $delivery = get_post_meta( $post->ID, '_babel_delivery', true );
        if ( empty( $delivery ) ) {
            $delivery = get_post_meta( $post->ID, '_bd_delivery', true );
        }

        $price_range = get_post_meta( $post->ID, '_babel_price_range', true );
        if ( empty( $price_range ) ) {
            $price_range = get_post_meta( $post->ID, '_bd_price_range', true );
        }

        $spaces_meta = get_post_meta( $post->ID, '_babel_spaces', true );
        if ( empty( $spaces_meta ) ) {
            $spaces_meta = get_post_meta( $post->ID, '_bd_spaces', true );
        }
        if ( is_array( $spaces_meta ) ) {
            $spaces = $spaces_meta;
        } else {
            $spaces = json_decode( $spaces_meta, true );
        }
        if ( ! is_array( $spaces ) ) {
            $spaces = array();
        }

        $biz_type = get_post_meta( $post->ID, '_babel_biz_type', true );
        if ( empty( $biz_type ) ) {
            $biz_type = get_post_meta( $post->ID, '_bd_biz_type', true );
        }

        $languages_meta = get_post_meta( $post->ID, '_babel_languages', true );
        if ( empty( $languages_meta ) ) {
            $languages_meta = get_post_meta( $post->ID, '_bd_languages', true );
        }
        if ( is_array( $languages_meta ) ) {
            $languages = $languages_meta;
        } else {
            $languages = json_decode( $languages_meta, true );
        }
        if ( ! is_array( $languages ) ) {
            $languages = array();
        }

        $employees = get_post_meta( $post->ID, '_babel_employees', true );
        if ( empty( $employees ) ) {
            $employees = get_post_meta( $post->ID, '_bd_employees', true );
        }

        $coverage_area = get_post_meta( $post->ID, '_babel_coverage_area', true );
        if ( empty( $coverage_area ) ) {
            $coverage_area = get_post_meta( $post->ID, '_bd_coverage_area', true );
        }

        $menu_url = get_post_meta( $post->ID, '_babel_menu_url', true );
        if ( empty( $menu_url ) ) {
            $menu_url = get_post_meta( $post->ID, '_bd_menu_url', true );
        }

        $booking_url = get_post_meta( $post->ID, '_babel_booking_url', true );
        if ( empty( $booking_url ) ) {
            $booking_url = get_post_meta( $post->ID, '_bd_booking_url', true );
        }

        $phone_alt = get_post_meta( $post->ID, '_babel_phone_alt', true );
        if ( empty( $phone_alt ) ) {
            $phone_alt = get_post_meta( $post->ID, '_bd_phone_alt', true );
        }

        $email_alt = get_post_meta( $post->ID, '_babel_email_alt', true );
        if ( empty( $email_alt ) ) {
            $email_alt = get_post_meta( $post->ID, '_bd_email_alt', true );
        }
        ?>
        <style>
            /* 0. WordPress postbox transparentizer to eliminate double border clutter */
            #babel_business_central_panel {
                background: transparent !important;
                border: none !important;
                box-shadow: none !important;
            }
            #babel_business_central_panel > .postbox-header {
                display: none !important;
            }
            #babel_business_central_panel > .inside {
                padding: 0 !important;
                margin: 0 !important;
            }

            /* 1. Reset y Contenedor Principal (Bento Grid) */
            .bd-metabox-wrapper {
                background: transparent;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                color: #334155;
                box-sizing: border-box;
                padding: 0;
            }
            
            .bd-dashboard-wrapper {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
                gap: 24px;
                background: transparent;
            }
            
            .bd-card {
                background: #ffffff;
                border: 1px solid #cbd5e1;
                border-radius: 12px;
                padding: 24px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
                display: flex;
                flex-direction: column;
                gap: 16px;
                align-self: start;
            }
            
            .bd-card-full {
                grid-column: 1 / -1;
            }
            
            .bd-card-title {
                font-size: 16px;
                font-weight: 700;
                color: #0f172a;
                margin: 0 0 8px 0;
                padding-bottom: 12px;
                border-bottom: 1px solid #f1f5f9;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            /* Sistema de Grillas Interno */
            .bd-metabox-grid {
                display: grid;
                grid-template-columns: repeat(12, 1fr);
                gap: 16px;
            }
            
            .bd-grid-span-12 { grid-column: span 12; }
            .bd-grid-span-8 { grid-column: span 8; }
            .bd-grid-span-6 { grid-column: span 6; }
            .bd-grid-span-4 { grid-column: span 4; }
            .bd-grid-span-3 { grid-column: span 3; }
            
            /* Elementos de Formulario Compactos */
            .bd-field-group {
                display: flex;
                flex-direction: column;
                gap: 6px;
            }
            .bd-field-group label {
                font-weight: 600;
                font-size: 13px;
                color: #1e293b;
            }
            .bd-field-group input[type="text"],
            .bd-field-group input[type="email"],
            .bd-field-group input[type="url"],
            .bd-field-group input[type="time"],
            .bd-field-group select,
            .bd-field-group textarea {
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
            .bd-field-group input:focus,
            .bd-field-group select:focus,
            .bd-field-group textarea:focus {
                background-color: #ffffff;
                border-color: #219ebc;
                box-shadow: 0 0 0 3px rgba(33, 158, 188, 0.15);
                outline: none;
            }
            .bd-field-desc {
                margin: 2px 0 0;
                font-size: 11px;
                color: #64748b;
            }
            
            /* Premium Hierarchical Category Checklist */
            .bd-category-checklist {
                max-height: 280px;
                overflow-y: auto;
                border: 1px solid #cbd5e1;
                padding: 16px;
                border-radius: 8px;
                background: #ffffff;
                box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
            }

            /* ===  PREDICTIVE CATEGORY SEARCH === */
            .bd-cat-autocomplete {
                position: relative;
            }
            .bd-cat-search-wrap {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
                align-items: center;
                border: 1.5px solid #cbd5e1;
                border-radius: 8px;
                padding: 8px 14px;
                background: #ffffff;
                cursor: text;
                min-height: 46px;
                box-sizing: border-box;
                transition: border-color 0.2s, box-shadow 0.2s;
            }
            .bd-cat-search-wrap:focus-within {
                border-color: #219ebc;
                box-shadow: 0 0 0 3px rgba(33, 158, 188, 0.15);
            }
            .bd-cat-chip {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                background: #e0f2fe;
                border: 1px solid #0284c7;
                color: #0369a1;
                font-size: 12px;
                font-weight: 600;
                padding: 4px 10px 4px 12px;
                border-radius: 20px;
                white-space: nowrap;
            }
            .bd-cat-chip-x {
                cursor: pointer;
                font-size: 16px;
                line-height: 1;
                color: #0369a1;
                background: none;
                border: none;
                padding: 0;
                display: flex;
                align-items: center;
                margin-left: 2px;
            }
            .bd-cat-chip-x:hover { color: #dc2626; }
            .bd-cat-search-input {
                border: none !important;
                outline: none !important;
                background: transparent !important;
                padding: 2px 0 !important;
                box-shadow: none !important;
                font-size: 13px !important;
                min-width: 200px;
                flex: 1;
                color: #334155 !important;
            }
            .bd-cat-dropdown {
                position: absolute;
                top: calc(100% + 4px);
                left: 0;
                right: 0;
                background: #ffffff;
                border: 1px solid #cbd5e1;
                border-radius: 8px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.12);
                z-index: 99999;
                max-height: 260px;
                overflow-y: auto;
                display: none;
            }
            .bd-cat-dropdown-item {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 10px 16px;
                cursor: pointer;
                font-size: 13px;
                color: #334155;
                border-bottom: 1px solid #f1f5f9;
                transition: background 0.15s;
            }
            .bd-cat-dropdown-item:last-child { border-bottom: none; }
            .bd-cat-dropdown-item:hover {
                background: #f0f9ff;
                color: #0369a1;
            }
            .bd-cat-dropdown-item.is-selected {
                background: #e0f2fe;
                color: #0369a1;
                font-weight: 600;
            }
            .bd-cat-dropdown-item .bd-cat-parent-path {
                font-size: 11px;
                color: #94a3b8;
                margin-left: 8px;
                white-space: nowrap;
            }
            .bd-cat-dropdown-item.is-selected .bd-cat-parent-path { color: #7dd3fc; }
            .bd-cat-dropdown-empty {
                padding: 14px 16px;
                font-size: 13px;
                color: #94a3b8;
                font-style: italic;
            }
            .bd-cat-dropdown-checkmark {
                color: #0284c7;
                font-size: 14px;
                flex-shrink: 0;
            }

            /* === TAGS SEO CHIP INPUT === */
            .bd-tags-wrap {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
                align-items: center;
                border: 1.5px solid #cbd5e1;
                border-radius: 8px;
                padding: 8px 14px;
                background: #ffffff;
                cursor: text;
                min-height: 46px;
                box-sizing: border-box;
                transition: border-color 0.2s, box-shadow 0.2s;
            }
            .bd-tags-wrap:focus-within {
                border-color: #219ebc;
                box-shadow: 0 0 0 3px rgba(33, 158, 188, 0.15);
            }
            .bd-tag-chip {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                background: #f0fdf4;
                border: 1px solid #16a34a;
                color: #15803d;
                font-size: 12px;
                font-weight: 600;
                padding: 4px 10px 4px 12px;
                border-radius: 20px;
                white-space: nowrap;
            }
            .bd-tag-chip-x {
                cursor: pointer;
                font-size: 16px;
                line-height: 1;
                color: #15803d;
                background: none;
                border: none;
                padding: 0;
                display: flex;
                align-items: center;
                margin-left: 2px;
            }
            .bd-tag-chip-x:hover { color: #dc2626; }
            .bd-tag-input-inline {
                border: none !important;
                outline: none !important;
                background: transparent !important;
                padding: 2px 0 !important;
                box-shadow: none !important;
                font-size: 13px !important;
                min-width: 200px;
                flex: 1;
                color: #334155 !important;
            }
            .bd-category-checklist ul {
                list-style: none;
                margin: 0;
                padding: 0;
            }
            .bd-category-checklist ul.children {
                padding-left: 24px;
                margin-top: 8px;
                margin-bottom: 4px;
                border-left: 2px solid #e2e8f0; /* Connector tree line */
            }
            .bd-category-checklist li {
                margin-bottom: 8px;
                font-size: 13px;
                color: #334155;
                position: relative;
            }
            .bd-category-checklist li:last-child {
                margin-bottom: 0;
            }
            .bd-category-checklist label.selectit {
                display: flex;
                align-items: center;
                gap: 10px;
                cursor: pointer;
                font-weight: 500;
                font-size: 13px;
                padding: 8px 14px;
                border-radius: 6px;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                transition: all 0.2s ease;
                user-select: none;
                width: 100%;
                box-sizing: border-box;
            }
            .bd-category-checklist label.selectit:hover {
                background: #f1f5f9;
                border-color: #cbd5e1;
                color: #0f172a;
            }
            .bd-category-checklist input[type="checkbox"] {
                margin: 0 !important;
                width: 15px !important;
                height: 15px !important;
                border: 1px solid #cbd5e1 !important;
                border-radius: 4px !important;
                cursor: pointer;
            }
            /* Highlight selected items */
            .bd-category-checklist li.checked > label.selectit {
                background: #e0f2fe;
                border-color: #0284c7;
                color: #0369a1;
                font-weight: 600;
            }
            
            /* Toggles / Contenedor de Estados */
            .bd-states-container {
                display: flex;
                gap: 24px;
                background: #f8fafc;
                border: 1px solid #cbd5e1;
                padding: 14px 18px;
                border-radius: 6px;
                align-items: center;
            }
            .bd-state-checkbox {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                cursor: pointer;
                font-weight: 600;
                font-size: 13px;
                color: #1e293b;
            }
            .bd-state-checkbox input[type="checkbox"] {
                width: 16px;
                height: 16px;
                border-radius: 4px;
                border: 1px solid #cbd5e1;
                cursor: pointer;
                margin: 0;
            }
            
            /* Horas y Rueda de Horarios */
            .bd-hours-container {
                background: #f8fafc;
                border: 1px solid #cbd5e1;
                border-radius: 6px;
                padding: 15px;
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            .bd-hours-row {
                display: flex;
                align-items: center;
                gap: 15px;
                font-size: 13px;
                border-bottom: 1px solid #e2e8f0;
                padding-bottom: 8px;
            }
            .bd-hours-row:last-child {
                border-bottom: none;
                padding-bottom: 0;
            }
            .bd-hours-day {
                width: 90px;
                font-weight: 600;
                color: #475569;
            }
            .bd-hours-inputs {
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .bd-hours-inputs input[type="time"] {
                padding: 4px 8px;
                border-radius: 4px;
                border: 1px solid #cbd5e1;
            }
            .bd-hours-closed-label {
                display: flex;
                align-items: center;
                gap: 6px;
                cursor: pointer;
                margin-left: auto;
                font-weight: 500;
                color: #64748b;
            }
            
            /* Contenedor de Carga de Medios / Logo */
            .bd-media-upload-container {
                display: flex;
                gap: 16px;
                align-items: center;
                border: 1px dashed #cbd5e1;
                padding: 12px;
                border-radius: 6px;
                background: #f8fafc;
                min-height: 90px;
                box-sizing: border-box;
            }
            .bd-media-preview-box {
                width: 64px;
                height: 64px;
                border-radius: 6px;
                border: 1px solid #e2e8f0;
                background: #ffffff;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
                position: relative;
                flex-shrink: 0;
            }
            .bd-media-preview-box img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            .bd-media-placeholder-icon {
                font-size: 24px;
                color: #94a3b8;
            }
            .bd-media-actions {
                display: flex;
                flex-direction: row;
                flex-wrap: wrap;
                gap: 8px;
            }
            
            /* Galería de Fotos Múltiples */
            .bd-gallery-container {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }
            .bd-gallery-grid {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                min-height: 90px;
                border: 1px dashed #cbd5e1;
                border-radius: 6px;
                padding: 12px;
                background: #f8fafc;
                box-sizing: border-box;
            }
            .bd-gallery-item {
                width: 64px;
                height: 64px;
                border-radius: 6px;
                border: 1px solid #cbd5e1;
                background: #ffffff;
                position: relative;
                cursor: grab;
                overflow: hidden;
                display: flex;
                align-items: center;
                justify-content: center;
                box-sizing: border-box;
            }
            .bd-gallery-item img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            .bd-gallery-item .bd-remove-gallery-item {
                position: absolute;
                top: 2px;
                right: 2px;
                background: rgba(239, 68, 68, 0.9);
                color: white;
                border: none;
                border-radius: 50%;
                width: 16px;
                height: 16px;
                font-size: 10px;
                cursor: pointer;
                line-height: 1;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 0;
            }
            
            /* Premium Buttons (SaaS look) */
            .bd-btn-primary {
                background: #219ebc !important;
                color: #ffffff !important;
                border: none !important;
                border-radius: 6px !important;
                padding: 6px 14px !important;
                font-weight: 600 !important;
                font-size: 12px !important;
                cursor: pointer !important;
                transition: background 0.2s !important;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
            }
            .bd-btn-primary:hover {
                background: #023047 !important;
            }
            .bd-btn-danger {
                background: #ef4444 !important;
                color: #ffffff !important;
                border: none !important;
                border-radius: 6px !important;
                padding: 6px 14px !important;
                font-weight: 600 !important;
                font-size: 12px !important;
                cursor: pointer !important;
                transition: background 0.2s !important;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
            }
            .bd-btn-danger:hover {
                background: #dc2626 !important;
            }
            
            /* === GEO-STATUS INDICATOR === */
            .bd-geo-idle    { color: #94a3b8; }
            .bd-geo-loading { color: #f59e0b; font-style: italic; }
            .bd-geo-ok      { color: #16a34a; font-weight: 600; }
            .bd-geo-warn    { color: #f59e0b; font-weight: 500; }
            .bd-geo-error   { color: #ef4444; }
            #babel_maps.geo-loading { border-color: #f59e0b !important; }
            #babel_maps.geo-ok      { border-color: #16a34a !important; box-shadow: 0 0 0 2px rgba(22,163,74,0.15) !important; }
            #babel_maps.geo-warn    { border-color: #f59e0b !important; }

            /* Ocultamiento del Sidebar y Centrado del Panel */
            .post-type-babel_business #poststuff {
                max-width: 1300px;
                margin: 20px auto 0;
            }
            .post-type-babel_business #post-body {
                display: grid;
                grid-template-columns: 1fr 280px;
                gap: 20px;
            }
            .post-type-babel_business #post-body-content {
                grid-column: 1;
                grid-row: 1;
                margin-bottom: 0 !important;
                padding-bottom: 0 !important;
            }
            .post-type-babel_business #postbox-container-1 {
                grid-column: 2;
                grid-row: 1 / span 2;
            }
            .post-type-babel_business #postbox-container-2 {
                grid-column: 1;
                grid-row: 2;
                clear: both;
                width: 100%;
            }
            .post-type-babel_business .postbox-container {
                float: none !important;
            }
            
            /* Ocultar barra lateral nativa de categorías e imagen destacada */
            .post-type-babel_business #babel_categorydiv,
            .post-type-babel_business #postimagediv {
                display: none !important;
            }
            
            @media (max-width: 850px) {
                .post-type-babel_business #post-body {
                    grid-template-columns: 1fr;
                }
                .post-type-babel_business #postbox-container-1 {
                    grid-column: 1;
                    grid-row: auto;
                }
                .post-type-babel_business #postbox-container-2 {
                    grid-column: 1;
                    grid-row: auto;
                }
                .post-type-babel_business #post-body-content {
                    grid-column: 1;
                    grid-row: auto;
                }
            }

            /* Fase 2 Premium CSS */
            .bd-section-title {
                grid-column: span 12;
                margin: 25px 0 10px 0;
                padding-bottom: 8px;
                border-bottom: 2px solid #cbd5e1;
                color: #219ebc;
                font-size: 15px;
                font-weight: 700;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .bd-checkbox-group-container {
                display: flex;
                flex-wrap: wrap;
                gap: 14px;
                background: #f8fafc;
                border: 1px solid #cbd5e1;
                padding: 12px 16px;
                border-radius: 6px;
            }
        </style>

        <div class="bd-metabox-wrapper">
            <div class="bd-dashboard-wrapper">

            <!-- TARJETA 1: GENERAL -->
            <div class="bd-card bd-card-full">
                <h3 class="bd-card-title">🏢 <?php esc_html_e( 'Información Principal', 'babel-directory' ); ?></h3>
                <div class="bd-metabox-grid">
                    <!-- Fila 1: Nombre del Negocio (12 cols) -->
                    <div class="bd-field-group bd-grid-span-12">
                        <label for="_babel_biz_name"><?php esc_html_e( 'Nombre del Negocio', 'babel-directory' ); ?></label>
                        <input type="text" id="_babel_biz_name" name="_babel_biz_name" value="<?php echo esc_attr( $biz_name ); ?>" placeholder="<?php esc_attr_e( 'Ej: Cafetería Central', 'babel-directory' ); ?>" required />
                        <p class="bd-field-desc"><?php esc_html_e( 'Nombre público oficial del comercio.', 'babel-directory' ); ?></p>
                    </div>

            <!-- Fila 2: BÚSQUEDA PREDICTIVA DE CATEGORÍAS -->
                    <div class="bd-field-group bd-grid-span-12">
                        <label>🔍 <?php esc_html_e( 'Buscar Categoría', 'babel-directory' ); ?></label>
                        <div class="bd-cat-autocomplete" id="bd-cat-autocomplete">
                            <div class="bd-cat-search-wrap" id="bd-cat-search-wrap">
                                <!-- chips dinámicos aquí -->
                                <input
                                    type="text"
                                    id="bd-cat-search-input"
                                    class="bd-cat-search-input"
                                    placeholder="<?php esc_attr_e( 'Escribe para buscar...', 'babel-directory' ); ?>"
                                    autocomplete="off"
                                />
                            </div>
                            <div class="bd-cat-dropdown" id="bd-cat-dropdown"></div>
                        </div>
                        <p class="bd-field-desc"><?php esc_html_e( 'Búsqueda instantánea. Los resultados se sincronizan con el listado inferior.', 'babel-directory' ); ?></p>
                    </div>

                    <!-- Fila 3: Categorías del Negocio (listado jerárquico) -->
                    <div class="bd-field-group bd-grid-span-12">
                        <label><?php esc_html_e( 'Categorías del Negocio', 'babel-directory' ); ?></label>
                        <div class="bd-category-checklist" id="bd-category-checklist">
                            <ul>
                                <?php wp_terms_checklist( $post->ID, array( 'taxonomy' => 'babel_category' ) ); ?>
                            </ul>
                        </div>
                        <p class="bd-field-desc"><?php esc_html_e( 'Selecciona los rubros asociados. Puedes marcar múltiples categorías y subcategorías.', 'babel-directory' ); ?></p>
                    </div>

                    <!-- Fila 4: Tags / Palabras Clave SEO (12 cols) -->
                    <div class="bd-field-group bd-grid-span-12">
                        <label>🏷️ <?php esc_html_e( 'Tags / Palabras Clave SEO', 'babel-directory' ); ?></label>
                        <div class="bd-tags-wrap" id="bd-tags-wrap">
                            <!-- tag chips dinámicos -->
                            <input
                                type="text"
                                id="bd-tag-input-inline"
                                class="bd-tag-input-inline"
                                placeholder="<?php esc_attr_e( 'Ej: pizzería, delivery, familiar... (Enter o coma para agregar)', 'babel-directory' ); ?>"
                                autocomplete="off"
                            />
                        </div>
                        <input type="hidden" name="babel_biz_tags" id="babel_biz_tags_hidden" value="<?php echo esc_attr( $biz_tags ); ?>" />
                        <p class="bd-field-desc"><?php esc_html_e( 'Palabras clave para SEO y búsqueda interna. Presiona Enter o coma para agregar cada tag.', 'babel-directory' ); ?></p>
                    </div>

                    <!-- Fila 5: Descripción (12 cols) -->
                    <div class="bd-field-group bd-grid-span-12">
                        <label for="_babel_biz_desc"><?php esc_html_e( 'Descripción del Negocio', 'babel-directory' ); ?></label>
                        <textarea id="_babel_biz_desc" name="_babel_biz_desc" rows="6" placeholder="<?php esc_attr_e( 'Describe los productos, servicios y valor agregado del negocio...', 'babel-directory' ); ?>"><?php echo esc_textarea( $biz_desc ); ?></textarea>
                        <p class="bd-field-desc"><?php esc_html_e( 'Información completa del comercio. Admite texto enriquecido básico.', 'babel-directory' ); ?></p>
                    </div>

                    <!-- Fila 6: Subsección de Amenities (span 12) -->
                    <div class="bd-section-title bd-grid-span-12">
                        <span>🌿 Atributos & Amenities del Negocio</span>
                    </div>

                    <!-- Tipo de Negocio -->
                    <div class="bd-field-group bd-grid-span-4">
                        <label for="_babel_biz_type"><?php esc_html_e( 'Tipo de Negocio', 'babel-directory' ); ?></label>
                        <select id="_babel_biz_type" name="babel_biz_type">
                            <option value="physical" <?php selected( $biz_type, 'physical' ); ?>><?php esc_html_e( 'Local físico', 'babel-directory' ); ?></option>
                            <option value="online" <?php selected( $biz_type, 'online' ); ?>><?php esc_html_e( 'Solo online', 'babel-directory' ); ?></option>
                            <option value="hybrid" <?php selected( $biz_type, 'hybrid' ); ?>><?php esc_html_e( 'Híbrido', 'babel-directory' ); ?></option>
                            <option value="mobile" <?php selected( $biz_type, 'mobile' ); ?>><?php esc_html_e( 'Itinerante/Móvil', 'babel-directory' ); ?></option>
                        </select>
                    </div>

                    <!-- Área de Cobertura (solo visible si tipo es itinerante) -->
                    <div class="bd-field-group bd-grid-span-4" id="bd-wrapper-coverage" style="display: none;">
                        <label for="_babel_coverage_area"><?php esc_html_e( 'Área de Cobertura', 'babel-directory' ); ?></label>
                        <input type="text" id="_babel_coverage_area" name="babel_coverage_area" value="<?php echo esc_attr( $coverage_area ); ?>" placeholder="Ej: Región Metropolitana, Valparaíso" />
                        <p class="bd-field-desc"><?php esc_html_e( 'Especifique zonas de servicio.', 'babel-directory' ); ?></p>
                    </div>

                    <!-- Rango de Precios -->
                    <div class="bd-field-group bd-grid-span-4">
                        <label for="_babel_price_range"><?php esc_html_e( 'Rango de Precios', 'babel-directory' ); ?></label>
                        <select id="_babel_price_range" name="babel_price_range">
                            <option value="" <?php selected( $price_range, '' ); ?>>-- Seleccionar --</option>
                            <option value="$" <?php selected( $price_range, '$' ); ?>><?php esc_html_e( '$ Económico', 'babel-directory' ); ?></option>
                            <option value="$$" <?php selected( $price_range, '$$' ); ?>><?php esc_html_e( '$$ Moderado', 'babel-directory' ); ?></option>
                            <option value="$$$" <?php selected( $price_range, '$$$' ); ?>><?php esc_html_e( '$$$ Premium', 'babel-directory' ); ?></option>
                            <option value="$$$$" <?php selected( $price_range, '$$$$' ); ?>><?php esc_html_e( '$$$$ Lujo', 'babel-directory' ); ?></option>
                        </select>
                    </div>

                    <!-- Estacionamiento -->
                    <div class="bd-field-group bd-grid-span-4">
                        <label for="_babel_parking"><?php esc_html_e( 'Estacionamiento', 'babel-directory' ); ?></label>
                        <select id="_babel_parking" name="babel_parking">
                            <option value="" <?php selected( $parking, '' ); ?>>-- Seleccionar --</option>
                            <option value="none" <?php selected( $parking, 'none' ); ?>><?php esc_html_e( 'Sin estacionamiento', 'babel-directory' ); ?></option>
                            <option value="street" <?php selected( $parking, 'street' ); ?>><?php esc_html_e( 'En la calle', 'babel-directory' ); ?></option>
                            <option value="private" <?php selected( $parking, 'private' ); ?>><?php esc_html_e( 'Privado', 'babel-directory' ); ?></option>
                            <option value="valet" <?php selected( $parking, 'valet' ); ?>><?php esc_html_e( 'Valet Parking', 'babel-directory' ); ?></option>
                        </select>
                    </div>

                    <!-- Mascotas Pet Friendly -->
                    <div class="bd-field-group bd-grid-span-4">
                        <label for="_babel_pet_friendly"><?php esc_html_e( 'Mascotas / Pet Friendly', 'babel-directory' ); ?></label>
                        <select id="_babel_pet_friendly" name="babel_pet_friendly">
                            <option value="" <?php selected( $pet_friendly, '' ); ?>>-- Seleccionar --</option>
                            <option value="no" <?php selected( $pet_friendly, 'no' ); ?>><?php esc_html_e( 'No permitido', 'babel-directory' ); ?></option>
                            <option value="terrace_only" <?php selected( $pet_friendly, 'terrace_only' ); ?>><?php esc_html_e( 'Solo en terraza/exterior', 'babel-directory' ); ?></option>
                            <option value="full_access" <?php selected( $pet_friendly, 'full_access' ); ?>><?php esc_html_e( 'Permitido en todo el local', 'babel-directory' ); ?></option>
                        </select>
                    </div>

                    <!-- Accesibilidad Universal -->
                    <div class="bd-field-group bd-grid-span-4">
                        <label for="_babel_accessibility"><?php esc_html_e( 'Accesibilidad Universal', 'babel-directory' ); ?></label>
                        <select id="_babel_accessibility" name="babel_accessibility">
                            <option value="" <?php selected( $accessibility, '' ); ?>>-- Seleccionar --</option>
                            <option value="full" <?php selected( $accessibility, 'full' ); ?>><?php esc_html_e( 'Totalmente accesible', 'babel-directory' ); ?></option>
                            <option value="partial" <?php selected( $accessibility, 'partial' ); ?>><?php esc_html_e( 'Parcialmente accesible', 'babel-directory' ); ?></option>
                            <option value="none" <?php selected( $accessibility, 'none' ); ?>><?php esc_html_e( 'Sin accesibilidad', 'babel-directory' ); ?></option>
                        </select>
                    </div>

                    <!-- Wi-Fi -->
                    <div class="bd-field-group bd-grid-span-4">
                        <label for="_babel_wifi"><?php esc_html_e( 'Wi-Fi', 'babel-directory' ); ?></label>
                        <select id="_babel_wifi" name="babel_wifi">
                            <option value="" <?php selected( $wifi, '' ); ?>>-- Seleccionar --</option>
                            <option value="free" <?php selected( $wifi, 'free' ); ?>><?php esc_html_e( 'Gratuito', 'babel-directory' ); ?></option>
                            <option value="clients_only" <?php selected( $wifi, 'clients_only' ); ?>><?php esc_html_e( 'Solo para clientes', 'babel-directory' ); ?></option>
                            <option value="none" <?php selected( $wifi, 'none' ); ?>><?php esc_html_e( 'No disponible', 'babel-directory' ); ?></option>
                        </select>
                    </div>

                    <!-- Sistema de Reservas -->
                    <div class="bd-field-group bd-grid-span-4">
                        <label for="_babel_reservations"><?php esc_html_e( 'Sistema de Reservas', 'babel-directory' ); ?></label>
                        <select id="_babel_reservations" name="babel_reservations">
                            <option value="" <?php selected( $reservations, '' ); ?>>-- Seleccionar --</option>
                            <option value="required" <?php selected( $reservations, 'required' ); ?>><?php esc_html_e( 'Obligatoria', 'babel-directory' ); ?></option>
                            <option value="recommended" <?php selected( $reservations, 'recommended' ); ?>><?php esc_html_e( 'Recomendada', 'babel-directory' ); ?></option>
                            <option value="not_needed" <?php selected( $reservations, 'not_needed' ); ?>><?php esc_html_e( 'Sin reserva necesaria', 'babel-directory' ); ?></option>
                        </select>
                    </div>

                    <!-- Delivery -->
                    <div class="bd-field-group bd-grid-span-4">
                        <label for="_babel_delivery"><?php esc_html_e( 'Delivery / Despacho', 'babel-directory' ); ?></label>
                        <select id="_babel_delivery" name="babel_delivery">
                            <option value="" <?php selected( $delivery, '' ); ?>>-- Seleccionar --</option>
                            <option value="own" <?php selected( $delivery, 'own' ); ?>><?php esc_html_e( 'Delivery propio', 'babel-directory' ); ?></option>
                            <option value="third_party" <?php selected( $delivery, 'third_party' ); ?>><?php esc_html_e( 'Delivery terceros', 'babel-directory' ); ?></option>
                            <option value="pickup_only" <?php selected( $delivery, 'pickup_only' ); ?>><?php esc_html_e( 'Solo retiro en local', 'babel-directory' ); ?></option>
                            <option value="none" <?php selected( $delivery, 'none' ); ?>><?php esc_html_e( 'Sin delivery', 'babel-directory' ); ?></option>
                        </select>
                    </div>

                    <!-- Número de empleados -->
                    <div class="bd-field-group bd-grid-span-4">
                        <label for="_babel_employees"><?php esc_html_e( 'Número de Empleados', 'babel-directory' ); ?></label>
                        <select id="_babel_employees" name="babel_employees">
                            <option value="" <?php selected( $employees, '' ); ?>>-- Seleccionar --</option>
                            <option value="1-5" <?php selected( $employees, '1-5' ); ?>>1-5</option>
                            <option value="6-20" <?php selected( $employees, '6-20' ); ?>>6-20</option>
                            <option value="21-50" <?php selected( $employees, '21-50' ); ?>>21-50</option>
                            <option value="50+" <?php selected( $employees, '50+' ); ?>>+50</option>
                        </select>
                    </div>

                    <!-- Teléfono Alternativo -->
                    <div class="bd-field-group bd-grid-span-4">
                        <label for="_babel_phone_alt"><?php esc_html_e( 'Teléfono Alternativo', 'babel-directory' ); ?></label>
                        <input type="text" id="_babel_phone_alt" name="babel_phone_alt" value="<?php echo esc_attr( $phone_alt ); ?>" placeholder="Ej: +56 2 2345 6789" />
                    </div>

                    <!-- Email Alternativo -->
                    <div class="bd-field-group bd-grid-span-4">
                        <label for="_babel_email_alt"><?php esc_html_e( 'Email Alternativo', 'babel-directory' ); ?></label>
                        <input type="email" id="_babel_email_alt" name="babel_email_alt" value="<?php echo esc_attr( $email_alt ); ?>" placeholder="Ej: administracion@empresa.cl" />
                    </div>

                    <!-- URL Menú/Carta PDF -->
                    <div class="bd-field-group bd-grid-span-6" id="bd-wrapper-menu" style="display: none;">
                        <label for="_babel_menu_url"><?php esc_html_e( 'URL Menú / Carta PDF', 'babel-directory' ); ?></label>
                        <input type="url" id="_babel_menu_url" name="babel_menu_url" value="<?php echo esc_url( $menu_url ); ?>" placeholder="Ej: https://empresa.cl/menu.pdf" />
                        <p class="bd-field-desc"><?php esc_html_e( 'Visible para rubros gastronómicos.', 'babel-directory' ); ?></p>
                    </div>

                    <!-- URL Reservas Online -->
                    <div class="bd-field-group bd-grid-span-6">
                        <label for="_babel_booking_url"><?php esc_html_e( 'URL Reservas Online', 'babel-directory' ); ?></label>
                        <input type="url" id="_babel_booking_url" name="babel_booking_url" value="<?php echo esc_url( $booking_url ); ?>" placeholder="Ej: https://booksy.com/..." />
                        <p class="bd-field-desc"><?php esc_html_e( 'Enlace a plataforma de reservas (Booksy, Acuity, etc.).', 'babel-directory' ); ?></p>
                    </div>

                    <!-- Métodos de Pago -->
                    <div class="bd-field-group bd-grid-span-12">
                        <label><?php esc_html_e( 'Métodos de Pago Aceptados', 'babel-directory' ); ?></label>
                        <div class="bd-checkbox-group-container">
                            <label class="bd-state-checkbox">
                                <input type="checkbox" name="babel_payments[]" value="cash" <?php checked( in_array( 'cash', $payments ), true ); ?> />
                                <span>💵 <?php esc_html_e( 'Efectivo', 'babel-directory' ); ?></span>
                            </label>
                            <label class="bd-state-checkbox">
                                <input type="checkbox" name="babel_payments[]" value="debit" <?php checked( in_array( 'debit', $payments ), true ); ?> />
                                <span>💳 <?php esc_html_e( 'Débito', 'babel-directory' ); ?></span>
                            </label>
                            <label class="bd-state-checkbox">
                                <input type="checkbox" name="babel_payments[]" value="credit" <?php checked( in_array( 'credit', $payments ), true ); ?> />
                                <span>💳 <?php esc_html_e( 'Crédito', 'babel-directory' ); ?></span>
                            </label>
                            <label class="bd-state-checkbox">
                                <input type="checkbox" name="babel_payments[]" value="transfer" <?php checked( in_array( 'transfer', $payments ), true ); ?> />
                                <span>📲 <?php esc_html_e( 'Transferencia', 'babel-directory' ); ?></span>
                            </label>
                            <label class="bd-state-checkbox">
                                <input type="checkbox" name="babel_payments[]" value="webpay" <?php checked( in_array( 'webpay', $payments ), true ); ?> />
                                <span>🌐 <?php esc_html_e( 'Webpay', 'babel-directory' ); ?></span>
                            </label>
                            <label class="bd-state-checkbox">
                                <input type="checkbox" name="babel_payments[]" value="mercadopago" <?php checked( in_array( 'mercadopago', $payments ), true ); ?> />
                                <span>⚡ <?php esc_html_e( 'MercadoPago', 'babel-directory' ); ?></span>
                            </label>
                        </div>
                    </div>

                    <!-- Espacios -->
                    <div class="bd-field-group bd-grid-span-12">
                        <label><?php esc_html_e( 'Espacios / Instalaciones', 'babel-directory' ); ?></label>
                        <div class="bd-checkbox-group-container">
                            <label class="bd-state-checkbox">
                                <input type="checkbox" name="babel_spaces[]" value="terrace" <?php checked( in_array( 'terrace', $spaces ), true ); ?> />
                                <span>🪑 <?php esc_html_e( 'Terraza exterior', 'babel-directory' ); ?></span>
                            </label>
                            <label class="bd-state-checkbox">
                                <input type="checkbox" name="babel_spaces[]" value="air_conditioned" <?php checked( in_array( 'air_conditioned', $spaces ), true ); ?> />
                                <span>❄️ <?php esc_html_e( 'Aire acondicionado', 'babel-directory' ); ?></span>
                            </label>
                            <label class="bd-state-checkbox">
                                <input type="checkbox" name="babel_spaces[]" value="heating" <?php checked( in_array( 'heating', $spaces ), true ); ?> />
                                <span>🔥 <?php esc_html_e( 'Calefacción', 'babel-directory' ); ?></span>
                            </label>
                            <label class="bd-state-checkbox">
                                <input type="checkbox" name="babel_spaces[]" value="smoking_area" <?php checked( in_array( 'smoking_area', $spaces ), true ); ?> />
                                <span>🚬 <?php esc_html_e( 'Área fumadores', 'babel-directory' ); ?></span>
                            </label>
                            <label class="bd-state-checkbox">
                                <input type="checkbox" name="babel_spaces[]" value="private_room" <?php checked( in_array( 'private_room', $spaces ), true ); ?> />
                                <span>🚪 <?php esc_html_e( 'Sala privada', 'babel-directory' ); ?></span>
                            </label>
                        </div>
                    </div>

                    <!-- Idiomas de Atención -->
                    <div class="bd-field-group bd-grid-span-12">
                        <label><?php esc_html_e( 'Idiomas de Atención', 'babel-directory' ); ?></label>
                        <div class="bd-checkbox-group-container">
                            <label class="bd-state-checkbox">
                                <input type="checkbox" name="babel_languages[]" value="es" <?php checked( in_array( 'es', $languages ), true ); ?> />
                                <span>🇪🇸 <?php esc_html_e( 'Español', 'babel-directory' ); ?></span>
                            </label>
                            <label class="bd-state-checkbox">
                                <input type="checkbox" name="babel_languages[]" value="en" <?php checked( in_array( 'en', $languages ), true ); ?> />
                                <span>🇬🇧 <?php esc_html_e( 'Inglés', 'babel-directory' ); ?></span>
                            </label>
                            <label class="bd-state-checkbox">
                                <input type="checkbox" name="babel_languages[]" value="pt" <?php checked( in_array( 'pt', $languages ), true ); ?> />
                                <span>🇧🇷 <?php esc_html_e( 'Portugués', 'babel-directory' ); ?></span>
                            </label>
                            <label class="bd-state-checkbox">
                                <input type="checkbox" name="babel_languages[]" value="other" <?php checked( in_array( 'other', $languages ), true ); ?> />
                                <span>🗣️ <?php esc_html_e( 'Otro', 'babel-directory' ); ?></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TARJETA 2: CONTACTO -->
            <div class="bd-card">
                <h3 class="bd-card-title">📞 <?php esc_html_e( 'Ubicación y Contacto', 'babel-directory' ); ?></h3>
                <div class="bd-metabox-grid">
                    <!-- Fila 1: Teléfono, WhatsApp, Email (4 cols c/u) -->
                    <div class="bd-field-group bd-grid-span-4">
                        <label for="babel_phone"><?php esc_html_e( 'Teléfono de Contacto', 'babel-directory' ); ?></label>
                        <input type="text" id="babel_phone" name="babel_phone" value="<?php echo esc_attr( $phone ); ?>" placeholder="Ej: +56 9 1234 5678" />
                        <p class="bd-field-desc"><?php esc_html_e( 'Teléfono comercial directo.', 'babel-directory' ); ?></p>
                    </div>

                    <div class="bd-field-group bd-grid-span-4">
                        <label for="babel_whatsapp"><?php esc_html_e( 'WhatsApp', 'babel-directory' ); ?></label>
                        <input type="text" id="babel_whatsapp" name="babel_whatsapp" value="<?php echo esc_attr( $whatsapp ); ?>" placeholder="Ej: +56987654321" />
                        <p class="bd-field-desc"><?php esc_html_e( 'Número directo para chat comercial (sin espacios).', 'babel-directory' ); ?></p>
                    </div>

                    <div class="bd-field-group bd-grid-span-4">
                        <label for="babel_email"><?php esc_html_e( 'Email Comercial', 'babel-directory' ); ?></label>
                        <input type="email" id="babel_email" name="babel_email" value="<?php echo esc_attr( $email ); ?>" placeholder="Ej: contacto@empresa.cl" />
                        <p class="bd-field-desc"><?php esc_html_e( 'Correo para solicitudes de clientes.', 'babel-directory' ); ?></p>
                    </div>

                    <!-- Fila 2: Dirección y Maps (6 cols c/u) -->
                    <div class="bd-field-group bd-grid-span-6">
                        <label for="babel_address"><?php esc_html_e( 'Dirección Física', 'babel-directory' ); ?></label>
                        <input type="text" id="babel_address" name="babel_address" value="<?php echo esc_attr( $address ); ?>" placeholder="Ej: Av. Providencia 1234, Oficina 501" />
                        <p class="bd-field-desc"><?php esc_html_e( 'Ubicación completa del local/oficina.', 'babel-directory' ); ?></p>
                    </div>

                    <div class="bd-field-group bd-grid-span-6">
                        <label for="babel_maps"><?php esc_html_e( 'Enlace de Google Maps', 'babel-directory' ); ?></label>
                        <input type="url" id="babel_maps" name="babel_maps" value="<?php echo esc_url( $maps ); ?>" placeholder="Ej: https://maps.app.goo.gl/..." />
                        <p id="bd-geo-status" class="bd-field-desc bd-geo-idle"><?php esc_html_e( 'Se completa automáticamente al ingresar la dirección.', 'babel-directory' ); ?></p>
                    </div>

                    <!-- Fila 3: Coordenadas GPS (3 cols c/u) y Sitio Web (6 cols) -->
                    <div class="bd-field-group bd-grid-span-3">
                        <label for="babel_lat"><?php esc_html_e( 'Latitud GPS', 'babel-directory' ); ?></label>
                        <input type="text" id="babel_lat" name="babel_lat" value="<?php echo esc_attr( $lat ); ?>" placeholder="Ej: -33.4372" />
                    </div>

                    <div class="bd-field-group bd-grid-span-3">
                        <label for="babel_lng"><?php esc_html_e( 'Longitud GPS', 'babel-directory' ); ?></label>
                        <input type="text" id="babel_lng" name="babel_lng" value="<?php echo esc_attr( $lng ); ?>" placeholder="Ej: -70.6506" />
                    </div>

                    <div class="bd-field-group bd-grid-span-6">
                        <label for="babel_website"><?php esc_html_e( 'Sitio Web', 'babel-directory' ); ?></label>
                        <input type="url" id="babel_website" name="babel_website" value="<?php echo esc_url( $website ); ?>" placeholder="Ej: https://www.negocio.cl" />
                        <p class="bd-field-desc"><?php esc_html_e( 'URL oficial de la empresa.', 'babel-directory' ); ?></p>
                    </div>
                </div>
            </div>

            <!-- TARJETA 3: REDES SOCIALES Y ESTADOS -->
            <div class="bd-card">
                <h3 class="bd-card-title">🌐 <?php esc_html_e( 'Redes y Estados', 'babel-directory' ); ?></h3>
                <div class="bd-metabox-grid">
                    <!-- Fila 1: Instagram, Facebook, LinkedIn (4 cols c/u) -->
                    <div class="bd-field-group bd-grid-span-4">
                        <label for="babel_instagram"><?php esc_html_e( 'Instagram', 'babel-directory' ); ?></label>
                        <input type="url" id="babel_instagram" name="babel_instagram" value="<?php echo esc_url( $instagram ); ?>" placeholder="Ej: https://instagram.com/perfil" />
                    </div>

                    <div class="bd-field-group bd-grid-span-4">
                        <label for="babel_facebook"><?php esc_html_e( 'Facebook', 'babel-directory' ); ?></label>
                        <input type="url" id="babel_facebook" name="babel_facebook" value="<?php echo esc_url( $facebook ); ?>" placeholder="Ej: https://facebook.com/pagina" />
                    </div>

                    <div class="bd-field-group bd-grid-span-4">
                        <label for="babel_linkedin"><?php esc_html_e( 'LinkedIn', 'babel-directory' ); ?></label>
                        <input type="url" id="babel_linkedin" name="babel_linkedin" value="<?php echo esc_url( $linkedin ); ?>" placeholder="Ej: https://linkedin.com/company/empresa" />
                    </div>

                    <!-- Fila 1b: TikTok, YouTube Canal, Twitter/X, Pinterest (3 cols c/u) -->
                    <div class="bd-field-group bd-grid-span-3">
                        <label for="babel_tiktok"><?php esc_html_e( 'TikTok', 'babel-directory' ); ?></label>
                        <input type="url" id="babel_tiktok" name="babel_tiktok" value="<?php echo esc_url( $tiktok ); ?>" placeholder="Ej: https://tiktok.com/@perfil" />
                    </div>

                    <div class="bd-field-group bd-grid-span-3">
                        <label for="babel_youtube_channel"><?php esc_html_e( 'YouTube Canal', 'babel-directory' ); ?></label>
                        <input type="url" id="babel_youtube_channel" name="babel_youtube_channel" value="<?php echo esc_url( $youtube_channel ); ?>" placeholder="Ej: https://youtube.com/@canal" />
                    </div>

                    <div class="bd-field-group bd-grid-span-3">
                        <label for="babel_twitter"><?php esc_html_e( 'Twitter / X', 'babel-directory' ); ?></label>
                        <input type="url" id="babel_twitter" name="babel_twitter" value="<?php echo esc_url( $twitter ); ?>" placeholder="Ej: https://x.com/perfil" />
                    </div>

                    <div class="bd-field-group bd-grid-span-3">
                        <label for="babel_pinterest"><?php esc_html_e( 'Pinterest', 'babel-directory' ); ?></label>
                        <input type="url" id="babel_pinterest" name="babel_pinterest" value="<?php echo esc_url( $pinterest ); ?>" placeholder="Ej: https://pinterest.com/perfil" />
                    </div>

                    <!-- Fila 2: Estados (12 cols) -->
                    <div class="bd-grid-span-12">
                        <div class="bd-states-container">
                            <label class="bd-state-checkbox">
                                <input type="checkbox" id="babel_verified" name="babel_verified" value="1" <?php checked( $verified, '1' ); ?> />
                                <span>✨ <?php esc_html_e( 'Negocio Verificado', 'babel-directory' ); ?></span>
                            </label>

                            <label class="bd-state-checkbox">
                                <input type="checkbox" id="babel_featured" name="babel_featured" value="1" <?php checked( $featured, '1' ); ?> />
                                <span>🔥 <?php esc_html_e( 'Destacar Negocio', 'babel-directory' ); ?></span>
                            </label>

                            <label class="bd-state-checkbox">
                                <input type="checkbox" id="babel_is_institution" name="babel_is_institution" value="1" <?php checked( $is_institution, '1' ); ?> />
                                <span>🏛️ <?php esc_html_e( 'Institución Pública / Emergencias', 'babel-directory' ); ?></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TARJETA 4: HORARIOS Y MEDIOS -->
            <div class="bd-card bd-card-full">
                <h3 class="bd-card-title">⏰ <?php esc_html_e( 'Horarios y Medios', 'babel-directory' ); ?></h3>
                <div class="bd-metabox-grid">
                    <!-- Fila 1: Horarios Día por Día (12 cols) -->
                    <div class="bd-field-group bd-grid-span-12">
                        <label><?php esc_html_e( 'Horarios de Atención', 'babel-directory' ); ?></label>
                        <div class="bd-hours-container">
                            <?php foreach ( $days_of_week as $day ) : 
                                $day_open   = isset( $hours[ $day ]['open'] ) ? $hours[ $day ]['open'] : '09:00';
                                $day_close  = isset( $hours[ $day ]['close'] ) ? $hours[ $day ]['close'] : '18:00';
                                $day_closed = isset( $hours[ $day ]['closed'] ) && $hours[ $day ]['closed'];
                                ?>
                                <div class="bd-hours-row">
                                    <span class="bd-hours-day"><?php echo esc_html( $day ); ?></span>
                                    <div class="bd-hours-inputs">
                                        <input type="time" name="babel_hours[<?php echo esc_attr( $day ); ?>][open]" value="<?php echo esc_attr( $day_open ); ?>" />
                                        <span><?php esc_html_e( 'a', 'babel-directory' ); ?></span>
                                        <input type="time" name="babel_hours[<?php echo esc_attr( $day ); ?>][close]" value="<?php echo esc_attr( $day_close ); ?>" />
                                    </div>
                                    <label class="bd-hours-closed-label">
                                        <input type="checkbox" class="bd-hours-closed-checkbox" name="babel_hours[<?php echo esc_attr( $day ); ?>][closed]" value="1" <?php checked( $day_closed, true ); ?> />
                                        <span><?php esc_html_e( 'Cerrado', 'babel-directory' ); ?></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Fila 2: Logotipo (4 cols) y Galería (8 cols) -->
                    <div class="bd-field-group bd-grid-span-4">
                        <label><?php esc_html_e( 'Imagen Principal / Logotipo', 'babel-directory' ); ?></label>
                        <div class="bd-media-upload-container">
                            <div class="bd-media-preview-box" id="bd-logo-preview">
                                <?php if ( $logo_url ) : ?>
                                    <img src="<?php echo esc_url( $logo_url ); ?>" alt="Preview" />
                                <?php else : ?>
                                    <span class="bd-media-placeholder-icon">🏢</span>
                                <?php endif; ?>
                            </div>
                            <div class="bd-media-actions">
                                <input type="hidden" id="babel_logo_id" name="babel_logo_id" value="<?php echo esc_attr( $logo_id ); ?>" />
                                <button type="button" class="button bd-btn-primary" id="bd-select-logo-btn">
                                    <?php esc_html_e( 'Seleccionar Imagen', 'babel-directory' ); ?>
                                </button>
                                <button type="button" class="button bd-btn-danger" id="bd-remove-logo-btn" style="<?php echo $logo_url ? '' : 'display: none;'; ?>">
                                    <?php esc_html_e( 'Eliminar', 'babel-directory' ); ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="bd-field-group bd-grid-span-8 bd-gallery-container">
                        <label><?php esc_html_e( 'Galería de Fotos Múltiple', 'babel-directory' ); ?></label>
                        <input type="hidden" id="babel_gallery" name="babel_gallery" value="<?php echo esc_attr( $gallery ); ?>" />
                        <div class="bd-gallery-grid" id="bd-gallery-grid">
                            <?php foreach ( $gallery_ids as $img_id ) : 
                                $img_url = wp_get_attachment_image_url( $img_id, 'thumbnail' );
                                if ( ! $img_url ) {
                                    continue;
                                }
                                ?>
                                <div class="bd-gallery-item" data-id="<?php echo esc_attr( $img_id ); ?>">
                                    <img src="<?php echo esc_url( $img_url ); ?>" alt="Thumbnail" />
                                    <button type="button" class="bd-remove-gallery-item">&times;</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="button bd-btn-primary" id="bd-add-gallery-btn" style="align-self: flex-start;">
                            📸 <?php esc_html_e( 'Añadir Fotos', 'babel-directory' ); ?>
                        </button>
                    </div>

                    <!-- Fila 3: Videos (6 cols c/u) -->
                    <div class="bd-field-group bd-grid-span-6">
                        <label for="babel_youtube_url"><?php esc_html_e( 'URL Video YouTube Principal', 'babel-directory' ); ?></label>
                        <input type="url" id="babel_youtube_url" name="babel_youtube_url" value="<?php echo esc_url( $youtube_url ); ?>" placeholder="Ej: https://www.youtube.com/watch?v=..." />
                        <p class="bd-field-desc"><?php esc_html_e( 'El video se embeberá automáticamente en el perfil.', 'babel-directory' ); ?></p>
                    </div>

                    <div class="bd-field-group bd-grid-span-6">
                        <label for="babel_youtube_url_2"><?php esc_html_e( 'URL Video YouTube 2 (Opcional)', 'babel-directory' ); ?></label>
                        <input type="url" id="babel_youtube_url_2" name="babel_youtube_url_2" value="<?php echo esc_url($youtube_url_2); ?>" placeholder="Ej: https://www.youtube.com/watch?v=..." />
                        <p class="bd-field-desc"><?php esc_html_e( 'Segundo video para la galería multimedia.', 'babel-directory' ); ?></p>
                    </div>
                </div>
            </div>

            <!-- TARJETA 5: LEGAL & COMERCIAL -->
            <div class="bd-card">
                <h3 class="bd-card-title">🇨🇱 <?php esc_html_e( 'Legal & Comercial', 'babel-directory' ); ?></h3>
                <div class="bd-metabox-grid">
                    <div class="bd-section-title bd-grid-span-12">
                        <span>🇨🇱 Identidad Legal (Chile)</span>
                    </div>

                    <!-- RUT -->
                    <div class="bd-field-group bd-grid-span-4">
                        <label for="_babel_rut"><?php esc_html_e( 'RUT de la Empresa', 'babel-directory' ); ?></label>
                        <input type="text" id="_babel_rut" name="babel_rut" value="<?php echo esc_attr( $rut ); ?>" placeholder="Ej: 76.123.456-K" />
                        <p class="bd-field-desc"><?php esc_html_e( 'RUT de la empresa (auto-formato).', 'babel-directory' ); ?></p>
                    </div>

                    <!-- Razón Social -->
                    <div class="bd-field-group bd-grid-span-8">
                        <label for="_babel_razon_social"><?php esc_html_e( 'Razón Social', 'babel-directory' ); ?></label>
                        <input type="text" id="_babel_razon_social" name="babel_razon_social" value="<?php echo esc_attr( $razon_social ); ?>" placeholder="Ej: Comercializadora y Servicios Limitada" />
                        <p class="bd-field-desc"><?php esc_html_e( 'Nombre legal registrado ante el SII.', 'babel-directory' ); ?></p>
                    </div>

                    <!-- Nombre Comercial -->
                    <div class="bd-field-group bd-grid-span-6">
                        <label for="_babel_nombre_comercial"><?php esc_html_e( 'Nombre Comercial', 'babel-directory' ); ?></label>
                        <input type="text" id="_babel_nombre_comercial" name="babel_nombre_comercial" value="<?php echo esc_attr( $nombre_comercial ); ?>" placeholder="Ej: Cafetería Central" />
                        <p class="bd-field-desc"><?php esc_html_e( 'Nombre de fantasía o marca comercial.', 'babel-directory' ); ?></p>
                    </div>

                    <!-- Giro Comercial -->
                    <div class="bd-field-group bd-grid-span-6">
                        <label for="_babel_giro"><?php esc_html_e( 'Giro Comercial SII', 'babel-directory' ); ?></label>
                        <input type="text" id="_babel_giro" name="babel_giro" value="<?php echo esc_attr( $giro ); ?>" placeholder="Ej: Venta de café y pastelería" />
                        <p class="bd-field-desc"><?php esc_html_e( 'Actividad económica declarada.', 'babel-directory' ); ?></p>
                    </div>

                    <!-- Patente Municipal -->
                    <div class="bd-field-group bd-grid-span-4">
                        <label for="_babel_patente"><?php esc_html_e( 'Patente Municipal', 'babel-directory' ); ?></label>
                        <input type="text" id="_babel_patente" name="babel_patente" value="<?php echo esc_attr( $patente ); ?>" placeholder="Ej: 12345-A" />
                        <p class="bd-field-desc"><?php esc_html_e( 'Número de patente municipal.', 'babel-directory' ); ?></p>
                    </div>

                    <!-- Representante Legal -->
                    <div class="bd-field-group bd-grid-span-5">
                        <label for="_babel_rep_legal"><?php esc_html_e( 'Representante Legal', 'babel-directory' ); ?></label>
                        <input type="text" id="_babel_rep_legal" name="babel_rep_legal" value="<?php echo esc_attr( $rep_legal ); ?>" placeholder="Ej: Juan Pérez Muñoz" />
                        <p class="bd-field-desc"><?php esc_html_e( 'Persona natural representante de la empresa.', 'babel-directory' ); ?></p>
                    </div>

                    <!-- Año de Fundación -->
                    <div class="bd-field-group bd-grid-span-3">
                        <label for="_babel_founded_year"><?php esc_html_e( 'Año de Fundación', 'babel-directory' ); ?></label>
                        <input type="number" id="_babel_founded_year" name="babel_founded_year" min="1800" max="2100" value="<?php echo esc_attr( $founded_year ); ?>" placeholder="Ej: 2015" />
                        <p class="bd-field-desc"><?php esc_html_e( 'Año de inicio (4 dígitos).', 'babel-directory' ); ?></p>
                    </div>
                </div>
            </div>

            </div> <!-- Cierre bd-dashboard-wrapper -->
        </div>

        <script>
            jQuery(document).ready(function($) {

                // --- MANEJO DE LOGOTIPO / IMAGEN DESTACADA ---
                var logoFrame;
                $('#bd-select-logo-btn').on('click', function(e) {
                    e.preventDefault();
                    if (logoFrame) {
                        logoFrame.open();
                        return;
                    }
                    logoFrame = wp.media({
                        title: 'Seleccionar Logotipo o Imagen Principal',
                        button: {
                            text: 'Usar como Imagen Principal'
                        },
                        multiple: false
                    });
                    logoFrame.on('select', function() {
                        var attachment = logoFrame.state().get('selection').first().toJSON();
                        $('#babel_logo_id').val(attachment.id);
                        var imageUrl = (attachment.sizes && attachment.sizes.thumbnail) ? attachment.sizes.thumbnail.url : attachment.url;
                        $('#bd-logo-preview').html('<img src="' + imageUrl + '" />');
                        $('#bd-remove-logo-btn').show();
                    });
                    logoFrame.open();
                });

                $('#bd-remove-logo-btn').on('click', function(e) {
                    e.preventDefault();
                    $('#babel_logo_id').val('0');
                    $('#bd-logo-preview').html('<span class="bd-media-placeholder-icon">🏢</span>');
                    $(this).hide();
                });

                // --- MANEJO DE GALERÍA DE FOTOS ---
                var galleryFrame;
                $('#bd-add-gallery-btn').on('click', function(e) {
                    e.preventDefault();
                    if (galleryFrame) {
                        galleryFrame.open();
                        return;
                    }
                    galleryFrame = wp.media({
                        title: 'Añadir Fotos a la Galería',
                        button: {
                            text: 'Añadir a la Galería'
                        },
                        multiple: true
                    });
                    galleryFrame.on('select', function() {
                        var selection = galleryFrame.state().get('selection');
                        var currentIds = $('#babel_gallery').val() ? $('#babel_gallery').val().split(',') : [];
                        
                        selection.each(function(attachment) {
                            attachment = attachment.toJSON();
                            if (currentIds.indexOf(attachment.id.toString()) === -1) {
                                currentIds.push(attachment.id);
                                var imgUrl = (attachment.sizes && attachment.sizes.thumbnail) ? attachment.sizes.thumbnail.url : attachment.url;
                                $('#bd-gallery-grid').append(
                                    '<div class="bd-gallery-item" data-id="' + attachment.id + '">' +
                                        '<img src="' + imgUrl + '" />' +
                                        '<button type="button" class="bd-remove-gallery-item">&times;</button>' +
                                    '</div>'
                                );
                            }
                        });
                        $('#babel_gallery').val(currentIds.join(','));
                    });
                    galleryFrame.open();
                });

                $(document).on('click', '.bd-remove-gallery-item', function(e) {
                    e.preventDefault();
                    var $item = $(this).closest('.bd-gallery-item');
                    var idToRemove = $item.data('id').toString();
                    $item.remove();
                    
                    var currentIds = $('#babel_gallery').val() ? $('#babel_gallery').val().split(',') : [];
                    var index = currentIds.indexOf(idToRemove);
                    if (index > -1) {
                        currentIds.splice(index, 1);
                    }
                    $('#babel_gallery').val(currentIds.join(','));
                });

                // Habilitar ordenación por Drag-and-Drop (Sortable UI)
                var $galleryGrid = $('#bd-gallery-grid');
                if ($galleryGrid.length && $.fn.sortable) {
                    $galleryGrid.sortable({
                        items: '.bd-gallery-item',
                        cursor: 'move',
                        opacity: 0.8,
                        update: function(event, ui) {
                            var ids = [];
                            $galleryGrid.find('.bd-gallery-item').each(function() {
                                ids.push($(this).data('id'));
                            });
                            $('#babel_gallery').val(ids.join(','));
                        }
                    });
                }

                // --- CATEGORÍAS CHECKLIST SELECTION STYLING (sync with autocomplete) ---
                function bdSyncChecklistHighlights() {
                    $('.bd-category-checklist input[type="checkbox"]').each(function() {
                        if ($(this).is(':checked')) {
                            $(this).closest('li').addClass('checked');
                        } else {
                            $(this).closest('li').removeClass('checked');
                        }
                    });
                }
                bdSyncChecklistHighlights();

                $(document).on('change', '.bd-category-checklist input[type="checkbox"]', function() {
                    bdSyncChecklistHighlights();
                });

                // ===================================================
                // PREDICTIVE CATEGORY SEARCH (WooCommerce style)
                // ===================================================
                var bdAllCats = <?php
                    // Build JS array from all_categories with parent name lookup
                    $cats_js = array();
                    $term_map = array(); // id => name
                    foreach ( $all_categories as $term ) {
                        $term_map[ $term->term_id ] = $term->name;
                    }
                    foreach ( $all_categories as $term ) {
                        $parent_name = '';
                        if ( $term->parent && isset( $term_map[ $term->parent ] ) ) {
                            $parent_name = $term_map[ $term->parent ];
                        }
                        $cats_js[] = array(
                            'id'     => $term->term_id,
                            'name'   => $term->name,
                            'parent' => $parent_name,
                            'slug'   => $term->slug,
                        );
                    }
                    echo wp_json_encode( $cats_js );
                ?>;

                var bdSelectedCatIds = <?php echo wp_json_encode( array_values( array_map( 'intval', (array) $assigned_cat_ids ) ) ); ?>;

                // Render initial chips for pre-assigned categories
                function bdRenderCatChips() {
                    $('#bd-cat-search-wrap .bd-cat-chip').remove();
                    bdSelectedCatIds.forEach(function(id) {
                        var cat = bdAllCats.find(function(c){ return c.id === id; });
                        if (!cat) return;
                        var chip = $('<span class="bd-cat-chip" data-id="' + id + '">' +
                            '<span>' + cat.name + '</span>' +
                            '<button type="button" class="bd-cat-chip-x" aria-label="Quitar">&times;</button>' +
                        '</span>');
                        $('#bd-cat-search-input').before(chip);
                    });
                }
                bdRenderCatChips();

                // Sync checkboxes to match bdSelectedCatIds
                function bdSyncCheckboxes() {
                    $('.bd-category-checklist input[type="checkbox"]').each(function() {
                        var id = parseInt($(this).val(), 10);
                        if (bdSelectedCatIds.indexOf(id) !== -1) {
                            $(this).prop('checked', true);
                        } else {
                            $(this).prop('checked', false);
                        }
                    });
                    bdSyncChecklistHighlights();
                    if (typeof toggleMenuVisibility === 'function') {
                        toggleMenuVisibility();
                    }
                }

                function bdAddCategory(id) {
                    if (bdSelectedCatIds.indexOf(id) === -1) {
                        bdSelectedCatIds.push(id);
                        bdRenderCatChips();
                        bdSyncCheckboxes();
                    }
                }

                function bdRemoveCategory(id) {
                    bdSelectedCatIds = bdSelectedCatIds.filter(function(i){ return i !== id; });
                    bdRenderCatChips();
                    bdSyncCheckboxes();
                }

                // Chip X click
                $(document).on('click', '#bd-cat-search-wrap .bd-cat-chip-x', function() {
                    var id = parseInt($(this).closest('.bd-cat-chip').data('id'), 10);
                    bdRemoveCategory(id);
                });

                // Checklist checkbox change -> sync chips
                $(document).on('change', '.bd-category-checklist input[type="checkbox"]', function() {
                    var id = parseInt($(this).val(), 10);
                    if ($(this).is(':checked')) {
                        bdAddCategory(id);
                    } else {
                        bdRemoveCategory(id);
                    }
                });

                // Build dropdown
                function bdBuildDropdown(query) {
                    var $dd = $('#bd-cat-dropdown');
                    $dd.empty();
                    var q = query.toLowerCase().trim();
                    if (q.length < 1) { $dd.hide(); return; }

                    var results = bdAllCats.filter(function(c) {
                        return c.name.toLowerCase().indexOf(q) !== -1 ||
                               c.parent.toLowerCase().indexOf(q) !== -1;
                    });

                    if (results.length === 0) {
                        $dd.append('<div class="bd-cat-dropdown-empty">Sin resultados para "' + query + '"</div>');
                    } else {
                        results.forEach(function(cat) {
                            var isSelected = bdSelectedCatIds.indexOf(cat.id) !== -1;
                            var parentHtml = cat.parent ? '<span class="bd-cat-parent-path">' + cat.parent + ' &rsaquo;</span>' : '';
                            var checkmark = isSelected ? '<span class="bd-cat-dropdown-checkmark">&#10003;</span>' : '<span class="bd-cat-dropdown-checkmark"></span>';
                            var cls = isSelected ? 'bd-cat-dropdown-item is-selected' : 'bd-cat-dropdown-item';
                            var item = $('<div class="' + cls + '" data-id="' + cat.id + '">' +
                                '<span>' + parentHtml + cat.name + '</span>' +
                                checkmark +
                            '</div>');
                            $dd.append(item);
                        });
                    }
                    $dd.show();
                }

                $('#bd-cat-search-input').on('input', function() {
                    bdBuildDropdown($(this).val());
                }).on('keydown', function(e) {
                    if (e.key === 'Escape') { $('#bd-cat-dropdown').hide(); $(this).val(''); }
                });

                $(document).on('click', '#bd-cat-dropdown .bd-cat-dropdown-item', function() {
                    var id = parseInt($(this).data('id'), 10);
                    if (bdSelectedCatIds.indexOf(id) !== -1) {
                        bdRemoveCategory(id);
                    } else {
                        bdAddCategory(id);
                    }
                    bdBuildDropdown($('#bd-cat-search-input').val());
                });

                // Click outside closes dropdown
                $(document).on('click', function(e) {
                    if (!$(e.target).closest('#bd-cat-autocomplete').length) {
                        $('#bd-cat-dropdown').hide();
                        $('#bd-cat-search-input').val('');
                    }
                });

                // Focus input when clicking the wrap
                $('#bd-cat-search-wrap').on('click', function() {
                    $('#bd-cat-search-input').focus();
                });

                // ===================================================
                // TAGS SEO CHIP INPUT
                // ===================================================
                var bdTags = [];
                var rawTags = $('#babel_biz_tags_hidden').val();
                if (rawTags && rawTags.trim() !== '') {
                    bdTags = rawTags.split(',').map(function(t){ return t.trim(); }).filter(function(t){ return t.length > 0; });
                }

                function bdRenderTags() {
                    $('#bd-tags-wrap .bd-tag-chip').remove();
                    bdTags.forEach(function(tag, idx) {
                        var chip = $('<span class="bd-tag-chip" data-idx="' + idx + '">' +
                            '<span>' + $('<div/>').text(tag).html() + '</span>' +
                            '<button type="button" class="bd-tag-chip-x" aria-label="Quitar">&times;</button>' +
                        '</span>');
                        $('#bd-tag-input-inline').before(chip);
                    });
                    $('#babel_biz_tags_hidden').val(bdTags.join(','));
                }
                bdRenderTags();

                function bdAddTag(rawVal) {
                    var tags = rawVal.split(',');
                    tags.forEach(function(t) {
                        t = t.trim().toLowerCase().replace(/\s+/g, '-');
                        if (t.length > 0 && bdTags.indexOf(t) === -1) {
                            bdTags.push(t);
                        }
                    });
                    bdRenderTags();
                    $('#bd-tag-input-inline').val('');
                }

                $('#bd-tag-input-inline').on('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        bdAddTag($(this).val());
                    }
                }).on('keypress', function(e) {
                    if (e.which === 44) { // coma
                        e.preventDefault();
                        bdAddTag($(this).val());
                    }
                }).on('blur', function() {
                    if ($(this).val().trim().length > 0) {
                        bdAddTag($(this).val());
                    }
                });

                $(document).on('click', '#bd-tags-wrap .bd-tag-chip-x', function() {
                    var idx = parseInt($(this).closest('.bd-tag-chip').data('idx'), 10);
                    bdTags.splice(idx, 1);
                    bdRenderTags();
                });

                $('#bd-tags-wrap').on('click', function() {
                    $('#bd-tag-input-inline').focus();
                });

                // --- MANEJO DE SECCIÓN DE HORARIOS (DESACTIVAR AL MARCAR CERRADO) ---
                $('.bd-hours-closed-checkbox').each(function() {
                    var $row = $(this).closest('.bd-hours-row');
                    if ($(this).is(':checked')) {
                        $row.find('input[type="time"]').prop('disabled', true).css('opacity', '0.5');
                    }
                });

                $(document).on('change', '.bd-hours-closed-checkbox', function() {
                    var $row = $(this).closest('.bd-hours-row');
                    if ($(this).is(':checked')) {
                        $row.find('input[type="time"]').prop('disabled', true).css('opacity', '0.5');
                    } else {
                        $row.find('input[type="time"]').prop('disabled', false).css('opacity', '1');
                    }
                });

                // ===================================================
                // AUTO-GEOCODIFICACIÓN POR DIRECCIÓN (Nominatim / OpenStreetMap)
                // Sin costo, sin API Key requerida.
                // ===================================================
                var bdGeoTimer = null;

                function bdSetGeoStatus(msg, cls) {
                    var $s = $('#bd-geo-status');
                    $s.text(msg).removeClass('bd-geo-idle bd-geo-loading bd-geo-ok bd-geo-warn bd-geo-error').addClass(cls);
                }

                function bdGeocode(address) {
                    if (!address || address.length < 8) return;

                    bdSetGeoStatus('⏳ Buscando ubicación…', 'bd-geo-loading');
                    $('#babel_maps').removeClass('geo-ok geo-warn').addClass('geo-loading');

                    // Auto-rellenar Maps URL inmediatamente (funciona sin API)
                    var encodedAddr = encodeURIComponent(address);
                    var mapsEmbedUrl = 'https://maps.google.com/maps?q=' + encodedAddr + '&t=&z=15&ie=UTF8&iwloc=&output=embed';
                    // Solo reemplazar si el campo está vacío o era un embed autogenerado prev.
                    var currentMaps = $('#babel_maps').val();
                    if (!currentMaps || currentMaps.indexOf('maps.google.com/maps?q=') !== -1) {
                        $('#babel_maps').val(mapsEmbedUrl);
                    }

                    // Geocoding con Nominatim para lat/lng preciso
                    $.ajax({
                        url: 'https://nominatim.openstreetmap.org/search',
                        method: 'GET',
                        data: {
                            q: address,
                            format: 'json',
                            limit: 1,
                            countrycodes: 'cl',
                            addressdetails: 1
                        },
                        headers: { 'Accept-Language': 'es' },
                        success: function(data) {
                            $('#babel_maps').removeClass('geo-loading');
                            if (data && data.length > 0) {
                                var r = data[0];
                                var lat = parseFloat(r.lat).toFixed(6);
                                var lng = parseFloat(r.lon).toFixed(6);

                                // Rellenar lat/lng
                                if (!$('#babel_lat').val() || $('#babel_lat').data('auto')) {
                                    $('#babel_lat').val(lat).data('auto', true);
                                }
                                if (!$('#babel_lng').val() || $('#babel_lng').data('auto')) {
                                    $('#babel_lng').val(lng).data('auto', true);
                                }

                                // Actualizar URL de Maps con coordenadas exactas
                                var preciseUrl = 'https://maps.google.com/maps?q=' + lat + ',' + lng + '&t=&z=17&ie=UTF8&iwloc=&output=embed';
                                if (!$('#babel_maps').val() || $('#babel_maps').val().indexOf('maps.google.com/maps?q=') !== -1) {
                                    $('#babel_maps').val(preciseUrl);
                                }

                                $('#babel_maps').addClass('geo-ok');
                                var displayName = r.display_name ? r.display_name.substring(0, 70) : address;
                                bdSetGeoStatus('✅ Encontrado: ' + displayName + (r.display_name && r.display_name.length > 70 ? '…' : ''), 'bd-geo-ok');
                            } else {
                                $('#babel_maps').addClass('geo-warn');
                                bdSetGeoStatus('⚠️ No se encontró la ubicación exacta. Verifica la dirección o usa el campo Manual.', 'bd-geo-warn');
                            }
                        },
                        error: function() {
                            $('#babel_maps').removeClass('geo-loading').addClass('geo-warn');
                            bdSetGeoStatus('⚠️ Error de conexión. El campo de Maps se prellenó con la dirección ingresada.', 'bd-geo-error');
                        }
                    });
                }

                // Disparar geocodificación al editar la dirección (con debounce de 900ms)
                $('#babel_address').on('input', function() {
                    clearTimeout(bdGeoTimer);
                    var addr = $(this).val().trim();
                    bdGeoTimer = setTimeout(function() {
                        bdGeocode(addr);
                    }, 900);
                });

                // Si ya hay dirección cargada pero no hay lat/lng, geocodificar al iniciar
                (function() {
                    var hasAddr = $('#babel_address').val().trim().length > 8;
                    var hasLat  = $('#babel_lat').val().trim().length > 0;
                    var hasMaps = $('#babel_maps').val().trim().length > 0;
                    if (hasAddr && !hasLat && !hasMaps) {
                        bdGeocode($('#babel_address').val().trim());
                    }
                })();

                // ===================================================
                // AUTO-FORMATO RUT CHILE
                // ===================================================
                $('#_babel_rut').on('input', function() {
                    var rut = $(this).val().replace(/[^0-9kK]/g, '');
                    if (rut.length > 9) {
                        rut = rut.substring(0, 9);
                    }
                    if (rut.length <= 1) {
                        $(this).val(rut.toUpperCase());
                        return;
                    }
                    var dv = rut.substring(rut.length - 1).toUpperCase();
                    var cuerpo = rut.substring(0, rut.length - 1);
                    
                    var formatted = '';
                    var i = cuerpo.length;
                    while (i > 3) {
                        formatted = '.' + cuerpo.substring(i - 3, i) + formatted;
                        i -= 3;
                    }
                    formatted = cuerpo.substring(0, i) + formatted + '-' + dv;
                    $(this).val(formatted);
                });

                // ===================================================
                // VISIBILIDAD CONDICIONAL: ÁREA DE COBERTURA
                // ===================================================
                function toggleCoverageVisibility() {
                    var bizType = $('#_babel_biz_type').val();
                    if (bizType === 'mobile') {
                        $('#bd-wrapper-coverage').slideDown(200);
                    } else {
                        $('#bd-wrapper-coverage').slideUp(200);
                    }
                }
                $('#_babel_biz_type').on('change', toggleCoverageVisibility);
                toggleCoverageVisibility();

                // ===================================================
                // VISIBILIDAD CONDICIONAL: MENÚ/CARTA PDF
                // ===================================================
                var gastroKeywords = ['restaurante', 'cafetería', 'cafeteria', 'gastronomía', 'gastronomia', 'comida', 'bar', 'pub', 'pastelería', 'pasteleria', 'heladería', 'heladeria', 'gourmet', 'fuente de soda', 'pizzería', 'pizzeria', 'shack'];
                
                function toggleMenuVisibility() {
                    var isGastronomy = false;
                    $('.bd-category-checklist input[type="checkbox"]:checked').each(function() {
                        var labelText = $(this).closest('label').text().toLowerCase();
                        gastroKeywords.forEach(function(keyword) {
                            if (labelText.indexOf(keyword) !== -1) {
                                isGastronomy = true;
                            }
                        });
                    });
                    
                    if (isGastronomy) {
                        $('#bd-wrapper-menu').slideDown(200);
                    } else {
                        $('#bd-wrapper-menu').slideUp(200);
                    }
                }
                
                $(document).on('change', '.bd-category-checklist input[type="checkbox"]', function() {
                    toggleMenuVisibility();
                });
                
                setTimeout(toggleMenuVisibility, 300);
            });
        </script>
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

        // 4. Mapear Nombre y Descripción a campos nativos del Post sin loops infinitos
        if ( isset( $_POST['_babel_biz_name'] ) || isset( $_POST['_babel_biz_desc'] ) ) {
            $updated_title = isset( $_POST['_babel_biz_name'] ) ? sanitize_text_field( wp_unslash( $_POST['_babel_biz_name'] ) ) : $post->post_title;
            $updated_desc  = isset( $_POST['_babel_biz_desc'] ) ? sanitize_textarea_field( wp_unslash( $_POST['_babel_biz_desc'] ) ) : $post->post_content;

            if ( $updated_title !== $post->post_title || $updated_desc !== $post->post_content ) {
                remove_action( 'save_post_babel_business', array( $this, 'save_business_meta' ), 10 );
                
                wp_update_post( array(
                    'ID'           => $post_id,
                    'post_title'   => $updated_title,
                    'post_content' => $updated_desc,
                ) );
                
                add_action( 'save_post_babel_business', array( $this, 'save_business_meta' ), 10, 2 );
            }
        }

        // 5. Sanitizar y guardar cada campo personalizado de forma individual

        // Teléfono
        if ( isset( $_POST['babel_phone'] ) ) {
            $phone = sanitize_text_field( wp_unslash( $_POST['babel_phone'] ) );
            update_post_meta( $post_id, '_babel_phone', $phone );
            update_post_meta( $post_id, '_bd_telefono', $phone );
        }

        // WhatsApp
        if ( isset( $_POST['babel_whatsapp'] ) ) {
            $whatsapp = sanitize_text_field( wp_unslash( $_POST['babel_whatsapp'] ) );
            $whatsapp = preg_replace( '/[^0-9+]/', '', $whatsapp ); // Permitir sólo números y '+'
            update_post_meta( $post_id, '_babel_whatsapp', $whatsapp );
            update_post_meta( $post_id, '_bd_whatsapp', $whatsapp );
        }

        // Email Comercial
        if ( isset( $_POST['babel_email'] ) ) {
            $email = sanitize_email( wp_unslash( $_POST['babel_email'] ) );
            update_post_meta( $post_id, '_babel_email', $email );
            update_post_meta( $post_id, '_bd_email', $email );
        }

        // Dirección Física
        if ( isset( $_POST['babel_address'] ) ) {
            $address = sanitize_text_field( wp_unslash( $_POST['babel_address'] ) );
            update_post_meta( $post_id, '_babel_address', $address );
            update_post_meta( $post_id, '_bd_direccion', $address );
        }

        // Enlace de Google Maps y duplicación por retrocompatibilidad
        if ( isset( $_POST['babel_maps'] ) ) {
            $maps = esc_url_raw( wp_unslash( $_POST['babel_maps'] ) );
            update_post_meta( $post_id, '_babel_maps', $maps );
            update_post_meta( $post_id, '_babel_gmaps', $maps );
            update_post_meta( $post_id, '_bd_gmaps', $maps );
        }

        // Coordenadas Lat/Lng y duplicados
        if ( isset( $_POST['babel_lat'] ) ) {
            $lat = sanitize_text_field( wp_unslash( $_POST['babel_lat'] ) );
            update_post_meta( $post_id, '_babel_lat', $lat );
            update_post_meta( $post_id, '_babel_latitude', $lat );
            update_post_meta( $post_id, '_bd_latitud', $lat );
        }
        if ( isset( $_POST['babel_lng'] ) ) {
            $lng = sanitize_text_field( wp_unslash( $_POST['babel_lng'] ) );
            update_post_meta( $post_id, '_babel_lng', $lng );
            update_post_meta( $post_id, '_babel_longitude', $lng );
            update_post_meta( $post_id, '_bd_longitud', $lng );
        }

        // Redes Sociales y Web
        if ( isset( $_POST['babel_website'] ) ) {
            $website = esc_url_raw( wp_unslash( $_POST['babel_website'] ) );
            update_post_meta( $post_id, '_babel_website', $website );
            update_post_meta( $post_id, '_bd_sitio_web', $website );
            update_post_meta( $post_id, '_bd_web', $website );
        }
        if ( isset( $_POST['babel_instagram'] ) ) {
            update_post_meta( $post_id, '_babel_instagram', esc_url_raw( wp_unslash( $_POST['babel_instagram'] ) ) );
        }
        if ( isset( $_POST['babel_facebook'] ) ) {
            update_post_meta( $post_id, '_babel_facebook', esc_url_raw( wp_unslash( $_POST['babel_facebook'] ) ) );
        }
        if ( isset( $_POST['babel_linkedin'] ) ) {
            update_post_meta( $post_id, '_babel_linkedin', esc_url_raw( wp_unslash( $_POST['babel_linkedin'] ) ) );
        }

        // Categorías (WooCommerce style checklist saving)
        if ( isset( $_POST['tax_input']['babel_category'] ) ) {
            $categories = array_map( 'intval', (array) $_POST['tax_input']['babel_category'] );
            $categories = array_filter( $categories );
            wp_set_object_terms( $post_id, $categories, 'babel_category' );
        } else {
            // Si el checklist se envía vacío (ninguna categoría seleccionada)
            wp_set_object_terms( $post_id, array(), 'babel_category' );
        }

        // Imagen Principal / Logotipo (Post Thumbnail / Destacada)
        if ( isset( $_POST['babel_logo_id'] ) ) {
            $logo_id = intval( $_POST['babel_logo_id'] );
            update_post_meta( $post_id, '_bd_logo_id', $logo_id );
            if ( $logo_id > 0 ) {
                set_post_thumbnail( $post_id, $logo_id );
            } else {
                delete_post_thumbnail( $post_id );
            }
        }

        // Galería de fotos
        if ( isset( $_POST['babel_gallery'] ) ) {
            $gallery = sanitize_text_field( wp_unslash( $_POST['babel_gallery'] ) );
            update_post_meta( $post_id, '_babel_gallery', $gallery );
            update_post_meta( $post_id, '_bd_galeria', $gallery );
        }

        // Horarios tipo rueda en JSON
        if ( isset( $_POST['babel_hours'] ) && is_array( $_POST['babel_hours'] ) ) {
            $hours_data = array();
            foreach ( $_POST['babel_hours'] as $day => $data ) {
                $hours_data[ $day ] = array(
                    'open'   => isset( $data['open'] ) ? sanitize_text_field( $data['open'] ) : '',
                    'close'  => isset( $data['close'] ) ? sanitize_text_field( $data['close'] ) : '',
                    'closed' => isset( $data['closed'] ) ? true : false,
                );
            }
            update_post_meta( $post_id, '_babel_hours', wp_json_encode( $hours_data ) );
        }

        // Estados
        $verified = isset( $_POST['babel_verified'] ) ? '1' : '0';
        update_post_meta( $post_id, '_babel_verified', $verified );
        update_post_meta( $post_id, '_babel_is_verified', $verified ); // Duplicación para compatibilidad
        update_post_meta( $post_id, '_bd_verificado', $verified );

        $featured = isset( $_POST['babel_featured'] ) ? '1' : '0';
        update_post_meta( $post_id, '_babel_featured', $featured );
        update_post_meta( $post_id, '_babel_is_featured', $featured ); // Duplicación para compatibilidad
        update_post_meta( $post_id, '_bd_destacado', $featured );

        $is_institution = isset( $_POST['babel_is_institution'] ) ? '1' : '0';
        update_post_meta( $post_id, '_babel_is_institution', $is_institution );
        update_post_meta( $post_id, '_bd_is_institution', $is_institution );

        // Tags / Palabras Clave SEO
        if ( isset( $_POST['babel_biz_tags'] ) ) {
            $raw_tags   = sanitize_text_field( wp_unslash( $_POST['babel_biz_tags'] ) );
            $tags_array = array_filter( array_map( 'sanitize_text_field', explode( ',', $raw_tags ) ) );
            update_post_meta( $post_id, '_babel_biz_tags', implode( ',', $tags_array ) );
        }

        // --- Fase 2 - Guardado y sanitización de nuevos campos ---

        // RUT de la Empresa
        if ( isset( $_POST['babel_rut'] ) ) {
            $rut_val = sanitize_text_field( wp_unslash( $_POST['babel_rut'] ) );
            update_post_meta( $post_id, '_babel_rut', $rut_val );
            update_post_meta( $post_id, '_bd_rut', $rut_val );
        }

        // Razón Social
        if ( isset( $_POST['babel_razon_social'] ) ) {
            $razon_social_val = sanitize_text_field( wp_unslash( $_POST['babel_razon_social'] ) );
            update_post_meta( $post_id, '_babel_razon_social', $razon_social_val );
            update_post_meta( $post_id, '_bd_razon_social', $razon_social_val );
        }

        // Nombre Comercial
        if ( isset( $_POST['babel_nombre_comercial'] ) ) {
            $nombre_comercial_val = sanitize_text_field( wp_unslash( $_POST['babel_nombre_comercial'] ) );
            update_post_meta( $post_id, '_babel_nombre_comercial', $nombre_comercial_val );
            update_post_meta( $post_id, '_bd_nombre_comercial', $nombre_comercial_val );
        }

        // Giro Comercial SII
        if ( isset( $_POST['babel_giro'] ) ) {
            $giro_val = sanitize_text_field( wp_unslash( $_POST['babel_giro'] ) );
            update_post_meta( $post_id, '_babel_giro', $giro_val );
            update_post_meta( $post_id, '_bd_giro', $giro_val );
        }

        // Patente Municipal
        if ( isset( $_POST['babel_patente'] ) ) {
            $patente_val = sanitize_text_field( wp_unslash( $_POST['babel_patente'] ) );
            update_post_meta( $post_id, '_babel_patente', $patente_val );
            update_post_meta( $post_id, '_bd_patente', $patente_val );
        }

        // Representante Legal
        if ( isset( $_POST['babel_rep_legal'] ) ) {
            $rep_legal_val = sanitize_text_field( wp_unslash( $_POST['babel_rep_legal'] ) );
            update_post_meta( $post_id, '_babel_rep_legal', $rep_legal_val );
            update_post_meta( $post_id, '_bd_rep_legal', $rep_legal_val );
        }

        // Año de Fundación
        if ( isset( $_POST['babel_founded_year'] ) ) {
            $founded_year_val = intval( $_POST['babel_founded_year'] );
            update_post_meta( $post_id, '_babel_founded_year', $founded_year_val );
            update_post_meta( $post_id, '_bd_founded_year', $founded_year_val );
        }

        // TikTok
        if ( isset( $_POST['babel_tiktok'] ) ) {
            $tiktok_val = esc_url_raw( wp_unslash( $_POST['babel_tiktok'] ) );
            update_post_meta( $post_id, '_babel_tiktok', $tiktok_val );
            update_post_meta( $post_id, '_bd_tiktok', $tiktok_val );
        }

        // YouTube Canal
        if ( isset( $_POST['babel_youtube_channel'] ) ) {
            $youtube_channel_val = esc_url_raw( wp_unslash( $_POST['babel_youtube_channel'] ) );
            update_post_meta( $post_id, '_babel_youtube_channel', $youtube_channel_val );
            update_post_meta( $post_id, '_bd_youtube_channel', $youtube_channel_val );
        }

        // Twitter/X
        if ( isset( $_POST['babel_twitter'] ) ) {
            $twitter_val = esc_url_raw( wp_unslash( $_POST['babel_twitter'] ) );
            update_post_meta( $post_id, '_babel_twitter', $twitter_val );
            update_post_meta( $post_id, '_bd_twitter', $twitter_val );
        }

        // Pinterest
        if ( isset( $_POST['babel_pinterest'] ) ) {
            $pinterest_val = esc_url_raw( wp_unslash( $_POST['babel_pinterest'] ) );
            update_post_meta( $post_id, '_babel_pinterest', $pinterest_val );
            update_post_meta( $post_id, '_bd_pinterest', $pinterest_val );
        }

        // Video YouTube Principal
        if ( isset( $_POST['babel_youtube_url'] ) ) {
            $youtube_url_val = esc_url_raw( wp_unslash( $_POST['babel_youtube_url'] ) );
            update_post_meta( $post_id, '_babel_youtube_url', $youtube_url_val );
            update_post_meta( $post_id, '_bd_youtube_url', $youtube_url_val );
        }

        // Video YouTube 2
        if ( isset( $_POST['babel_youtube_url_2'] ) ) {
            $youtube_url_2_val = esc_url_raw( wp_unslash( $_POST['babel_youtube_url_2'] ) );
            update_post_meta( $post_id, '_babel_youtube_url_2', $youtube_url_2_val );
            update_post_meta( $post_id, '_bd_youtube_url_2', $youtube_url_2_val );
        }

        // Estacionamiento
        if ( isset( $_POST['babel_parking'] ) ) {
            $parking_val = sanitize_text_field( wp_unslash( $_POST['babel_parking'] ) );
            update_post_meta( $post_id, '_babel_parking', $parking_val );
            update_post_meta( $post_id, '_bd_parking', $parking_val );
        }

        // Mascotas Pet Friendly
        if ( isset( $_POST['babel_pet_friendly'] ) ) {
            $pet_friendly_val = sanitize_text_field( wp_unslash( $_POST['babel_pet_friendly'] ) );
            update_post_meta( $post_id, '_babel_pet_friendly', $pet_friendly_val );
            update_post_meta( $post_id, '_bd_pet_friendly', $pet_friendly_val );
        }

        // Métodos de Pago (JSON array)
        if ( isset( $_POST['babel_payments'] ) && is_array( $_POST['babel_payments'] ) ) {
            $payments_val = array_map( 'sanitize_text_field', $_POST['babel_payments'] );
            $payments_json = wp_json_encode( $payments_val );
            update_post_meta( $post_id, '_babel_payments', $payments_json );
            update_post_meta( $post_id, '_bd_payments', $payments_json );
        } else {
            $payments_empty = wp_json_encode( array() );
            update_post_meta( $post_id, '_babel_payments', $payments_empty );
            update_post_meta( $post_id, '_bd_payments', $payments_empty );
        }

        // Accesibilidad Universal
        if ( isset( $_POST['babel_accessibility'] ) ) {
            $accessibility_val = sanitize_text_field( wp_unslash( $_POST['babel_accessibility'] ) );
            update_post_meta( $post_id, '_babel_accessibility', $accessibility_val );
            update_post_meta( $post_id, '_bd_accessibility', $accessibility_val );
        }

        // Wi-Fi
        if ( isset( $_POST['babel_wifi'] ) ) {
            $wifi_val = sanitize_text_field( wp_unslash( $_POST['babel_wifi'] ) );
            update_post_meta( $post_id, '_babel_wifi', $wifi_val );
            update_post_meta( $post_id, '_bd_wifi', $wifi_val );
        }

        // Sistema de Reservas
        if ( isset( $_POST['babel_reservations'] ) ) {
            $reservations_val = sanitize_text_field( wp_unslash( $_POST['babel_reservations'] ) );
            update_post_meta( $post_id, '_babel_reservations', $reservations_val );
            update_post_meta( $post_id, '_bd_reservations', $reservations_val );
        }

        // Delivery
        if ( isset( $_POST['babel_delivery'] ) ) {
            $delivery_val = sanitize_text_field( wp_unslash( $_POST['babel_delivery'] ) );
            update_post_meta( $post_id, '_babel_delivery', $delivery_val );
            update_post_meta( $post_id, '_bd_delivery', $delivery_val );
        }

        // Rango de Precios
        if ( isset( $_POST['babel_price_range'] ) ) {
            $price_range_val = sanitize_text_field( wp_unslash( $_POST['babel_price_range'] ) );
            update_post_meta( $post_id, '_babel_price_range', $price_range_val );
            update_post_meta( $post_id, '_bd_price_range', $price_range_val );
        }

        // Espacios (JSON array)
        if ( isset( $_POST['babel_spaces'] ) && is_array( $_POST['babel_spaces'] ) ) {
            $spaces_val = array_map( 'sanitize_text_field', $_POST['babel_spaces'] );
            $spaces_json = wp_json_encode( $spaces_val );
            update_post_meta( $post_id, '_babel_spaces', $spaces_json );
            update_post_meta( $post_id, '_bd_spaces', $spaces_json );
        } else {
            $spaces_empty = wp_json_encode( array() );
            update_post_meta( $post_id, '_babel_spaces', $spaces_empty );
            update_post_meta( $post_id, '_bd_spaces', $spaces_empty );
        }

        // Tipo de Negocio
        if ( isset( $_POST['babel_biz_type'] ) ) {
            $biz_type_val = sanitize_text_field( wp_unslash( $_POST['babel_biz_type'] ) );
            update_post_meta( $post_id, '_babel_biz_type', $biz_type_val );
            update_post_meta( $post_id, '_bd_biz_type', $biz_type_val );
        }

        // Idiomas de Atención (JSON array)
        if ( isset( $_POST['babel_languages'] ) && is_array( $_POST['babel_languages'] ) ) {
            $languages_val = array_map( 'sanitize_text_field', $_POST['babel_languages'] );
            $languages_json = wp_json_encode( $languages_val );
            update_post_meta( $post_id, '_babel_languages', $languages_json );
            update_post_meta( $post_id, '_bd_languages', $languages_json );
        } else {
            $languages_empty = wp_json_encode( array() );
            update_post_meta( $post_id, '_babel_languages', $languages_empty );
            update_post_meta( $post_id, '_bd_languages', $languages_empty );
        }

        // Número de Empleados
        if ( isset( $_POST['babel_employees'] ) ) {
            $employees_val = sanitize_text_field( wp_unslash( $_POST['babel_employees'] ) );
            update_post_meta( $post_id, '_babel_employees', $employees_val );
            update_post_meta( $post_id, '_bd_employees', $employees_val );
        }

        // Área de Cobertura
        if ( isset( $_POST['babel_coverage_area'] ) ) {
            $coverage_area_val = sanitize_text_field( wp_unslash( $_POST['babel_coverage_area'] ) );
            update_post_meta( $post_id, '_babel_coverage_area', $coverage_area_val );
            update_post_meta( $post_id, '_bd_coverage_area', $coverage_area_val );
        }

        // URL Menú/Carta PDF
        if ( isset( $_POST['babel_menu_url'] ) ) {
            $menu_url_val = esc_url_raw( wp_unslash( $_POST['babel_menu_url'] ) );
            update_post_meta( $post_id, '_babel_menu_url', $menu_url_val );
            update_post_meta( $post_id, '_bd_menu_url', $menu_url_val );
        }

        // URL Reservas Online
        if ( isset( $_POST['babel_booking_url'] ) ) {
            $booking_url_val = esc_url_raw( wp_unslash( $_POST['babel_booking_url'] ) );
            update_post_meta( $post_id, '_babel_booking_url', $booking_url_val );
            update_post_meta( $post_id, '_bd_booking_url', $booking_url_val );
        }

        // Teléfono Alternativo
        if ( isset( $_POST['babel_phone_alt'] ) ) {
            $phone_alt_val = sanitize_text_field( wp_unslash( $_POST['babel_phone_alt'] ) );
            update_post_meta( $post_id, '_babel_phone_alt', $phone_alt_val );
            update_post_meta( $post_id, '_bd_phone_alt', $phone_alt_val );
        }

        // Email Alternativo
        if ( isset( $_POST['babel_email_alt'] ) ) {
            $email_alt_val = sanitize_email( wp_unslash( $_POST['babel_email_alt'] ) );
            update_post_meta( $post_id, '_babel_email_alt', $email_alt_val );
            update_post_meta( $post_id, '_bd_email_alt', $email_alt_val );
        }
    }

    /**
     * Renderiza el Editor de Negocios dentro de la SPA (Soy de Chile).
     */
    public static function render_spa_editor( $post_id = 0 ) {
        $post = null;
        $title = '';
        $desc = '';
        $logo_id = '';
        $logo_url = '';
        $gallery = '';
        $whatsapp = '';
        $phone = '';
        $email = '';
        $website = '';
        $instagram = '';
        $facebook = '';
        $tiktok = '';
        $linkedin = '';
        $rut = '';
        $razon_social = '';
        $giro = '';
        $rep_legal = '';
        $address = '';
        $lat = '';
        $lng = '';
        $wifi = '';
        $parking = '';
        $pet_friendly = '';
        $delivery = '';
        $hours_meta = array();
        $assigned_cat_ids = array();
        $is_edit = false;

        if ( $post_id > 0 ) {
            $is_edit = true;
            $post = get_post( $post_id );
            if ( $post ) {
                $title = $post->post_title;
                $desc = $post->post_content;

                // Contacto
                $whatsapp = get_post_meta( $post->ID, '_babel_whatsapp', true ) ?: get_post_meta( $post->ID, '_bd_whatsapp', true );
                $phone = get_post_meta( $post->ID, '_babel_phone', true ) ?: get_post_meta( $post->ID, '_bd_telefono', true );
                $email = get_post_meta( $post->ID, '_babel_email', true ) ?: get_post_meta( $post->ID, '_bd_email', true );
                $website = get_post_meta( $post->ID, '_babel_website', true ) ?: get_post_meta( $post->ID, '_bd_sitio_web', true );
                $instagram = get_post_meta( $post->ID, '_babel_instagram', true );
                $facebook = get_post_meta( $post->ID, '_babel_facebook', true );
                $tiktok = get_post_meta( $post->ID, '_babel_tiktok', true );
                $linkedin = get_post_meta( $post->ID, '_babel_linkedin', true );

                // Detalles Legales
                $rut = get_post_meta( $post->ID, '_babel_rut', true ) ?: get_post_meta( $post->ID, '_bd_rut', true );
                $razon_social = get_post_meta( $post->ID, '_babel_razon_social', true ) ?: get_post_meta( $post->ID, '_bd_razon_social', true );
                $giro = get_post_meta( $post->ID, '_babel_giro', true ) ?: get_post_meta( $post->ID, '_bd_giro', true );
                $rep_legal = get_post_meta( $post->ID, '_babel_rep_legal', true ) ?: get_post_meta( $post->ID, '_bd_rep_legal', true );

                // Ubicación
                $address = get_post_meta( $post->ID, '_babel_address', true ) ?: get_post_meta( $post->ID, '_bd_direccion', true );
                $lat = get_post_meta( $post->ID, '_babel_lat', true ) ?: get_post_meta( $post->ID, '_bd_latitud', true );
                $lng = get_post_meta( $post->ID, '_babel_lng', true ) ?: get_post_meta( $post->ID, '_bd_longitud', true );

                // Amenities
                $wifi = get_post_meta( $post->ID, '_babel_wifi', true ) ?: get_post_meta( $post->ID, '_bd_wifi', true );
                $parking = get_post_meta( $post->ID, '_babel_parking', true ) ?: get_post_meta( $post->ID, '_bd_estacionamiento', true );
                $pet_friendly = get_post_meta( $post->ID, '_babel_pet_friendly', true );
                $delivery = get_post_meta( $post->ID, '_babel_delivery', true );

                // Horarios
                $hours_meta = get_post_meta( $post->ID, '_babel_hours', true ) ?: get_post_meta( $post->ID, '_bd_horarios', true );

                // Galería
                $gallery = get_post_meta( $post->ID, '_babel_gallery', true ) ?: get_post_meta( $post->ID, '_bd_galeria', true );
                if ( is_array( $gallery ) ) {
                    $gallery = implode( ',', $gallery );
                }

                // Logo
                $logo_id = get_post_thumbnail_id( $post->ID ) ?: get_post_meta( $post->ID, '_bd_logo_id', true );
                if ( $logo_id ) {
                    $logo_url = wp_get_attachment_image_url( $logo_id, 'thumbnail' );
                }

                // Categorías
                $assigned_cat_ids = wp_get_object_terms( $post->ID, 'babel_category', array( 'fields' => 'ids' ) );
                if ( is_wp_error( $assigned_cat_ids ) ) {
                    $assigned_cat_ids = array();
                }
            }
        }
        ?>
        <style>
            .sdc-saas-container {
                max-width: 1000px;
                margin: 32px auto;
                background: #ffffff;
                border-radius: 12px;
                box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1);
                border: 1px solid var(--sdc-border, #e2e8f0);
                overflow: hidden;
            }
            .sdc-hours-row {
                display: grid;
                grid-template-columns: 120px 1fr auto auto;
                align-items: center;
                gap: 16px;
                padding: 12px 16px;
                background: #f8fafc;
                border-radius: 8px;
                border: 1px solid var(--sdc-border, #e2e8f0);
                margin-bottom: 8px;
            }
            .sdc-hours-day {
                font-weight: 600;
                color: #334155;
                text-transform: capitalize;
            }
            .sdc-hours-inputs {
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .sdc-hours-inputs input[type="time"] {
                border: 1px solid #cbd5e1;
                border-radius: 6px;
                padding: 6px 12px;
                font-family: inherit;
                color: #475569;
                background: #fff;
            }
            /* Bento Grid Styles for SPA */
            .sdc-bento-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
                gap: 24px;
                padding: 24px;
                background: transparent;
            }
            .sdc-bento-card {
                background: #ffffff;
                border: 1px solid #cbd5e1;
                border-radius: 12px;
                padding: 24px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
                display: flex;
                flex-direction: column;
                gap: 16px;
                align-self: start;
            }
            .sdc-bento-card.full {
                grid-column: 1 / -1;
            }
        </style>
        <div class="sdc-saas-container">
            <div class="sdc-editor-layout" style="display: block;">
            
            <!-- Formularios en Layout Bento -->
            <div class="sdc-editor-main" style="width: 100%;">
                <form id="sdc-business-form">
                    <?php if ( $is_edit && $post ) : ?>
                        <input type="hidden" name="post_id" id="sdc_post_id" value="<?php echo esc_attr( $post->ID ); ?>">
                    <?php endif; ?>
                    
                    <div class="sdc-bento-grid">
                    
                    <!-- General -->
                    <div id="sdc-biz-general" class="sdc-bento-card full">
                        <h3 style="margin-top:0; border-bottom: 1px solid var(--sdc-border); padding-bottom:8px;">Información Principal</h3>
                        
                        <div class="sdc-form-group">
                            <label class="sdc-label">Nombre del Negocio</label>
                            <input type="text" id="sdc_biz_name" name="biz_name" class="sdc-input" placeholder="Ej. Restaurante El Olivo" value="<?php echo esc_attr( $title ); ?>">
                        </div>
                        
                        <div class="sdc-form-group">
                            <label class="sdc-label">Descripción</label>
                            <textarea id="sdc_biz_desc" name="biz_desc" class="sdc-input" rows="5" placeholder="Describe el negocio..."><?php echo esc_textarea( $desc ); ?></textarea>
                        </div>

                        <?php
                        $categories = get_terms( array(
                            'taxonomy'   => 'babel_category',
                            'hide_empty' => false,
                        ) );
                        ?>
                        <div class="sdc-form-group">
                            <label class="sdc-label">Categorías y Subcategorías</label>
                            <select id="sdc_biz_categories" name="biz_categories[]" class="sdc-input sdc-select2" multiple="multiple" style="width: 100%;">
                                <?php if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) : ?>
                                    <?php foreach ( $categories as $category ) : ?>
                                        <option value="<?php echo esc_attr( $category->term_id ); ?>" <?php selected( in_array( $category->term_id, $assigned_cat_ids ) ); ?>><?php echo esc_html( $category->name ); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <p class="sdc-text-muted" style="font-size: 12px; margin-top: 4px;">Escribe para buscar. Puedes seleccionar varias opciones.</p>
                        </div>

                        <div class="sdc-grid-2" style="display: grid; gap: 16px; grid-template-columns: 1fr 1fr;">
                            <div class="sdc-form-group" style="height: 100%;">
                                <label class="sdc-label">Logo del Negocio</label>
                                <div id="sdc_upload_logo_btn" style="border: 2px dashed var(--sdc-border); border-radius: 8px; padding: 24px; text-align: center; background: #f8fafc; cursor: pointer; height: calc(100% - 24px); box-sizing: border-box; display: flex; flex-direction: column; justify-content: center; align-items: center; <?php if($logo_url) echo 'background-image: url('.esc_url($logo_url).'); background-size: contain; background-repeat: no-repeat; background-position: center;'; ?>">
                                    <?php if(!$logo_url): ?>
                                    <span class="dashicons dashicons-upload" style="font-size: 32px; width: 32px; height: 32px; color: var(--sdc-blue);"></span>
                                    <p style="margin-top: 12px; font-weight: 500; margin-bottom: 0;">Haz clic para subir el logotipo</p>
                                    <?php endif; ?>
                                </div>
                                <input type="hidden" name="biz_logo_id" id="sdc_biz_logo_id" value="<?php echo esc_attr($logo_id); ?>">
                            </div>

                            <div class="sdc-form-group" style="height: 100%;">
                                <label class="sdc-label">Galería de Fotos</label>
                                <div id="sdc_upload_gallery_btn" style="border: 2px dashed var(--sdc-border); border-radius: 8px; padding: 24px; text-align: center; background: #f8fafc; cursor: pointer; height: calc(100% - 24px); box-sizing: border-box; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                                    <span class="dashicons dashicons-images-alt2" style="font-size: 32px; width: 32px; height: 32px; color: var(--sdc-blue);"></span>
                                    <p style="margin-top: 12px; font-weight: 500; margin-bottom: 0;"><?php echo $gallery ? 'Cambiar Galería (' . count(explode(',',$gallery)) . ' fotos)' : 'Haz clic para añadir fotos a la galería'; ?></p>
                                </div>
                                <input type="hidden" name="biz_gallery" id="sdc_biz_gallery" value="<?php echo esc_attr($gallery); ?>">
                            </div>
                        </div>

                        <style>
                            .sdc-grid-4 { display: grid; gap: 16px; grid-template-columns: 1fr; }
                            @media (min-width: 768px) { .sdc-grid-4 { grid-template-columns: repeat(2, 1fr); } }
                            @media (min-width: 1024px) { .sdc-grid-4 { grid-template-columns: repeat(4, 1fr); } }
                        </style>
                        <h3 style="margin-top:32px; border-bottom: 1px solid var(--sdc-border); padding-bottom:8px;">Contacto y Redes</h3>
                        <div class="sdc-grid-4">
                            <div class="sdc-form-group">
                                <label class="sdc-label">WhatsApp</label>
                                <input type="text" name="biz_whatsapp" class="sdc-input" placeholder="+56 9..." value="<?php echo esc_attr($whatsapp); ?>">
                            </div>
                            <div class="sdc-form-group">
                                <label class="sdc-label">Teléfono Fijo</label>
                                <input type="text" name="biz_phone" class="sdc-input" placeholder="(2) 2..." value="<?php echo esc_attr($phone); ?>">
                            </div>
                            <div class="sdc-form-group">
                                <label class="sdc-label">Correo Electrónico</label>
                                <input type="email" name="biz_email" class="sdc-input" placeholder="contacto@..." value="<?php echo esc_attr($email); ?>">
                            </div>
                            <div class="sdc-form-group">
                                <label class="sdc-label">Sitio Web</label>
                                <input type="url" name="biz_website" class="sdc-input" placeholder="https://..." value="<?php echo esc_attr($website); ?>">
                            </div>
                            <div class="sdc-form-group">
                                <label class="sdc-label">Instagram</label>
                                <input type="text" name="biz_instagram" class="sdc-input" placeholder="ej: usuario o @usuario" value="<?php echo esc_attr($instagram); ?>">
                            </div>
                            <div class="sdc-form-group">
                                <label class="sdc-label">Facebook</label>
                                <input type="text" name="biz_facebook" class="sdc-input" placeholder="ej: usuario" value="<?php echo esc_attr($facebook); ?>">
                            </div>
                            <div class="sdc-form-group">
                                <label class="sdc-label">TikTok</label>
                                <input type="text" name="biz_tiktok" class="sdc-input" placeholder="ej: usuario o @usuario" value="<?php echo esc_attr($tiktok); ?>">
                            </div>
                            <div class="sdc-form-group">
                                <label class="sdc-label">LinkedIn</label>
                                <input type="text" name="biz_linkedin" class="sdc-input" placeholder="ej: usuario o mi-empresa" value="<?php echo esc_attr($linkedin); ?>">
                            </div>
                        </div>

                        <h3 style="margin-top:32px; border-bottom: 1px solid var(--sdc-border); padding-bottom:8px;">Datos Legales y Administrativos</h3>
                        <div class="sdc-grid-4">
                            <div class="sdc-form-group">
                                <label class="sdc-label">RUT Empresa</label>
                                <input type="text" name="biz_rut" class="sdc-input" placeholder="76.123.456-7" value="<?php echo esc_attr($rut); ?>">
                            </div>
                            <div class="sdc-form-group">
                                <label class="sdc-label">Razón Social</label>
                                <input type="text" name="biz_razon_social" class="sdc-input" placeholder="Comercial SPA" value="<?php echo esc_attr($razon_social); ?>">
                            </div>
                            <div class="sdc-form-group">
                                <label class="sdc-label">Giro Comercial</label>
                                <input type="text" name="biz_giro" class="sdc-input" placeholder="Restaurante..." value="<?php echo esc_attr($giro); ?>">
                            </div>
                            <div class="sdc-form-group">
                                <label class="sdc-label">Representante Legal</label>
                                <input type="text" name="biz_rep_legal" class="sdc-input" placeholder="Nombre completo" value="<?php echo esc_attr($rep_legal); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Ubicación y Mapa -->
                    <div id="sdc-biz-location" class="sdc-bento-card">
                        <h3 style="margin-top:0;">Ubicación (Auto-Mapa OpenStreetMap)</h3>
                        
                        <div class="sdc-form-group">
                            <label class="sdc-label">Dirección Completa</label>
                            <div style="display: flex; gap: 8px;">
                                <input type="text" id="sdc_biz_address" name="biz_address" class="sdc-input" placeholder="Ej. Av. Providencia 1234, Santiago" value="<?php echo esc_attr($address); ?>" style="flex-grow: 1;">
                                <button type="button" id="sdc_btn_geocode" class="sdc-btn sdc-btn-primary" style="white-space: nowrap; padding: 8px 16px;">
                                    <span class="dashicons dashicons-location-alt" style="margin-top:2px;"></span> Ubicar
                                </button>
                            </div>
                            <p class="sdc-text-muted" style="font-size:12px; margin-top:4px;">Presiona "Ubicar" para buscar las coordenadas en el mapa (OpenStreetMap).</p>
                        </div>
                        
                        <div class="sdc-grid-2" style="display: grid; gap: 16px; grid-template-columns: 1fr 1fr;">
                            <div class="sdc-form-group">
                                <label class="sdc-label">Latitud</label>
                                <input type="text" id="sdc_biz_lat" name="biz_lat" class="sdc-input" placeholder="-33.4..." value="<?php echo esc_attr($lat); ?>" readonly style="background:#f1f5f9; cursor:not-allowed;">
                            </div>
                            <div class="sdc-form-group">
                                <label class="sdc-label">Longitud</label>
                                <input type="text" id="sdc_biz_lng" name="biz_lng" class="sdc-input" placeholder="-70.6..." value="<?php echo esc_attr($lng); ?>" readonly style="background:#f1f5f9; cursor:not-allowed;">
                            </div>
                        </div>

                        <div style="margin-top:16px; border:1px solid var(--sdc-border); border-radius:8px; overflow:hidden;">
                            <?php 
                            $map_src = "about:blank";
                            if($lat && $lng) {
                                $bbox = (floatval($lng)-0.01) . "," . (floatval($lat)-0.01) . "," . (floatval($lng)+0.01) . "," . (floatval($lat)+0.01);
                                $map_src = "https://www.openstreetmap.org/export/embed.html?bbox=" . $bbox . "&layer=mapnik&marker=" . $lat . "," . $lng;
                            }
                            ?>
                            <iframe id="sdc_map_preview" width="100%" height="300" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="<?php echo esc_url($map_src); ?>"></iframe>
                        </div>
                    </div>

                    <!-- Comodidades -->
                    <div id="sdc-biz-amenities" class="sdc-bento-card">
                        <h3 style="margin-top:0;">Comodidades y Servicios</h3>
                        
                        <div class="sdc-grid">
                            <!-- Wi-Fi -->
                            <div class="sdc-flex sdc-justify-between sdc-items-center" style="padding:12px; border:1px solid var(--sdc-border); border-radius:8px;">
                                <div>
                                    <strong>Wi-Fi Gratis</strong>
                                    <div class="sdc-text-muted" style="font-size:12px;">Conexión gratuita para clientes</div>
                                </div>
                                <label class="sdc-toggle">
                                    <input type="checkbox" name="biz_wifi" value="1" <?php checked($wifi, '1'); ?>>
                                    <span class="sdc-toggle-slider"></span>
                                </label>
                            </div>

                            <!-- Estacionamiento -->
                            <div class="sdc-flex sdc-justify-between sdc-items-center" style="padding:12px; border:1px solid var(--sdc-border); border-radius:8px;">
                                <div>
                                    <strong>Estacionamiento</strong>
                                    <div class="sdc-text-muted" style="font-size:12px;">Estacionamiento privado o cercano</div>
                                </div>
                                <label class="sdc-toggle">
                                    <input type="checkbox" name="biz_parking" value="1" <?php checked($parking, '1'); ?>>
                                    <span class="sdc-toggle-slider"></span>
                                </label>
                            </div>

                            <!-- Pet Friendly -->
                            <div class="sdc-flex sdc-justify-between sdc-items-center" style="padding:12px; border:1px solid var(--sdc-border); border-radius:8px;">
                                <div>
                                    <strong>Pet Friendly</strong>
                                    <div class="sdc-text-muted" style="font-size:12px;">Mascotas bienvenidas</div>
                                </div>
                                <label class="sdc-toggle">
                                    <input type="checkbox" name="biz_pet_friendly" value="1" <?php checked($pet_friendly, '1'); ?>>
                                    <span class="sdc-toggle-slider"></span>
                                </label>
                            </div>

                            <!-- Delivery -->
                            <div class="sdc-flex sdc-justify-between sdc-items-center" style="padding:12px; border:1px solid var(--sdc-border); border-radius:8px;">
                                <div>
                                    <strong>Delivery</strong>
                                    <div class="sdc-text-muted" style="font-size:12px;">Entrega a domicilio</div>
                                </div>
                                <label class="sdc-toggle">
                                    <input type="checkbox" name="biz_delivery" value="1" <?php checked($delivery, '1'); ?>>
                                    <span class="sdc-toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Horarios -->
                    <div id="sdc-biz-hours" class="sdc-bento-card full">
                        <h3 style="margin-top:0;">Horarios de Atención</h3>
                        <p class="sdc-text-muted" style="margin-bottom: 24px;">Configura los días y horas de apertura. Añade bloques dinámicos para agrupar días con el mismo horario.</p>
                        
                        <div id="sdc-dynamic-hours-container" style="display:flex; flex-direction:column; gap:16px;">
                            <!-- Blocks will be injected here via JS -->
                        </div>
                        <button type="button" id="sdc-add-hours-btn" class="sdc-btn" style="margin-top: 16px; width: fit-content;">
                            <span class="dashicons dashicons-plus-alt2" style="margin-top: 4px;"></span> Añadir Bloque de Horario
                        </button>
                    </div>

                    </div> <!-- End sdc-bento-grid -->
                    <div class="sdc-editor-footer" style="position: sticky; bottom: 0; background: white; padding: 16px 24px; border-top: 1px solid var(--sdc-border); z-index: 10;">
                        <button type="button" class="sdc-btn sdc-btn-primary" onclick="alert('Funcionalidad de guardado en desarrollo.')" style="width: 100%;">
                            <span class="dashicons dashicons-saved"></span> Guardar Negocio Completamente
                        </button>
                    </div>

                </form>
            </div>
        </div>
        </div>
        <?php
        self::render_spa_editor_scripts( $hours_meta );
    }

    /**
     * Renderiza los scripts específicos para el Editor de la SPA
     */
    private static function render_spa_editor_scripts( $hours_meta = array() ) {
        ?>
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <style>
            .select2-container--default .select2-selection--multiple {
                border-color: #cbd5e1;
                border-radius: 8px;
                min-height: 42px;
                padding: 4px;
            }
            .sdc-day-pill {
                display: inline-block;
                padding: 4px 10px;
                border-radius: 20px;
                border: 1px solid #cbd5e1;
                font-size: 12px;
                cursor: pointer;
                user-select: none;
                margin-right: 4px;
                margin-bottom: 4px;
                color: #64748b;
            }
            .sdc-day-pill input[type="checkbox"] {
                display: none;
            }
            .sdc-day-pill.active {
                background: var(--sdc-blue);
                color: white;
                border-color: var(--sdc-blue);
            }
        </style>
        <script>
        jQuery(document).ready(function($){
            // Inicializar Select2 para Categorías
            $('#sdc_biz_categories').select2({
                placeholder: "Selecciona categorías...",
                allowClear: true,
                tags: true,
                createTag: function (params) {
                    var term = $.trim(params.term);
                    if (term === '') {
                        return null;
                    }
                    return {
                        id: term,
                        text: term,
                        newTag: true // add additional parameters
                    }
                }
            });

            // Upload Logo
            var sdc_logo_frame;
            $('#sdc_upload_logo_btn').on('click', function(e){
                e.preventDefault();
                if ( sdc_logo_frame ) {
                    sdc_logo_frame.open();
                    return;
                }
                sdc_logo_frame = wp.media({
                    title: 'Seleccionar Logo del Negocio',
                    button: { text: 'Usar este Logo' },
                    multiple: false
                });
                sdc_logo_frame.on('select', function() {
                    var attachment = sdc_logo_frame.state().get('selection').first().toJSON();
                    $('#sdc_biz_logo_id').val(attachment.id);
                    $('#sdc_upload_logo_btn').html('<img src="'+attachment.url+'" style="max-width:100%; max-height:100px; border-radius:6px;">');
                });
                sdc_logo_frame.open();
            });

            // Upload Gallery
            var sdc_gallery_frame;
            $('#sdc_upload_gallery_btn').on('click', function(e){
                e.preventDefault();
                if ( sdc_gallery_frame ) {
                    sdc_gallery_frame.open();
                    return;
                }
                sdc_gallery_frame = wp.media({
                    title: 'Seleccionar Fotos para la Galería',
                    button: { text: 'Añadir a la Galería' },
                    multiple: true
                });
                sdc_gallery_frame.on('select', function() {
                    var selection = sdc_gallery_frame.state().get('selection');
                    var ids = [];
                    var imgs = '';
                    selection.map(function(attachment) {
                        attachment = attachment.toJSON();
                        ids.push(attachment.id);
                        imgs += '<img src="'+attachment.url+'" style="max-width:50px; max-height:50px; border-radius:4px; margin-right:4px;">';
                    });
                    $('#sdc_biz_gallery').val(ids.join(','));
                    $('#sdc_upload_gallery_btn').html('<div style="display:flex; flex-wrap:wrap; gap:4px; justify-content:center; align-items:center;">'+imgs+'</div>');
                });
                sdc_gallery_frame.open();
            });

            // Lógica de Horarios Dinámicos
            let hoursBlockIndex = 0;
            const daysOfWeek = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
            const daysLabels = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
            
            const existingHours = <?php echo wp_json_encode( $hours_meta ?: new \stdClass() ); ?>;

            function addHoursBlock(prefillDays = [], openTime = '09:00', closeTime = '18:00') {
                let html = '<div class="sdc-hours-block" style="border:1px solid #cbd5e1; border-radius:8px; padding:16px; position:relative;">';
                html += '<button type="button" class="sdc-remove-hours-btn" style="position:absolute; top:8px; right:8px; background:none; border:none; color:#ef4444; cursor:pointer;"><span class="dashicons dashicons-trash"></span></button>';
                html += '<div style="margin-bottom:12px;"><strong>Días:</strong><br>';
                
                daysOfWeek.forEach((day, index) => {
                    let checked = prefillDays.includes(day) ? 'checked' : '';
                    let activeClass = checked ? 'active' : '';
                    html += '<label class="sdc-day-pill '+activeClass+'"><input type="checkbox" name="biz_hours_dynamic['+hoursBlockIndex+'][days][]" value="'+day+'" '+checked+'> '+daysLabels[index]+'</label>';
                });
                
                html += '</div>';
                html += '<div style="display:flex; align-items:center; gap:8px;">';
                html += '<span>Abre: </span><input type="time" name="biz_hours_dynamic['+hoursBlockIndex+'][open]" class="sdc-input" style="width:120px;" value="'+openTime+'">';
                html += '<span>Cierra: </span><input type="time" name="biz_hours_dynamic['+hoursBlockIndex+'][close]" class="sdc-input" style="width:120px;" value="'+closeTime+'">';
                html += '</div></div>';
                
                $('#sdc-dynamic-hours-container').append(html);
                hoursBlockIndex++;
            }

            // Group existing hours by open/close times to rebuild blocks
            let hoursGrouped = {};
            if (Object.keys(existingHours).length > 0) {
                for (const [day, data] of Object.entries(existingHours)) {
                    if (data.closed === '1' || data.closed === true || data.closed === 1 || !data.open) {
                        continue;
                    }
                    let timeKey = data.open + '-' + data.close;
                    if (!hoursGrouped[timeKey]) {
                        hoursGrouped[timeKey] = {
                            open: data.open,
                            close: data.close,
                            days: []
                        };
                    }
                    hoursGrouped[timeKey].days.push(day);
                }
                
                for (const timeKey in hoursGrouped) {
                    addHoursBlock(hoursGrouped[timeKey].days, hoursGrouped[timeKey].open, hoursGrouped[timeKey].close);
                }
            }

            // Añadir un bloque por defecto si está vacío
            if ($('.sdc-hours-block').length === 0) {
                addHoursBlock();
            }

            $('#sdc-add-hours-btn').on('click', function(){
                addHoursBlock();
            });

            $(document).on('click', '.sdc-remove-hours-btn', function(){
                $(this).closest('.sdc-hours-block').remove();
            });

            $(document).on('change', '.sdc-day-pill input', function(){
                if($(this).is(':checked')){
                    $(this).parent('.sdc-day-pill').addClass('active');
                } else {
                    $(this).parent('.sdc-day-pill').removeClass('active');
                }
            });

        });
        </script>
        <?php
    }

    /**
     * Renderiza el panel de configuración para el banner publicitario.
     */
    public function render_ad_banner_panel( $post ) {
        wp_nonce_field( 'bd_ad_banner_nonce_action', 'bd_ad_banner_nonce' );

        $position    = get_post_meta( $post->ID, '_bd_ad_position', true );
        $image_id    = get_post_meta( $post->ID, '_bd_ad_image_id', true );
        $link        = get_post_meta( $post->ID, '_bd_ad_link', true );
        $code        = get_post_meta( $post->ID, '_bd_ad_code', true );
        $impressions = get_post_meta( $post->ID, '_bd_ad_impressions', true );
        $clicks      = get_post_meta( $post->ID, '_bd_ad_clicks', true );

        if ( empty( $impressions ) ) { $impressions = 0; }
        if ( empty( $clicks ) ) { $clicks = 0; }
        $ctr = $impressions > 0 ? round( ( $clicks / $impressions ) * 100, 2 ) : 0;

        $image_url = '';
        if ( $image_id ) {
            $image_url = wp_get_attachment_image_url( $image_id, 'medium' );
        }
        ?>
        <style>
            .bd-ad-metabox {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                background: #ffffff;
                border: 1px solid #cbd5e1;
                border-radius: 8px;
                padding: 20px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            }
            .bd-ad-grid {
                display: grid;
                grid-template-columns: repeat(12, 1fr);
                gap: 16px;
            }
            .bd-ad-span-12 { grid-column: span 12; }
            .bd-ad-span-6 { grid-column: span 6; }
            .bd-ad-span-4 { grid-column: span 4; }
            .bd-ad-field-group {
                display: flex;
                flex-direction: column;
                gap: 6px;
            }
            .bd-ad-field-group label {
                font-weight: 600;
                font-size: 13px;
                color: #1e293b;
            }
            .bd-ad-field-group input[type="text"],
            .bd-ad-field-group input[type="url"],
            .bd-ad-field-group select,
            .bd-ad-field-group textarea {
                width: 100%;
                border: 1px solid #cbd5e1;
                border-radius: 6px;
                padding: 8px 12px;
                font-size: 13px;
                color: #334155;
                background-color: #f8fafc;
                box-sizing: border-box;
            }
            .bd-ad-field-group input:focus,
            .bd-ad-field-group select:focus,
            .bd-ad-field-group textarea:focus {
                background-color: #ffffff;
                border-color: #219ebc;
                box-shadow: 0 0 0 3px rgba(33, 158, 188, 0.15);
                outline: none;
            }
            .bd-ad-media-preview {
                width: 100%;
                max-width: 300px;
                min-height: 100px;
                border: 2px dashed #cbd5e1;
                border-radius: 6px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #f8fafc;
                margin-top: 8px;
                overflow: hidden;
            }
            .bd-ad-media-preview img {
                max-width: 100%;
                height: auto;
                display: block;
            }
            .bd-ad-media-actions {
                margin-top: 10px;
                display: flex;
                gap: 10px;
            }
            .bd-ad-stats-card {
                background: #f1f5f9;
                border: 1px solid #cbd5e1;
                border-radius: 6px;
                padding: 15px;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                text-align: center;
            }
            .bd-ad-stats-val {
                font-size: 24px;
                font-weight: 700;
                color: #0f172a;
            }
            .bd-ad-stats-lbl {
                font-size: 11px;
                color: #64748b;
                text-transform: uppercase;
                margin-top: 4px;
            }
            .bd-ad-placeholder-text {
                font-size: 13px;
                color: #94a3b8;
            }
        </style>
        <div class="bd-ad-metabox">
            <div class="bd-ad-grid">
                <!-- Ubicación -->
                <div class="bd-ad-field-group bd-ad-span-6">
                    <label for="bd_ad_position"><?php esc_html_e( 'Ubicación del Anuncio', 'babel-directory' ); ?></label>
                    <select id="bd_ad_position" name="bd_ad_position">
                        <option value="top_leaderboard" <?php selected( $position, 'top_leaderboard' ); ?>><?php esc_html_e( 'Banner Horizontal Superior (Top Leaderboard - 728x90 / 320x100)', 'babel-directory' ); ?></option>
                        <option value="sidebar_ad" <?php selected( $position, 'sidebar_ad' ); ?>><?php esc_html_e( 'Banner de Barra Lateral (Sidebar - 300x250 / 300x600)', 'babel-directory' ); ?></option>
                        <option value="in_loop_ad" <?php selected( $position, 'in_loop_ad' ); ?>><?php esc_html_e( 'Banner en Grilla de Negocios (In-Loop / Card)', 'babel-directory' ); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e( 'Define dónde se inyectará este anuncio.', 'babel-directory' ); ?></p>
                </div>

                <!-- Enlace de redirección -->
                <div class="bd-ad-field-group bd-ad-span-6">
                    <label for="bd_ad_link"><?php esc_html_e( 'Enlace del Banner (URL)', 'babel-directory' ); ?></label>
                    <input type="url" id="bd_ad_link" name="bd_ad_link" value="<?php echo esc_url( $link ); ?>" placeholder="https://ejemplo.com/pagina-destino" />
                    <p class="description"><?php esc_html_e( 'URL externa a donde irá el usuario al hacer clic en la imagen.', 'babel-directory' ); ?></p>
                </div>

                <!-- Imagen del banner (Media Library) -->
                <div class="bd-ad-field-group bd-ad-span-6">
                    <label><?php esc_html_e( 'Imagen del Banner', 'babel-directory' ); ?></label>
                    <input type="hidden" id="bd_ad_image_id" name="bd_ad_image_id" value="<?php echo esc_attr( $image_id ); ?>" />
                    <div id="bd-ad-image-preview" class="bd-ad-media-preview">
                        <?php if ( $image_url ) : ?>
                            <img src="<?php echo esc_url( $image_url ); ?>" alt="Preview" />
                        <?php else : ?>
                            <span class="bd-ad-placeholder-text"><?php esc_html_e( 'Ninguna imagen seleccionada', 'babel-directory' ); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="bd-ad-media-actions">
                        <button type="button" id="bd-ad-select-img-btn" class="button button-secondary"><?php esc_html_e( 'Seleccionar Imagen', 'babel-directory' ); ?></button>
                        <button type="button" id="bd-ad-remove-img-btn" class="button button-link-delete" style="<?php echo $image_id ? '' : 'display:none;'; ?>"><?php esc_html_e( 'Eliminar', 'babel-directory' ); ?></button>
                    </div>
                </div>

                <!-- Código HTML/JS Alternativo -->
                <div class="bd-ad-field-group bd-ad-span-6">
                    <label for="bd_ad_code"><?php esc_html_e( 'Código HTML o Script Alternativo', 'babel-directory' ); ?></label>
                    <textarea id="bd_ad_code" name="bd_ad_code" rows="6" placeholder="&lt;ins class='adsbygoogle' ...&gt;&lt;/ins&gt;"><?php echo esc_textarea( $code ); ?></textarea>
                    <p class="description"><?php esc_html_e( 'Si ingresas código aquí (ej. AdSense, iframes de terceros), se mostrará este código en lugar de la imagen y enlace anteriores.', 'babel-directory' ); ?></p>
                </div>

                <!-- Estadísticas (4 cols cada una) -->
                <div class="bd-ad-span-12" style="margin-top: 15px;">
                    <hr style="border: 0; border-top: 1px solid #cbd5e1; margin-bottom: 20px;" />
                    <label style="font-weight: 600; font-size: 14px; display: block; margin-bottom: 12px;"><?php esc_html_e( 'Estadísticas del Anuncio', 'babel-directory' ); ?></label>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
                        <div class="bd-ad-stats-card">
                            <span class="bd-ad-stats-val"><?php echo number_format( $impressions ); ?></span>
                            <span class="bd-ad-stats-lbl"><?php esc_html_e( 'Impresiones', 'babel-directory' ); ?></span>
                        </div>
                        <div class="bd-ad-stats-card">
                            <span class="bd-ad-stats-val"><?php echo number_format( $clicks ); ?></span>
                            <span class="bd-ad-stats-lbl"><?php esc_html_e( 'Clics', 'babel-directory' ); ?></span>
                        </div>
                        <div class="bd-ad-stats-card">
                            <span class="bd-ad-stats-val"><?php echo $ctr; ?>%</span>
                            <span class="bd-ad-stats-lbl"><?php esc_html_e( 'CTR (Click-Through Rate)', 'babel-directory' ); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            jQuery(document).ready(function($) {
                var adFrame;
                $('#bd-ad-select-img-btn').on('click', function(e) {
                    e.preventDefault();
                    if (adFrame) {
                        adFrame.open();
                        return;
                    }
                    adFrame = wp.media({
                        title: 'Seleccionar Imagen de Banner',
                        button: {
                            text: 'Usar como Banner'
                        },
                        multiple: false
                    });
                    adFrame.on('select', function() {
                        var attachment = adFrame.state().get('selection').first().toJSON();
                        $('#bd_ad_image_id').val(attachment.id);
                        var imageUrl = (attachment.sizes && attachment.sizes.medium) ? attachment.sizes.medium.url : attachment.url;
                        $('#bd-ad-image-preview').html('<img src="' + imageUrl + '" />');
                        $('#bd-ad-remove-img-btn').show();
                    });
                    adFrame.open();
                });

                $('#bd-ad-remove-img-btn').on('click', function(e) {
                    e.preventDefault();
                    $('#bd_ad_image_id').val('0');
                    $('#bd-ad-image-preview').html('<span class="bd-ad-placeholder-text">Ninguna imagen seleccionada</span>');
                    $(this).hide();
                });
            });
        </script>
        <?php
    }

    /**
     * Guarda la configuración de la metabox de banners publicitarios.
     */
    public function save_ad_banner_meta( $post_id, $post ) {
        // Validar nonce
        if ( ! isset( $_POST['bd_ad_banner_nonce'] ) || ! wp_verify_nonce( $_POST['bd_ad_banner_nonce'], 'bd_ad_banner_nonce_action' ) ) {
            return;
        }

        // Evitar auto-save
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Verificar permisos
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Guardar Posición
        if ( isset( $_POST['bd_ad_position'] ) ) {
            $position = sanitize_text_field( wp_unslash( $_POST['bd_ad_position'] ) );
            update_post_meta( $post_id, '_bd_ad_position', $position );
        }

        // Guardar Imagen ID
        if ( isset( $_POST['bd_ad_image_id'] ) ) {
            $image_id = sanitize_text_field( wp_unslash( $_POST['bd_ad_image_id'] ) );
            update_post_meta( $post_id, '_bd_ad_image_id', $image_id );
        }

        // Guardar Enlace
        if ( isset( $_POST['bd_ad_link'] ) ) {
            $link = esc_url_raw( wp_unslash( $_POST['bd_ad_link'] ) );
            update_post_meta( $post_id, '_bd_ad_link', $link );
        }

        // Guardar Código Script / HTML alternativo
        if ( isset( $_POST['bd_ad_code'] ) ) {
            $code = current_user_can( 'unfiltered_html' ) ? wp_unslash( $_POST['bd_ad_code'] ) : wp_kses_post( wp_unslash( $_POST['bd_ad_code'] ) );
            update_post_meta( $post_id, '_bd_ad_code', $code );
        }
    }
}
