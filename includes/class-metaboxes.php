<?php
/**
 * Clase para el manejo de Metaboxes y Custom Fields unificados con estética SaaS moderna.
 * v8.0.0 — Panel Central Compacto por Pestañas, sin Sidebar de Categorías/Imagen y sin editor Gutenberg.
 *
 * @package Babel_Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}

namespace Babel\Directory;


class Metaboxes {

    /**
     * Constructor de la clase de metaboxes.
     * Enlaza los hooks de WordPress para renderizado, guardado y assets del administrador.
     */
    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_business_meta_box' ) );
        add_action( 'save_post_babel_business', array( $this, 'save_business_meta' ), 10, 2 );
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
     * Encola los scripts nativos de medios y taxonomías en la pantalla del CPT.
     *
     * @param string $hook Identificador de la página actual del panel de administración.
     */
    public function enqueue_admin_assets( $hook ) {
        $screen = get_current_screen();
        if ( $screen && 'babel_business' === $screen->post_type ) {
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
            $hours = json_decode( $hours_meta, true );
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

        // Parsear IDs de galería
        $gallery_ids = array();
        if ( ! empty( $gallery ) ) {
            $gallery_ids = explode( ',', $gallery );
            $gallery_ids = array_filter( array_map( 'intval', $gallery_ids ) );
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

            /* 1. Reset y Contenedor Principal */
            .bd-metabox-wrapper {
                background: transparent;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                color: #334155;
                box-sizing: border-box;
                padding: 0;
            }
            
            /* Pestañas / Tabs Navigation with seamless overlapping */
            .bd-tabs-nav {
                display: flex;
                gap: 6px;
                border-bottom: 2px solid #cbd5e1;
                margin: 0 0 15px 0;
                list-style: none;
                padding: 0;
            }
            .bd-tab-link {
                padding: 12px 20px;
                cursor: pointer;
                border-radius: 8px 8px 0 0;
                background: #e2e8f0;
                font-weight: 600;
                font-size: 13px;
                color: #64748b;
                border: 1px solid #cbd5e1;
                border-bottom: none;
                transition: all 0.2s ease;
                display: flex;
                align-items: center;
                gap: 8px;
                margin-bottom: -2px; /* Pull down to overlay the active border line */
                position: relative;
                z-index: 1;
            }
            .bd-tab-link:hover {
                background: #cbd5e1;
                color: #1e293b;
            }
            .bd-tab-link.active {
                background: #ffffff;
                color: #219ebc;
                border-color: #cbd5e1;
                border-bottom-color: #ffffff;
                z-index: 3;
            }
            
            /* Paneles de Contenido */
            .bd-tab-panel {
                display: none;
            }
            .bd-tab-panel.active {
                display: block;
            }
            
            /* Sistema de Grillas Premium */
            .bd-metabox-grid {
                display: grid;
                grid-template-columns: repeat(12, 1fr);
                gap: 16px;
                padding: 20px;
                background: #ffffff;
                border: 1px solid #cbd5e1;
                border-radius: 0 0 8px 8px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
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
        </style>

        <div class="bd-metabox-wrapper">
            <!-- Navegación por pestañas -->
            <ul class="bd-tabs-nav">
                <li class="bd-tab-link active" data-tab="tab-general">🏢 <?php esc_html_e( 'General', 'babel-directory' ); ?></li>
                <li class="bd-tab-link" data-tab="tab-contacto">📞 <?php esc_html_e( 'Contacto', 'babel-directory' ); ?></li>
                <li class="bd-tab-link" data-tab="tab-redes">🌐 <?php esc_html_e( 'Redes y Estados', 'babel-directory' ); ?></li>
                <li class="bd-tab-link" data-tab="tab-horarios">⏰ <?php esc_html_e( 'Horarios y Medios', 'babel-directory' ); ?></li>
            </ul>

            <!-- PANELES DE CONTENIDO -->

            <!-- PESTAÑA 1: GENERAL -->
            <div id="tab-general" class="bd-tab-panel active">
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
                </div>
            </div>

            <!-- PESTAÑA 2: CONTACTO -->
            <div id="tab-contacto" class="bd-tab-panel">
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

            <!-- PESTAÑA 3: REDES SOCIALES Y ESTADOS -->
            <div id="tab-redes" class="bd-tab-panel">
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
                        </div>
                    </div>
                </div>
            </div>

            <!-- PESTAÑA 4: HORARIOS Y MEDIOS -->
            <div id="tab-horarios" class="bd-tab-panel">
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
                </div>
            </div>
        </div>

        <script>
            jQuery(document).ready(function($) {
                // --- CAMBIO DE PESTAÑAS (TABS LOGIC) ---
                $('.bd-tab-link').on('click', function(e) {
                    e.preventDefault();
                    var tabId = $(this).data('tab');
                    
                    $('.bd-tab-link').removeClass('active');
                    $('.bd-tab-panel').removeClass('active');
                    
                    $(this).addClass('active');
                    $('#' + tabId).addClass('active');
                });

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

        // Tags / Palabras Clave SEO
        if ( isset( $_POST['babel_biz_tags'] ) ) {
            $raw_tags   = sanitize_text_field( wp_unslash( $_POST['babel_biz_tags'] ) );
            $tags_array = array_filter( array_map( 'sanitize_text_field', explode( ',', $raw_tags ) ) );
            update_post_meta( $post_id, '_babel_biz_tags', implode( ',', $tags_array ) );
        }
    }
}
