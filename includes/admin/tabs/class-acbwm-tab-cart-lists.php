<?php
defined('ABSPATH') || exit;

class Acbwm_Admin_Tab_Carts extends WP_List_Table
{
    function __construct()
    {
        parent::__construct(array(
            'singular' => __('Cart', ACBWM_TEXT_DOMAIN),
            'plural' => __('Carts', ACBWM_TEXT_DOMAIN),
            'ajax' => false
        ));
    }

    /**
     * bulk actions list
     * @return array
     */
    public function get_bulk_actions()
    {
        return array(
            'delete' => __('Delete', ACBWM_TEXT_DOMAIN),
            'end_mail_sequence' => __('End mail sequence', ACBWM_TEXT_DOMAIN),
        );
    }

    /**
     * bulk actions checkbox
     * @param object $item
     * @return string|void
     */
    public function column_cb($item)
    {
        return sprintf('<input type="checkbox" name="%1$s[]" value="%2$s" />', 'cart', $item['cart_token']);
    }

    /**
     * prepare items to display the carts
     */
    public function prepare_items()
    {
        /** Process bulk action */
        $this->process_bulk_action();
        $per_page = $this->get_items_per_page('acbwm_ac_per_page', 20);
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;
        $search = sanitize_text_field(isset($_GET['s']) ? $_GET['s'] : '');
        $user_type = sanitize_text_field(isset($_GET['user_type']) ? $_GET['user_type'] : 'all');
        $total_items = Acbwm_Data_Store_Cart::get_all_carts_count($search, $user_type);
        //$hidden = $this->get_hidden_columns();
        $this->_column_headers = $this->get_column_info();
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page
        ));
        $order_by = sanitize_text_field((!empty($_GET['orderby'])) ? $_GET['orderby'] : 'cart_id');
        // If no order, default to asc
        $order = sanitize_text_field((!empty($_GET['order'])) ? $_GET['order'] : 'desc');
        $this->items = Acbwm_Data_Store_Cart::get_all_carts($per_page, $offset, $order_by, $order, $search, $user_type);
    }

    /**
     * Process the bulk action
     */
    public function process_bulk_action()
    {
        $current_action = $this->current_action();
        switch ($current_action) {
            case "delete":
                if (isset($_POST['cart']) && !empty($_POST['cart'])) {
                    foreach ($_POST['cart'] as $cart_token) {
                        Acbwm_Admin_Carts::delete_cart_by_token($cart_token);
                    }
                }
                break;
            case "end_mail_sequence":
                if (isset($_POST['cart']) && !empty($_POST['cart'])) {
                    foreach ($_POST['cart'] as $cart_token) {
                        $cart = Acbwm_Carts::get_cart_by_cart_token($cart_token);
                        $cart->end_mail_sequences();
                    }
                }
                break;
            default:
                break;
        }
    }

    /**
     * get all columns
     * @return array
     */
    public function get_columns()
    {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'cart_id' => __('ID', ACBWM_TEXT_DOMAIN),
            'user_email' => __('User email', ACBWM_TEXT_DOMAIN),
            'customer' => __('Customer', ACBWM_TEXT_DOMAIN),
            'cart_total' => __('Cart total<small>(Shop currency)</small>', ACBWM_TEXT_DOMAIN),
            'order_id' => __('Order id', ACBWM_TEXT_DOMAIN),
            'order_status' => __('Order status', ACBWM_TEXT_DOMAIN),
            'abandoned_at' => __('Abandoned at', ACBWM_TEXT_DOMAIN),
            'status' => __('Cart status', ACBWM_TEXT_DOMAIN),
        );
        return apply_filters(ACBWM_HOOK_PREFIX . 'carts_list_get_columns', $columns);
    }

    /**
     * sortable columns
     * @return array
     */
    public function get_sortable_columns()
    {
        return array(
            'cart_id' => array('cart_id', false),
            'user_email' => array('user_email', false),
            'abandoned_at' => array('abandoned_at', false),
            'order_id' => array('abandoned_at', false),
            'order_status' => array('abandoned_at', false),
        );
    }

    /**
     * sortable columns
     * @return array
     */
    public function get_hidden_columns()
    {
        return array(
            'used_coupons',
            'is_all_mails_sent',
            'recovery_link_clicked'
        );
    }

    public function column_default($item, $column_name)
    {
        $abandoned_at = $item['abandoned_at'];
        switch ($column_name) {
            case 'abandoned_at':
                $return = get_date_from_gmt($abandoned_at, 'Y-m-d H:i:s');
                break;
            case 'user_email':
                $return = $item['user_email'];
                $id = $item['cart_token'];
                $url = admin_url("admin-ajax.php");
                $actions['view'] = sprintf('<a href="%s?action=%s&cart=%s" rel="modal:open">%s</a>', $url, ACBWM_HOOK_PREFIX . 'view_cart', $id, __('View', ACBWM_TEXT_DOMAIN));
                if (!empty($item['user_email'])) {
                    $actions = apply_filters(ACBWM_HOOK_PREFIX . 'admin_send_cart_emails_manually', $actions, $id, $url);
                }
                $actions['delete'] = sprintf('<a href="#" data-cart="%s" class="acbwm-delete-cart">%s</a>', $id, __('Delete', ACBWM_TEXT_DOMAIN));
                $return .= $this->row_actions($actions);
                break;
            case 'cart_total':
                $return = wc_price($item['cart_total']);
                break;
            case 'status':
                if ($item['is_recovered'] == '1') {
                    $return = '<p class="success">' . __("Recovered", ACBWM_TEXT_DOMAIN) . '</p>';
                } elseif (!empty($item['order_id'])) {
                    $return = '<p class="gray">' . __("Normal order", ACBWM_TEXT_DOMAIN) . '</p>';
                } elseif ($abandoned_at < current_time('mysql', true)) {
                    $return = '<p class="error-text">' . __("Abandoned", ACBWM_TEXT_DOMAIN) . '</p>';
                } else {
                    $return = '<p class="primary">' . __("Live", ACBWM_TEXT_DOMAIN) . '</p>';
                }
                break;
            case 'customer':
                if (!empty($item['user_name'])) {
                    $return = $item['user_name'];
                } else {
                    $return = $item['user_ip'];
                }
                break;
            case 'cart_id':
                $id = $item['cart_id'];
                $return = '#' . $id;
                break;
            case 'used_coupons':
                $cart = maybe_unserialize($item['user_cart']);
                $coupons = array();
                if (isset($cart['applied_coupons'])) {
                    $coupons = $cart['applied_coupons'];
                }
                $return = implode(', ', $coupons);
                break;
            case 'is_all_mails_sent':
            case 'recovery_link_clicked':
                $return = '<p class="error-text"><span class="dashicons dashicons-no"></span></p>';
                if ($item[$column_name] == '1') {
                    $return = '<p class="success"><span class="dashicons dashicons-yes-alt"></span></p>';
                }
                break;
            case "order_id":
                if (!empty($item['order_id'])) {
                    $order_id = $item['order_id'];
                    $return = '<a href="' . get_edit_post_link($order_id) . '" target="_blank">#' . $order_id . '</a>';
                } else {
                    $return = " -- ";
                }
                break;
            default:
                if (!empty($item[$column_name])) {
                    $return = $item[$column_name];
                } else {
                    $return = "--";
                }
                break;
        }
        return $return;
    }

    /**
     * cart actions
     * @return array
     */
    public function get_views()
    {
        $views = array();
        $current = sanitize_text_field(!empty($_REQUEST['user_type']) ? $_REQUEST['user_type'] : 'all');
        //All link
        $class = ($current == 'all' ? ' class="current"' : '');
        $all_url = remove_query_arg('user_type');
        $views['all'] = "<a href='{$all_url }' {$class} >" . __('All', ACBWM_TEXT_DOMAIN) . "</a>";
        //Opened link
        $foo_url = add_query_arg('user_type', 'registered');
        $class = ($current == 'registered' ? ' class="current"' : '');
        $views['recovered'] = "<a href='{$foo_url}' {$class} >" . __('Registered user\'s carts', ACBWM_TEXT_DOMAIN) . "</a>";
        //Clicked link
        $bar_url = add_query_arg('user_type', 'guest');
        $class = ($current == 'guest' ? ' class="current"' : '');
        $views['guest'] = "<a href='{$bar_url}' {$class} >" . __('Carts with customer details', ACBWM_TEXT_DOMAIN) . "</a>";
        // visitor link
        $bar_url = add_query_arg('user_type', 'visitor');
        $class = ($current == 'visitor' ? ' class="current"' : '');
        $views['visitor'] = "<a href='{$bar_url}' {$class} >" . __('Carts without customer details', ACBWM_TEXT_DOMAIN) . "</a>";
        return $views;
    }

    /**
     * Text displayed when no carts data is available
     */
    public function no_items()
    {
        _e('No carts available.', ACBWM_TEXT_DOMAIN);
    }
}