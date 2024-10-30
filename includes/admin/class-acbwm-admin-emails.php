<?php
/**
 * Admin Emails
 *
 * Functions used for displaying ac emails in admin.
 *
 */
defined('ABSPATH') || exit;
if (class_exists('Acbwm_Admin_Reminders', false)) {
    return;
}

/**
 * Acbwm_Admin_Reminders Class.
 */
class Acbwm_Admin_Reminders
{
    use Acbwm_Trait_Language;

    public static $emails_obj;

    /**
     * Handles output of the emails page in admin.
     */
    public static function output()
    {
        include_once dirname(__FILE__) . '/views/html-admin-page-emails.php';
    }

    /**
     * turn of scheduler manually
     */
    public static function turn_off_scheduler_manually()
    {
        acbwm_clear_scheduled_hooks();
        update_option('acbwm_cron_turned_off_manually', 'yes');
        wp_safe_redirect(admin_url('admin.php?page=acbwm-settings'));
    }

    /**
     * turn on scheduler manually
     */
    public static function turn_on_scheduler_manually()
    {
        acbwm_clear_scheduled_hooks();
        acbwm_schedule_hooks();
        update_option('acbwm_cron_turned_off_manually', 'no');
        wp_safe_redirect(admin_url('admin.php?page=acbwm-settings'));
    }

    /**
     * get template html
     * @return false|string
     */
    public static function get_email_template_html()
    {
        $body = stripslashes(isset($_POST['template']) ? $_POST['template'] : '');
        if (!empty($body)) {
            $email_heading = sanitize_text_field(isset($_POST['heading']) ? $_POST['heading'] : '');
            $cart_data = self::get_sample_email_object();
            WC_Emails::instance();
            $email = new Acbwm_Email_Recover_abandoned_cart(array());
            ob_start();
            include_once ACBWM_ABSPATH . 'templates/emails/customer-acbwm-recover-ac-default.php';
            $content = ob_get_clean();
            $body = $email->style_inline($content);
        }
        return $body;
    }

    /**
     * send the test email
     */
    public static function send_test_email()
    {
        $user_email = sanitize_email(isset($_POST['email']) ? $_POST['email'] : '');
        if (!empty($user_email)) {
            $html = self::get_email_template_html();
            wc_mail($user_email, 'This is preview email', $html);
            wp_send_json_success('Preview system sent\'s email with sample data', ACBWM_TEXT_DOMAIN);
        } else {
            wp_send_json_error('invalid data', ACBWM_TEXT_DOMAIN);
        }
    }

    /**
     * view preview email
     */
    public static function view_email_preview()
    {
        $html = "<div class='acbwm'><h3>" . __('Template preview', ACBWM_TEXT_DOMAIN) . "</h3>";
        $html .= self::get_email_template_html();
        $html .= "<div class='acbwm'>";
        wp_send_json_success($html);
    }

    /**
     * sample email obj
     */
    public static function get_sample_email_object()
    {
        global $inperks_abandoned_carts;
        $obj = new stdClass();
        $prefix = $inperks_abandoned_carts->settings->get('coupon_code_prefix', 'WMC');
        if (empty($prefix)) {
            $prefix = 'wmc';
        }
        $coupon_code = strtoupper($prefix . '-' . uniqid());
        $items = array(
            array(
                'image' => '<img src="' . acbwm_get_assets_url('images/cap.jpg') . '" alt="sample product" width="50" height="50" />',
                'title' => "Sample product 1",
                'sku' => "sp1",
                'quantity' => 1,
                'total' => 300,
                'extra' => array(),
                'raw' => ''
            ), array(
                'image' => '<img src="' . acbwm_get_assets_url('images/cap1.jpg') . '" alt="sample product" width="50" height="50" />',
                'title' => "Sample product 2",
                'sku' => "sp1",
                'quantity' => 1,
                'total' => 200,
                'extra' => array(),
                'raw' => ''
            )
        );
        $obj->coupon_code = $coupon_code;
        $obj->expire_time = date('Y-m-d H:i:s', (current_time('timestamp') + 36000));
        $obj->discount = "30%";
        $obj->items = $items;
        $obj->currency = '';
        $totals = array(
            array(
                'label' => __('Subtotal', ACBWM_TEXT_DOMAIN),
                'value' => 500,
            ), array(
                'label' => __('Discount', ACBWM_TEXT_DOMAIN),
                'value' => 50,
            ), array(
                'label' => __('Shipping', ACBWM_TEXT_DOMAIN),
                'value' => 10,
            ), array(
                'label' => __('Fees', ACBWM_TEXT_DOMAIN),
                'value' => 10,
            ),
            array(
                'label' => __('Total', ACBWM_TEXT_DOMAIN),
                'value' => 470
            )
        );
        $totals = apply_filters(ACBWM_HOOK_PREFIX . 'get_cart_items_total', $totals, array());
        $obj->totals = $totals;
        $obj->recovery_url = "#";
        $obj->discount_url = "#";
        $obj->tracking_image = "";
        $obj->customer = "John Miller";
        $obj->use_woocommerce_style = sanitize_key(isset($_POST['use_woocommerce_style']) ? $_POST['use_woocommerce_style'] : '1');
        return $obj;
    }

