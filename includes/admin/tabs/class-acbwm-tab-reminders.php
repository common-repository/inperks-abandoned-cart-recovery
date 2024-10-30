<?php
defined('ABSPATH') || exit;

class Acbwm_Admin_Tab_Email_Templates extends WP_List_Table
{
    use Acbwm_Trait_Language;

    function __construct()
    {
        parent::__construct(array(
            'singular' => __('Email template', ACBWM_TEXT_DOMAIN),
            'plural' => __('Email templates', ACBWM_TEXT_DOMAIN),
            'ajax' => false
        ));
    }

    /**
     * all bulk actions
     * @return array
     */
    public function get_bulk_actions()
    {
        return array(
            'enable' => __('Enable', ACBWM_TEXT_DOMAIN),
            'disable' => __('Disable', ACBWM_TEXT_DOMAIN),
            'delete' => __('Delete', ACBWM_TEXT_DOMAIN)
        );
    }

    /**
     * bulk actions checkbox
     * @param object $item
     * @return string|void
     */
    public function column_cb($item)
    {
        return sprintf('<input type="checkbox" name="%1$s[]" value="%2$s" />', 'emails', $item['mail_id']);
    }

    /**
     * Process the bulk action
     */
    public function process_bulk_action()
    {
        $current_action = $this->current_action();
        switch ($current_action) {
            case "delete":
                if (isset($_POST['emails']) && !empty($_POST['emails'])) {
                    foreach ($_POST['emails'] as $email_id) {
                        $email_id = sanitize_key($email_id);
                        Acbwm_Admin_Reminders::delete_email_template_by_id($email_id);
                    }
                }
                Acbwm_Admin_Reminders::reschedule_ac_emails();
                break;
            case "enable":
                if (isset($_POST['emails']) && !empty($_POST['emails'])) {
                    foreach ($_POST['emails'] as $email_id) {
                        $email_id = sanitize_key($email_id);
                        Acbwm_Admin_Reminders::update_template_status($email_id, 1);
                    }
                }
                break;
            case "disable":
                if (isset($_POST['emails']) && !empty($_POST['emails'])) {
                    foreach ($_POST['emails'] as $email_id) {
                        $email_id = sanitize_key($email_id);
                        Acbwm_Admin_Reminders::update_template_status($email_id, 0);
                    }
                }
                break;
            default:
                break;
        }
    }

    /**
     * Items to be displayed
     */
    public function prepare_items()
    {
        $this->process_bulk_action();
        $per_page = $this->get_items_per_page('acbwm_email_templates_per_page', 20);
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;
        $search = sanitize_text_field(isset($_GET['s']) ? $_GET['s'] : '');
        $language = sanitize_text_field((!empty($_GET['language'])) ? $_GET['language'] : 'all');
        $total_items = Acbwm_Data_Store_Email::get_all_emails_count($search, $language);
        //$hidden = $this->get_hidden_columns();
        $this->_column_headers = $this->get_column_info();
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page
        ));
        $order_by = sanitize_text_field((!empty($_GET['orderby'])) ? $_GET['orderby'] : 'send_after');
        // If no order, default to asc
        $order = sanitize_text_field((!empty($_GET['order'])) ? $_GET['order'] : 'asc');
        $this->items = Acbwm_Data_Store_Email::get_all_emails($per_page, $offset, $order_by, $order, $search, $language);
    }

    /**
     * All colums
     * @return array
     */
    public function get_columns()
    {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'name' => __('Name', ACBWM_TEXT_DOMAIN),
            'reminder_type' => __('Type', ACBWM_TEXT_DOMAIN),
            'send_after' => __('Send After', ACBWM_TEXT_DOMAIN),
            'subject' => __('Subject', ACBWM_TEXT_DOMAIN),
            'status' => __('Status', ACBWM_TEXT_DOMAIN),
        );
        return apply_filters(ACBWM_HOOK_PREFIX . 'email_templates_list_get_columns', $columns);
    }

    /**
     * Set the default values for the column
     * @param object $email
     * @param string $column_name
     * @return int|string|void
     */
    public function column_default($email, $column_name)
    {
        $send_after = $email['send_after_time'];
        $send_after_type = $email['send_after_unit'];
        $email_id = $email['mail_id'];
        $total_emails_sent = Acbwm_Data_Store_Cart::get_total_sent_emails($email_id);
        $opened_emails = Acbwm_Data_Store_Cart::get_total_opened_emails($email_id);
        $clicked_emails = Acbwm_Data_Store_Cart::get_total_clicked_emails($email_id);
        switch ($column_name) {
            case 'opened':
                $response = $opened_emails;
                break;
            case 'open_rate':
                if ($total_emails_sent > 0) {
                    $open = ($opened_emails / $total_emails_sent) * 100;
                } else {
                    $open = 0;
                }
                $response = round($open, 2) . '%';
                break;
            case 'clicked':
                $response = $clicked_emails;
                break;
            case 'click_rate':
                if ($clicked_emails > 0) {
                    $click = ($clicked_emails / $total_emails_sent) * 100;
                } else {
                    $click = 0;
                }
                $response = round($click, 2) . '%';
                break;
            case 'status':
                $checked = ($email['status'] == 1) ? " checked" : "";
                $response = '<label class="switch">
                    <input name="status" type="checkbox" ' . $checked . ' class="acbwm-change-email-template-status" data-email="' . $email_id . '" value="1">
                    <span class="slider"></span>
                </label>';
                break;
            case 'subject':
                $response = $email['subject'];
                break;
            case 'reminder_type':
                $type = $email['reminder_type'];
                if ($type == "email") {
                    $response = "E-Mail Reminder";
                }
                break;
            case 'name':
                $response = $email['name'];
                $actions = array(
                    'edit' => sprintf('<a href="?page=%s&action=%s&tab=%s&email=%s">Edit</a>', sanitize_text_field($_REQUEST['page']), 'edit', 'edit-email-template', $email_id),
                    'delete' => sprintf('<a href="?page=%s&action=%s&tab=%s&email=%s">Delete</a>', sanitize_text_field($_REQUEST['page']), 'delete', 'delete-email-template', $email_id),
                );
                $response .= $this->row_actions($actions);
                break;
            case 'send_after':
                $response = $send_after . ' ' . $send_after_type;
                if ($send_after > 1) {
                    $response .= 's';
                }
                break;
            case 'sent':
                $response = $total_emails_sent;
                break;
            default:
                $response = print_r($email, true); //Show the whole array for troubleshooting purposes
                break;
        }
        return $response;
    }

    /**
     * Text displayed when no email templates data is available
     */
    public function no_items()
    {
        _e('No reminders available.', ACBWM_TEXT_DOMAIN);
    }

    /**
     * emails actions
     * @return array
     */
    public function get_views()
    {
        $available_languages = $this->get_all_available_languages();
        $views = array();
        $current = sanitize_text_field(!empty($_REQUEST['language']) ? $_REQUEST['language'] : 'all');
        //All link
        $class = ($current == 'all' ? ' class="current"' : '');
        $all_url = remove_query_arg('language');
        $views['all'] = "<a href='{$all_url }' {$class} >" . __('All', ACBWM_TEXT_DOMAIN) . "</a>";
        if (!empty($available_languages)) {
            foreach ($available_languages as $language_code => $language_title) {
                //Opened link
                $foo_url = add_query_arg('language', $language_code);
                $class = ($current == $language_code ? ' class="current"' : '');
                $views[$language_code] = "<a href='{$foo_url}' {$class} >" . $language_title . "</a>";
            }
        }
        return $views;
    }
}