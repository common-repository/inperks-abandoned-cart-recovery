<?php
defined('ABSPATH') || exit;
class Acbwm_settings
{
    public static $settings = array();
    /**
     * list of settings
     * @var array
     */
    public $default_settings = array(
        'cart_cut_off_time' => 60,
        'process_carts_on_each_cron' => 20,
        'auto_delete_ac_after' => 90,
        'coupon_code_prefix' => 'wmc',
        'black_listed_ips' => '',
        'email_capturing_fields' => '',
        'gdpr_message' => '',
        'track_guest_carts' => 'yes',
    );

    function __construct()
    {
        $settings = get_option(ACBWM_SETTINGS_OPTIONS, array());
        self::$settings = wp_parse_args($settings, $this->default_settings);
    }

    /**
     * get the settings
     * @param $key
     * @param $default
     * @return mixed
     */
    function get($key, $default = null)
    {
        if (isset(self::$settings[$key])) {
            return self::$settings[$key];
        }
        return $default;
    }
}