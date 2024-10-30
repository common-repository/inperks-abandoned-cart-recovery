<?php
/**
 * Acbwm setup
 *
 * @package Acbwm
 */
defined('ABSPATH') || exit;

/**
 * Main Acbwm Class.
 *
 * @class Acbwm
 */
final class Acbwm
{
    /**
     * The single instance of the class.
     *
     * @var Acbwm
     */
    protected static $_instance = null;
    /**
     * available template
     * @var null
     */
    public static $available_emails = null;
    /**
     * Acbwm version.
     *
     * @var string
     */
    public $version = '1.0.1';
    /**
     * settings
     * @var Acbwm_settings
     */
    public $settings = null;

    /**
     * WooCommerce Constructor.
     */
    public function __construct()
    {
        $this->define_constants();
        $this->define_tables();
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Define ACBWM Constants.
     */
    private function define_constants()
    {
        defined('ACBWM_ABSPATH') OR define('ACBWM_ABSPATH', dirname(ACBWM_PLUGIN_FILE) . '/');
        defined('ACBWM_HOOK_PREFIX') OR define('ACBWM_HOOK_PREFIX', 'acbwm_');
        defined('ACBWM_AC_EMAILS_LIST') OR define('ACBWM_AC_EMAILS_LIST', 'acbwm_ac_emails_list');
        defined('ACBWM_SETTINGS_OPTIONS') OR define('ACBWM_SETTINGS_OPTIONS', 'acbwm_plugin_settings');
        defined('ACBWM_HASH_KEY_OPTION') OR define('ACBWM_HASH_KEY_OPTION', 'acbwm_plugin_secret_key');
        defined('ACBWM_API_ENDPOINT') OR define('ACBWM_API_ENDPOINT', 'acbwm');
        defined('ACBWM_API_ENDPOINT_MAIL_OPENED') OR define('ACBWM_API_ENDPOINT_MAIL_OPENED', 'acbwm-mail-opened');
        defined('ACBWM_API_ENDPOINT_SEND_RECOVERY_MAILS') OR define('ACBWM_API_ENDPOINT_SEND_RECOVERY_MAILS', 'acbwm-send-recovery-mails');
        defined('ACBWM_PLUGIN_URL') OR define('ACBWM_PLUGIN_URL', trailingslashit(plugin_dir_url(ACBWM_PLUGIN_FILE)));
        defined('ACBWM_TEXT_DOMAIN') OR define('ACBWM_TEXT_DOMAIN', 'inperks-abandoned-carts');
        defined('ACBWM_PLUGIN_BASENAME') OR define('ACBWM_PLUGIN_BASENAME', plugin_basename(ACBWM_PLUGIN_FILE));
        defined('ACBWM_VERSION') OR define('ACBWM_VERSION', $this->version);
        defined('ACBWM_NOTICE_MIN_PHP_VERSION') OR define('ACBWM_NOTICE_MIN_PHP_VERSION', '7.0');
        defined('ACBWM_NOTICE_MIN_WP_VERSION') OR define('ACBWM_NOTICE_MIN_WP_VERSION', '5.0');
    }

    /**
     * Register custom tables within $wpdb object.
     */
    private function define_tables()
    {
        global $wpdb;
        // List of tables without prefixes.
        $tables = array(
            'acbwm_carts' => 'inperks_abandoned_carts',
            'acbwm_templates' => 'inperks_templates',
            'acbwm_discounts' => 'inperks_discount_codes',
            'acbwm_mail_log' => 'inperks_ac_sent_reminders',
        );
        foreach ($tables as $name => $table) {
            $wpdb->$name = $wpdb->prefix . $table;
            $wpdb->tables[] = $table;
        }
    }

    /**
     * Include required core files used in admin and on the frontend.
     */
    public function includes()
    {
        include_once ACBWM_ABSPATH . 'includes/acbwm-core-functions.php';
        include_once ACBWM_ABSPATH . 'includes/class-acbwm-install.php';
        if (acbwm_is_wc_active() && acbwm_is_valid_wp()) {
            include_once ACBWM_ABSPATH . 'includes/traits/trait-acbwm-cookie.php';
            include_once ACBWM_ABSPATH . 'includes/traits/trait-acbwm-language.php';
            include_once ACBWM_ABSPATH . 'includes/traits/trait-acbwm-currency.php';
            include_once ACBWM_ABSPATH . 'includes/data-stores/class-acbwm-data-store-emails.php';
            include_once ACBWM_ABSPATH . 'includes/data-stores/class-acbwm-data-store-carts.php';
            include_once ACBWM_ABSPATH . 'includes/data-stores/class-acbwm-data-store-discounts.php';
            if (is_admin()) {
                include_once ACBWM_ABSPATH . 'includes/admin/class-acbwm-admin.php';
            }
            include_once ACBWM_ABSPATH . 'includes/class-acbwm-coupons.php';
            include_once ACBWM_ABSPATH . 'includes/class-acbwm-settings.php';
            include_once ACBWM_ABSPATH . 'includes/class-acbwm-carts.php';
            include_once ACBWM_ABSPATH . 'includes/class-acbwm-ajax.php';
            //Customizer
            include_once ACBWM_ABSPATH . 'includes/customizer/class-acbwm-gdpr-compliance-customizer.php';
        }
    }

    /**
     * Hook into actions and filters.
     */
    private function init_hooks()
    {
        register_activation_hook(ACBWM_PLUGIN_FILE, 'Acbwm_Install::install');
        register_deactivation_hook(ACBWM_PLUGIN_FILE, 'Acbwm_Install::deactivate');
        if (acbwm_is_valid_wp() && acbwm_is_wc_active() && acbwm_is_valid_wc()) {
            // Before init action.
            do_action(ACBWM_HOOK_PREFIX . 'before_init');
            $this->settings = new Acbwm_settings();
            add_action('init', array($this, 'init'), 0);
            add_action('wp_enqueue_scripts', 'Acbwm_Carts::include_tacking_snippet', 11);
            add_action('woocommerce_init', array($this, 'woocommerce_init'), 10);
            add_action('woocommerce_init', 'Acbwm_Ajax::init', 10);
            add_action('wp_loaded', 'Acbwm_Carts::auto_apply_discount', 10);
            add_action(ACBWM_HOOK_PREFIX . 'send_recovery_mails', 'Acbwm_Carts::send_abandoned_cart_mails', 10);
            // Init action.
            do_action(ACBWM_HOOK_PREFIX . 'init');
        }
    }

    /**
     * Main inperks ac Instance.
     *
     * Ensures only one instance of WooCommerce is loaded or can be loaded.
     *
     * @return Acbwm - Main instance.
     * @see ACBWM()
     * @static
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Register the email templates
     * @param $emails
     * @return mixed
     */
    public function register_email($emails)
    {
        require_once ACBWM_ABSPATH . 'includes/emails/class-acbwm-email-recover-abandoned-carts.php';
        $mails_list = Acbwm_Data_Store_Email::get_active_emails();
        if (!empty($mails_list)) {
            foreach ($mails_list as $mail_details) {
                $send_after = $mail_details->send_after_time;
                $send_after_type = $mail_details->send_after_unit;
                $mail_id = $mail_details->mail_id;
                $send_after_string = "{$send_after} {$send_after_type}(s)";
                $params = array(
                    'id' => $mail_id,
                    'title' => $mail_details->name,
                    'description' => "Recovery emails will sent to the customers after {$send_after_string} of the cart abandonment.",
                    'subject' => $mail_details->subject,
                    'heading' => $mail_details->heading,
                );
                $emails[$mail_id] = new Acbwm_Email_Recover_abandoned_cart($params);
            }
        }
        self::$available_emails = $emails;
        return self::$available_emails;
    }

    /**
     * init all woocommerce related functionality
     */
    public function woocommerce_init()
    {
        //Emails
        add_filter('woocommerce_email_classes', array($this, 'register_email'), 10);
        if (!is_admin()) {
            $black_listed_ips = $this->settings->get('black_listed_ips', '');
            $black_listed = acbwm_validate_user_ip($black_listed_ips);
            $track_guest_carts = $this->settings->get('track_guest_carts', 'yes');
            if (($black_listed == false) && acbwm_can_track_guest_cart($track_guest_carts) && Acbwm_Carts::is_cart_tacking_accepted()) {
                add_action('woocommerce_add_to_cart', 'Acbwm_Carts::item_added_to_cart', 10, 6);
                add_action('woocommerce_cart_item_removed', 'Acbwm_Carts::item_removed_from_cart', 10, 2);
                add_action('woocommerce_before_cart_item_quantity_zero', 'Acbwm_Carts::item_removed_from_cart', 10, 2);
                add_action('woocommerce_cart_item_restored', 'Acbwm_Carts::item_restored_back_to_cart', 10, 2);
                add_action('woocommerce_after_cart_item_quantity_update', 'Acbwm_Carts::item_quantity_has_updated', 10, 4);
                //add_action('woocommerce_cart_emptied', 'Acbwm_Carts::cart_emptied', 10);
                add_action('woocommerce_applied_coupon', 'Acbwm_Carts::coupon_applied', 10);
                add_action('woocommerce_removed_coupon', 'Acbwm_Carts::coupon_removed', 10);
                add_action('woocommerce_after_calculate_totals', 'Acbwm_Carts::after_calculate_totals', 10);
            }
            add_action('woocommerce_thankyou', 'Acbwm_Carts::enqueue_token_remove_script', 10);
            add_action('woocommerce_payment_complete', 'Acbwm_Carts::clear_cart_token', 10);
        }
        add_action('woocommerce_api_' . ACBWM_API_ENDPOINT, 'Acbwm_Carts::recover_cart', 10);
        add_action('woocommerce_api_' . ACBWM_API_ENDPOINT_MAIL_OPENED, 'Acbwm_Carts::recover_email_opened', 10);
        add_action('woocommerce_api_' . ACBWM_API_ENDPOINT_SEND_RECOVERY_MAILS, 'Acbwm_Carts::send_abandoned_cart_mails', 10);
        add_action('woocommerce_new_order', 'Acbwm_Carts::assign_cart_token_to_order', 10, 3);
        add_action('woocommerce_order_status_changed', 'Acbwm_Carts::order_status_changed', 10, 4);
        add_action('woocommerce_cancelled_order', 'Acbwm_Carts::clear_cart_token', 10, 4);
        add_filter('woocommerce_get_shop_coupon_data', 'Acbwm_Coupons::add_virtual_coupon', 10, 2);
    }

    /**
     * Init WooCommerce when WordPress Initialises.
     */
    public function init()
    {
        // Set up localisation.
        $this->load_plugin_text_domain();
        // Classes/actions loaded for the frontend and for ajax requests.
        add_action('user_register', 'Acbwm_Carts::user_register', 10);
        add_action('wp_authenticate', 'Acbwm_Carts::user_login', 10);
        add_action('wp_logout', 'Acbwm_Carts::user_logged_out', 10);
        add_action('wp_footer', 'Acbwm_Carts::include_gdpr_compliance', 10);
    }

    /**
     * Load Localisation files.
     */
    public function load_plugin_text_domain()
    {
        load_plugin_textdomain(ACBWM_TEXT_DOMAIN, false, plugin_basename(dirname(ACBWM_PLUGIN_FILE)) . '/i18n/languages');
    }
}