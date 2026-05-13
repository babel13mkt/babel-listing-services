<?php
/**
 * Registro de Custom Post Type y Taxonomías (BD_CPT)
 * v4.0.0 — Hito 14: Editor desactivado (anti-Divi, anti-Gutenberg).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BD_CPT {

    public function __construct() {
        add_action( 'init', array( $this, 'register_listing_cpt' ) );
        add_action( 'init', array( $this, 'register_listing_taxonomy' ) );

        // ── Desactivar Gutenberg / Divi para este CPT ──
        add_filter( 'use_block_editor_for_post_type', array( $this, 'disable_block_editor' ), 10, 2 );
        // Quitar el soporte del editor clásico y título (elimina el área de ruido completamente)
        add_action( 'init', array( $this, 'remove_supports' ), 99 );
        // Remover metaboxes de taxonomías nativos
        add_action( 'admin_menu', array( $this, 'remove_native_metaboxes' ) );
        // Impedir que Divi inyecte su builder
        add_filter( 'et_builder_post_types', array( $this, 'remove_from_divi' ) );
        add_filter( 'et_fb_enabled_for_post', array( $this, 'divi_disabled_for_negocio' ), 10, 2 );
    }

    /**
     * Retorna false para Gutenberg en nuestro CPT
     */
    public function disable_block_editor( $enabled, $post_type ) {
        if ( $post_type === 'directorio_negocio' ) {
            return false;
        }
        return $enabled;
    }

    /**
     * Elimina el soporte de elementos nativos
     */
    public function remove_supports() {
        remove_post_type_support( 'directorio_negocio', 'editor' );
        remove_post_type_support( 'directorio_negocio', 'title' );
        remove_post_type_support( 'directorio_negocio', 'excerpt' );
        remove_post_type_support( 'directorio_negocio', 'comments' );
        remove_post_type_support( 'directorio_negocio', 'author' );
    }

    /**
     * Quita las cajas de taxonomía del sidebar
     */
    public function remove_native_metaboxes() {
        remove_meta_box( 'directorio_categoriadiv', 'directorio_negocio', 'side' );
        remove_meta_box( 'directorio_regiondiv', 'directorio_negocio', 'side' );
        remove_meta_box( 'tagsdiv-directorio_categoria', 'directorio_negocio', 'side' );
        remove_meta_box( 'tagsdiv-directorio_region', 'directorio_negocio', 'side' );
    }

    /**
     * Quitar el CPT del array de post types que Divi intercepta
     */
    public function remove_from_divi( $post_types ) {
        if ( is_array( $post_types ) ) {
            $key = array_search( 'directorio_negocio', $post_types, true );
            if ( $key !== false ) {
                unset( $post_types[ $key ] );
            }
        }
        return $post_types;
    }

    /**
     * Deshabilitar el Divi Frontend Builder para negocios
     */
    public function divi_disabled_for_negocio( $enabled, $post ) {
        if ( $post && get_post_type( $post ) === 'directorio_negocio' ) {
            return false;
        }
        return $enabled;
    }

    public function register_listing_cpt() {
        $labels = array(
            'name'               => 'Directorio',
            'singular_name'      => 'Negocio',
            'menu_name'          => 'Directorio',
            'add_new'            => 'Agregar Negocio',
            'add_new_item'       => 'Agregar Nuevo Negocio',
            'edit_item'          => 'Editar Negocio',
            'new_item'           => 'Nuevo Negocio',
            'view_item'          => 'Ver Negocio',
            'search_items'       => 'Buscar Negocios',
            'not_found'          => 'No se encontraron negocios',
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'show_in_nav_menus'  => true,
            'has_archive'        => true,
            'rewrite'            => array( 'slug' => 'empresas' ),
            'menu_icon'          => 'dashicons-store',
            // Enganchar al menú Panel
            'show_in_menu'       => 'bd-panel',
            // Solo thumbnail — todo lo demás es nuestra App
            'supports'           => array( 'thumbnail' ),
            // false = desactiva Gutenberg a nivel de CPT
            'show_in_rest'       => false,
            'hierarchical'       => false,
        );

        register_post_type( 'directorio_negocio', $args );
    }

    public function register_listing_taxonomy() {
        // Categorías de Negocios
        register_taxonomy( 'directorio_categoria', array( 'directorio_negocio' ), array(
            'hierarchical' => true,
            'labels'       => array(
                'name'          => 'Categorías de Negocios',
                'singular_name' => 'Categoría de Negocio',
                'menu_name'     => 'Categorías del Directorio',
                'all_items'     => 'Todas las Categorías',
                'edit_item'     => 'Editar Categoría',
                'view_item'     => 'Ver Categoría',
                'update_item'   => 'Actualizar Categoría',
                'add_new_item'  => 'Agregar Nueva Categoría',
                'new_item_name' => 'Nombre de Nueva Categoría',
            ),
            'show_ui'      => true,
            'show_in_menu' => 'bd-panel',
            'show_in_rest' => true,
            'rewrite'      => array( 'slug' => 'categoria-negocio' ),
        ) );

        // Regiones
        register_taxonomy( 'directorio_region', array( 'directorio_negocio' ), array(
            'hierarchical' => true,
            'labels'       => array(
                'name'          => 'Regiones',
                'singular_name' => 'Región',
                'menu_name'     => 'Regiones',
                'all_items'     => 'Todas las Regiones',
                'add_new_item'  => 'Agregar Nueva Región',
                'new_item_name' => 'Nombre de Nueva Región',
            ),
            'show_ui'      => true,
            'show_in_menu' => 'bd-panel',
            'show_in_rest' => true,
            'rewrite'      => array( 'slug' => 'region-negocio' ),
        ) );
    }
}
