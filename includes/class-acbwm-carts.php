<?php
/**
 * Acbwm setup
 *
 * @package Acbwm
 */
defined('ABSPATH') || exit;

class Acbwm_Carts
{
    use Acbwm_Trait_Cookie, Acbwm_Trait_Currency;

    const CART_TOKEN = "_acbwm_cart_token";

    /**
     * include the GDPR compliance
     */
    public static function include_gdpr_compliance()
    {
        $settings = Acbwm_Gdpr_Compliance_Customizer::get_settings();
        if ($settings['enable_compliance'] == "yes" && !self::is_cart_tacking_accepted()) {
            $mappings = Acbwm_Gdpr_Compliance_Customizer::style_mappings();
            include_once ACBWM_ABSPATH . 'templates/common/gdpr-compliance.php';
        }
    }

    /**
     * check is cart tracking accepted or not
     * @return bool
     */
    public static function is_cart_tacking_accepted()
    {
        $gdpr = Acbwm_Gdpr_Compliance_Customizer::get_setting('enable_compliance', 'no');
        if (is_user_logged_in() || $gdpr == "no") {
            return true;
        }
        $is_accepted = self::get_cookie_value('acbwm_is_cart_tracking_accepted', 'no');
        return ($is_accepted == "yes");
    }

    /**
     * cart tracking accepted or not
     */
    public static function set_cart_tacking_accepted()
    {
        self::set_cookie_value('acbwm_is_cart_tracking_accepted', 'yes');
    }

