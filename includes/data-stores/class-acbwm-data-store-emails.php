<?php
defined('ABSPATH') || exit;

class Acbwm_Data_Store_Email
{
    const SENT_EMAILS_TABLE_NAME = 'inperks_ac_sent_reminders';
    const TEMPLATES_TABLE_NAME = 'inperks_templates';

    /**
     * total all email templates
     * @param $limit
     * @param $offset
     * @param $order
     * @param $order_by
     * @param null $search
     * @param string $language
     * @return array|object|null
     */
    public static function get_all_emails($limit, $offset, $order_by, $order, $search = null, $language = "all")
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TEMPLATES_TABLE_NAME;
        $where = " WHERE 1 = 1 ";
        if (!empty($search)) {
            $where .= " AND (`name` LIKE '%" . esc_sql($wpdb->esc_like($search)) . "%' OR `subject` LIKE '%" . esc_sql($wpdb->esc_like($search)) . "%') ";
        }
        if ($language != "all") {
            $where .= " AND `language`='{$language}' ";
        }
        $query = "SELECT * FROM {$table_name} {$where} ORDER BY {$order_by} {$order} LIMIT {$limit} OFFSET {$offset}";
        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * total all active email templates
     * @param string $select
     * @return array|object|null
     */
    public static function get_active_emails($select = "*")
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TEMPLATES_TABLE_NAME;
        $where = " WHERE `status` = '1' ";
        $query = "SELECT {$select} FROM {$table_name} {$where} ORDER BY `send_after` ASC";
        return $wpdb->get_results($query, OBJECT);
    }

    /**
     * total all email templates
     * @param null $search
     * @param string $language
     * @return int
     */
    public static function get_all_emails_count($search = null, $language = "all")
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TEMPLATES_TABLE_NAME;
        $where = " WHERE 1 = 1 ";
        if (!empty($search)) {
            $where .= " AND (`name` LIKE '%" . esc_sql($wpdb->esc_like($search)) . "%' OR `subject` LIKE '%" . esc_sql($wpdb->esc_like($search)) . "%') ";
        }
        if ($language != "all") {
            $where .= " AND `language`='{$language}' ";
        }
        $query = "SELECT count(mail_id) FROM {$table_name} {$where}";
        return $wpdb->get_var($query);
    }

    /**
     * total all email templates by its time
     * @param $time int
     * @param $language string
     * @return string|null
     */
    public static function get_template_by_time($time, $language)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TEMPLATES_TABLE_NAME;
        $where = " WHERE `send_after` = {$time} AND `language` ='{$language}'";
        $query = "SELECT mail_id FROM {$table_name} {$where}";
        return $wpdb->get_results($query);
    }

    /**
     * total all email templates by its time
     * @param $language
     * @param null $last_sent
     * @return array|object|void|null
     */
    public static function get_next_template_to_schedule($language, $last_sent = null)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TEMPLATES_TABLE_NAME;
        $where = " WHERE `language` LIKE '%{$language}%' AND `status`='1' ";
        if (!empty($last_sent)) {
            $where .= " AND `send_after` > {$last_sent}";
        }
        $query = "SELECT send_after,mail_id FROM {$table_name} {$where} ORDER BY `send_after` ASC LIMIT 1";
        return $wpdb->get_row($query);
    }

    /**
     * all email templates by language
     * @param $language
     * @return array|object|void|null
     */
    public static function get_all_emails_by_language($language)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TEMPLATES_TABLE_NAME;
        $where = " WHERE `language` LIKE '%{$language}%' AND `status`='1' ";
        $query = "SELECT * FROM {$table_name} {$where} ORDER BY `send_after` ASC";
        return $wpdb->get_results($query);
    }

    /**
     * save email template
     * @param $data array
     * @param $type array
     * @return int
     */
    public static function save_new_template($data, $type)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TEMPLATES_TABLE_NAME;
        $wpdb->insert($table_name, $data, $type);
        return $wpdb->insert_id;
    }

    /**
     * save email template
     * @param $data array
     * @param $type array
     * @param $where array
     * @return int
     */
    public static function save_edited_template($data, $where, $type)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TEMPLATES_TABLE_NAME;
        return $wpdb->update($table_name, $data, $where, $type);
    }

    /**
     * get template by ID
     * @param $id
     * @param string $return_type
     * @return array|object|void|null
     */
    public static function get_template_by_id($id, $return_type = OBJECT)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TEMPLATES_TABLE_NAME;
        $query = "SELECT * FROM {$table_name} WHERE `mail_id` = '%d'";
        $prepared_query = $wpdb->prepare($query, array($id));
        return $wpdb->get_row($prepared_query, $return_type);
    }

    /**
     * delete template by ID
     * @param $id
     * @return false|int
     */
    public static function delete_template($id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TEMPLATES_TABLE_NAME;
        return $wpdb->delete($table_name, array('mail_id' => $id), null);
    }

    /**
     * update the column of the table
     * @param $mail_id
     * @param $column_name
     * @param $data
     * @return false|int
     */
    public static function update_template_column($mail_id, $column_name, $data)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TEMPLATES_TABLE_NAME;
        return $wpdb->update($table_name, array($column_name => $data), array('mail_id' => $mail_id));
    }
}