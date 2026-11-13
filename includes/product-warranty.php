<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('woocommerce_product_meta_end', 'nbe_render_product_warranty');

/**
 * Muestra la garantía sincronizada en el bloque de metadatos de la ficha de producto.
 *
 */
function nbe_render_product_warranty()
{
    global $product;

    if (!$product instanceof WC_Product) {
        return;
    }

    $warranty = (string) $product->get_meta('_nbe_warranty');
    if ($warranty === '') {
        return;
    }

    echo '<span class="nbe-warranty">Garantía: <span class="nbe-warranty__value">' . esc_html($warranty) . '</span></span>';
}
