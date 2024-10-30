<?php
defined('ABSPATH') || exit;

class Acbwm_Data_Store_Discounts
{
    const DISCOUNTS_TABLE_NAME = 'inperks_discount_codes';

    /**
     * save coupon code
     * @param $data array
     * @param $type array
     * @return int
     */
    public static function create_coupon($data, $type)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::DISCOUNTS_TABLE_NAME;
        $wpdb->insert($table_name, $data, $type);
        return $wpdb->insert_id;
    }

    /**
     * get the cart from db
     * @param $coupon_code
     * @param $un_used_only bool
     * @return array|object|void|null
     */
    public static function get_coupon($coupon_code, $un_used_only = true)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::DISCOUNTS_TABLE_NAME;
        $where = "WHERE `code` = '%s'";
        if ($un_used_only) {
            $where .= " AND `is_used` = '0'";
        }
        $query = "SELECT * FROM {$table_name} {$where}";
        $prepared_query = $wpdb->prepare($query, array($coupon_code));
        return $wpdb->get_row($prepared_query);
    }

    /**
     * get the cart from db
     * @param $email_id
     * @param $cart_id
     * @return array|object|void|null
     */
    public static function already_generated_coupon($email_id, $cart_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::DISCOUNTS_TABLE_NAME;
        $where = "WHERE `cart_id` = %d AND `mail_id` = %d";
        $query = "SELECT * FROM {$table_name} {$where}";
        $prepared_query = $wpdb->prepare($query, array($cart_id, $email_id));
        return $wpdb->get_row($prepared_query);
    }

    /**
     * update the coupon code column
     * @param $coupon_code
     * @param $column_name
     * @param $data
     * @return false|int
     */
    public static function update_coupon_by_code($coupon_code, $column_name, $data)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::DISCOUNTS_TABLE_NAME;
        return $wpdb->update($table_name, array($column_name => $data, 'updated_at' => current_time('mysql', true)), array('code' => $coupon_code));
    }
}