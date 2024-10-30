<?php
/**
 * Installation related functions and actions.
 */
defined('ABSPATH') || exit;

/**
 * Acbwm_Install Class.
 */
class Acbwm_Install
{
    /**
     * Hook in tabs.
     */
    public static function init()
    {
        add_filter('wpmu_drop_tables', array(__CLASS__, 'wpmu_drop_tables'));
        add_filter('cron_schedules', array(__CLASS__, 'cron_schedules'));
    }

    /**
     * Install Acbwm.
     */
    public static function install()
    {
        if (!acbwm_is_valid_wp()) {
            exit(__('Abandoned cart plugin can not be activated because it requires minimum Wordpress version of ', ACBWM_TEXT_DOMAIN) . ' 5.3');
        }
        if (!acbwm_is_wc_active()) {
            exit(__('Woocommerce must installed and activated in-order to use abandoned cart plugin!', ACBWM_TEXT_DOMAIN));
        }
        if (!acbwm_is_valid_wc()) {
            exit(__('Abandoned cart plugin requires at least Woocommerce', ACBWM_TEXT_DOMAIN) . ' 4.0');
        }
        global $wpdb;
        // check if it is a multisite network
        if (is_multisite()) {
            // check if the plugin has been activated on the network or on a single site
            if (is_plugin_active_for_network(ACBWM_PLUGIN_FILE)) {
                // get ids of all sites
                $blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
                foreach ($blogids as $blog_id) {
                    switch_to_blog($blog_id);
                    // create tables for each site
                    self::create_tables($wpdb->blogid);
                    restore_current_blog();
                }
            } else {
                // activated on a single site, in a multi-site
                self::create_tables($wpdb->blogid);
            }
        } else {
            // activated on a single site
            self::create_tables($wpdb->blogid);
        }
        acbwm_set_and_get_plugin_secret();
        self::create_cron_jobs();
        do_action(ACBWM_HOOK_PREFIX . 'installed');
    }

