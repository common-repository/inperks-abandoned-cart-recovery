<?php
defined('ABSPATH') || exit;

class Acbwm_Coupons
{
    /**
     * default_values for coupon
     * @return array
     */
    public static function coupon_default_values()
    {
        return array(
            'type' => 'fixed_cart',
            'value' => '0',
            'free_shipping' => 'no'
        );
    }

    /**
     * add our own coupon to cart
     * @param $response
     * @param $coupon_code
     * @return mixed|void
     */
    public static function add_virtual_coupon($response, $coupon_code)
    {
        global $inperks_abandoned_carts;
        $prefix = $inperks_abandoned_carts->settings->get('coupon_code_prefix', 'WMC');
        if (empty($prefix)) {
            $prefix = 'WMC';
        }
        $coupon_code = sanitize_text_field($coupon_code);
        if (!empty($coupon_code) && acbwm_string_has_prefix($coupon_code, $prefix)) {
            $coupon_code = strtoupper($coupon_code);
            $coupon = Acbwm_Data_Store_Discounts::get_coupon($coupon_code);
            if (empty($coupon)) {
                return $response;
            }
            $expired_at = strtotime($coupon->expired_at);
            if (current_time('timestamp', true) > $expired_at) {
                return $response;
            }
            $coupon_details_db = maybe_unserialize($coupon->details);
            $default_coupon_details = self::coupon_default_values();
            $coupon_details = wp_parse_args($coupon_details_db, $default_coupon_details);
            if ($coupon_details['value'] > 0) {
                $coupon = array(
                    'id' => 321123 . rand(2, 9),
                    'amount' => $coupon_details['value'],
                    'individual_use' => false,
                    'product_ids' => array(),
                    'excluded_product_ids' => array(),
                    'usage_limit' => '',
                    'usage_limit_per_user' => '',
                    'limit_usage_to_x_items' => '',
                    'usage_count' => '',
                    'expiry_date' => $expired_at,
                    'apply_before_tax' => 'no',
                    'free_shipping' => ($coupon_details['free_shipping'] == "yes"),
                    'product_categories' => array(),
                    'excluded_product_categories' => array(),
                    'exclude_sale_items' => false,
                    'minimum_amount' => '',
                    'maximum_amount' => '',
                    'customer_email' => '',
                    'discount_type' => $coupon_details['type'],
                    'virtual' => true
                );
                return apply_filters(ACBWM_HOOK_PREFIX . 'add_virtual_coupon', $coupon, $coupon_code);
            }
        }
        return $response;
    }

    /**
     * create coupon for cart
     * @param $coupon_value
     * @param $coupon_type
     * @param $expire_time
     * @param string $free_shipping
     * @param string $created_by
     * @param int $cart_id
     * @param int $mail_id
     * @return array|bool
     */
    public static function create_coupon($coupon_value, $coupon_type, $expire_time, $free_shipping = 'yes', $cart_id = 0, $mail_id = 0, $created_by = 'coupon_timer')
    {
        global $inperks_abandoned_carts;
        if (empty($coupon_value)) {
            return false;
        }
        if (empty($coupon_type) || !in_array($coupon_type, array('fixed_cart', 'percent'))) {
            return false;
        }
        if (!in_array($free_shipping, array('yes', 'no'))) {
            return false;
        }
        $prefix = $inperks_abandoned_carts->settings->get('coupon_code_prefix', 'WMC');
        if (empty($prefix)) {
            $prefix = 'wmc';
        }
        $coupon_code = strtoupper($prefix . '-' . uniqid());
        $details = array(
            'type' => $coupon_type,
            'value' => $coupon_value,
            'free_shipping' => $free_shipping
        );
        $details = maybe_serialize($details);
        $coupon_details = array(
            'cart_id' => $cart_id,
            'code' => $coupon_code,
            'expired_at' => $expire_time,
            'created_at' => current_time('mysql', true),
            'updated_at' => current_time('mysql', true),
            'is_used' => '0',
            'created_by' => $created_by,
            'mail_id' => $mail_id,
            'details' => $details
        );
        $data_types = array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s');
        Acbwm_Data_Store_Discounts::create_coupon($coupon_details, $data_types);
        return $coupon_details;
    }
}