    /**
     * Add screen options
     */
    public static function search_coupons()
    {
        wp_verify_nonce(ACBWM_HOOK_PREFIX . 'search_coupons', 'security');
        $response = array();
        $coupon_code = sanitize_text_field(isset($_POST['coupon']) ? $_POST['coupon'] : '');
        if (!empty($coupon_code)) {
            $args = array(
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'asc',
                's' => $coupon_code,
                'post_type' => 'shop_coupon',
            );
            $coupons = get_posts($args);
            if (!empty($coupons)) {
                foreach ($coupons as $coupon) {
                    $response[] = array('text' => $coupon->post_title, 'id' => $coupon->post_title);
                }
            }
        }
        wp_send_json_success($response);
    }

    /**
     * Add screen options
     */
    public static function emails_menu_add_option()
    {
        $tab = sanitize_text_field(isset($_GET['tab']) ? $_GET['tab'] : 'reminders-list');
        if ($tab == "sent-reminders") {
            $option = 'per_page';
            $args = array(
                'default' => 20,
                'option' => 'acbwm_sent_emails_per_page'
            );
            add_screen_option($option, $args);
        }
        if ($tab == "reminders-list") {
            $option = 'per_page';
            $args = array(
                'default' => 20,
                'option' => 'acbwm_email_templates_per_page'
            );
            add_screen_option($option, $args);
            include_once dirname(__FILE__) . '/tabs/class-acbwm-tab-reminders.php';
            self::$emails_obj = new Acbwm_Admin_Tab_Email_Templates();
        }
    }

    /**
     * Handles output of the emails list tab in admin.
     */
    public static function tab_template_lists()
    {
        include_once dirname(__FILE__) . '/tabs/class-acbwm-tab-reminders.php';
        $email_templates = new Acbwm_Admin_Tab_Email_Templates();
        $page_url = admin_url('admin.php?page=acbwm-reminders');
        $email_templates->prepare_items();
        $page = sanitize_text_field(isset($_GET['page']) ? $_GET['page'] : '');
        $paged = sanitize_key(isset($_GET['paged']) ? $_GET['paged'] : '1');
        $language = sanitize_text_field(isset($_GET['language']) ? $_GET['language'] : acbwm_get_default_language());
        ?>
        <h3><?php esc_attr_e('Reminders', ACBWM_TEXT_DOMAIN) ?>
            <a class="add-new-h2"
               href="<?php echo add_query_arg(array('tab' => 'add-new-email-reminder', 'language' => $language), $page_url) ?>"><?php esc_attr_e('Add New Email Reminder', ACBWM_TEXT_DOMAIN) ?></a>
            <?php
            do_action(ACBWM_HOOK_PREFIX . 'remainders_extra_link');
            ?>
        </h3>
        <?php
        $email_templates->views();
        ?>
        <form method="get">
            <input type="hidden" name="page" value="<?php echo $page; ?>"/>
            <input type="hidden" name="paged" value="<?php echo $paged; ?>"/>
            <?php
            $email_templates->search_box('Search', 'name');
            ?>
        </form>
        <form method="post">
            <input type="hidden" name="page" value="<?php echo $page; ?>"/>
            <?php
            $email_templates->display();
            ?>
        </form>
        <?php
    }

    /**
     * Handles output of the create new email tab in admin.
     */
    public static function tab_add_new_template()
    {
        $action = 'create';
        $id = '';
        $email = array();
        $available_languages = self::get_all_available_languages();
        include_once dirname(__FILE__) . '/views/tabs/html-admin-tab-emails-edit-template.php';
    }

