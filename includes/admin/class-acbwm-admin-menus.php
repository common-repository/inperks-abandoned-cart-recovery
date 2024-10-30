<?php
/**
 * Setup menus in WP admin.
 */
defined('ABSPATH') || exit;
if (class_exists('Acbwm_Admin_Menus', false)) {
    return new Acbwm_Admin_Menus();
}

class Acbwm_Admin_Menus
{
    const MAIN_SLUG = "acbwm";

    /**
     * Hook in tabs.
     */
    public function __construct()
    {
        // Add menus.
        add_action('admin_menu', array($this, 'admin_menu'), 9);
        add_filter('set-screen-option', array($this, 'set_screen_option'), 10, 3);
    }

    /**
     * Add menu items.
     */
    public function admin_menu()
    {
        global $menu;
        if (current_user_can('manage_woocommerce')) {
            $menu[] = array('', 'read', 'separator-woocommerce', '', 'wp-menu-separator woocommerce'); // WPCS: override ok.
            $main_menu = add_menu_page(__('Abandoned Carts', ACBWM_TEXT_DOMAIN), __('Abandoned Carts', ACBWM_TEXT_DOMAIN), 'manage_woocommerce', self::MAIN_SLUG, array($this, 'dashboard_page'), 'dashicons-cart', '55.6');
            add_submenu_page(self::MAIN_SLUG, __('Dashboard', ACBWM_TEXT_DOMAIN), __('Dashboard', ACBWM_TEXT_DOMAIN), 'manage_woocommerce', self::MAIN_SLUG, array($this, 'dashboard_page'));
            $carts_menu = add_submenu_page(self::MAIN_SLUG, __('Carts', ACBWM_TEXT_DOMAIN), __('Carts', ACBWM_TEXT_DOMAIN), 'manage_woocommerce', 'acbwm-carts', array($this, 'carts_page'));
            $mails_menu = add_submenu_page(self::MAIN_SLUG, __('Reminders', ACBWM_TEXT_DOMAIN), __('Reminders', ACBWM_TEXT_DOMAIN), 'manage_woocommerce', 'acbwm-reminders', array($this, 'emails_page'));
            add_submenu_page(self::MAIN_SLUG, __('Settings', ACBWM_TEXT_DOMAIN), __('Settings', ACBWM_TEXT_DOMAIN), 'manage_woocommerce', 'acbwm-settings', array($this, 'settings_page'));
            add_submenu_page(self::MAIN_SLUG, __('Add-ons', ACBWM_TEXT_DOMAIN), __('Add-ons', ACBWM_TEXT_DOMAIN), 'manage_woocommerce', 'acbwm-add-ons', array($this, 'add_ons_page'));
            //Screen options
            add_action("load-{$mails_menu}", 'Acbwm_Admin_Reminders::emails_menu_add_option');
            add_action("load-{$carts_menu}", 'Acbwm_Admin_Carts::carts_menu_add_option');
        }
    }

    /**
     * Validate screen options on update.
     *
     * @param bool|int $status Screen option value. Default false to skip.
     * @param string $option The option name.
     * @param int $value The number of rows to use.
     * @return mixed
     */
    public function set_screen_option($status, $option, $value)
    {
        if (in_array($option, array('acbwm_email_templates_per_page', 'acbwm_ac_per_page', 'acbwm_sent_emails_per_page'), true)) {
            return $value;
        }
        return $status;
    }

    /**
     * Add-ons page output
     */
    function add_ons_page()
    {
        Acbwm_Admin_Add_Ons::output();
    }

    /**
     * Settings page output
     */
    function settings_page()
    {
        Acbwm_Admin_Settings::output();
    }

    /**
     * Manage emails page
     */
    function emails_page()
    {
        Acbwm_Admin_Reminders::output();
    }

    /**
     * Manage carts
     */
    function carts_page()
    {
        Acbwm_Admin_Carts::output();
    }

    /**
     * manage dashboard
     */
    function dashboard_page()
    {
        Acbwm_Admin_Dashboard::output();
    }
}

new Acbwm_Admin_Menus();