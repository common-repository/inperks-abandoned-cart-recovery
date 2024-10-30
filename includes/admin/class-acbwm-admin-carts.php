<?php
/**
 * Admin carts
 *
 * Functions used for displaying ac in admin.
 *
 */
defined('ABSPATH') || exit;
if (class_exists('Acbwm_Admin_Carts', false)) {
    return;
}

/**
 * Acbwm_Admin_Carts Class.
 */
class Acbwm_Admin_Carts
{
    /**
     * @var Acbwm_Admin_Tab_Carts
     */
    public static $cart_obj;

    /**
     * Handles output of the settings page in admin.
     */
    public static function view_cart()
    {
        $cart_token = sanitize_text_field(isset($_GET['cart']) ? $_GET['cart'] : '');
        if (!empty($cart_token)) {
            $cart = Acbwm_Carts::get_cart_by_cart_token($cart_token);
            if (!empty($cart)) {
                echo '<div class="acbwm">';
                echo '<h3>' . __('Cart details', ACBWM_TEXT_DOMAIN) . '</h3>';
                $cart_data = $cart->create_cart_items_object('0', '');
                echo '<div class="cart-items">';
                include_once dirname(ACBWM_PLUGIN_FILE) . '/templates/common/cart-items.php';
                echo '</div>';
                do_action(ACBWM_HOOK_PREFIX . 'admin_view_cart', $cart);
                wp_die();
            } else {
                wp_die(__("Cart not found", ACBWM_TEXT_DOMAIN));
            }
        } else {
            wp_die(__("Cart not found", ACBWM_TEXT_DOMAIN));
        }
        include_once dirname(__FILE__) . '/views/html-admin-page-carts.php';
    }

    /**
     * Handles output of the settings page in admin.
     */
    public static function send_email()
    {
        $cart_token = sanitize_text_field(isset($_GET['cart']) ? $_GET['cart'] : '');
        if (!empty($cart_token)) {
            $cart = Acbwm_Carts::get_cart_by_cart_token($cart_token);
            if (!empty($cart)) {
                echo '<div class="acbwm">';
                $language = $cart->get_language();
                $all_mail_templates = Acbwm_Data_Store_Email::get_all_emails_by_language($language);
                include_once dirname(ACBWM_PLUGIN_FILE) . '/includes/admin/views/tabs/html-admin-tab-emails-send-emails-manually.php';
                echo '</div>';
                die;
            } else {
                wp_die(__("Cart not found", ACBWM_TEXT_DOMAIN));
            }
        } else {
            wp_die(__("Cart not found", ACBWM_TEXT_DOMAIN));
        }
    }

    /**
     * Handles ajax save template
     */
    public static function send_emails_manually()
    {
        wp_verify_nonce(ACBWM_HOOK_PREFIX . 'send_emails_manually', 'security');
        $cart_token = sanitize_key(isset($_POST['cart']) ? $_POST['cart'] : 0);
        $mail_id = sanitize_key(isset($_POST['mail_id']) ? $_POST['mail_id'] : 0);
        $mail = Acbwm_Data_Store_Email::get_template_by_id($mail_id);
        $cart = Acbwm_Carts::get_cart_by_cart_token($cart_token);
        if (!empty($mail) && !empty($cart)) {
            if (is_user_logged_in()) {
                $user = wp_get_current_user();
                $action = "Mail sent manually by '{$user->display_name}'";
            } else {
                $action = "Mail sent manually";
            }
            new WC_Emails();
            $cart->update_history($action, 'reminders', array(), true);
            $subject = $mail->subject;
            $replaced_subject = str_replace('{customer}', $cart->get_user_name(), $subject);
            $email = $cart->get_user_email();
            $email_id = $cart->set_sent_email($mail_id, $replaced_subject, $email);
            do_action('send_scheduled_' . $mail_id, $cart, $email_id, $mail);
            wp_send_json_success('Mail sent successfully!', ACBWM_TEXT_DOMAIN);
        } else {
            wp_send_json_error('Invalid request!', ACBWM_TEXT_DOMAIN);
        }
    }

    /**
     * Handles output of the settings page in admin.
     */
    public static function delete_cart()
    {
        $cart_token = sanitize_text_field(isset($_POST['cart']) ? $_POST['cart'] : null);
        self::delete_cart_by_token($cart_token);
        wp_send_json_success(__('Cart deleted successfully!', ACBWM_TEXT_DOMAIN));
    }

    /**
     * delete the carts
     * @param $cart_token
     */
    public static function delete_cart_by_token($cart_token)
    {
        $cart = Acbwm_Carts::get_cart_by_cart_token($cart_token);
        $cart->get_cart_id();
        $cart->delete_cart();
    }

    public static function output()
    {
        include_once dirname(__FILE__) . '/views/html-admin-page-carts.php';
    }

    /**
     * Add screen options
     */
    public static function carts_menu_add_option()
    {
        $option = 'per_page';
        $args = array(
            'default' => 20,
            'option' => 'acbwm_ac_per_page'
        );
        add_screen_option($option, $args);
        include_once dirname(__FILE__) . '/tabs/class-acbwm-tab-cart-lists.php';
        self::$cart_obj = new Acbwm_Admin_Tab_Carts();
    }

    /**
     * Handles output of the settings page in admin.
     */
    public static function display_carts_lists()
    {
        $carts_lists = self::$cart_obj;
        $carts_lists->prepare_items();
        $carts_lists->views();
        $page = sanitize_text_field(isset($_GET['page']) ? $_GET['page'] : '');
        $paged = sanitize_key(isset($_GET['paged']) ? $_GET['paged'] : '1');
        ?>
        <form method="get">
            <input type="hidden" name="page" value="<?php echo $page; ?>"/>
            <input type="hidden" name="paged" value="<?php echo $paged; ?>"/>
            <?php
            $carts_lists->search_box('Search', 'email');
            ?>
        </form>
        <form method="post">
            <input type="hidden" name="page" value="<?php echo $page; ?>"/>
            <?php
            $carts_lists->display();
            ?>
        </form>
        <?php
    }
}
