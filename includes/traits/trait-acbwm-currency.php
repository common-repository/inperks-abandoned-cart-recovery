<?php
defined('ABSPATH') || exit;

trait Acbwm_Trait_Currency
{
    /**
     * get all the available languages
     * @return mixed
     */
    public static function get_store_currency()
    {
        return get_woocommerce_currency();
    }

    /**
     * get the active currency of the site
     * @return mixed
     */
    public static function get_active_currency()
    {
        if (isset($GLOBALS['WOOCS'])) {
            $default_currency_code = $GLOBALS['WOOCS']->current_currency;
        } elseif (class_exists('WOOMULTI_CURRENCY_F_Data')) {
            $setting = new \WOOMULTI_CURRENCY_F_Data();
            $default_currency_code = $setting->get_current_currency();
        } elseif (class_exists('WOOMULTI_CURRENCY_Data')) {
            $setting = new \WOOMULTI_CURRENCY_Data();
            $default_currency_code = $setting->get_current_currency();
        } else {
            $default_currency_code = self::get_default_currency();
        }
        return $default_currency_code;
    }

    /**
     * get the default currency code
     * @return mixed|void
     */
    public static function get_default_currency()
    {
        if (isset($GLOBALS['WOOCS'])) {
            $default_currency_code = $GLOBALS['WOOCS']->default_currency;
        } elseif (class_exists('WOOMULTI_CURRENCY_F_Data')) {
            $setting = new \WOOMULTI_CURRENCY_F_Data();
            $default_currency_code = $setting->get_default_currency();
        } elseif (class_exists('WOOMULTI_CURRENCY_Data')) {
            $setting = new \WOOMULTI_CURRENCY_Data();
            $default_currency_code = $setting->get_default_currency();
        } else {
            $default_currency_code = self::get_store_currency();
        }
        return $default_currency_code;
    }

    /**
     * set the current currency
     * @param $currency_code
     */
    public static function set_current_currency($currency_code)
    {
        if (isset($GLOBALS['WOOCS'])) {
            $GLOBALS['WOOCS']->set_currency($currency_code);
        } elseif (class_exists('WOOMULTI_CURRENCY_F_Data')) {
            $setting = new \WOOMULTI_CURRENCY_F_Data();
            $setting->set_current_currency($currency_code);
        } elseif (class_exists('WOOMULTI_CURRENCY_Data')) {
            $setting = new \WOOMULTI_CURRENCY_Data();
            $setting->set_current_currency($currency_code);
        }
    }

    /**
     * get the currency rate
     * @param $value integer|float
     * @param $currency_code
     * @return integer|float
     */
    public static function get_currency_rate($value, $currency_code)
    {
        if (isset($GLOBALS['WOOCS'])) {
            $selected_currencies = $GLOBALS['WOOCS']->get_currencies();
            $value = isset($selected_currencies[$currency_code]['rate']) ? $selected_currencies[$currency_code]['rate'] : 1;
        } elseif (class_exists('WOOMULTI_CURRENCY_F_Data')) {
            $setting = new \WOOMULTI_CURRENCY_F_Data();
            $selected_currencies = $setting->get_list_currencies();
            $value = isset($selected_currencies[$currency_code]['rate']) ? $selected_currencies[$currency_code]['rate'] : 1;
        } elseif (class_exists('WOOMULTI_CURRENCY_Data')) {
            $setting = new \WOOMULTI_CURRENCY_Data();
            $selected_currencies = $setting->get_list_currencies();
            $value = isset($selected_currencies[$currency_code]['rate']) ? $selected_currencies[$currency_code]['rate'] : 1;
        }
        return $value;
    }

    /**
     * Convert price to another price as per currency rate
     * @param $price
     * @param $currency_code
     * @return float|int
     */
    public static function convert_price_to_shop_currency($price, $currency_code)
    {
        $exchange_rate = self::get_currency_rate($price, $currency_code);
        if ($exchange_rate == $price) {
            return $price;
        }
        if (!empty($price) && !empty($exchange_rate)) {
            return $price / $exchange_rate;
        }
        return $price;
    }
}