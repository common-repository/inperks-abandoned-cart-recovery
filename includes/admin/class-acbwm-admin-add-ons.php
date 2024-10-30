<?php
/**
 * Admin Add-ons
 *
 * Functions used for displaying available add-ons
 *
 */
defined('ABSPATH') || exit;
if (class_exists('Acbwm_Admin_Add_Ons', false)) {
    return;
}

/**
 * Acbwm_Admin_Add_Ons Class.
 */
class Acbwm_Admin_Add_Ons
{
    /**
     * Handles output of the settings page in admin.
     */
    public static function output()
    {
        if (isset($_GET['action']) && isset($_GET['add-on']) && $_GET['status']) {
            $action = sanitize_text_field($_GET['action']);
            if ($action == "change-status") {
                $add_on = sanitize_text_field($_GET['add-on']);
                $status = sanitize_text_field($_GET['status']);
                $status = (in_array($status, array('yes', 'no'), true)) ? $status : "yes";
                switch ($add_on) {
                    case "add-to-cart-popup":
                        $to_change_status['enable_add_to_cart_popup'] = $status;
                        update_option('acbwm_enable_add_to_cart_popup', $status);
                        break;
                    case "coupon-timer":
                        $ct_settings = get_option('acbwm_atc_popup', array());
                        $ct_settings['enable_incentivize_coupon'] = $status;
                        update_option('acbwm_atc_popup', $ct_settings);
                        break;
                    case "fortune-wheel":
                        $fw_settings = get_option('acbwm_fortune_wheel', array());
                        $fw_settings['enable_fw'] = $status;
                        update_option('acbwm_fortune_wheel', $fw_settings);
                        break;
                    default:
                        break;
                }
                wp_safe_redirect(admin_url('admin.php?page=acbwm-add-ons'));
            }
        }
        include_once dirname(__FILE__) . '/views/html-admin-page-add-ons.php';
    }
}
