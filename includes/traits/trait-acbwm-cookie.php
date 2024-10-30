<?php
defined('ABSPATH') || exit;

trait Acbwm_Trait_Cookie
{
    /**
     * ge the value form the cookie
     * @param $key
     * @param $default_value
     * @return mixed
     */
    public static function get_cookie_value($key, $default_value = null)
    {
        if (method_exists(WC()->session, 'get')) {
            return WC()->session->get($key, $default_value);
        } else {
            return $default_value;
        }
        /*if (isset($_COOKIE[$key])) {
            return $_COOKIE[$key];
        } else {
            return $default_value;
        }*/
    }

    /**
     * remove the value form the cookie
     * @param $key
     */
    public static function remove_cookie_value($key)
    {
        if (method_exists(WC()->session, '__unset')) {
            WC()->session->__unset($key);
        }
        /*if (isset($_COOKIE[$key])) {
            unset($_COOKIE[$key]);
        }
        self::set_cookie_value($key, '', -3600);*/
    }

    /**
     * set the cookie
     * @param $name - name of the session to set
     * @param $value - value of the session to set
     * @param int $expire -  expire time
     * @param bool $secure - need only in https or http also
     * @param bool $httponly
     */
    public static function set_cookie_value($name, $value, $expire = 0, $secure = false, $httponly = false)
    {
        self::set_session_cookie();
        if (method_exists(WC()->session, 'set')) {
            WC()->session->set($name, $value);
        }
        /*if (!headers_sent()) {
            setcookie($name, $value, $expire, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, $secure, $httponly);
        }*/
    }

    /**
     * Set the authentication cookie to true
     */
    public static function set_session_cookie()
    {
        if (!WC()->session->has_session() && !defined('DOING_CRON')) {
            WC()->session->set_customer_session_cookie(true);
        }
    }
}