<?php
/**
 * Admin Reports
 *
 * Functions used for displaying sales and customer reports in admin.
 *
 */
defined('ABSPATH') || exit;
if (class_exists('Acbwm_Admin_Settings', false)) {
    return;
}

/**
 * Acbwm_Admin_Settings Class.
 */
class Acbwm_Admin_Settings
{
    /**
     * Handles output of the settings page in admin.
     */
    public static function output()
    {
        global $inperks_abandoned_carts;
        $settings = $inperks_abandoned_carts->settings;
        include_once dirname(__FILE__) . '/views/html-admin-page-settings.php';
    }

    /**
     * Handles saving settings.
     */
    public static function save_acbwm_settings()
    {
        wp_verify_nonce(ACBWM_HOOK_PREFIX . 'save_acbwm_settings', 'security');
        global $inperks_abandoned_carts;
        $default_settings = $inperks_abandoned_carts->settings->default_settings;
        $new_settings = isset($_POST['settings']) ? $_POST['settings'] : array();
        $settings = wp_parse_args($new_settings, $default_settings);
        update_option(ACBWM_SETTINGS_OPTIONS, $settings);
        wp_send_json_success(__('Settings saved successfully', ACBWM_TEXT_DOMAIN));
    }
}
