<?php
defined('ABSPATH') || exit;

class Acbwm_Data_Store_Cart
{
    use Acbwm_Trait_Language;

    const TABLE_NAME = 'inperks_abandoned_carts';
    const MAILS_TABLE_NAME = 'inperks_ac_sent_reminders';
    protected $cart_token, $currency, $is_all_mails_sent, $is_recovered, $recovery_link_opened, $recovery_link_clicked, $mail_id, $previously_scheduled, $language, $next_mail_at, $sent_emails, $scheduled_after, $abandoned_at, $user_ip, $user_name, $user_id, $order_id, $order_status, $cart_sub_total, $cart_total, $user_email, $user_cart, $created_at, $cart_id, $cart_history = array();

    public function __construct($cart_token)
    {
        $this->load_cart_data($cart_token);
    }

    /**
     * load the cart
     * @param $cart_token
     * @return $this
     */
    public function load_cart_data($cart_token)
    {
        $cart = $this->get_cart($cart_token);
        if (empty($cart)) {
            $this->create_cart($cart_token);
            $cart = $this->get_cart($cart_token);
        }
        if (property_exists($cart, 'cart_id')) {
            $this->cart_token = $cart->cart_token;
            $this->cart_id = $cart->cart_id;
            $this->created_at = $cart->created_at;
            $this->cart_sub_total = $cart->cart_sub_total;
            $this->cart_total = $cart->cart_total;
            $this->user_id = $cart->user_id;
            $this->user_email = $cart->user_email;
            $this->order_id = $cart->order_id;
            $this->order_status = $cart->order_status;
            $this->user_name = $cart->user_name;
            $this->user_ip = $cart->user_ip;
            $this->abandoned_at = $cart->abandoned_at;
            $this->scheduled_after = $cart->scheduled_after;
            $this->next_mail_at = $cart->next_mail_at;
            $this->language = $cart->language;
            $this->previously_scheduled = $cart->previously_scheduled;
            $this->mail_id = $cart->mail_id;
            $this->is_recovered = $cart->is_recovered;
            $this->is_all_mails_sent = $cart->is_all_mails_sent;
            $this->recovery_link_clicked = $cart->recovery_link_clicked;
            $this->recovery_link_opened = $cart->recovery_link_opened;
            $this->currency = $cart->currency;
            $history = $abandoned_cart = array();
            if (empty(!$cart->cart_history)) {
                $history = maybe_unserialize($cart->cart_history);
            }
            $this->cart_history = $history;
            if (empty(!$cart->user_cart)) {
                $abandoned_cart = maybe_unserialize($cart->user_cart);
            }
            $this->user_cart = $abandoned_cart;
        }
        return $this;
    }

    /**
     * currency of cart
     * @return mixed
     */
    public function get_currency()
    {
        return $this->currency;
    }

    /**
     * set currency of cart
     * @param $currency
     */
    public function set_currency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * is recovered
     * @return mixed
     */
    public function get_is_recovered()
    {
        return $this->is_recovered;
    }

    /**
     * is all mails sent
     * @return mixed
     */
    public function get_is_all_mails_sent()
    {
        return $this->is_all_mails_sent;
    }

    /**
     * set is cart recovered
     * @param $is_recovered
     */
    public function set_is_recovered($is_recovered)
    {
        $this->is_recovered = $is_recovered;
    }

    /**
     * set is all mails sent
     * @param $is_all_mails_sent
     */
    public function set_is_all_mails_sent($is_all_mails_sent)
    {
        $this->is_all_mails_sent = $is_all_mails_sent;
    }

    /**
     * is recovery link clicked
     * @return mixed
     */
    public function get_recovery_link_clicked()
    {
        return $this->recovery_link_clicked;
    }

    /**
     * is recovery link opened
     * @return mixed
     */
    public function get_recovery_link_opened()
    {
        return $this->recovery_link_opened;
    }

