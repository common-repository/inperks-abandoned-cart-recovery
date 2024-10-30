<?php
/**
 * Admin cart recovery email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-acbwm-recover-ac-default.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 * @var $email_heading string
 * @var $cart_data stdClass
 */
defined('ABSPATH') || exit;
/*
 * @hooked WC_Emails::email_header() Output the email header
 */
if ($cart_data->use_woocommerce_style == 1) {
    do_action('woocommerce_email_header', $email_heading, $email);
}
$short_codes = array(
    '{{cart.recovery_url}}' => $cart_data->recovery_url,
    '{{cart.items_table}}' => wc_get_template_html('cart-items.php', array('cart_data' => $cart_data), '', ACBWM_ABSPATH . 'templates/common/'),
    '{{coupon_code}}' => $cart_data->coupon_code,
    '{{expire_time}}' => $cart_data->expire_time,
    '{{coupon_value}}' => $cart_data->discount,
    '{{coupon_apply_url}}' => $cart_data->discount_url,
    '{{customer.name}}' => $cart_data->customer
);
$short_codes = apply_filters(ACBWM_HOOK_PREFIX . 'email_template_short_codes', $short_codes, $cart_data);
foreach ($short_codes as $find => $replace) {
    $body = str_replace($find, $replace, $body);
}
include ACBWM_ABSPATH . 'includes/library/class-acbwm-lib-template-parser.php';
echo Acbwm_Lib_Template_Parser::parse($body, $cart_data);
echo $cart_data->tracking_image;
/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
if ($cart_data->use_woocommerce_style == 1) {
    do_action('woocommerce_email_footer', $email);
}