    /**
     * Handles output of the sent tab in admin.
     */
    public static function tab_sent_mails_lists()
    {
        include_once dirname(__FILE__) . '/tabs/class-acbwm-tab-sent-reminders.php';
        $sent_emails = new Acbwm_Admin_Tab_Sent_Emails();
        $sent_emails->prepare_items();
        $page_no = sanitize_key(isset($_GET['paged']) ? $_GET['paged'] : 1);
        $page = sanitize_text_field(isset($_GET['page']) ? $_GET['page'] : '');
        ?>
        <h3><?php esc_attr_e('Sent reminders', ACBWM_TEXT_DOMAIN) ?></h3>
        <form method="get">
            <?php
            $sent_emails->search_box('Search', 'email');
            ?>
            <input type="hidden" name="page" value="acbwm-reminders"/>
            <input type="hidden" name="paged" value="<?php echo $page_no; ?>"/>
            <input type="hidden" name="tab" value="sent-reminders"/>
        </form>
        <?php
        $sent_emails->views();
        ?>
        <form method="post">
            <input type="hidden" name="page" value="<?php echo $page; ?>"/>
            <?php
            $sent_emails->display();
            ?>
        </form>
        <?php
    }

    /**
     * Handles output of the edit email tab in admin.
     */
    public static function tab_edit_template()
    {
        $action = 'edit';
        $id = sanitize_key(isset($_GET['email']) ? $_GET['email'] : '');
        $email = Acbwm_Data_Store_Email::get_template_by_id($id, ARRAY_A);
        if (!empty($id) && !empty($email)) {
            $available_languages = self::get_all_available_languages();
            include_once dirname(__FILE__) . '/views/tabs/html-admin-tab-emails-edit-template.php';
        } else {
            esc_html_e('No template found!', ACBWM_TEXT_DOMAIN);
        }
    }

    /**
     * Handles ajax save template
     */
    public static function save_new_template()
    {
        wp_verify_nonce(ACBWM_HOOK_PREFIX . 'create_new_email_template', 'security');
        $send_after = sanitize_key(isset($_POST['send_after_time']) ? $_POST['send_after_time'] : 1);
        $send_after_type = sanitize_text_field(isset($_POST['send_after_unit']) ? $_POST['send_after_unit'] : 'hour');
        $default_language = acbwm_get_default_language();
        $language = sanitize_text_field(isset($_POST['language']) ? $_POST['language'] : $default_language);
        $time = acbwm_get_timestamp($send_after, $send_after_type);
        $templates_by_time = Acbwm_Data_Store_Email::get_template_by_time($time, $language);
        if (!empty($templates_by_time)) {
            wp_send_json_error(esc_html__('Email template already found for same send time', ACBWM_TEXT_DOMAIN));
        } else {
            $edit_url = self::save_template($send_after, $time, $send_after_type, $language, 'new');
            wp_send_json_success(array('edit_url' => $edit_url, 'message' => esc_html__('Email template created successfully!', ACBWM_TEXT_DOMAIN)));
        }
    }

    /**
     * Handles ajax save template
     */
    public static function delete_email_template()
    {
        $action = sanitize_text_field(isset($_GET['action']) ? $_GET['action'] : "");
        if ($action == "delete") {
            $email = sanitize_key(isset($_GET['email']) ? $_GET['email'] : '0');
            $template_deleted = self::delete_email_template_by_id($email);
            if (!$template_deleted) {
                esc_html__('Email template not deleted properly. Please try again!', ACBWM_TEXT_DOMAIN);
            } else {
                self::reschedule_ac_emails();
            }
        }
        $page_url = admin_url('admin.php?page=acbwm-reminders');
        wp_safe_redirect($page_url);
    }

    /**
     * delete the email template by id
     * @param $email
     * @return false|int
     */
    public static function delete_email_template_by_id($email)
    {
        return $template_deleted = Acbwm_Data_Store_Email::delete_template($email);
    }

    /**
     * reschedule all emails
     */
    public static function reschedule_ac_emails()
    {
        $all_cart_tokens = Acbwm_Data_Store_Cart::get_all_carts_to_re_schedule();
        if (!empty($all_cart_tokens)) {
            foreach ($all_cart_tokens as $cart_token) {
                $cart = Acbwm_Carts::get_cart_by_cart_token($cart_token->cart_token);
                $cart->schedule_next_email();
            }
        }
    }

