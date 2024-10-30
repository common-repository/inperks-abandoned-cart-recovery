<?php
/**
 * Admin View: tab - create / edit email template
 *
 * @var $cart Acbwm_Data_Store_Cart
 * @var $all_mail_templates stdClass
 */
defined('ABSPATH') || exit;
?>
<h3>
    <?php
    esc_attr_e('Send Email', ACBWM_TEXT_DOMAIN);
    ?>
</h3>
<form id="acbwm_sending_email_manually">
    <input type="hidden" name="action" value="<?php echo ACBWM_HOOK_PREFIX; ?>send_emails_manually">
    <input type="hidden" name="cart" value="<?php echo $cart->get_cart_token(); ?>">
    <input type="hidden" name="security"
           value="<?php echo wp_create_nonce(ACBWM_HOOK_PREFIX . 'send_emails_manually'); ?>">
    <p class="e"></p>
    <table class="form-table" role="presentation">
        <tbody>
        <tr>
            <th scope="row"><?php esc_html_e('Select email template ', ACBWM_TEXT_DOMAIN); ?></th>
            <td>
                <label>
                    <select name="mail_id" class="regular-text">
                        <?php
                        if (!empty($all_mail_templates)) {
                            foreach ($all_mail_templates as $template) {
                                ?>
                                <option value="<?php echo $template->mail_id ?>"><?php echo $template->name; ?></option>
                                <?php
                            }
                        }
                        ?>
                    </select>
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row"></th>
            <td>
                <div class="error-text"><?php
                    if (strtotime($cart->get_abandoned_at()) >= current_time('timestamp', true)) {
                        _e('Cart still not abandoned!', ACBWM_TEXT_DOMAIN);
                    }
                    ?></div>
                <br class="clear">
                <input type="submit" name="submit" id="submit" class="button"
                       value="<?php esc_html_e('Send', ACBWM_TEXT_DOMAIN); ?>">
            </td>
        </tr>
        </tbody>
    </table>
</form>