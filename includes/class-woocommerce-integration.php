<?php
namespace Babel\Directory;

/**
 * Integración con WooCommerce para Monetización
 * Maneja el cambio de estado de pedidos para activar Planes de Suscripción.
 *
 * @package Babel_Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WooCommerce_Integration {

    public function __construct() {
        // Hook que se dispara cuando un pago se completa en WooCommerce
        add_action( 'woocommerce_order_status_completed', array( $this, 'process_plan_activation' ) );
        
        // Evitar que el ítem del carrito necesite envío (aunque sea virtual, asegurar checkout directo)
        add_filter( 'woocommerce_cart_needs_shipping_address', '__return_false' );
    }

    /**
     * Procesa la activación del plan cuando el pedido es "Completado" (Pagado).
     */
    public function process_plan_activation( $order_id ) {
        if ( ! $order_id ) {
            return;
        }

        $order = wc_get_order( $order_id );
        $items = $order->get_items();

        foreach ( $items as $item_id => $item ) {
            $product = $item->get_product();
            if ( ! $product ) {
                continue;
            }

            $sku = $product->get_sku();

            // Ver si es un producto nuestro
            if ( in_array( $sku, array( 'BABEL-PRO', 'BABEL-PREMIUM' ) ) {

                // Obtener el meta oculto que enviamos al carrito
                $target_post_id = wc_get_order_item_meta( $item_id, 'babel_target_post_id', true );

                if ( $target_post_id ) {
                    $this->upgrade_business( $target_post_id, $sku );
                } else {
                    // Fallback: Si por alguna razón no venía el ID (compra manual), aplicarlo al último negocio del usuario
                    $user_id = $order->get_customer_id();
                    $last_business = get_posts( array(
                        'post_type'      => 'babel_business',
                        'author'         => $user_id,
                        'posts_per_page' => 1,
                        'orderby'        => 'ID',
                        'order'          => 'DESC',
                        'fields'         => 'ids'
                    ) );
                    if ( ! empty( $last_business ) ) {
                        $this->upgrade_business( $last_business[0], $sku );
                    }
                }
            } elseif ( strpos( $sku, 'BABEL-FEATURED-' ) === 0 ) {
                // Featured Listing: activar destacado por tiempo limitado
                $this->process_featured_activation( $order, $item_id, $sku );
            }
        }
    }

    /**
     * Aplica el upgrade en la base de datos para el negocio
     */
    private function upgrade_business( $post_id, $sku ) {
        // Asegurarnos que es un negocio
        if ( get_post_type( $post_id ) !== 'babel_business' ) {
            return;
        }

        if ( $sku === 'BABEL-PREMIUM' ) {
            update_post_meta( $post_id, '_babel_plan_type', 'premium' );
            update_post_meta( $post_id, '_babel_is_featured', 1 );
        } elseif ( $sku === 'BABEL-PRO' ) {
            update_post_meta( $post_id, '_babel_plan_type', 'profesional' );
            // Si baja de Premium a Pro, quitar el destacado
            update_post_meta( $post_id, '_babel_is_featured', 0 ); 
        }

        // Forzar actualización de la tabla de búsqueda rápida (sync)
        if ( class_exists( 'Babel\Directory\Search_Index' ) ) {
            $indexer = new \Babel\Directory\Search_Index();
            $indexer->sync_business_to_index( $post_id, get_post( $post_id ), true );
        }
    }

    /**
     * Procesa la activación de un Featured Listing tras el pago.
     *
     * @param \WC_Order $order    Objeto del pedido.
     * @param int       $item_id  ID del item del pedido.
     * @param string    $sku      SKU del producto (BABEL-FEATURED-7D/30D/90D).
     */
    private function process_featured_activation( $order, $item_id, $sku ) {
        // Extraer duración del SKU: BABEL-FEATURED-30D -> 30
        $duration_days = absint( str_replace( array( 'BABEL-FEATURED-', 'D' ), '', $sku ) );

        // Obtener el negocio objetivo
        $target_post_id = wc_get_order_item_meta( $item_id, 'babel_target_post_id', true );
        if ( ! $target_post_id ) {
            // Fallback: último negocio del usuario
            $user_id = $order->get_customer_id();
            $last_business = get_posts( array(
                'post_type'      => 'babel_business',
                'author'         => $user_id,
                'post_status'    => 'publish',
                'posts_per_page' => 1,
                'orderby'        => 'ID',
                'order'          => 'DESC',
                'fields'         => 'ids'
            ) );
            if ( ! empty( $last_business ) ) {
                $target_post_id = $last_business[0];
            }
        }

        if ( ! $target_post_id ) {
            return;
        }

        // Activar via Featured_Listings
        if ( class_exists( 'Babel\Directory\Featured_Listings' ) ) {
            $featured = new Featured_Listings();
            $featured->activate_featured( $target_post_id, $duration_days, $sku );
        }

        // Registrar meta de pago para auditoría
        update_post_meta( $target_post_id, '_babel_featured_payment_' . $order->get_id(), array(
            'sku'      => $sku,
            'duration' => $duration_days,
            'date'     => current_time( 'mysql' ),
            'amount'   => $order->get_total(),
        ) );
    }
}
