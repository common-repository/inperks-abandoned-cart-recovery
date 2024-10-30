<?php
/**
 * Admin dashboard
 *
 * Functions used for displaying carts in admin.
 *
 */
defined('ABSPATH') || exit;
if (class_exists('Acbwm_Admin_Dashboard', false)) {
    return;
}

/**
 * Acbwm_Admin_Dashboard Class.
 */
class Acbwm_Admin_Dashboard
{
    /**
     * Handles output of the settings page in admin.
     */
    public static function output()
    {
        $duration = sanitize_text_field(isset($_GET['duration']) ? $_GET['duration'] : 'last_seven');
        $all_duration_values = acbwm_duration_values();
        $duration_value = isset($all_duration_values[$duration]) ? $all_duration_values[$duration] : array();
        $start = $duration_value['start_date'] . ' 00:01:01';
        $end = $duration_value['end_date'] . ' 23:59:59';
        $total_carts = Acbwm_Data_Store_Cart::get_total_carts_count();
        $total_abandoned_carts_count = Acbwm_Data_Store_Cart::get_total_abandoned_carts_count($start, $end);
        $total_recoverable_carts_count = Acbwm_Data_Store_Cart::get_total_recoverable_carts_count($start, $end);
        $total_recovered_carts_count = Acbwm_Data_Store_Cart::get_total_recovered_carts_count($start, $end);
        $total_abandoned_carts_value = Acbwm_Data_Store_Cart::get_total_abandoned_carts_count($start, $end, true);
        $total_recoverable_carts_value = Acbwm_Data_Store_Cart::get_total_recoverable_carts_count($start, $end, true);
        $total_recovered_carts_value = Acbwm_Data_Store_Cart::get_total_recovered_carts_count($start, $end, true);
        include_once dirname(__FILE__) . '/views/html-admin-page-dashboard.php';
    }
}
