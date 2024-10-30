<?php
defined('ABSPATH') || exit;
if (!class_exists('WC_Email')) {
    return;
}

/**
 * Register customer email
 * Class Acbwm_Email_Recover_abandoned_cart
 */
class Acbwm_Email_Recover_abandoned_cart extends WC_Email
{
    public static $mail_content = null;

    function __construct($params)
    {
        $default_params = array(
            'id' => rand(),
            'title' => __('Send Cart recovery emails after 1 Hrs', ACBWM_TEXT_DOMAIN),
            'description' => __('Recovery emails will sent to the customers after 1 Hrs of the cart abandonment.', ACBWM_TEXT_DOMAIN),
            'subject' => __('Hi {customer}, You left something in your cart!', ACBWM_TEXT_DOMAIN),
            'heading' => __('Your cart is waiting for you!', ACBWM_TEXT_DOMAIN),
        );
        $options = wp_parse_args($params, $default_params);
        $this->id = $options['id'];
        $this->title = $options['title'];
        $this->description = $options['description'];
        $this->subject = $options['subject'];
        $this->heading = $options['heading'];
        $this->customer_email = true;
        $this->template_html = 'emails/customer-acbwm-recover-ac-default.php';
        $this->template_plain = 'emails/plain/customer-acbwm-recover-ac-default.php';
        $this->recipient = $this->get_option('recipient', get_option('admin_email'));
        $this->template_base = ACBWM_ABSPATH . 'templates/';
        add_action('send_scheduled_' . $options['id'], array($this, 'trigger'), 10, 3);
        parent::__construct();
    }

    /**
     * Trigger Function that will send this email to the customer.
     *
     * @param $cart Acbwm_Data_Store_Cart
     * @param $email_id int
     * @param $mail Object
     * @access public
     * @return void
     */
    function trigger($cart, $email_id, $mail)
    {
        $this->recipient = $cart->get_user_email();
        if (!$this->is_enabled() || !$this->get_recipient()) {
            return;
        }
        $mail_content = acbwm_maybe_base64_decode($mail->email_body);
        self::$mail_content = (empty($mail_content)) ? "{{cart.items_table}} <a href='{{cart.recovery_url}}'>Click here</a> to recover your cart." : $mail_content;
        $this->object = $cart->create_cart_items_object($email_id, $mail);
        $this->placeholders = array(
            '{{customer.name}}' => $cart->get_user_name()
        );
        // send the email!
        $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
    }

    /**
     * get_content_html function. to get the content of the mail
     * @return false|string
     */
    function get_content_html()
    {
        ob_start();
        wc_get_template(
            $this->template_html,
            array(
                'body' => self::$mail_content,
                'email' => $this,
                'cart_data' => $this->object,
                'email_heading' => $this->get_heading()
            ),
            '',
            $this->template_base
        );
        return ob_get_clean();
    }

    /**
     * Get the content Plain Mail
     * @return false|string
     */
    function get_content_plain()
    {
        ob_start();
        wc_get_template(
            $this->template_plain,
            array(
                'email' => $this,
                'cart_data' => $this->object,
                'email_heading' => $this->get_heading()
            ),
            '',
            $this->template_base
        );
        return ob_get_clean();
    }

    /**
     * Form fields for mail
     */
    function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', ACBWM_TEXT_DOMAIN),
                'type' => 'checkbox',
                'label' => __('Enable this email notification', ACBWM_TEXT_DOMAIN),
                'default' => 'yes',
            ),
            'email_type' => array(
                'title' => __('Email type', ACBWM_TEXT_DOMAIN),
                'type' => 'select',
                'description' => __('Choose which format of email to send.', ACBWM_TEXT_DOMAIN),
                'default' => 'html',
                'class' => 'email_type',
                'options' => array(
                    'plain' => __('Plain text', ACBWM_TEXT_DOMAIN),
                    'html' => __('HTML', ACBWM_TEXT_DOMAIN),
                    'multipart' => __('Multipart', ACBWM_TEXT_DOMAIN),
                )
            )
        );
    }
}