    /**
     * get the cart from db
     * @param $cart_token
     * @return array|object|void|null
     */
    public static function get_cart($cart_token)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $query = "SELECT * FROM {$table_name} WHERE `cart_token` = '%s'";
        $prepared_query = $wpdb->prepare($query, array($cart_token));
        return $wpdb->get_row($prepared_query);
    }

    /**
     * mae an entry to the db about the cart
     * @param $cart_token
     * @return int
     */
    public function create_cart($cart_token)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $wpdb->insert($table_name, array('cart_token' => $cart_token), array('%s'));
        return $wpdb->insert_id;
    }

    /**
     * get the all cart tokens need to send on every cron run
     * @return array|object|null
     */
    public static function get_cart_tokens_to_send_emails()
    {
        global $inperks_abandoned_carts, $wpdb;
        $no_of_carts_on_each_cron = $inperks_abandoned_carts->settings->get('process_carts_on_each_cron', 20);
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $query = "SELECT `cart_token` FROM {$table_name} WHERE `next_mail_at` < '%s' AND `user_email` IS NOT NULL AND `order_id` IS NULL AND `is_all_mails_sent` = '%s' LIMIT %d";
        $prepared_query = $wpdb->prepare($query, array(current_time('mysql', true), '0', $no_of_carts_on_each_cron));
        return $wpdb->get_results($prepared_query);
    }

    /**
     * get all cart token to stop mail sequences
     * @param $email_id
     * @return array|object|null
     */
    public static function get_cart_tokens_to_stop_mail_sequence($email_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $query = "SELECT `cart_token` FROM {$table_name} WHERE `user_email` ='{$email_id}' AND `order_id` IS NULL AND `is_all_mails_sent` = '%s'";
        $prepared_query = $wpdb->prepare($query, array('0'));
        return $wpdb->get_results($prepared_query);
    }

    /**
     * total sent emails
     * @param $mail_id
     * @return int
     */
    public static function get_total_sent_emails($mail_id = null)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::MAILS_TABLE_NAME;
        $query = "SELECT count(id) FROM {$table_name}";
        $prepare = array();
        if (!empty($mail_id)) {
            $query .= " WHERE `mail_id` = '%s'";
            $prepare[] = $mail_id;
            $query = $wpdb->prepare($query, $prepare);
        }
        return $wpdb->get_var($query);
    }

    /**
     * total all sent emails
     * @param $limit
     * @param $offset
     * @param $order
     * @param $order_by
     * @param null $search
     * @param  $mail_type string
     * @return array|object|null
     */
    public static function get_all_sent_emails($limit, $offset, $order_by, $order, $search = null, $mail_type = "all")
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::MAILS_TABLE_NAME;
        $where = " WHERE 1 = 1 ";
        if (!empty($search)) {
            $where .= " AND (`email` LIKE '%" . esc_sql($wpdb->esc_like($search)) . "%' OR `subject` LIKE '%" . esc_sql($wpdb->esc_like($search)) . "%' )";
        }
        if ($mail_type == "clicked") {
            $where .= " AND is_clicked = '1' ";
        }
        if ($mail_type == "opened") {
            $where .= " AND is_opened = '1' ";
        }
        $query = "SELECT * FROM {$table_name} {$where} ORDER BY {$order_by} {$order} LIMIT {$limit} OFFSET {$offset}";
        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * total all user carts
     * @param $limit
     * @param $offset
     * @param $order
     * @param $order_by
     * @param null $search
     * @param  $user_type string
     * @return array|object|null
     */
    public static function get_all_carts($limit, $offset, $order_by, $order, $search = null, $user_type = "all")
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $where = " WHERE 1 = 1 ";
        if (!empty($search)) {
            $where .= " AND (`user_email` LIKE '%" . esc_sql($wpdb->esc_like($search)) . "%' OR `user_name` LIKE '%" . esc_sql($wpdb->esc_like($search)) . "%') ";
        }
        if ($user_type == "registered") {
            $where .= " AND user_id IS NOT NULL ";
        }
        if ($user_type == "guest") {
            $where .= " AND user_email IS NOT NULL ";
        }
        if ($user_type == "visitor") {
            $where .= " AND user_email IS NULL AND user_email IS NULL ";
        }
        $where .= " AND created_at IS NOT NULL ";
        $query = "SELECT * FROM {$table_name} {$where} ORDER BY {$order_by} {$order} LIMIT {$limit} OFFSET {$offset}";
        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * total all sent emails
     * @param null $search
     * @param  $mail_type string
     * @return int
     */
    public static function get_all_sent_emails_count($search = null, $mail_type = 'all')
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::MAILS_TABLE_NAME;
        $where = " WHERE 1 = 1 ";
        if (!empty($search)) {
            $where .= " AND (`email` LIKE '%{" . esc_sql($wpdb->esc_like($search)) . "%' OR `subject` LIKE '%" . esc_sql($wpdb->esc_like($search)) . "%') ";
        }
        if ($mail_type == "clicked") {
            $where .= " AND is_clicked = '1' ";
        }
        if ($mail_type == "opened") {
            $where .= " AND is_opened = '1' ";
        }
        $query = "SELECT count(id) FROM {$table_name} {$where}";
        return $wpdb->get_var($query);
    }

    /**
     * total all sent emails
     * @param null $search
     * @param  $user_type string
     * @return int
     */
    public static function get_all_carts_count($search = null, $user_type = 'all')
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $where = " WHERE 1 = 1 ";
        if (!empty($search)) {
            $where .= " AND (`user_email` LIKE '%{" . esc_sql($wpdb->esc_like($search)) . "%' OR `user_name` LIKE '%" . esc_sql($wpdb->esc_like($search)) . "%') ";
        }
        if ($user_type == "registered") {
            $where .= " AND user_id IS NOT NULL ";
        }
        if ($user_type == "guest") {
            $where .= " AND user_email IS NOT NULL ";
        }
        if ($user_type == "visitor") {
            $where .= " AND user_email IS NULL AND user_email IS NULL ";
        }
        $where .= " AND created_at IS NOT NULL ";
        $query = "SELECT count(cart_id) FROM {$table_name} {$where}";
        return $wpdb->get_var($query);
    }

    /**
     * total opened emails
     * @param $mail_id
     * @return int
     */
    public static function get_total_opened_emails($mail_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::MAILS_TABLE_NAME;
        $query = "SELECT count(id) FROM {$table_name} WHERE `mail_id` = '%s' AND `is_opened`=%s";
        $prepared_query = $wpdb->prepare($query, array($mail_id, '1'));
        return $wpdb->get_var($prepared_query);
    }

    /**
     * total clicked emails
     * @param $mail_id
     * @return int
     */
    public static function get_total_clicked_emails($mail_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::MAILS_TABLE_NAME;
        $query = "SELECT count(id) FROM {$table_name} WHERE `mail_id` = '%s' AND `is_clicked`=%s";
        $prepared_query = $wpdb->prepare($query, array($mail_id, '1'));
        return $wpdb->get_var($prepared_query);
    }

    /**
     * Set the cart id
     * @param $cart_id
     */
    public function set_cart_id($cart_id)
    {
        $this->cart_id = $cart_id;
    }

    /**
     * Set previously completed email template time
     * @param $previously_scheduled
     */
    public function set_previously_scheduled($previously_scheduled)
    {
        $this->previously_scheduled = $previously_scheduled;
    }

    /**
     * get next mail at time
     * @return null
     */
    public function get_next_mail_at()
    {
        return $this->next_mail_at;
    }

    /**
     * get language of the cart
     * @return null
     */
    public function get_language()
    {
        return $this->language;
    }

    /**
     * get previously completed email template time
     * @return null
     */
    public function get_previously_scheduled()
    {
        return $this->previously_scheduled;
    }

    /**
     *
     * @param $next_mail_at
     */
    public function set_next_mail_at($next_mail_at)
    {
        $this->next_mail_at = $next_mail_at;
    }

    /**
     * get scheduled after at time
     * @return null
     */
    public function get_scheduled_after()
    {
        return $this->scheduled_after;
    }

    /**
     * get scheduled mail id
     * @return null
     */
    public function get_mail_id()
    {
        return $this->mail_id;
    }

    /**
     * set scheduled after
     * @param $scheduled_after
     */
    public function set_scheduled_after($scheduled_after)
    {
        $this->scheduled_after = $scheduled_after;
    }

    /**
     * set scheduled after
     * @param $mail_id
     */
    public function set_mail_id($mail_id)
    {
        $this->mail_id = $mail_id;
    }

    /**
     * create cart obj for mail
     * @param $email_id
     * @param $mail
     * @return stdClass
     */
    public function create_cart_items_object($email_id, $mail)
    {
        $obj = new stdClass();
        $user_cart = $this->get_user_cart();
        $abandoned_cart_items = isset($user_cart['cart']) ? $user_cart['cart'] : array();
        $items = array();
        if (!empty($abandoned_cart_items)) {
            foreach ($abandoned_cart_items as $cart_item_key => $cart_details) {
                $item_id = $cart_details['product_id'];
                if (!empty($cart_details['variation_id'])) {
                    $item_id = $cart_details['variation_id'];
                }
                $product = wc_get_product($item_id);
                if ($product instanceof WC_Product) {
                    $extra = array();
                    $exclude_cart_item_params = apply_filters(ACBWM_HOOK_PREFIX . 'exclude_cart_item_params', array('key', 'product_id', 'variation_id', 'variation', 'quantity', 'data_hash', 'line_tax_data', 'line_subtotal', 'line_subtotal_tax', 'line_total', 'line_tax'));
                    foreach ($cart_details as $key => $val) {
                        if (!in_array($key, $exclude_cart_item_params)) {
                            $extra[$key] = $val;
                        }
                    }
                    $items[] = array(
                        'image' => $product->get_image(array(50, 50)),
                        'title' => $product->get_title(),
                        'sku' => $product->get_sku(),
                        'quantity' => $cart_details['quantity'],
                        'total' => $cart_details['line_total'],
                        'extra' => $extra,
                        'raw' => $cart_details,
                    );
                }
            }
        }
        $obj->items = $items;
        $obj->currency = $this->get_currency();
        $totals = array(
            array(
                'label' => __('Subtotal', ACBWM_TEXT_DOMAIN),
                'value' => $user_cart['cart_totals']['subtotal'],
            ), array(
                'label' => __('Discount', ACBWM_TEXT_DOMAIN),
                'value' => $user_cart['cart_totals']['discount_total'],
            ), array(
                'label' => __('Shipping', ACBWM_TEXT_DOMAIN),
                'value' => $user_cart['cart_totals']['shipping_total'],
            ), array(
                'label' => __('Fees', ACBWM_TEXT_DOMAIN),
                'value' => $user_cart['cart_totals']['fee_total'],
            ),
            array(
                'label' => __('Total', ACBWM_TEXT_DOMAIN),
                'value' => $user_cart['cart_totals']['total']
            )
        );
        $totals = apply_filters(ACBWM_HOOK_PREFIX . 'get_cart_items_total', $totals, $user_cart);
        $obj->totals = $totals;
        $recovery_url = self::generate_recovery_url($email_id);
        $obj->recovery_url = $recovery_url;
        $obj->tracking_image = self::generate_tracking_pixel($email_id);
        $obj->customer = $this->get_user_name();
        $extra_Details = array();
        $use_woocommerce_style = 1;
        if (!empty($mail)) {
            $extra = $mail->details;
            $extra_Details = maybe_unserialize($extra);
            $use_woocommerce_style = $mail->use_woocommerce_style;
        }
        $obj->coupon_code = '';
        $obj->expire_time = '';
        $obj->discount = '';
        $obj->discount_url = '#';
        apply_filters(ACBWM_HOOK_PREFIX . 'acbwm_email_template_coupons', $obj, $mail, $this, $extra_Details, $recovery_url);
        $obj->use_woocommerce_style = $use_woocommerce_style;
        return apply_filters(ACBWM_HOOK_PREFIX . 'email_create_object', $obj, $this);
    }

    /**
     * get cart user cart
     * @return null
     */
    public function get_user_cart()
    {
        return $this->user_cart;
    }

    /**
     * generate the recovery URL
     * @param $email_id ;
     * @return string
     */
    public function generate_recovery_url($email_id)
    {
        $cart_token = $this->get_cart_token();
        $data = array(
            'cart_token' => $cart_token,
            'id' => $email_id
        );
        $data = base64_encode(wp_json_encode($data));
        $wc_api_url = WC()->api_request_url(ACBWM_API_ENDPOINT);
        $secret = acbwm_set_and_get_plugin_secret();
        $hash = acbwm_hash_data($data, $secret);
        $abandoned_cart_url = add_query_arg(array('token' => rawurlencode($data), 'hash' => $hash), $wc_api_url);
        return apply_filters(ACBWM_HOOK_PREFIX . 'generate_recovery_url', $abandoned_cart_url, $this);
    }

    /**
     * generate the recovery URL
     * @param $email_id ;
     * @return string
     */
    public function generate_tracking_pixel($email_id)
    {
        $cart_token = $this->get_cart_token();
        $data = array(
            'cart_token' => $cart_token,
            'id' => $email_id
        );
        $wc_api_url = WC()->api_request_url(ACBWM_API_ENDPOINT_MAIL_OPENED);
        $data = base64_encode(wp_json_encode($data));
        $secret = acbwm_set_and_get_plugin_secret();
        $hash = acbwm_hash_data($data, $secret);
        $abandoned_cart_url = add_query_arg(array('token' => rawurlencode($data), 'hash' => $hash), $wc_api_url);
        $url = apply_filters(ACBWM_HOOK_PREFIX . 'generate_tracking_pixel', $abandoned_cart_url, $this);
        return "<img src='{$url}' width='1' height='1' alt='' />";
    }

    /**
     * get cart token
     * @return null
     */
    public function get_cart_token()
    {
        return $this->cart_token;
    }

    /**
     * Set the cart token
     * @param $cart_token
     */
    public function set_cart_token($cart_token)
    {
        $this->cart_token = $cart_token;
    }

    /**
     * get cart IP
     * @return null
     */
    public function get_user_ip()
    {
        return $this->user_ip;
    }

    /**
     * get cart user name
     * @return null
     */
    public function get_user_name()
    {
        return $this->user_name;
    }

    /**
     * get order id
     * @return null
     */
    public function get_order_id()
    {
        return $this->order_id;
    }

    /**
     * get order status
     * @return null
     */
    public function get_order_status()
    {
        return $this->order_status;
    }

    /**
     * get cart total
     * @return null
     */
    public function get_cart_total()
    {
        return $this->cart_total;
    }

    /**
     * get user id
     * @return null
     */
    public function get_user_id()
    {
        return $this->user_id;
    }

    /**
     * get cart sub total
     * @return null
     */
    public function get_cart_sub_total()
    {
        return $this->cart_sub_total;
    }

    /**
     * get cart user email
     * @return null
     */
    public function get_user_email()
    {
        return $this->user_email;
    }

    /**
     * set the current history of the cart
     * @param $user_name string
     */
    public function set_user_name($user_name)
    {
        $this->user_name = $user_name;
    }

    /**
     * set the abandoned at time of the cart
     * @param $abandoned_at string
     */
    public function set_abandoned_at($abandoned_at)
    {
        $this->abandoned_at = $abandoned_at;
    }

    /**
     * set the current cart
     * @param $user_cart string|array
     */
    public function set_user_cart($user_cart)
    {
        $user_cart = maybe_unserialize($user_cart);
        $this->user_cart = $user_cart;
    }

    /**
     * set the current IP of the cart
     * @param $user_ip string
     */
    public function set_user_ip($user_ip)
    {
        $this->user_ip = $user_ip;
    }

    /**
     * set the current order status of the cart
     * @param $order_status string
     */
    public function set_order_status($order_status)
    {
        $this->order_status = $order_status;
    }

    /**
     * set the current order id of the cart
     * @param $order_id int
     */
    public function set_order_id($order_id)
    {
        $this->order_id = $order_id;
    }

    /**
     * update the current order status of the cart
     * @param $order_status string
     */
    public function update_order_status($order_status)
    {
        $this->update_column('order_status', $order_status, true);
    }

    /**
     * update the column of the table
     * @param $column_name
     * @param $data
     * @param $update_class bool
     */
    public function update_column($column_name, $data, $update_class = false)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $wpdb->update($table_name, array($column_name => $data, 'updated_at' => current_time('mysql', true)), array('cart_id' => $this->cart_id));
        if ($update_class) {
            $method_name = 'set_' . $column_name;
            if (method_exists($this, $method_name)) {
                $this->$method_name($data);
            }
        }
    }

    /**
     * update the column of the ac sent emails table
     * @param $id
     * @param $column_name
     * @param $data
     */
    public static function update_sent_email($id, $column_name, $data)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::MAILS_TABLE_NAME;
        $wpdb->update($table_name, array($column_name => $data, 'updated_at' => current_time('mysql', true)), array('id' => $id));
    }

    /**
     * update the IP of the cart
     * @param $user_ip string
     */
    public function update_user_ip($user_ip)
    {
        $this->update_column('user_ip', $user_ip, true);
    }

    /**
     * update the current user name of the cart
     * @param $user_name string
     */
    public function update_user_name($user_name)
    {
        $this->update_column('user_name', $user_name, true);
    }

    /**
     * update the current order id of the cart
     * @param $order_id int
     */
    public function update_order_id($order_id)
    {
        $this->update_column('order_id', $order_id, true);
    }

    /**
     * set the current history of the cart
     * @param $user_id int
     */
    public function set_user_id($user_id)
    {
        $this->user_id = $user_id;
    }

    /**
     * set the cart sub total
     * @param $cart_sub_total float
     */
    public function set_cart_sub_total($cart_sub_total)
    {
        $this->cart_sub_total = $cart_sub_total;
    }

    /**
     * set the cart total
     * @param $cart_total float
     */
    public function set_cart_total($cart_total)
    {
        $this->cart_total = $cart_total;
    }

    /**
     * set the cart user email
     * @param $user_email string
     */
    public function set_user_email($user_email)
    {
        $this->user_email = $user_email;
    }

    /**
     * set the current history of the cart
     * @param $created_at string
     */
    public function set_created_at($created_at)
    {
        if (empty($created_at)) {
            $created_at = current_time('mysql', true);
        }
        $this->created_at = $created_at;
    }

    /**
     * update the totals of the cart
     * @param $total
     * @param $subtotal
     * @param $currency string
     */
    public function update_totals($total, $subtotal, $currency)
    {
        $this->update_column('cart_sub_total', $subtotal, true);
        $this->update_column('cart_total', $total, true);
        $this->update_column('currency', $currency, true);
    }

    /**
     * update the cart history to db
     * @param $action string
     * @param $type string
     * @param $extra array
     * @param $force_history bool
     */
    public function update_history($action, $type, $extra = array(), $force_history = false)
    {
        $new_history = $this->make_history($action, $type, $extra);
        $history = $this->get_cart_history();
        $update_history = false;
        if (!empty($history)) {
            $last_history = end($history);
            if (is_array($last_history) && isset($last_history['action']) && $last_history['action'] != $action) {
                $update_history = true;
            }
        } else {
            $update_history = true;
        }
        if ($force_history) {
            $update_history = true;
        }
        if ($update_history) {
            array_push($history, $new_history);
            $this->update_column('cart_history', maybe_serialize($history));
            $this->set_cart_history($history);
        }
    }

    /**
     * make the history
     * @param $action
     * @param $type
     * @param $extra array
     * @return array
     */
    public function make_history($action, $type, $extra)
    {
        $history = array('timestamp' => current_time('timestamp', true), 'action' => $action, 'type' => $type);
        if (!empty($extra)) {
            $history['extra'] = $extra;
        }
        return $history;
    }

    /**
     * get the current history of the cart
     * @return array
     */
    public function get_cart_history()
    {
        return $this->cart_history;
    }

    /**
     * set the current history of the cart
     * @param $history array
     */
    public function set_cart_history($history)
    {
        $this->cart_history = $history;
    }

    /**
     * update the language of the cart
     */
    function update_language()
    {
        $current_language = $this->get_current_language();
        if ($this->language !== $current_language) {
            $this->update_column('language', $current_language, true);
            $this->schedule_next_email();
        }
    }

    /**
     * schedule all mail sequences
     */
    function schedule_next_email()
    {
        $previously_completed = $this->get_previously_scheduled();
        $next_mail_sequence_at = Acbwm_Data_Store_Email::get_next_template_to_schedule($this->language, $previously_completed);
        if (!empty($next_mail_sequence_at)) {
            $this->set_next_scheduled_time($next_mail_sequence_at->send_after, $next_mail_sequence_at->mail_id);
        } else {
            $this->end_mail_sequences();
        }
    }

    /**
     * update the user cart
     * @param $cart
     */
    public function update_cart($cart)
    {
        $cart = maybe_serialize($cart);
        $this->update_column('user_cart', $cart, true);
        $this->update_language();
        if (empty($this->get_created_at())) {
            global $inperks_abandoned_carts;
            $mysql_created_at = current_time('mysql', true);
            $this->update_created_at($mysql_created_at);
            $cut_off_time = $inperks_abandoned_carts->settings->get('cart_cut_off_time', 60);
            $mysql_abandoned_at = acbwm_add_time($cut_off_time, 'min', 'Y-m-d H:i:s');
            $this->update_abandoned_at($mysql_abandoned_at);
            $this->schedule_next_email();
        }
        $this->update_user_id();
    }

    /**
     * get cart created time
     * @return null
     */
    public function get_created_at()
    {
        return $this->created_at;
    }

    /**
     * update the cart created
     * @param $created_at
     */
    public function update_created_at($created_at = null)
    {
        if (empty($created_at)) {
            $created_at = current_time('mysql', true);
        }
        $this->update_column('created_at', $created_at, true);
    }

    /**
     * update the cart abandoned time
     * @param $abandoned_at
     */
    public function update_abandoned_at($abandoned_at)
    {
        $this->update_column('abandoned_at', $abandoned_at, true);
    }

    /**
     * set the next scheduled time
     * @param $next_at
     * @param $mail_id
     */
    public function set_next_scheduled_time($next_at, $mail_id)
    {
        $abandoned_time = $this->get_abandoned_at();
        if (!empty($abandoned_time)) {
            $abandoned_timestamp = strtotime($abandoned_time);
            $next_scheduled_at = acbwm_concat_time($abandoned_timestamp, $next_at);
            $mysql_next_scheduled_at = date('Y-m-d H:i:s', $next_scheduled_at);
            $this->update_column('next_mail_at', $mysql_next_scheduled_at, true);
            $this->update_column('scheduled_after', $next_at, true);
            $this->update_column('mail_id', $mail_id, true);
        }
    }

    /**
     * End the mail sequences
     * @param string $message
     */
    public function end_mail_sequences($message = "Mail sequences ended")
    {
        if ($this->get_is_all_mails_sent() == "0") {
            //setting all emails where sent
            $this->update_column('is_all_mails_sent', '1', true);
            $this->update_history($message, 'mails');
        }
    }

    /**
     * get abandoned at
     * @return null
     */
    public function get_abandoned_at()
    {
        return $this->abandoned_at;
    }

    /**
     * update the cart user id
     * @param $user_id
     */
    public function update_user_id($user_id = null)
    {
        if (empty($user_id) && is_user_logged_in()) {
            $user_id = get_current_user_id();
        }
        if (!empty($user_id)) {
            $this->update_column('user_id', $user_id, true);
        }
    }

    /**
     * update the cart user email
     * @param $user_email
     */
    public function update_user_email($user_email)
    {
        $this->update_column('user_email', $user_email, true);
    }

    /**
     * get all the set email
     * @return array|object|null
     */
    public function get_sent_emails()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::MAILS_TABLE_NAME;
        $query = "SELECT * FROM {$table_name} WHERE `cart_id` = '%d'";
        $cart_id = $this->get_cart_id();
        $prepared_query = $wpdb->prepare($query, array($cart_id));
        return $wpdb->get_results($prepared_query);
    }

    /**
     * get cart id
     * @return null
     */
    public function get_cart_id()
    {
        return $this->cart_id;
    }

    /**
     * create sent emails entry
     * @param $mail_id
     * @param $subject
     * @param $email
     * @return int
     */
    public function set_sent_email($mail_id, $subject, $email)
    {
        $cart_id = $this->get_cart_id();
        global $wpdb;
        $table_name = $wpdb->prefix . self::MAILS_TABLE_NAME;
        $wpdb->insert($table_name, array('mail_id' => $mail_id, 'cart_id' => $cart_id, 'subject' => $subject, 'created_at' => current_time('mysql', true), 'updated_at' => current_time('mysql', true), 'email' => $email, 'reminder_type' => 'email'), array('%s', '%d', '%s', '%s', '%s', '%s', '%s'));
        return $wpdb->insert_id;
    }

    /**
     * delete sent emails entry
     */
    public function delete_all_mail_log()
    {
        global $wpdb;
        $cart_id = $this->get_cart_id();
        $table_name = $wpdb->prefix . self::MAILS_TABLE_NAME;
        $wpdb->delete($table_name, array('cart_id' => $cart_id), null);
    }

    /**
     * delete cart
     */
    public function delete_cart()
    {
        global $wpdb;
        $cart_id = $this->get_cart_id();
        $this->delete_all_mail_log();
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $wpdb->delete($table_name, array('cart_id' => $cart_id), null);
    }

    /**
     * All cart tokens to re schedule email template
     * @return array|object|null
     */
    public static function get_all_carts_to_re_schedule()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $query = "SELECT `cart_token` FROM {$table_name} WHERE `created_at` IS NOT NULL AND `is_recovered` =0";
        return $wpdb->get_results($query);
    }

    /**
     * totals number carts
     * @return string|null
     */
    public static function get_total_carts_count()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $query = "SELECT count(`cart_id`) FROM {$table_name}";
        return $wpdb->get_var($query);
    }

    /**
     * totals number carts
     * @param $start string
     * @param $end string
     * @param $need_sum bool
     * @return string|null
     */
    public static function get_total_abandoned_carts_count($start, $end, $need_sum = false)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $current_time = current_time('mysql', true);
        $select = 'count(`cart_id`)';
        if ($need_sum) {
            $select = 'sum(`cart_total`)';
        }
        $query = "SELECT {$select} FROM {$table_name} WHERE `created_at` IS NOT NULL AND `is_recovered` =0 AND `order_id` IS NULL AND `abandoned_at` < '{$current_time}' AND `created_at` BETWEEN '{$start}' AND '{$end}'";
        return $wpdb->get_var($query);
    }

    /**
     * totals number carts
     * @param $need_sum bool
     * @param $start string
     * @param $end string
     * @return string|null
     */
    public static function get_total_recoverable_carts_count($start, $end, $need_sum = false)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $current_time = current_time('mysql', true);
        $select = 'count(`cart_id`)';
        if ($need_sum) {
            $select = 'sum(`cart_total`)';
        }
        $query = "SELECT {$select} FROM {$table_name} WHERE `created_at` IS NOT NULL AND `is_recovered` =0 AND `order_id` IS NULL AND `abandoned_at` < '{$current_time}' AND `user_email` IS NOT NULL AND `created_at` BETWEEN '{$start}' AND '{$end}'";
        return $wpdb->get_var($query);
    }

    /**
     * totals number carts
     * @param $need_sum bool
     * @param $start string
     * @param $end string
     * @return string|null
     */
    public static function get_total_recovered_carts_count($start, $end, $need_sum = false)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $select = 'count(`cart_id`)';
        if ($need_sum) {
            $select = 'sum(`cart_total`)';
        }
        $query = "SELECT {$select} FROM {$table_name} WHERE `is_recovered` =1 AND `created_at` BETWEEN '{$start}' AND '{$end}'";
        return $wpdb->get_var($query);
    }

    /**
     * totals number carts
     * @param $start string
     * @param $end string
     * @param $days_diff int
     * @return string|null
     */
    public static function get_total_carts_for_graph($start, $end, $days_diff)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $group_by = self::get_group_by_graph($days_diff);
        $current_time = current_time('mysql', true);
        $query = "SELECT ({$group_by}) as x,count(cart_id) as total_carts, sum(is_recovered) as recovered_cart FROM {$table_name} WHERE `created_at` BETWEEN '{$start}' AND '{$end}' AND `abandoned_at` < '{$current_time}' GROUP BY  {$group_by}";
        return $wpdb->get_results($query);
    }

    /**
     * group by
     * @param $days_diff
     * @return string
     */
    public static function get_group_by_graph($days_diff)
    {
        $offset = acbwm_get_time_zone_offset();
        if ($days_diff > 728) {
            $group_by = "DATE_FORMAT(CONVERT_TZ(created_at,'+00:00','{$offset}'), '%Y')";
        } elseif ($days_diff > 64) {
            $group_by = "DATE_FORMAT(CONVERT_TZ(created_at,'+00:00','{$offset}'), '%M, %Y')";
        } else {
            $group_by = "DATE_FORMAT(CONVERT_TZ(created_at,'+00:00','{$offset}'), '%M %d, %Y')";
        }
        return $group_by;
    }

    /**
     * totals number sent emails
     * @return string|null
     */
    public static function get_total_sent_mails_count()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::MAILS_TABLE_NAME;
        $query = "SELECT count(`id`) FROM {$table_name}";
        return $wpdb->get_var($query);
    }

    /**
     * totals number opened email
     * @return string|null
     */
    public static function get_total_opened_email_count()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::MAILS_TABLE_NAME;
        $query = "SELECT count(`id`) FROM {$table_name} WHERE `is_opened` = '1'";
        return $wpdb->get_var($query);
    }

    /**
     * totals number clicked email
     * @return string|null
     */
    public static function get_total_clicked_email_count()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . self::MAILS_TABLE_NAME;
        $query = "SELECT count(`id`) FROM {$table_name} WHERE `is_clicked` = '1'";
        return $wpdb->get_var($query);
    }
}