    /**
     * update the cart
     * @param $cart_item_key
     * @param $product_id
     * @param $quantity
     * @param $variation_id
     * @param $variation
     * @param $cart_item_data
     * @return bool
     */
    public static function item_added_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data)
    {
        $cart_item = WC()->cart->get_cart_item($cart_item_key);
        $product = null;
        if (isset($cart_item['data'])) {
            $product = $cart_item['data'];
        }
        if ($product instanceof WC_Product) {
            $id = (!empty($variation_id)) ? $variation_id : $product_id;
            $cart = self::get_cart();
            $product_title = $product->get_title();
            $wc_cart_obj = self::get_cart_details();
            $cart->update_cart($wc_cart_obj);
            $action = "Product \"{$product_title}(#{$id})\" of quantity {$quantity} has added to cart";
            $cart->update_history($action, 'cart_updated', array('id' => $id, 'quantity' => $quantity));
        }
        return true;
    }

    /**
     * get the cart
     * @return Acbwm_Data_Store_Cart
     */
    public static function get_cart()
    {
        $cart_token = self::get_cart_token();
        if (empty($cart_token)) {
            $cart_token = acbwm_generate_uuid();
            self::set_cart_token($cart_token);
        }
        return self::get_cart_by_cart_token($cart_token);
    }

    /**
     * get the cart token
     *
     * if user logged in , then get the cart token from user meta,
     * otherwise get the token from cookie
     * @return mixed|void
     */
    public static function get_cart_token()
    {
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $cart_token = get_user_meta($user_id, self::CART_TOKEN, true);
        } else {
            $cart_token = self::get_cookie_value(self::CART_TOKEN, null);
        }
        return apply_filters(ACBWM_HOOK_PREFIX . 'get_cart_token', $cart_token, __class__);
    }

    /**
     * set the cart token
     *
     * set cart token in user meta if user logged in
     * otherwise update it in cookies
     * @param $cart_token
     */
    public static function set_cart_token($cart_token)
    {
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            update_user_meta($user_id, self::CART_TOKEN, $cart_token);
        } else {
            self::set_cookie_value(self::CART_TOKEN, $cart_token);
        }
    }

    /**
     * get the cart data store
     * @param $cart_token
     * @return Acbwm_Data_Store_Cart
     */
    public static function get_cart_by_cart_token($cart_token)
    {
        return new Acbwm_Data_Store_Cart($cart_token);
    }

    /**
     * Get the cart details
     * @return mixed|void
     */
    public static function get_cart_details()
    {
        $cart = array(
            'cart' => WC()->session->get('cart', array()),
            'cart_totals' => WC()->session->get('cart_totals', array()),
            'applied_coupons' => WC()->session->get('applied_coupons', array()),
            'customer' => WC()->session->get('customer', array()),
            'chosen_shipping_methods' => WC()->session->get('chosen_shipping_methods', array()),
        );
        return apply_filters(ACBWM_HOOK_PREFIX . 'get_cart_details', $cart);
    }

    /**
     * send the abandoned cart emails
     */
    public static function send_abandoned_cart_mails()
    {
        $abandoned_cart_tokens = Acbwm_Data_Store_Cart::get_cart_tokens_to_send_emails();
        if (!empty($abandoned_cart_tokens)) {
            new WC_Emails();
            $processed_carts = array();
            foreach ($abandoned_cart_tokens as $abandoned_cart_token) {
                $cart_token = $abandoned_cart_token->cart_token;
                $cart = self::get_cart_by_cart_token($cart_token);
                $scheduled_mail_id = $cart->get_mail_id();
                $scheduled_time = $cart->get_scheduled_after();
                $mail = Acbwm_Data_Store_Email::get_template_by_id($scheduled_mail_id);
                if (!empty($mail) && $mail->status == 1) {
                    $time_sent_after = acbwm_convert_seconds($scheduled_time);
                    $action = "Mail sent after \"{$time_sent_after}\" of cart abandonment";
                    $cart->update_history($action, 'mails');
                    $subject = $mail->subject;
                    $replaced_subject = str_replace('{{customer.name}}', $cart->get_user_name(), $subject);
                    $email = $cart->get_user_email();
                    $email_id = $cart->set_sent_email($scheduled_mail_id, $replaced_subject, $email);
                    $cart->update_column('previously_scheduled', $scheduled_time, true);
                    do_action('send_scheduled_' . $scheduled_mail_id, $cart, $email_id, $mail);
                    $processed_carts[] = array(
                        'email' => $email_id,
                        'mail_log_id' => $email_id,
                        'cart_token' => $cart_token
                    );
                }
                $cart->schedule_next_email();
            }
            wp_send_json_success($processed_carts);
        } else {
            wp_send_json_error(esc_html__('No carts left to process!', ACBWM_TEXT_DOMAIN));
        }
    }

    /**
     * Remove Item from the cart
     * @param $cart_item_key
     * @param $wc_cart WC_Cart
     * @return bool
     */
    public static function item_removed_from_cart($cart_item_key, $wc_cart)
    {
        $cart_item = $wc_cart->get_cart_item($cart_item_key);
        $product = null;
        if (isset($cart_item['data'])) {
            $product = $cart_item['data'];
        }
        if (!empty($product) && $product instanceof WC_Product) {
            $cart = self::get_cart();
            $product_title = $product->get_title();
            $id = $product->get_id();
            $wc_cart_obj = self::get_cart_details();
            $cart->update_cart($wc_cart_obj);
            $action = "Product \"{$product_title}(#{$id})\" has removed from cart";
            $cart->update_history($action, 'cart_updated', array('id' => $id));
        }
        return true;
    }

    /**
     * Empty the cart
     * @param $clear_persistent_cart
     * @return bool
     */
    public static function cart_emptied($clear_persistent_cart)
    {
        $cart = self::get_cart();
        $cart->update_cart(array());
        $action = "Cart emptied";
        $cart->update_history($action, 'cart_updated', array());
        return true;
    }

    /**
     * User login to user
     * @param $login_name string
     * @return bool
     */
    public static function user_login($login_name)
    {
        if (!empty($login_name)) {
            $user = get_user_by('login', $login_name);
            if (!empty($user)) {
                self::user_register($user->ID);
            } else {
                $user = get_user_by('email', $login_name);
                if ($user) {
                    self::user_register($user->ID);
                }
            }
        }
        return true;
    }

    /**
     * register the user
     * @param $user_id
     * @return bool
     */
    public static function user_register($user_id)
    {
        $cart_token = self::get_cookie_value(self::CART_TOKEN, null);
        if (!empty($cart_token)) {
            update_user_meta($user_id, self::CART_TOKEN, $cart_token);
        }
        $cart = self::get_cart();
        $cart->update_user_id($user_id);
        $user = get_user_by('ID', $user_id);
        $user_email = $user->user_email;
        $cart->update_user_email($user_email);
        $display_name = $user->display_name;
        $action = "Customer \"{$display_name}({$user_email})\" has registered/Logged in";
        $cart->update_history($action, 'user_action', array());
        return true;
    }

    /**
     * Apply coupon code
     * @param $coupon_code
     * @return bool
     */
    public static function coupon_applied($coupon_code)
    {
        $cart = self::get_cart();
        $action = "Coupon code\"{$coupon_code}\" has applied to cart";
        $cart->update_history($action, 'coupon', array('code' => $coupon_code));
        return true;
    }

    /**
     * set the cart totals details
     * @param $wc_cart WC_Cart
     */
    public static function after_calculate_totals($wc_cart)
    {
        $cart = self::get_cart();
        $sub_total = floatval($wc_cart->get_subtotal() + $wc_cart->get_shipping_tax());
        if (wc_prices_include_tax()) {
            $total = floatval($wc_cart->get_cart_contents_total() + $wc_cart->get_cart_contents_tax());
        } else {
            $total = $wc_cart->get_cart_contents_total();
        }
        if (count($wc_cart->get_cart()) > 0) {
            $cart_details = self::get_cart_details();
            $cart->update_cart($cart_details);
            $name = WC()->customer->get_billing_first_name();
            if (!empty($name)) {
                $cart->update_user_name(trim($name));
            }
            $email = WC()->customer->get_billing_email();
            if (!empty($email)) {
                $cart->update_user_email($email);
            }
        }
        $currency = self::get_active_currency();
        $total = self::convert_price_to_shop_currency($total, $currency);
        $sub_total = self::convert_price_to_shop_currency($sub_total, $currency);
        $cart->update_totals($total, $sub_total, $currency);
    }

    /**
     * Remove coupon code
     * @param $coupon_code
     * @return bool
     */
    public static function coupon_removed($coupon_code)
    {
        $cart = self::get_cart();
        $action = "Coupon code\"{$coupon_code}\" has removed from cart";
        $cart->update_history($action, 'coupon', array('code' => $coupon_code));
        return true;
    }

    /**
     * handle user logged out
     */
    public static function user_logged_out()
    {
        self::clear_cart_token();
    }

    /**
     * remove the cart token form the user cart
     * @param $delete_user_meta bool
     */
    public static function clear_cart_token($delete_user_meta = false)
    {
        self::remove_cookie_value(self::CART_TOKEN);
        if ($delete_user_meta && is_user_logged_in()) {
            $user_id = get_current_user_id();
            delete_user_meta($user_id, self::CART_TOKEN);
        }
    }

    /**
     * Removed item put backed to cart
     * @param $cart_item_key
     * @param $wc_cart WC_Cart
     * @return bool
     */
    public static function item_restored_back_to_cart($cart_item_key, $wc_cart)
    {
        $cart_item = $wc_cart->get_cart_item($cart_item_key);
        $product = null;
        if (isset($cart_item['data'])) {
            $product = $cart_item['data'];
        }
        if (!empty($product) && $product instanceof WC_Product) {
            $cart = self::get_cart();
            $product_title = $product->get_title();
            $id = $product->get_id();
            $wc_cart_obj = self::get_cart_details();
            $cart->update_cart($wc_cart_obj);
            $action = "Product \"{$product_title}(#{$id})\" has restored back to cart";
            $cart->update_history($action, 'cart_updated', array('id' => $id));
        }
        return true;
    }

    /**
     * item quantity has updated
     * @param $cart_item_key
     * @param $quantity int
     * @param $old_quantity int
     * @param $wc_cart WC_Cart
     * @return bool
     */
    public static function item_quantity_has_updated($cart_item_key, $quantity, $old_quantity, $wc_cart)
    {
        $cart_item = $wc_cart->get_cart_item($cart_item_key);
        $product = null;
        if (isset($cart_item['data'])) {
            $product = $cart_item['data'];
        }
        if (!empty($product) && $product instanceof WC_Product) {
            $cart = self::get_cart();
            $product_title = $product->get_title();
            $id = $product->get_id();
            $wc_cart_obj = self::get_cart_details();
            $cart->update_cart($wc_cart_obj);
            $action = "Product \"{$product_title}(#{$id})\" quantity has changed from {$old_quantity} to {$quantity}";
            $cart->update_history($action, 'cart_updated', array('id' => $id));
        }
        return true;
    }

    /**
     * include the javascript tracking snippet
     */
    public static function include_tacking_snippet()
    {
        global $inperks_abandoned_carts;
        $url = self::get_cart_tacking_script_src();
        $email_capturing_fields = $inperks_abandoned_carts->settings->get('email_capturing_fields', null);
        wp_enqueue_script(ACBWM_HOOK_PREFIX . 'cart_tracker', $url, array('jquery'), ACBWM_VERSION, true);
        wp_localize_script(ACBWM_HOOK_PREFIX . 'cart_tracker', 'acbwm_carts', array(
            'endpoint' => Acbwm_Ajax::get_endpoint(),
            'email_capturing_fields' => (empty($email_capturing_fields)) ? null : $email_capturing_fields
        ));
    }

    /**
     * get the cart tracking js file
     * @return mixed|void
     */
    public static function get_cart_tacking_script_src()
    {
        $url = acbwm_get_assets_url('js/cart.min.js');
        return apply_filters(ACBWM_HOOK_PREFIX . 'get_cart_tacking_script_src', $url);
    }

    /**
     * @param $order WC_Order
     */
    public static function end_previous_carts_mail_sequences($order)
    {
        $email = $order->get_billing_email();
        if (!empty($email)) {
            $cart_tokens = Acbwm_Data_Store_Cart::get_cart_tokens_to_stop_mail_sequence($email);
            $order_id = $order->get_id();
            foreach ($cart_tokens as $cart_token) {
                $cart_token = $cart_token->cart_token;
                $cart = self::get_cart_by_cart_token($cart_token);
                $message = "Customer placed order '#{$order_id}' with e-mail '{$email}', So mail sequence stopped.";
                $cart->end_mail_sequences($message);
            }
        }
    }

    /**
     * make changes when order status changed
     * @param $order_id int
     * @param $old_status string
     * @param $new_status string
     * @param $wc_order WC_Order
     */
    public static function order_status_changed($order_id, $old_status, $new_status, $wc_order)
    {
        global $inperks_abandoned_carts;
        $cart_token = $wc_order->get_meta(self::CART_TOKEN);
        $cart = null;
        if (!empty($cart_token)) {
            $cart = self::get_cart_by_cart_token($cart_token);
            $cart->update_order_id($order_id);
            $cart->update_order_status($new_status);
            $action = "Order status changed from \"{$old_status}\" to \"{$new_status}\"";
            $cart->update_history($action, 'order');
            if ($cart->get_recovery_link_opened() == '1' || $cart->get_recovery_link_clicked() == "1") {
                $cart->update_column('is_recovered', 1, true);
            }
            $cart->end_mail_sequences();
            self::end_previous_carts_mail_sequences($wc_order);
        }
        $applied_coupons = $wc_order->get_coupon_codes();
        if (!empty($applied_coupons)) {
            $prefix = $inperks_abandoned_carts->settings->get('coupon_code_prefix', 'WMC');
            foreach ($applied_coupons as $coupon) {
                if (acbwm_string_has_prefix($coupon, $prefix)) {
                    $coupon = strtoupper($coupon);
                    Acbwm_Data_Store_Discounts::update_coupon_by_code($coupon, 'is_used', '1');
                    if (!empty($cart)) {
                        $cart->update_column('is_recovered', 1, true);
                    }
                }
            }
        }
    }

    /**
     * assign cart token to the order
     * @param $order_id
     */
    public static function assign_cart_token_to_order($order_id)
    {
        $cart_token = self::get_cart_token();
        if (!empty($cart_token)) {
            $wc_order = wc_get_order($order_id);
            update_post_meta($order_id, self::CART_TOKEN, $cart_token);
            //$wc_order->update_meta_data(self::CART_TOKEN, $cart_token);
            $cart = self::get_cart_by_cart_token($cart_token);
            $cart->update_order_id($order_id);
            $order_status = $wc_order->get_status();
            $cart->update_order_status($order_status);
            $action = "Customer placed an order #{$order_id}({$order_status})";
            $cart->update_history($action, 'order');
            $cart->end_mail_sequences();
            do_action(ACBWM_HOOK_PREFIX . 'assign_cart_token_to_order', $order_id, $cart_token, $cart);
        }
    }

    /**
     * remove the cart token form the user cart
     */
    public static function enqueue_token_remove_script()
    {
        $cart_token = self::get_cookie_value(self::CART_TOKEN, null);
        echo "<span id='acbwm-remove-cart-token'>{$cart_token}</span>";
    }

    /**
     * set recovery emails are opened or not
     */
    public static function recover_email_opened()
    {
        if (isset($_GET['token']) && !empty($_GET['token']) && isset($_GET['hash']) && !empty($_GET['hash'])) {
            $hash = wc_clean($_GET['hash']);
            $data = wc_clean($_GET['token']);
            $secret = acbwm_set_and_get_plugin_secret();
            if (acbwm_is_hash_match($hash, $data, $secret)) {
                $decoded_data = json_decode(base64_decode($data));
                $cart_token = $decoded_data->cart_token;
                $email_id = $decoded_data->id;
                $cart = self::get_cart_by_cart_token($cart_token);
                $cart->update_column('recovery_link_opened', '1');
                $cart->update_history("Customer opened recovery link #{$email_id}", 'mail');
                $cart->update_sent_email($email_id, 'is_opened', '1');
                $cart->update_sent_email($email_id, 'opened_at', current_time('mysql', true));
                $extra = array(
                    'referer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
                    'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
                    'ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
                );
                $encoded_extra = maybe_serialize($extra);
                $cart->update_sent_email($email_id, 'extra', $encoded_extra);
            }
        }
        //only if graphics library enabled
        if (extension_loaded('gd')) {
            $image = imagecreate(1, 1);
            $white = imagecolorallocate($image, 255, 255, 255);
            imagesetpixel($image, 1, 1, $white);
            header("content-type:image/jpg");
            imagejpeg($image);
            imagedestroy($image);
        }
    }

    /**
     * recover the user cart
     */
    public static function recover_cart()
    {
        $success_redirect_url = wc_get_cart_url();
        if (isset($_GET['token']) && !empty($_GET['token']) && isset($_GET['hash']) && !empty($_GET['hash'])) {
            $data = wc_clean(rawurldecode($_GET['token']));
            $hash = wc_clean($_GET['hash']);
            $secret = acbwm_set_and_get_plugin_secret();
            if (acbwm_is_hash_match($hash, $data, $secret)) {
                $decoded_data = json_decode(base64_decode($data));
                $cart_token = $decoded_data->cart_token;
                $email_id = $decoded_data->id;
                $has_cart = Acbwm_Data_Store_Cart::get_cart($cart_token);
                if (!empty($has_cart)) {
                    self::set_cart_tacking_accepted();
                    $cart = self::get_cart_by_cart_token($cart_token);
                    $cart->update_column('recovery_link_clicked', '1');
                    $cart->update_sent_email($email_id, 'is_clicked', '1');
                    $cart->update_sent_email($email_id, 'clicked_at', current_time('mysql', true));
                    $cart->update_history("Customer clicked recovery link #{$email_id}", 'mail');
                    self::set_current_currency($cart->get_currency());
                    $order_id = $cart->get_order_id();
                    if ($order_id && $order = wc_get_order($order_id)) {
                        // re-enable a cancelled order for payment
                        if ($order->has_status('cancelled')) {
                            $order->set_status('pending', 'inperks order recovery link clicked');
                        }
                        if ($order->needs_payment()) {
                            $order_redirect = $order->get_checkout_payment_url();
                        } else {
                            $order_redirect = $order->get_checkout_order_received_url();
                        }
                        wp_safe_redirect($order_redirect);
                        exit;
                    }
                    $user_id = $cart->get_user_id();
                    $cart_recreated = false;
                    if (!empty($user_id) && self::auto_login_user($user_id)) {
                        $current_cart = WC()->cart->get_cart();
                        if (empty($current_cart)) {
                            $cart_recreated = false;
                        } else {
                            $cart_recreated = true;
                        }
                    }
                    if (!$cart_recreated) {
                        $user_cart = $cart->get_user_cart();
                        self::recreate_cart($user_cart);
                    }
                    self::set_cart_token($cart_token);
                    $success_redirect_url = wc_get_checkout_url();
                } else {
                    wc_add_notice(__('Note: Unable to process your request, Please contact store owner!', ACBWM_TEXT_DOMAIN));
                }
            }
        } else {
            wc_add_notice(__('Note: Unable to process your request, Please contact store owner!', ACBWM_TEXT_DOMAIN));
        }
        //apply the coupon code
        if (isset($_GET['coupon_code'])) {
            $coupon_code = sanitize_text_field($_GET['coupon_code']);
            $success_redirect_url = add_query_arg('coupon_code', $coupon_code, $success_redirect_url);
        }
        //Finally redirect to the checkout url
        wp_safe_redirect($success_redirect_url);
    }

    /**
     * auto apply coupon discount
     */
    public static function auto_apply_discount()
    {
        if (isset($_GET['coupon_code'])) {
            $coupon_code = sanitize_text_field($_GET['coupon_code']);
            if (!WC()->cart->has_discount($coupon_code)) {
                WC()->cart->add_discount($coupon_code);
            }
        }
    }

    /**
     * automatically login the registered user
     * @param $user_id
     * @return bool
     */
    public static function auto_login_user($user_id)
    {
        if (is_user_logged_in()) {
            // another user is logged in
            if ((int)$user_id !== get_current_user_id()) {
                wp_logout();
                // log the current user out, log in the new one
                if (self::allow_user_to_auto_login($user_id)) {
                    //"Another user is logged in, logging them out & logging in user {$user_id}"
                    wp_set_current_user($user_id);
                    wp_set_auth_cookie($user_id);
                    return true;
                    // safety check fail: do not let an admin to be logged in automatically
                } else {
                    wc_add_notice(__('Note: Auto-login disabled when recreating cart for WordPress Admin account. Checking out as guest.', ACBWM_TEXT_DOMAIN));
                    //"Not logging in user {$user_id} with admin rights"
                    return false;
                }
            } else {
                return true;
            }
        } else {
            // log the user in automatically
            if (self::allow_user_to_auto_login($user_id)) {
                //User is not logged in, logging in;
                wp_set_current_user($user_id);
                wp_set_auth_cookie($user_id);
                return true;
                // safety check fail: do not let an admin to be logged in automatically
            } else {
                wc_add_notice(__('Note: Auto-login disabled when recreating cart for WordPress Admin account. Checking out as guest.', ACBWM_TEXT_DOMAIN));
                //"Not logging in user {$user_id} with admin rights"
                return false;
            }
        }
    }

    /**
     * Check if a user is allowed to be logged in for cart recovery
     *
     * @param int $user_id WP_User id
     * @return bool
     */
    private static function allow_user_to_auto_login($user_id)
    {
        $allow_user_login = (!user_can($user_id, 'edit_others_posts'));
        return apply_filters(ACBWM_HOOK_PREFIX . 'allow_user_to_auto_login', $allow_user_login, $user_id);
    }

    /**
     * recreate cart for user
     * @param $cart
     */
    public static function recreate_cart($cart)
    {
        if (!empty($cart)) {
            foreach ($cart as $key => $values) {
                WC()->session->set($key, $values);
            }
        }
    }
}
