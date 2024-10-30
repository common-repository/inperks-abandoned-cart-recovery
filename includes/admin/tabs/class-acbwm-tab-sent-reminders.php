<?php
defined('ABSPATH') || exit;

class Acbwm_Admin_Tab_Sent_Emails extends WP_List_Table
{
    function __construct()
    {
        parent::__construct(array(
            'singular' => __('Sent mail', ACBWM_TEXT_DOMAIN),
            'plural' => __('Sent E-Mails', ACBWM_TEXT_DOMAIN),
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
            'mark_as_opened' => __('Mark as opened', ACBWM_TEXT_DOMAIN),
            'mark_as_clicked' => __('Mark as clicked', ACBWM_TEXT_DOMAIN),
            'mark_as_un_opened' => __('Mark as un-open', ACBWM_TEXT_DOMAIN),
            'mark_as_un_clicked' => __('Mark as un-click', ACBWM_TEXT_DOMAIN),
        );
    }

    /**
     * bulk actions checkbox
     * @param object $item
     * @return string|void
     */
    public function column_cb($item)
    {
        return sprintf('<input type="checkbox" name="%1$s[]" value="%2$s" />', 'ids', $item['id']);
    }

    /**
     * Process the bulk action
     */
    public function process_bulk_action()
    {
        $current_action = $this->current_action();
        switch ($current_action) {
            case "mark_as_opened":
                if (isset($_POST['ids']) && !empty($_POST['ids'])) {
                    foreach ($_POST['ids'] as $id) {
                        $id = sanitize_key($id);
                        Acbwm_Data_Store_Cart::update_sent_email($id, 'is_opened', '1');
                    }
                }
                break;
            case "mark_as_clicked":
                if (isset($_POST['ids']) && !empty($_POST['ids'])) {
                    foreach ($_POST['ids'] as $id) {
                        $id = sanitize_key($id);
                        Acbwm_Data_Store_Cart::update_sent_email($id, 'is_clicked', '1');
                    }
                }
                break;
            case "mark_as_un_opened":
                if (isset($_POST['ids']) && !empty($_POST['ids'])) {
                    foreach ($_POST['ids'] as $id) {
                        $id = sanitize_key($id);
                        Acbwm_Data_Store_Cart::update_sent_email($id, 'is_opened', '0');
                    }
                }
                break;
            case "mark_as_un_clicked":
                if (isset($_POST['ids']) && !empty($_POST['ids'])) {
                    foreach ($_POST['ids'] as $id) {
                        $id = sanitize_key($id);
                        Acbwm_Data_Store_Cart::update_sent_email($id, 'is_clicked', '0');
                    }
                }
                break;
            default:
                break;
        }
    }

    /**
     * prepare items
     */
    public function prepare_items()
    {
        /** Process bulk action */
        $this->process_bulk_action();
        $columns = $this->get_columns();
        $per_page = $this->get_items_per_page('acbwm_sent_emails_per_page', 20);
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;
        $search = sanitize_text_field(isset($_GET['s']) ? $_GET['s'] : '');
        $mail_type = sanitize_text_field(isset($_GET['mail_type']) ? $_GET['mail_type'] : 'all');
        $total_items = Acbwm_Data_Store_Cart::get_all_sent_emails_count($search, $mail_type);
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page
        ));
        $order_by = sanitize_text_field((!empty($_GET['orderby'])) ? $_GET['orderby'] : 'id');
        // If no order, default to asc
        $order = sanitize_text_field((!empty($_GET['order'])) ? $_GET['order'] : 'desc');
        $this->items = Acbwm_Data_Store_Cart::get_all_sent_emails($per_page, $offset, $order_by, $order, $search, $mail_type);
    }

    /**
     * get columns
     * @return array
     */
    public function get_columns()
    {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'id' => __('ID', ACBWM_TEXT_DOMAIN),
            'email' => __('User email', ACBWM_TEXT_DOMAIN),
            'subject' => __('Subject', ACBWM_TEXT_DOMAIN),
            'send_after' => __('Send after', ACBWM_TEXT_DOMAIN),
            'created_at' => __('Sent At', ACBWM_TEXT_DOMAIN),
        );
        return apply_filters(ACBWM_HOOK_PREFIX . 'sent_emails_list_get_columns', $columns);
    }

    /**
     * sortable columns
     * @return array
     */
    public function get_sortable_columns()
    {
        return array(
            'id' => array('id', false),
            'email' => array('email', false),
            'created_at' => array('created_at', false),
        );
    }

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'created_at':
                $return = get_date_from_gmt($item['created_at'], 'Y-m-d H:i:s');
                break;
            case 'id':
                $return = '#' . $item['id'];
                break;
            case 'send_after':
                $mail_id = $item['mail_id'];
                $mail = Acbwm_Data_Store_Email::get_template_by_id($mail_id);
                if (!empty($mail)) {
                    $send_after = $mail->send_after_time;
                    $send_after_type = $mail->send_after_unit;
                    $return = "{$send_after} {$send_after_type}";
                    if ($send_after > 1) {
                        $return .= 's';
                    }
                } else {
                    $return = '-';
                }
                break;
            case 'is_clicked':
            case 'is_opened':
                $return = '<p class="error-text"><span class="dashicons dashicons-no"></span></p>';
                if ($item[$column_name] == '1') {
                    $return = '<p class="success"><span class="dashicons dashicons-yes-alt"></span></p>';
                }
                break;
            default:
                $return = $item[$column_name];
                break;
        }
        return $return;
    }

    public function get_views()
    {
        $views = array();
        $current = sanitize_text_field(!empty($_REQUEST['mail_type']) ? $_REQUEST['mail_type'] : 'all');
        //All link
        $class = ($current == 'all' ? ' class="current"' : '');
        $all_url = remove_query_arg('mail_type');
        $views['all'] = "<a href='{$all_url }' {$class} >" . __('All', ACBWM_TEXT_DOMAIN) . "</a>";
        return apply_filters(ACBWM_HOOK_PREFIX . 'email_templates_list_get_views', $views, $current);
    }

    /**
     * Text displayed when no email logs data is available
     */
    public function no_items()
    {
        _e('No reminders log available.', ACBWM_TEXT_DOMAIN);
    }
}