    /**
     * Set up the database tables which the plugin needs to function.
     *
     * Tables:
     *      inperks_abandoned_carts - Table for storing carts
     *      inperks_ac_sent_reminders - Table for storing sent reminders
     *      inperks_discount_codes - Table for storing sent discount codes
     *      inperks_templates - Table for storing reminders
     * @param $blog_id
     */
    private static function create_tables($blog_id)
    {
        global $wpdb;
        $wpdb->hide_errors();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta(self::get_carts_schema());
        dbDelta(self::get_sent_reminders_schema());
        dbDelta(self::get_discounts_schema());
        dbDelta(self::get_templates_schema());
        $initial_migration_done = get_option('acbwm_initial_migration');
        if (empty($initial_migration_done)) {
            $table_name = "{$wpdb->prefix}inperks_templates";
            $language = acbwm_get_default_language();
            $email_body = "{{cart.items_table}} <a href='{{cart.recovery_url}}'>Click here</a> to recover your cart.";
            $wpdb->insert($table_name, array('send_after' => 3600, 'send_after_time' => 1, 'reminder_type' => 'email', 'email_body' => $email_body, 'send_after_unit' => 'hour', 'use_woocommerce_style' => '1', 'status' => '1', 'name' => 'Initial template', 'subject' => 'Hi {{customer.name}}, you forgot something in your cart!', 'heading' => 'Can we help?', 'language' => $language), array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'));
            $wpdb->insert($table_name, array('send_after' => 21600, 'send_after_time' => 6, 'reminder_type' => 'email', 'email_body' => $email_body, 'send_after_unit' => 'hour', 'use_woocommerce_style' => '1', 'status' => '1', 'name' => 'Send after 6 hours', 'subject' => 'Items in your shopping bag are selling out quickly...', 'heading' => 'Act fast if you want them', 'language' => $language), array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'));
            $wpdb->insert($table_name, array('send_after' => 86400, 'send_after_time' => 1, 'reminder_type' => 'email', 'email_body' => $email_body, 'send_after_unit' => 'day', 'use_woocommerce_style' => '1', 'status' => '1', 'name' => 'Send after 1 day', 'subject' => 'Hey, 10% off on your cart! Hurry up!', 'heading' => 'Act fast', 'language' => $language), array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'));
            update_option('acbwm_initial_migration', 'done');
        }
    }

    /**
     * Get Table schema.
     * @return string
     */
    private static function get_carts_schema()
    {
        global $wpdb;
        $collate = '';
        if ($wpdb->has_cap('collation')) {
            $collate = $wpdb->get_charset_collate();
        }
        return "
            CREATE TABLE IF NOT EXISTS {$wpdb->prefix}inperks_abandoned_carts (
              cart_id BIGINT UNSIGNED NOT NULL auto_increment,
              cart_token varchar(200) NULL,
              language varchar(200) NULL,
              currency varchar(200) NULL,
              user_cart text NULL,
              cart_history text NULL,
              cart_sub_total float NULL,
              cart_total float NULL,
              user_id BIGINT NULL,
              user_email varchar(200) NULL DEFAULT NULL,
              phone_number varchar(200) NULL DEFAULT NULL,
              facebook_id varchar(200) NULL DEFAULT NULL,
              whatsapp_id varchar(200) NULL DEFAULT NULL,
              user_name VARCHAR(200) NULL DEFAULT NULL,
              user_ip varchar(200) NULL DEFAULT NULL,
              order_id INT(11) NULL DEFAULT NULL,
              order_status VARCHAR(100) NULL DEFAULT NULL,
              is_all_mails_sent ENUM('0','1') NULL DEFAULT '0',
              recovery_link_clicked ENUM('0','1') NULL DEFAULT '0',
              recovery_link_opened ENUM('0','1') NULL DEFAULT '0',
              unsubscribed ENUM('0','1') NULL DEFAULT '0',
              is_recovered INT(11) NULL DEFAULT 0,
              extra text NULL,
              mail_id INT(11) NULL DEFAULT NULL,
              scheduled_after INT(11) NULL DEFAULT NULL,
              previously_scheduled INT(11) NULL DEFAULT NULL,
              next_mail_at datetime NULL DEFAULT NULL,
              abandoned_at datetime NULL DEFAULT NULL,
              recovered_at datetime NULL DEFAULT NULL,
              created_at datetime NULL DEFAULT NULL,
              updated_at datetime NULL DEFAULT NULL,
              PRIMARY KEY  (cart_id)
            ) {$collate};
		";
    }

    /**
     * Get Table schema.
     * @return string
     */
    private static function get_sent_reminders_schema()
    {
        global $wpdb;
        $collate = '';
        if ($wpdb->has_cap('collation')) {
            $collate = $wpdb->get_charset_collate();
        }
        return "
            CREATE TABLE IF NOT EXISTS {$wpdb->prefix}inperks_ac_sent_reminders (
              id BIGINT UNSIGNED NOT NULL auto_increment,
              cart_id BIGINT UNSIGNED NOT NULL,
              email varchar(200) NULL,
              mail_id int(11),
              reminder_type ENUM('email','sms','messenger','whatsapp') NULL DEFAULT 'email',
              subject varchar(200) NULL,
              is_opened ENUM('0','1') NULL DEFAULT '0',
              is_clicked ENUM('0','1') NULL DEFAULT '0',
              extra text NULL,
              opened_at datetime NULL default null,
              clicked_at datetime NULL default null,
              created_at datetime NULL default null,
              updated_at datetime NULL default null,
              PRIMARY KEY  (id),
              FOREIGN KEY (cart_id) REFERENCES {$wpdb->prefix}inperks_abandoned_carts(cart_id)
            ) {$collate};
		";
    }

    /**
     * Get Table schema.
     * @return string
     */
    private static function get_discounts_schema()
    {
        global $wpdb;
        $collate = '';
        if ($wpdb->has_cap('collation')) {
            $collate = $wpdb->get_charset_collate();
        }
        return "
            CREATE TABLE IF NOT EXISTS {$wpdb->prefix}inperks_discount_codes (
              discount_id BIGINT UNSIGNED NOT NULL auto_increment,
              cart_id BIGINT UNSIGNED NULL DEFAULT 0,
              mail_id BIGINT UNSIGNED NULL DEFAULT 0,
              code varchar(200) NULL,
              created_by varchar(200) NULL,
              details text NULL,
              is_used ENUM('0','1') NULL DEFAULT '0',
              expired_at datetime NULL default null,
              created_at datetime NULL default null,
              updated_at datetime NULL default null,
              PRIMARY KEY  (discount_id)
            ) {$collate};
		";
    }

    /**
     * Get Table schema.
     * @return string
     */
    private static function get_templates_schema()
    {
        global $wpdb;
        $collate = '';
        if ($wpdb->has_cap('collation')) {
            $collate = $wpdb->get_charset_collate();
        }
        return "
            CREATE TABLE IF NOT EXISTS {$wpdb->prefix}inperks_templates (
              mail_id BIGINT UNSIGNED NOT NULL auto_increment,
              status ENUM('0','1') NULL DEFAULT '0',
              name varchar(200) NULL,
              subject varchar(200) NULL,
              heading varchar(200) NULL,
              language varchar(200) NULL,
              send_after_unit ENUM('hour','min','day') NULL DEFAULT 'hour',
              use_woocommerce_style ENUM('0','1') NULL DEFAULT '1',
              send_after_time int(11) DEFAULT 1,
              send_after int(11) DEFAULT NULL,
              email_body text NULL,
              details text NULL,
              extra text NULL,
              reminder_type ENUM('email','sms','messenger','whatsapp') NULL DEFAULT 'email',
              PRIMARY KEY  (mail_id)
            ) {$collate};
		";
    }

    /**
     * Uninstall tables when MU blog is deleted.
     *
     * @param array $tables List of tables that will be deleted by WP.
     *
     * @return string[]
     */
    public static function wpmu_drop_tables($tables)
    {
        return array_merge($tables, self::get_tables());
    }

    /**
     * Return a list of WooCommerce tables. Used to make sure all WC tables are dropped when uninstalling the plugin
     * in a single site or multi site environment.
     *
     * @return array WC tables.
     */
    public static function get_tables()
    {
        global $wpdb;
        $tables = array(
            "{$wpdb->prefix}inperks_abandoned_carts",
            "{$wpdb->prefix}inperks_templates",
            "{$wpdb->prefix}inperks_discount_codes",
            "{$wpdb->prefix}inperks_ac_sent_reminders",
        );
        $tables = apply_filters(ACBWM_HOOK_PREFIX . 'install_get_tables', $tables);
        return $tables;
    }

    /**
     * new scheduled time
     * @param $schedules
     * @return mixed
     */
    public static function cron_schedules($schedules)
    {
        if (!isset($schedules["15min"])) {
            $schedules["15min"] = array(
                'interval' => 5 * 60,
                'display' => __('Once every 15 minutes'));
        }
        if (!isset($schedules["30min"])) {
            $schedules["30min"] = array(
                'interval' => 30 * 60,
                'display' => __('Once every 30 minutes'));
        }
        return $schedules;
    }

    /**
     * Create cron jobs (clear them first).
     */
    private static function create_cron_jobs()
    {
        $is_cron_turned_off_manually = get_option('acbwm_cron_turned_off_manually', 'no');
        if ($is_cron_turned_off_manually != "yes") {
            acbwm_clear_scheduled_hooks();
            acbwm_schedule_hooks();
        }
    }

    /**
     * clear scheduled hook on plugin de activation
     */
    public static function deactivate()
    {
        acbwm_clear_scheduled_hooks();
    }
}

Acbwm_Install::init();
