<?php
namespace Babel\Directory;

/**
 * Clase para el registro de Custom Post Types y Taxonomías
 *
 * @package Babel_Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}
class CPT {

    /**
     * Constructor de la clase CPT.
     * Registra los ganchos de inicialización.
     */
    public function __construct() {
        // Registrar taxonomías y CPT en el gancho 'init'
        add_action( 'init', array( $this, 'register_taxonomies' ), 9 );
        add_action( 'init', array( $this, 'register_cpts' ), 10 );
        add_action( 'init', array( $this, 'register_meta_fields' ), 11 );
        add_action( 'init', array( $this, 'register_rewrite_rules' ), 12 );
    }

    /**
     * Registra las reglas de reescritura estáticas para cruce de taxonomías.
     */
    public function register_rewrite_rules() {
        // Regla con paginación
        add_rewrite_rule(
            '^region/([^/]+)/categoria/([^/]+)/page/([0-9]{1,})/?$',
            'index.php?babel_region=$matches[1]&babel_category=$matches[2]&paged=$matches[3]',
            'top'
        );
        // Regla sin paginación
        add_rewrite_rule(
            '^region/([^/]+)/categoria/([^/]+)/?$',
            'index.php?babel_region=$matches[1]&babel_category=$matches[2]',
            'top'
        );
    }

    /**
     * Registra los Custom Post Types 'babel_business' (Negocios) y 'bd_ad_banner' (Anuncios).
     */
    public function register_cpts() {
        $labels = array(
            'name'                  => _x( 'Negocios', 'Post Type General Name', 'babel-directory' ),
            'singular_name'         => _x( 'Negocio', 'Post Type Singular Name', 'babel-directory' ),
            'menu_name'             => __( 'Negocios', 'babel-directory' ),
            'name_admin_bar'        => __( 'Negocio', 'babel-directory' ),
            'archives'              => __( 'Archivo de Negocios', 'babel-directory' ),
            'attributes'            => __( 'Atributos de Negocio', 'babel-directory' ),
            'parent_item_colon'     => __( 'Negocio Padre:', 'babel-directory' ),
            'all_items'             => __( 'Todos los Negocios', 'babel-directory' ),
            'add_new_item'          => __( 'Añadir Nuevo Negocio', 'babel-directory' ),
            'add_new'               => __( 'Añadir Nuevo', 'babel-directory' ),
            'new_item'              => __( 'Nuevo Negocio', 'babel-directory' ),
            'edit_item'             => __( 'Editar Negocio', 'babel-directory' ),
            'update_item'           => __( 'Actualizar Negocio', 'babel-directory' ),
            'view_item'             => __( 'Ver Negocio', 'babel-directory' ),
            'view_items'            => __( 'Ver Negocios', 'babel-directory' ),
            'search_items'          => __( 'Buscar Negocio', 'babel-directory' ),
            'not_found'             => __( 'No se encontraron negocios', 'babel-directory' ),
            'not_found_in_trash'    => __( 'No se encontraron negocios en la papelera', 'babel-directory' ),
            'featured_image'        => __( 'Logotipo/Imagen Destacada', 'babel-directory' ),
            'set_featured_image'    => __( 'Establecer logotipo/imagen', 'babel-directory' ),
            'remove_featured_image' => __( 'Eliminar logotipo/imagen', 'babel-directory' ),
            'use_featured_image'    => __( 'Usar como logotipo/imagen', 'babel-directory' ),
            'insert_into_item'      => __( 'Insertar en negocio', 'babel-directory' ),
            'uploaded_to_this_item' => __( 'Subido a este negocio', 'babel-directory' ),
            'items_list'            => __( 'Lista de negocios', 'babel-directory' ),
            'items_list_navigation' => __( 'Navegación de lista de negocios', 'babel-directory' ),
            'filter_items_list'     => __( 'Filtrar lista de negocios', 'babel-directory' ),
        );

        $args = array(
            'label'                 => __( 'Negocio', 'babel-directory' ),
            'description'           => __( 'Listado de Negocios de Babel Directory', 'babel-directory' ),
            'labels'                => $labels,
            'supports'              => array( 'thumbnail', 'revisions' ),
            'taxonomies'            => array( 'babel_region', 'babel_category' ),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => false,
            'menu_position'         => 5,
            'menu_icon'             => 'dashicons-store',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true, // Habilitar soporte REST API para Divi 5
            'rewrite'               => array( 'slug' => 'negocio' ),
        );

        register_post_type( 'babel_business', $args );

        // Registrar CPT para Anuncios Publicitarios (Banners)
        $labels_ads = array(
            'name'                  => _x( 'Anuncios', 'Post Type General Name', 'babel-directory' ),
            'singular_name'         => _x( 'Anuncio', 'Post Type Singular Name', 'babel-directory' ),
            'menu_name'             => __( 'Anuncios Publicitarios', 'babel-directory' ),
            'name_admin_bar'        => __( 'Anuncio', 'babel-directory' ),
            'all_items'             => __( 'Todos los Anuncios', 'babel-directory' ),
            'add_new_item'          => __( 'Añadir Nuevo Anuncio', 'babel-directory' ),
            'add_new'               => __( 'Añadir Nuevo', 'babel-directory' ),
            'new_item'              => __( 'Nuevo Anuncio', 'babel-directory' ),
            'edit_item'             => __( 'Editar Anuncio', 'babel-directory' ),
            'update_item'           => __( 'Actualizar Anuncio', 'babel-directory' ),
            'view_item'             => __( 'Ver Anuncio', 'babel-directory' ),
            'search_items'          => __( 'Buscar Anuncio', 'babel-directory' ),
            'not_found'             => __( 'No se encontraron anuncios', 'babel-directory' ),
            'not_found_in_trash'    => __( 'No se encontraron anuncios en la papelera', 'babel-directory' ),
        );

        $args_ads = array(
            'label'                 => __( 'Anuncio', 'babel-directory' ),
            'description'           => __( 'Banners y Espacios Publicitarios de Babel Directory', 'babel-directory' ),
            'labels'                => $labels_ads,
            'supports'              => array( 'title' ),
            'taxonomies'            => array( 'babel_region' ),
            'hierarchical'          => false,
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => true, // Mantener en menú lateral para fácil gestión
            'menu_position'         => 6,
            'menu_icon'             => 'dashicons-megaphone',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => false,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
        );

        register_post_type( 'bd_ad_banner', $args_ads );
    }

    /**
     * Registra las Taxonomías jerárquicas vinculadas al CPT.
     */
    public function register_taxonomies() {
        // 1. Taxonomía Región/Ubicación (babel_region)
        $labels_region = array(
            'name'                       => _x( 'Regiones', 'Taxonomy General Name', 'babel-directory' ),
            'singular_name'              => _x( 'Región', 'Taxonomy Singular Name', 'babel-directory' ),
            'menu_name'                  => __( 'Regiones/Comunas', 'babel-directory' ),
            'all_items'                  => __( 'Todas las Regiones/Comunas', 'babel-directory' ),
            'parent_item'                => __( 'Región Padre', 'babel-directory' ),
            'parent_item_colon'          => __( 'Región Padre:', 'babel-directory' ),
            'new_item_name'              => __( 'Nueva Región/Comuna', 'babel-directory' ),
            'add_new_item'               => __( 'Añadir Nueva Región/Comuna', 'babel-directory' ),
            'edit_item'                  => __( 'Editar Región/Comuna', 'babel-directory' ),
            'update_item'                => __( 'Actualizar Región/Comuna', 'babel-directory' ),
            'view_item'                  => __( 'Ver Región/Comuna', 'babel-directory' ),
            'separate_items_with_commas' => __( 'Separar comunas con comas', 'babel-directory' ),
            'add_or_remove_items'        => __( 'Añadir o quitar regiones/comunas', 'babel-directory' ),
            'choose_from_most_used'      => __( 'Elegir de las más usadas', 'babel-directory' ),
            'popular_items'              => __( 'Regiones populares', 'babel-directory' ),
            'search_items'               => __( 'Buscar Regiones/Comunas', 'babel-directory' ),
            'not_found'                  => __( 'No encontradas', 'babel-directory' ),
            'no_terms'                   => __( 'Sin regiones/comunas', 'babel-directory' ),
            'items_list'                 => __( 'Lista de Regiones/Comunas', 'babel-directory' ),
            'items_list_navigation'      => __( 'Navegación de lista de regiones/comunas', 'babel-directory' ),
        );

        $args_region = array(
            'labels'            => $labels_region,
            'hierarchical'      => true, // Jerárquica tipo categoría (Región -> Provincia -> Comuna)
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud'     => true,
            'show_in_rest'      => true, // Habilita rest para soporte en Gutenberg
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'region' ),
        );

        register_taxonomy( 'babel_region', array( 'babel_business', 'bd_ad_banner' ), $args_region );

        // 2. Taxonomía Categoría de Negocio (babel_category)
        $labels_category = array(
            'name'                       => _x( 'Categorías de Negocio', 'Taxonomy General Name', 'babel-directory' ),
            'singular_name'              => _x( 'Categoría de Negocio', 'Taxonomy Singular Name', 'babel-directory' ),
            'menu_name'                  => __( 'Categorías', 'babel-directory' ),
            'all_items'                  => __( 'Todas las Categorías', 'babel-directory' ),
            'parent_item'                => __( 'Categoría Padre', 'babel-directory' ),
            'parent_item_colon'          => __( 'Categoría Padre:', 'babel-directory' ),
            'new_item_name'              => __( 'Nueva Categoría', 'babel-directory' ),
            'add_new_item'               => __( 'Añadir Nueva Categoría', 'babel-directory' ),
            'edit_item'                  => __( 'Editar Categoría', 'babel-directory' ),
            'update_item'                => __( 'Actualizar Categoría', 'babel-directory' ),
            'view_item'                  => __( 'Ver Categoría', 'babel-directory' ),
            'separate_items_with_commas' => __( 'Separar categorías con comas', 'babel-directory' ),
            'add_or_remove_items'        => __( 'Añadir o quitar categorías', 'babel-directory' ),
            'choose_from_most_used'      => __( 'Elegir de las más usadas', 'babel-directory' ),
            'popular_items'              => __( 'Categorías populares', 'babel-directory' ),
            'search_items'               => __( 'Buscar Categorías', 'babel-directory' ),
            'not_found'                  => __( 'No encontradas', 'babel-directory' ),
            'no_terms'                   => __( 'Sin categorías', 'babel-directory' ),
            'items_list'                 => __( 'Lista de categorías', 'babel-directory' ),
            'items_list_navigation'      => __( 'Navegación de lista de categorías', 'babel-directory' ),
        );

        $args_category = array(
            'labels'            => $labels_category,
            'hierarchical'      => true, // Jerárquica tipo categoría (Categoría principal -> Subcategoría)
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud'     => true,
            'show_in_rest'      => true, // Soporte en Gutenberg
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'categoria' ),
        );

        register_taxonomy( 'babel_category', array( 'babel_business' ), $args_category );
    }

    /**
     * Registra los campos de metadatos en la REST API para Divi 5 (Dynamic Content).
     */
    public function register_meta_fields() {
        $meta_keys = array(
            '_babel_phone'            => 'string',
            '_babel_whatsapp'         => 'string',
            '_babel_email'            => 'string',
            '_babel_address'          => 'string',
            '_babel_maps'             => 'string',
            '_babel_gmaps'            => 'string',
            '_babel_lat'              => 'string',
            '_babel_latitude'         => 'string',
            '_babel_lng'              => 'string',
            '_babel_longitude'        => 'string',
            '_babel_website'          => 'string',
            '_babel_instagram'        => 'string',
            '_babel_facebook'         => 'string',
            '_babel_linkedin'         => 'string',
            '_babel_gallery'          => 'string',
            '_babel_hours'            => 'string',
            '_babel_verified'         => 'string',
            '_babel_featured'         => 'string',
            '_babel_biz_tags'         => 'string',
            '_babel_is_institution'   => 'string',
            // Legacy keys for compatibility
            '_bd_telefono'            => 'string',
            '_bd_whatsapp'            => 'string',
            '_bd_email'               => 'string',
            '_bd_direccion'           => 'string',
            '_bd_gmaps'               => 'string',
            '_bd_latitud'             => 'string',
            '_bd_longitud'            => 'string',
            '_bd_sitio_web'           => 'string',
            '_bd_web'                 => 'string',
            '_bd_galeria'             => 'string',
            '_bd_verificado'          => 'string',
            '_bd_destacado'           => 'string',
            '_bd_logo_id'             => 'string',
        );

        foreach ( $meta_keys as $key => $type ) {
            register_post_meta( 'babel_business', $key, array(
                'show_in_rest' => true,
                'single'       => true,
                'type'         => $type,
            ) );
        }

        // Meta campos para el CPT de anuncios publicitarios (bd_ad_banner)
        $ad_meta_keys = array(
            '_bd_ad_position'    => 'string',
            '_bd_ad_image_id'    => 'string',
            '_bd_ad_link'        => 'string',
            '_bd_ad_code'        => 'string',
            '_bd_ad_clicks'      => 'integer',
            '_bd_ad_impressions' => 'integer',
        );

        foreach ( $ad_meta_keys as $key => $type ) {
            register_post_meta( 'bd_ad_banner', $key, array(
                'show_in_rest' => true,
                'single'       => true,
                'type'         => $type,
            ) );
        }
    }
}