    /**
     * Handles ajax save edited template
     */
    public static function save_edited_template()
    {
        wp_verify_nonce(ACBWM_HOOK_PREFIX . 'edit_email_template', 'security');
        $send_after = sanitize_key(isset($_POST['send_after_time']) ? $_POST['send_after_time'] : 1);
        $send_after_type = sanitize_text_field(isset($_POST['send_after_unit']) ? $_POST['send_after_unit'] : 'hour');
        $mail_id = sanitize_key(isset($_POST['id']) ? $_POST['id'] : '0');
        $time = acbwm_get_timestamp($send_after, $send_after_type);
        $default_language = acbwm_get_default_language();
        $language = sanitize_text_field(isset($_POST['language']) ? $_POST['language'] : $default_language);
        $templates_by_time = Acbwm_Data_Store_Email::get_template_by_time($time, $language);
        $already_template_found = false;
        if (!empty($templates_by_time)) {
            foreach ($templates_by_time as $template) {
                if ($template->mail_id != $mail_id) {
                    $already_template_found = true;
                    break;
                }
            }
        }
        if ($already_template_found) {
            wp_send_json_error(esc_html__('Email template already found for same send time', ACBWM_TEXT_DOMAIN));
        }
        $edit_url = self::save_template($send_after, $time, $send_after_type, $language, 'edit');
        wp_send_json_success(array('edit_url' => $edit_url, 'message' => esc_html__('Email template created successfully!', ACBWM_TEXT_DOMAIN)));
    }

    /**
     * Handles ajax save edited template
     */
    public static function change_template_status()
    {
        $email = sanitize_key(isset($_POST['email']) ? $_POST['email'] : 0);
        $status = sanitize_key(isset($_POST['status']) ? $_POST['status'] : 0);
        if (self::update_template_status($email, $status)) {
            self::reschedule_ac_emails();
            wp_send_json_success(esc_html__('SUCCESS: Email status changed successfully!', ACBWM_TEXT_DOMAIN));
        } else {
            wp_send_json_success(esc_html__('ERROR: Unable to change the status of email template!', ACBWM_TEXT_DOMAIN));
        }
    }

    /**
     * update the status of the status
     * @param $email
     * @param $status
     * @return false|int
     */
    public static function update_template_status($email, $status)
    {
        return Acbwm_Data_Store_Email::update_template_column($email, 'status', $status);
    }

    /**
     * save the template
     * @param $send_after
     * @param $time
     * @param $send_after_type
     * @param $language
     * @param string $email_type
     * @return string
     */
    public static function save_template($send_after, $time, $send_after_type, $language, $email_type = 'new')
    {
        $email_body = isset($_POST['email_body']) ? $_POST['email_body'] : '';
        $template = array(
            'send_after_time' => $send_after,
            'send_after' => $time,
            'send_after_unit' => $send_after_type,
            'language' => $language,
            'status' => sanitize_key(isset($_POST['status']) ? $_POST['status'] : 0),
            'name' => sanitize_text_field(isset($_POST['name']) ? $_POST['name'] : ''),
            'subject' => sanitize_text_field(isset($_POST['subject']) ? $_POST['subject'] : ''),
            'heading' => sanitize_text_field(isset($_POST['heading']) ? $_POST['heading'] : ''),
            'use_woocommerce_style' => sanitize_text_field(isset($_POST['use_woocommerce_style']) ? $_POST['use_woocommerce_style'] : '1'),
            'email_body' => base64_encode(stripslashes($email_body)),
            'details' => maybe_serialize(isset($_POST['coupon']) ? $_POST['coupon'] : array()),
            'reminder_type' => 'email'
        );
        $type = array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');
        switch ($email_type) {
            case "edit":
                $mail_id = sanitize_key(isset($_POST['id']) ? $_POST['id'] : '0');
                Acbwm_Data_Store_Email::save_edited_template($template, array('mail_id' => $mail_id), $type);
                break;
            default:
            case "new":
                $mail_id = Acbwm_Data_Store_Email::save_new_template($template, $type);
                break;
        }
        self::reschedule_ac_emails();
        $page_url = admin_url('admin.php?page=acbwm-reminders');
        return add_query_arg(array('tab' => 'edit-email-template', 'action' => 'edit', 'email' => $mail_id), $page_url);
    }
}
