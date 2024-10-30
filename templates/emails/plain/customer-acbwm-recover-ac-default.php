<?php
echo $email_heading . "\n\n";
if (isset($cart_data) && !empty($cart_data)) {
    $items = $cart_data->items;
    $totals = $cart_data->totals;
    foreach ($items as $item) {
        echo sprintf(__('Title: %s', ACBWM_TEXT_DOMAIN), wp_kses_post($item['title'])) . "\n";
        if (!empty($item['sku'])) {
            echo sprintf(__('SKU: %s', ACBWM_TEXT_DOMAIN), wp_kses_post(' (#' . $item['sku'] . ')')) . "\n";
        }
        echo sprintf(__('Quantity: %s', ACBWM_TEXT_DOMAIN), $item['quantity']) . "\n";
        echo sprintf(__('Total: %s', ACBWM_TEXT_DOMAIN), $item['total']) . "\n\n";
    }
    echo "\n\n";
    if ($totals) {
        $i = 0;
        foreach ($totals as $total) {
            if ($total['value'] != 0) {
                echo $total['label'] . ': ' . $total['value'];
            }
        }
    }
    echo "\n\n";
    echo sprintf(__('Go to cart: %s', ACBWM_TEXT_DOMAIN), $cart_data->recovery_url);
}
echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
echo apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text'));