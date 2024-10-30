<?php
defined('ABSPATH') || exit;
/**
 * generate unique random id
 * @return string
 */
function acbwm_generate_uuid()
{
    return sprintf('%04x%04x%04x%04x%04x%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        // 16 bits for "time_mid"
        mt_rand(0, 0xffff),
        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand(0, 0x0fff) | 0x4000,
        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand(0, 0x3fff) | 0x8000,
        // 48 bits for "node"
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

/**
 * get the assets url
 * @param $file
 * @return string
 */
function acbwm_get_assets_url($file)
{
    return ACBWM_PLUGIN_URL . 'assets/' . $file;
}

/**
 * adds some amount
 * @param $time_to_add
 * @param $type
 * @param string $return_format
 * @return false|float|int|string
 */
function acbwm_add_time($time_to_add, $type, $return_format = "time_stamp")
{
    $current_time = current_time('timestamp', true);
    $timestamp = acbwm_get_timestamp($time_to_add, $type);
    $time = acbwm_concat_time($current_time, $timestamp);
    if ($return_format != 'timestamp') {
        return date($return_format, $time);
    }
    return $time;
}

/**
 * get the time stamp by type
 * @param $time_to_add
 * @param $type
 * @return float|int
 */
function acbwm_get_timestamp($time_to_add, $type)
{
    switch ($type) {
        case "week":
            $timestamp = $time_to_add * 60 * 60 * 24 * 7;
            break;
        case "day":
            $timestamp = $time_to_add * 60 * 60 * 24;
            break;
        case "hour":
            $timestamp = $time_to_add * 60 * 60;
            break;
        default:
        case "min":
            $timestamp = $time_to_add * 60;
            break;
    }
    return $timestamp;
}

/**
 * concat time to the given time
 * @param $time
 * @param $time_to_add
 * @return mixed
 */
function acbwm_concat_time($time, $time_to_add)
{
    return $time + $time_to_add;
}

/**
 * Convert seconds to day, hours and min
 * @param $seconds
 * @param $format
 * @return string
 */
function acbwm_convert_seconds($seconds, $format = '%02d hours %02d minutes')
{
    if (empty($seconds) || !is_numeric($seconds)) {
        return false;
    }
    $minutes = round($seconds / 60);
    $hours = floor($minutes / 60);
    $remainMinutes = ($minutes % 60);
    return sprintf($format, $hours, $remainMinutes);
}

/**
 * hash the data with secret key
 * @param $data
 * @param $secret
 * @return false|string
 */
function acbwm_hash_data($data, $secret)
{
    return hash_hmac('sha256', $data, $secret);
}

/**
 * hash the data with secret key and compare with hash data
 * @param $old_hash
 * @param $data
 * @param $secret
 * @return false
 */
function acbwm_is_hash_match($old_hash, $data, $secret)
{
    $hash = acbwm_hash_data($data, $secret);
    return hash_equals($old_hash, $hash);
}

/**
 * set plugin secret
 * @return bool|false|mixed|string|void
 */
function acbwm_set_and_get_plugin_secret()
{
    $secret = get_option(ACBWM_HASH_KEY_OPTION, null);
    if (empty($secret)) {
        $secret = acbwm_random_string_generate(35);
        update_option(ACBWM_HASH_KEY_OPTION, $secret);
    }
    return $secret;
}

/**
 * Randomly generate the chars
 * @param $length
 * @param $need_special_chars bool
 * @return false|string
 */
function acbwm_random_string_generate($length, $need_special_chars = true)
{
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    if ($need_special_chars) {
        $chars .= "!@#$%^&*()-=+";
    }
    return substr(str_shuffle($chars), 0, $length);
}

/**
 * check the given string has prefix
 * @param $string
 * @param $prefix
 * @return bool
 */
function acbwm_string_has_prefix($string, $prefix)
{
    $len = strlen($prefix);
    return (substr($string, 0, $len) === $prefix);
}

/**
 * Make clickable links from the given text
 * @param $text
 * @param array $protocols
 * @param array $attributes
 * @return string|string[]|null
 */
function acbwm_make_links_from_string($text, $protocols = array('http', 'mail'), array $attributes = array())
{
    // Link attributes
    $attr = '';
    foreach ($attributes as $key => $val) {
        $attr .= ' ' . $key . '="' . htmlentities($val) . '"';
    }
    $links = array();
    // Extract existing links and tags
    $text = preg_replace_callback('~(<a .*?>.*?</a>|<.*?>)~i', function ($match) use (&$links) {
        return '<' . array_push($links, $match[1]) . '>';
    }, $text);
    // Extract text links for each protocol
    foreach ((array)$protocols as $protocol) {
        switch ($protocol) {
            case 'http':
            case 'https':
                $text = preg_replace_callback('~(?:(https?)://([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![\.,:])~i', function ($match) use ($protocol, &$links, $attr) {
                    if ($match[1]) $protocol = $match[1];
                    $link = $match[2] ?: $match[3];
                    return '<' . array_push($links, "<a $attr href=\"$protocol://$link\">$link</a>") . '>';
                }, $text);
                break;
            case 'mail':
                $text = preg_replace_callback('~([^\s<]+?@[^\s<]+?\.[^\s<]+)(?<![\.,:])~', function ($match) use (&$links, $attr) {
                    return '<' . array_push($links, "<a $attr href=\"mailto:{$match[1]}\">{$match[1]}</a>") . '>';
                }, $text);
                break;
            case 'twitter':
                $text = preg_replace_callback('~(?<!\w)[@#](\w++)~', function ($match) use (&$links, $attr) {
                    return '<' . array_push($links, "<a $attr href=\"https://twitter.com/" . ($match[0][0] == '@' ? '' : 'search/%23') . $match[1] . "\">{$match[0]}</a>") . '>';
                }, $text);
                break;
            default:
                $text = preg_replace_callback('~' . preg_quote($protocol, '~') . '://([^\s<]+?)(?<![\.,:])~i', function ($match) use ($protocol, &$links, $attr) {
                    return '<' . array_push($links, "<a $attr href=\"$protocol://{$match[1]}\">{$match[1]}</a>") . '>';
                }, $text);
                break;
        }
    }
    // Insert all link
    return preg_replace_callback('/<(\d+)>/', function ($match) use (&$links) {
        return $links[$match[1] - 1];
    }, $text);
}

/**
 * add the editor
 * @param $content
 * @param $name
 */
function acbwm_html_editor($content, $name)
{
    $content = (empty($content)) ? "{{cart.items_table}} <a href='{{cart.recovery_url}}'>Click here</a> to recover your cart." : $content;
    ?>
    <label>
        <textarea name="<?php echo $name ?>" id="<?php echo $name ?>"><?php echo $content ?></textarea>
    </label>
    <script>
        jQuery(document).ready(function () {
            jQuery("#<?php echo $name ?>").cleditor();
        });
    </script>
    <?php
}

/**
 * Email template params
 * @return array
 */
function acbwm_email_template_params()
{
    return array(
        'status' => 1,
        'subject' => '',
        'name' => '',
        'details' => '',
        'heading' => '',
        'email_body' => '',
        'language' => '',
        'send_after_unit' => 'hour',
        'send_after_time' => '1',
        'use_woocommerce_style' => '1'
    );
}

/**
 * Email template params
 * @return array
 */
function acbwm_email_template_coupon_params()
{
    return array(
        'discount_type' => 'percent',
        'coupon_value' => '',
        'coupon_expired_at' => '7',
        'selected_coupon' => '',
        'generate_unique_coupons' => '0',
    );
}

/**
 * Email template lists
 * @return array
 */
function acbwm_email_templates_list()
{
    return array(
        'customer-acbwm-recover-ac-default' => array(
            'name' => 'Default 1',
            'url' => '',
            'id' => 'customer-acbwm-recover-ac-default'
        )
    );
}

/**
 * decode the base64 string
 * @param $string
 * @return false|string
 */
function acbwm_maybe_base64_decode($string)
{
    if (preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $string)) {
        return base64_decode($string);
    } else {
        return $string;
    }
}

/**
 * decode the json string
 * @param $string
 * @return false|mixed
 * @since 1.0.1
 */
function acbwm_maybe_json_decode($string)
{
    if (is_array($string)) {
        return $string;
    }
    $result = json_decode($string, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        return $result;
    }
    return $string;
}

/**
 * get font family mappings
 * @return array
 */
function acbwm_font_family_mappings()
{
    return array(
        'inherit' => 'inherit',
        'arial' => 'Arial, Helvetica, sans-serif',
        'arial_black' => '"Arial Black", Gadget, sans-serif',
        'courier' => '"Courier New", Courier, monospace',
        'helvetica' => '"Helvetica Neue", Helvetica, Roboto, Arial, sans-serif',
        'impact' => 'Impact, Charcoal, sans-serif',
        'lucida' => '"Lucida Sans Unicode", "Lucida Grande", sans-serif',
        'palatino' => '"Palatino Linotype", "Book Antiqua", Palatino, serif',
    );
}

/**
 * get all available font families
 * @return array
 */
function acbwm_get_font_families()
{
    return array(
        'inherit' => __('Inherit', ACBWM_TEXT_DOMAIN),
        'arial' => __('Arial', ACBWM_TEXT_DOMAIN),
        'arial_black' => __('Arial Black', ACBWM_TEXT_DOMAIN),
        'courier' => __('Courier New', ACBWM_TEXT_DOMAIN),
        'helvetica' => __('Helvetica', ACBWM_TEXT_DOMAIN),
        'impact' => __('Impact', ACBWM_TEXT_DOMAIN),
        'lucida' => __('Lucida', ACBWM_TEXT_DOMAIN),
        'palatino' => __('Palatino', ACBWM_TEXT_DOMAIN),
    );
}

/**
 * is customizer preview or not
 * @return bool
 */
function acbwm_is_customizer_preview()
{
    global $wp_customize;
    if (!empty($wp_customize)) {
        return true;
    }
    return false;
}

/**
 * border styles
 * @return array
 */
function acbwm_border_styles()
{
    return array(
        'none' => __('none', ACBWM_TEXT_DOMAIN),
        'hidden' => __('hidden', ACBWM_TEXT_DOMAIN),
        'dotted' => __('dotted', ACBWM_TEXT_DOMAIN),
        'dashed' => __('dashed', ACBWM_TEXT_DOMAIN),
        'solid' => __('solid', ACBWM_TEXT_DOMAIN),
        'double' => __('double', ACBWM_TEXT_DOMAIN),
        'groove' => __('groove', ACBWM_TEXT_DOMAIN),
        'ridge' => __('ridge', ACBWM_TEXT_DOMAIN),
        'inset' => __('inset', ACBWM_TEXT_DOMAIN),
        'outset' => __('outset', ACBWM_TEXT_DOMAIN),
    );
}

/**
 * text alignments
 * @return array
 */
function acbwm_content_alignments()
{
    return array(
        'left' => __('left', ACBWM_TEXT_DOMAIN),
        'center' => __('center', ACBWM_TEXT_DOMAIN),
        'right' => __('right', ACBWM_TEXT_DOMAIN),
        'justify' => __('justify', ACBWM_TEXT_DOMAIN),
    );
}

/**
 * Get font weight
 * @return array
 */
function acbwm_font_weights()
{
    return array(
        'normal' => __('normal', ACBWM_TEXT_DOMAIN),
        'bold' => __('bold', ACBWM_TEXT_DOMAIN),
        'bolder' => __('bolder', ACBWM_TEXT_DOMAIN),
        'lighter' => __('lighter', ACBWM_TEXT_DOMAIN),
        '100' => __('100', ACBWM_TEXT_DOMAIN),
        '200' => __('200', ACBWM_TEXT_DOMAIN),
        '300' => __('300', ACBWM_TEXT_DOMAIN),
        '400' => __('400', ACBWM_TEXT_DOMAIN),
        '500' => __('500', ACBWM_TEXT_DOMAIN),
        '600' => __('600', ACBWM_TEXT_DOMAIN),
        '700' => __('700', ACBWM_TEXT_DOMAIN),
        '800' => __('800', ACBWM_TEXT_DOMAIN),
        '900' => __('900', ACBWM_TEXT_DOMAIN),
    );
}

/**
 * Get pixel sizes
 * @param int $start
 * @param int $end
 * @return array
 */
function acbwm_pixels_size($start = 1, $end = 100)
{
    $return = array();
    for ($i = $start; $i <= $end; $i++) {
        $size = $i . 'px';
        $return[$size] = $size;
    }
    return $return;
}

/**
 * check the current page is blacklist
 * @param $exclude_pages
 * @param $page
 * @return bool
 */
function acbwm_contains_exclude_pages($exclude_pages, $page)
{
    if (is_string($exclude_pages)) {
        $exclude_pages = explode(',', $exclude_pages);
    }
    $request_uri = trim($page);
    if (!empty($exclude_pages) && is_array($exclude_pages)) {
        foreach ($exclude_pages as $page) {
            $page = trim($page);
            if (strpos($request_uri, $page)) {
                return true;
            }
        }
    }
    return false;
}

/**
 * list of durations
 * @return array
 */
function acbwm_durations()
{
    return array(
        'yesterday' => __('Yesterday', ACBWM_TEXT_DOMAIN),
        'today' => __('Today', ACBWM_TEXT_DOMAIN),
        'last_seven' => __('Last 7 days', ACBWM_TEXT_DOMAIN),
        'last_fifteen' => __('Last 15 days', ACBWM_TEXT_DOMAIN),
        'last_thirty' => __('Last 30 days', ACBWM_TEXT_DOMAIN),
        'last_ninety' => __('Last 90 days', ACBWM_TEXT_DOMAIN),
        'last_year_days' => __('Last 365 days', ACBWM_TEXT_DOMAIN),
    );
}

/**
 * list of durations values
 * @return array
 */
function acbwm_duration_values()
{
    return array(
        'yesterday' => array('start_date' => date("Y-m-d ", (current_time('timestamp', true) - 24 * 60 * 60)), 'end_date' => date("Y-m-d ", (current_time('timestamp', true) - 24 * 60 * 60))),
        'today' => array('start_date' => date("Y-m-d ", (current_time('timestamp', true))), 'end_date' => date("Y-m-d ", (current_time('timestamp', true)))),
        'last_seven' => array('start_date' => date("Y-m-d ", (current_time('timestamp', true) - 7 * 24 * 60 * 60)), 'end_date' => date("Y-m-d ", (current_time('timestamp', true)))),
        'last_fifteen' => array('start_date' => date("Y-m-d ", (current_time('timestamp', true) - 15 * 24 * 60 * 60)), 'end_date' => date("Y-m-d ", (current_time('timestamp', true)))),
        'last_thirty' => array('start_date' => date("Y-m-d ", (current_time('timestamp', true) - 30 * 24 * 60 * 60)), 'end_date' => date("Y-m-d ", (current_time('timestamp', true)))),
        'last_ninety' => array('start_date' => date("Y-m-d ", (current_time('timestamp', true) - 90 * 24 * 60 * 60)), 'end_date' => date("Y-m-d ", (current_time('timestamp', true)))),
        'last_year_days' => array('start_date' => date("Y-m-d ", (current_time('timestamp', true) - 365 * 24 * 60 * 60)), 'end_date' => date("Y-m-d ", (current_time('timestamp', true))))
    );
}

/**
 * find the difference between two dates
 * @param $date1
 * @param $date2
 * @return float|int
 */
function acbwm_date_difference($date1, $date2)
{
    $diff = strtotime($date2) - strtotime($date1);
    return abs(round($diff / 86400));
}

/**
 * get timezone offset
 * @return bool|string
 */
function acbwm_get_time_zone_offset()
{
    try {
        $timezone = new \DateTimeZone(acbwm_get_time_zone());
        $origin_dt = new \DateTime("now", $timezone);
        $init = $origin_dt->getOffset();
        $hours = floor($init / 3600);
        $sign = '';
        if ($hours >= 0) {
            $sign = '+';
        }
        $minutes = floor(($init / 60) % 60);
        return $sign . sprintf("%02d", $hours) . ':' . sprintf("%02d", $minutes);
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * Get wordpress time zone
 * @return mixed|string|void
 */
function acbwm_get_time_zone()
{
    if (function_exists('wp_timezone_string')) {
        return wp_timezone_string();
    } else {
        $timezone_string = get_option('timezone_string');
        if ($timezone_string) {
            return $timezone_string;
        }
        $offset = (float)get_option('gmt_offset');
        $hours = (int)$offset;
        $minutes = ($offset - $hours);
        $sign = ($offset < 0) ? '-' : '+';
        $abs_hour = abs($hours);
        $abs_min = abs($minutes * 60);
        return sprintf('%s%02d:%02d', $sign, $abs_hour, $abs_min);
    }
}

/**
 * check is woocommerce active or not
 * @return bool
 */
function acbwm_is_wc_active()
{
    $active_plugins = apply_filters('active_plugins', get_option('active_plugins', array()));
    if (is_multisite()) {
        $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
    }
    return in_array('woocommerce/woocommerce.php', $active_plugins, false) || array_key_exists('woocommerce/woocommerce.php', $active_plugins);
}

/**
 * get the version of woocommerce
 * @return mixed|null
 */
function acbwm_wc_active_version()
{
    if (defined('WC_VERSION')) {
        return WC_VERSION;
    }
    if (!function_exists('get_plugins')) {
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }
    $plugin_folder = get_plugins('/woocommerce');
    $plugin_file = 'woocommerce.php';
    $wc_installed_version = NULL;
    if (isset($plugin_folder[$plugin_file]['Version'])) {
        $wc_installed_version = $plugin_folder[$plugin_file]['Version'];
    }
    return $wc_installed_version;
}

/**
 * check is woocommerce compatible
 * @return bool
 */
function acbwm_is_valid_wc()
{
    $wc_version = acbwm_wc_active_version();
    return version_compare($wc_version, '4.0', '>');
}

/**
 * check is wordpress is compatible
 * @return bool
 */
function acbwm_is_valid_wp()
{
    global $wp_version;
    return version_compare($wp_version, '5.3', '>');
}

/**
 * clear scheduled hooks
 */
function acbwm_clear_scheduled_hooks()
{
    wp_unschedule_hook(ACBWM_HOOK_PREFIX . 'send_recovery_mails');
}

/**
 * schedule ac recovery mails
 */
function acbwm_schedule_hooks()
{
    wp_schedule_event(time() + 10, '15min', ACBWM_HOOK_PREFIX . 'send_recovery_mails');
}

/**
 * url to send recovery mails
 * @return string
 */
function acbwm_cron_send_mails_url()
{
    $api_url = WC()->api_request_url(ACBWM_API_ENDPOINT_SEND_RECOVERY_MAILS);
    return add_query_arg('send-mails', true, $api_url);
}

/**
 * check the given IP is blacklisted or not
 * @param $client_ip
 * @param $blacklisted_ip
 * @return bool
 */
function acbwm_is_blacklisted_ip($client_ip, $blacklisted_ip)
{
    if (!empty($blacklisted_ip)) {
        foreach ($blacklisted_ip as $ip) {
            if ($client_ip == $ip) {
                return true;
            } elseif (strpos($ip, '*') !== false) {
                $digits = explode(".", $ip);
                $client_ip_digits = explode(".", $client_ip);
                if (isset($digits[1]) && isset($client_ip_digits[0]) && $digits[1] == '*' && $digits[0] == $client_ip_digits[0]) {
                    return true;
                } elseif (isset($digits[2]) && isset($client_ip_digits[1]) && $digits[2] == '*' && $digits[0] == $client_ip_digits[0] && $digits[1] == $client_ip_digits[1]) {
                    return true;
                } elseif (isset($digits[3]) && isset($client_ip_digits[2]) && $digits[3] == '*' && $digits[0] == $client_ip_digits[0] && $digits[1] == $client_ip_digits[1] && $digits[2] == $client_ip_digits[2]) {
                    return true;
                }
            } elseif (strpos($ip, "-") !== false) {
                list($start_ip, $end_ip) = explode("-", $ip);
                $start_ip = preg_replace('/\s+/', '', $start_ip);
                $end_ip = preg_replace('/\s+/', '', $end_ip);
                $start_ip_long = ip2long($start_ip);
                $end_ip_long = ip2long($end_ip);
                $client_ip_long = ip2long($client_ip);
                if ($client_ip_long >= $start_ip_long && $client_ip_long <= $end_ip_long) {
                    return true;
                }
            }
        }
    }
    return false;
}

/**
 * validate the user ip
 * @param $black_listed_ips
 * @return bool
 */
function acbwm_validate_user_ip($black_listed_ips)
{
    return apply_filters(ACBWM_HOOK_PREFIX . 'acbwm_validate_user_ip', false, $black_listed_ips);
}

/**
 * check weather need to track guest cart
 * @param $can_track_guest_cart
 * @return bool
 */
function acbwm_can_track_guest_cart($can_track_guest_cart)
{
    if ($can_track_guest_cart == "yes") {
        return true;
    } else {
        if (is_user_logged_in()) {
            return true;
        }
    }
    return false;
}

/**
 * get default language
 * @return string
 */
function acbwm_get_default_language()
{
    $current_lang = get_locale();
    if (empty($current_lang) || $current_lang == 'en') {
        $current_lang = 'en_US';
    }
    return $current_lang;
}

/**
 * print style mappings
 * @param $settings
 * @param $font_family_mappings
 * @param $key
 * @param $map
 * @since 1.0.1
 */
function acbwm_print_style_mappings($settings, $font_family_mappings, $key, $map)
{
    $selector = $map[0];
    $property = $map[1];
    $value = '';
    if (isset($settings[$key])) {
        $value = $settings[$key];
    }
    if (!empty($value)) {
        if ($property == "background-image") {
            echo "$map[0] { $map[1]: url($value);background-size:cover; }\n";
        } else if ($property == "font-family") {
            echo "$map[0] { $map[1]: $font_family_mappings[$value] }\n";
        } else {
            if (!empty($value)) {
                echo "$map[0] { $map[1]: $value !important; }\n";
            }
        }
    }
}