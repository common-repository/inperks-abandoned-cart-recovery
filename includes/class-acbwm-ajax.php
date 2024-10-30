<?php
/**
 * Ajax related functions and actions.
 *
 * @package Acbwm/Classes
 * @version 3.0.0
 */
defined('ABSPATH') || exit;

/**
 * Acbwm_Ajax Class.
 */
class Acbwm_Ajax
{
    /**
     * Hook in tabs.
     */
    public static function init()
    {
        global $inperks_abandoned_carts;
        $black_listed_ips = $inperks_abandoned_carts->settings->get('black_listed_ips', '');
        $black_listed = acbwm_validate_user_ip($black_listed_ips);
        $track_guest_carts = $inperks_abandoned_carts->settings->get('track_guest_carts', 'yes');
        if (($black_listed == false) && acbwm_can_track_guest_cart($track_guest_carts) && Acbwm_Carts::is_cart_tacking_accepted()) {
            self::add_ajax_events();
        }
        self::add_public_ajax_events();
    }

    /**
     * Hook in methods - uses WordPress ajax handlers (admin-ajax).
     */
    public static function add_ajax_events()
    {
        $ajax_events_nopriv = array(
            'page_loaded',
            'clear_cart_token',
            'payment_method',
            'checkout_details',
            'updating_billing_email',
        );
        foreach ($ajax_events_nopriv as $ajax_event) {
            add_action('wp_ajax_' . ACBWM_HOOK_PREFIX . $ajax_event, array(__CLASS__, $ajax_event));
            add_action('wp_ajax_nopriv_' . ACBWM_HOOK_PREFIX . $ajax_event, array(__CLASS__, $ajax_event));
        }
    }

    /**
     * Hook in methods - uses WordPress ajax handlers (admin-ajax).
     */
    public static function add_public_ajax_events()
    {
        $ajax_events_nopriv = array(
            'set_cart_tracking_accepted',
            'send_ac_mails',
        );
        foreach ($ajax_events_nopriv as $ajax_event) {
            add_action('wp_ajax_' . ACBWM_HOOK_PREFIX . $ajax_event, array(__CLASS__, $ajax_event));
            add_action('wp_ajax_nopriv_' . ACBWM_HOOK_PREFIX . $ajax_event, array(__CLASS__, $ajax_event));
        }
    }

    /**
     * updating the billing email
     */
    public static function updating_billing_email()
    {
        if (isset($_POST['billing_email']) && !empty($_POST['billing_email'])) {
            $email = sanitize_email($_POST['billing_email']);
            $method_name = "set_billing_email";
            if (is_callable(array(WC()->customer, $method_name))) {
                WC()->customer->$method_name($email);
            }
            $cart = Acbwm_Carts::get_cart();
            $cart->update_user_email($email);
        }
    }

    /**
     * update the page visited action
     */
    public static function page_loaded()
    {
        if (isset($_POST['url'])) {
            $action = 'Customer visited ' . esc_url_raw($_POST['url']);
            $cart = Acbwm_Carts::get_cart();
            $cart->update_history($action, 'page_visit', array());
            $ip = WC_Geolocation::get_ip_address();
            $cart->update_user_ip($ip);
        }
        wp_send_json_success('');
    }

    /**
     * update the page visited action
     */
    public static function send_ac_mails()
    {
        Acbwm_Carts::send_abandoned_cart_mails();
        wp_send_json_success("executed at : " . current_time('mysql'));
    }

    /**
     * set user accepted cart tracking
     */
    public static function set_cart_tracking_accepted()
    {
        Acbwm_Carts::set_cart_tacking_accepted();
        wp_send_json_success('success');
    }

    /**
     * update the page visited action
     */
    public static function payment_method()
    {
        if (isset($_POST['method']) && isset($_POST['title'])) {
            $method = sanitize_text_field($_POST['method']);
            $title = sanitize_text_field($_POST['title']);
            $action = "Payment method changed to \"{$title}({$method})\"";
            WC()->session->set('chosen_payment_method', $method);
            $cart = Acbwm_Carts::get_cart();
            $cart->update_history($action, 'payment_method', array('method' => $method, 'title' => $title));
        }
        wp_send_json_success('');
    }

    /**
     * set the customer checkout values
     */
    public static function checkout_details()
    {
        if (!empty($_POST)) {
            foreach ($_POST as $name => $value) {
                if ($name != "billing_email") {
                    $name = sanitize_text_field($name);
                    $value = sanitize_text_field($value);
                    $method_name = "set_{$name}";
                    if (is_callable(array(WC()->customer, $method_name))) {
                        WC()->customer->$method_name($value);
                    }
                }
            }
            if (isset($_POST['billing_email']) && !empty($_POST['billing_email'])) {
                $email = sanitize_email($_POST['billing_email']);
                $cart = Acbwm_Carts::get_cart();
                $cart->update_user_email($email);
                if (isset($_POST['billing_first_name']) && isset($_POST['billing_last_name'])) {
                    $name = sanitize_text_field($_POST['billing_first_name']) . ' ' . sanitize_text_field($_POST['billing_last_name']);
                    $cart->update_user_name(trim($name));
                }
            }
        }
        wp_send_json_success('checkout details updated');
    }

    /**
     * Get ACBWM Ajax Endpoint.
     * @return string
     */
    public static function get_endpoint()
    {
        return esc_url_raw(apply_filters(ACBWM_HOOK_PREFIX . 'ajax_get_endpoint', add_query_arg('action', ACBWM_HOOK_PREFIX . '%%endpoint%%', admin_url('admin-ajax.php'))));
    }

    /**
     * Remove the cart token from
     */
    public static function clear_cart_token()
    {
        Acbwm_Carts::clear_cart_token();
    }
}