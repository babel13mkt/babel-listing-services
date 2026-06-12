<?php
// Script temporal para inyectar productos virtuales en WooCommerce
// Ejecutar con: wp eval-file create_wc_products.php

if ( ! class_exists( 'WooCommerce' ) ) {
    die( "WooCommerce no está activo.\n" );
}

echo "Creando productos en WooCommerce...\n";

function create_babel_product( $title, $price, $description, $sku ) {
    $existing_id = wc_get_product_id_by_sku( $sku );
    if ( $existing_id ) {
        echo "El producto '$title' ya existe (ID: $existing_id).\n";
        return $existing_id;
    }

    $product = new WC_Product_Simple();
    $product->set_name( $title );
    $product->set_status( 'publish' );
    $product->set_catalog_visibility( 'hidden' ); // Ocultarlo de la tienda general
    $product->set_description( $description );
    $product->set_sku( $sku );
    $product->set_price( $price );
    $product->set_regular_price( $price );
    $product->set_virtual( true ); // No requiere envío
    $product->set_sold_individually( true ); // Solo 1 a la vez por compra
    
    $product_id = $product->save();
    echo "✅ Creado: '$title' (ID: $product_id)\n";
    return $product_id;
}

// 1. Plan Profesional
$pro_desc = "<h2>Desbloquea todo el potencial de tu negocio en Soy de Chile</h2>
<p>El Plan Profesional está diseñado para aquellos que entienden que estar en internet no es suficiente: <strong>hay que convertir a los visitantes en clientes.</strong></p>
<ul>
<li>✅ <strong>Botón directo de WhatsApp:</strong> Recibe consultas directamente a tu móvil.</li>
<li>✅ <strong>Enlace a tu Sitio Web:</strong> Mejora dramáticamente tu posicionamiento en Google (SEO Dofollow).</li>
<li>✅ <strong>Galería Premium:</strong> Sube hasta 5 fotos para mostrar tus mejores productos o instalaciones.</li>
<li>✅ <strong>Horarios Dinámicos:</strong> Mantén a tus clientes informados de cuándo estás abierto.</li>
<li>✅ <strong>Sello de Empresa Verificada:</strong> Genera mayor confianza inmediata.</li>
</ul>
<p><em>Inversión 100% deducible. Un solo cliente ganado por WhatsApp paga tu plan por meses.</em></p>";

// Precio referencial: $19.990 (CLP) - Podremos cambiarlo luego en WooCommerce
create_babel_product( 'Plan Profesional - Soy de Chile', '19990', $pro_desc, 'BABEL-PRO' );

// 2. Plan Premium (Destacado)
$premium_desc = "<h2>Domina tu región y categoría. No dejes clientes a la competencia.</h2>
<p>El Plan Premium te convierte en el Rey de tu zona. Alguien busca tu rubro en tu región, y tú apareces primero. Siempre.</p>
<ul>
<li>👑 <strong>Todo lo del Plan Profesional incluido.</strong></li>
<li>⭐ <strong>Posición #1 Garantizada:</strong> Tu negocio se fijará en la parte superior de los resultados de búsqueda.</li>
<li>⭐ <strong>Sin anuncios de la competencia:</strong> Limpiamos tu perfil de distracciones. Tu página es 100% tuya.</li>
<li>⭐ <strong>Tarjeta Destacada:</strong> Diseño visual exclusivo en la grilla que atrae la vista.</li>
</ul>
<p><em>Ideal para rubros altamente competitivos donde el primer clic se lleva la venta.</em></p>";

// Precio referencial: $39.990 (CLP)
create_babel_product( 'Plan Premium (Destacado) - Soy de Chile', '39990', $premium_desc, 'BABEL-PREMIUM' );

echo "¡Listo! Puedes borrar este archivo.\n";
