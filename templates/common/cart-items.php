<?php
defined('ABSPATH') || exit;
$text_align = is_rtl() ? 'right' : 'left';
$margin_side = is_rtl() ? 'left' : 'right';
if (isset($cart_data) && !empty($cart_data)) {
    $items = $cart_data->items;
    $totals = $cart_data->totals;
    add_action(ACBWM_HOOK_PREFIX . 'before_abandoned_cart_email_table', $cart_data);
    ?>
    <table class="acbwm-cart" style="width: 100%;">
        <thead>
        <tr>
            <th style="text-align:<?php echo esc_attr($text_align); ?>;"><?php esc_html_e('Item', ACBWM_TEXT_DOMAIN); ?></th>
            <th style="text-align:<?php echo esc_attr($text_align); ?>;"><?php esc_html_e('Quantity', ACBWM_TEXT_DOMAIN); ?></th>
            <th style="text-align:<?php echo esc_attr($text_align); ?>;"><?php esc_html_e('Price', ACBWM_TEXT_DOMAIN); ?></th>
        </tr>
        </thead>
        <tbody class="acbwm-cart-items">
        <?php
        foreach ($items as $item) {
            ?>
            <tr>
                <td style="text-align:<?php echo esc_attr($text_align); ?>; vertical-align: middle; word-wrap:break-word;">
                    <?php
                    echo wp_kses_post($item['image']);
                    // Product name.
                    echo wp_kses_post($item['title']);
                    // SKU.
                    if (!empty($item['sku'])) {
                        echo wp_kses_post(' (#' . $item['sku'] . ')');
                    }
                    if (!empty($item['extra'])) {
                        ?>
                        <ul class="cart-item-details-extra">
                            <?php
                            foreach ($item['extra'] as $extra_key => $extra_value) {
                                echo apply_filters(ACBWM_HOOK_PREFIX . 'cart_item_details_extra_li', "<li>{$extra_value}</li>", $extra_key, $extra_value, $item['raw']);
                            }
                            ?>
                        </ul>
                        <?php
                    }
                    ?>
                </td>
                <td
                        style="text-align:<?php echo esc_attr($text_align); ?>; vertical-align:middle;">
                    <?php
                    echo wp_kses_post($item['quantity']);
                    ?>
                </td>
                <td
                        style="text-align:<?php echo esc_attr($text_align); ?>; vertical-align:middle; ">
                    <?php echo wp_kses_post(wc_price($item['total'], array('currency' => $cart_data->currency))); ?>
                </td>
            </tr>
            <?php
        }
        ?>
        </tbody>
        <tfoot>
        <?php
        if ($totals) {
            $i = 0;
            foreach ($totals as $total) {
                if ($total['value'] != 0) {
                    $i++;
                    ?>
                    <tr>
                        <th colspan="2" style="text-align: right;"><?php echo wp_kses_post($total['label']); ?>:</th>
                        <td><?php echo wp_kses_post(wc_price($total['value'], array('currency' => $cart_data->currency))); ?></td>
                    </tr>
                    <?php
                }
            }
        }
        ?>
        </tfoot>
    </table>
    <?php
    add_action(ACBWM_HOOK_PREFIX . 'after_abandoned_cart_email_table', $cart_data);
}