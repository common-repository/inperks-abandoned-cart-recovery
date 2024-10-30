<?php
/**
 * Admin View: tab - create / edit email template
 *
 * @var $action string
 * @var $email array
 */
defined('ABSPATH') || exit;
$page_url = admin_url('admin.php?page=acbwm-reminders');
$email_default_params = acbwm_email_template_params();
$mail_details = wp_parse_args($email, $email_default_params);
?>
<h3>
    <?php
    if ($action == "create") {
        esc_attr_e('Add New Email Reminder', ACBWM_TEXT_DOMAIN);
    } else {
        esc_attr_e('Edit Email Template', ACBWM_TEXT_DOMAIN);
    }
    ?>
    <a class="add-new-h2"
       href="<?php echo add_query_arg('tab', 'reminders-list', $page_url) ?>"><?php esc_attr_e('Back', ACBWM_TEXT_DOMAIN); ?></a>
</h3>
<form id="acbwm_saving_email_template">
    <h3><?php esc_html_e('General', ACBWM_TEXT_DOMAIN); ?></h3>
    <?php
    if ($action == "create") {
        ?>
        <input type="hidden" name="action" value="<?php echo ACBWM_HOOK_PREFIX; ?>create_new_email_template">
        <input type="hidden" name="security"
               value="<?php echo wp_create_nonce(ACBWM_HOOK_PREFIX . 'create_new_email_template'); ?>">
        <?php
    } elseif ($action == "edit") {
        ?>
        <input type="hidden" name="action" value="<?php echo ACBWM_HOOK_PREFIX; ?>edit_email_template">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <input type="hidden" name="security"
               value="<?php echo wp_create_nonce(ACBWM_HOOK_PREFIX . 'edit_email_template'); ?>">
        <?php
    }
    ?>
    <p class="e"></p>
    <table class="form-table" role="presentation">
        <tbody>
        <tr>
            <th scope="row"><?php esc_html_e('Active', ACBWM_TEXT_DOMAIN); ?></th>
            <td>
                <label for="status" class="switch">
                    <input name="status" type="checkbox" <?php if ($mail_details['status'] == 1) {
                        echo 'checked';
                    } ?> id="status" value="1">
                    <span class="slider"></span>
                </label>
                <br class="clear">
                <span class="description"><?php esc_html_e('Choose if activate or deactivate this email', ACBWM_TEXT_DOMAIN); ?></span>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Language', ACBWM_TEXT_DOMAIN); ?></th>
            <td>
                <label>
                    <select name="language" class="regular-text">
                        <?php
                        if (!empty($available_languages)) {
                            if ($action == "create") {
                                $mail_details['language'] = sanitize_text_field(isset($_GET['language']) ? $_GET['language'] : '');
                            }
                            foreach ($available_languages as $language_code => $language_label) {
                                ?>
                                <option value="<?php echo $language_code; ?>" <?php if ($mail_details['language'] == $language_code) {
                                    echo 'selected';
                                } ?>><?php echo $language_label; ?></option>
                                <?php
                            }
                        }
                        ?>
                    </select>
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Email name', ACBWM_TEXT_DOMAIN); ?></th>
            <td>
                <label for="name">
                    <input name="name" type="text" id="name"
                           value="<?php echo $mail_details['name'] ?>"
                           class="regular-text" required>
                </label>
                <br class="clear">
                <span class="description"><?php esc_html_e('Enter a  template name for reference', ACBWM_TEXT_DOMAIN); ?></span>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Send this mail after', ACBWM_TEXT_DOMAIN); ?></th>
            <td>
                <label for="send_after_time">
                    <input name="send_after_time" type="number" id="send_after"
                           value="<?php echo $mail_details['send_after_time'] ?>" class="small-text" required
                           min="1">
                </label>
                <label for="send_after_unit">
                    <select id="send_after_unit" name="send_after_unit" required>
                        <option value="hour"
                                <?php if ($mail_details['send_after_unit'] == 'hour'){ ?>selected<?php } ?>><?php esc_html_e('Hours', ACBWM_TEXT_DOMAIN); ?></option>
                        <option value="day"
                                <?php if ($mail_details['send_after_unit'] == 'day'){ ?>selected<?php } ?>><?php esc_html_e('Days', ACBWM_TEXT_DOMAIN); ?></option>
                    </select>
                </label>
                <br class="clear">
                <span class="description"><?php esc_html_e('Set the value for mail sending (e.g., Send after 2 hours of cart is abandoned.)', ACBWM_TEXT_DOMAIN); ?></span>
            </td>
        </tr>
        </tbody>
    </table>
    <h3><?php esc_html_e('Email Template', ACBWM_TEXT_DOMAIN); ?></h3>
    <table class="form-table" id="acbwm-coupon-settings" role="presentation">
        <tbody>
        <tr>
            <th scope="row"><?php esc_html_e('Email subject', ACBWM_TEXT_DOMAIN); ?></th>
            <td>
                <label for="subject">
                    <input name="subject" type="text" id="subject"
                           value="<?php echo $mail_details['subject'] ?>"
                           class="regular-text" required>
                </label>
                <br class="clear">
                <span class="description">
                    <b>{{customer.name}}</b> - <?php esc_html_e('Include customer name in email subject', ACBWM_TEXT_DOMAIN); ?><br>
                </span>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Email body', ACBWM_TEXT_DOMAIN); ?></th>
            <td>
                <div class="row">
                    <div class="col col-3">
                        <div class="card">
                            <img src="<?php echo acbwm_get_assets_url('images/template-1-preview.jpg') ?>"
                                 alt="template 1" width="100%"/>
                            <button type="button" class="button acbwm-get-template w100p"
                                    data-uri="<?php echo ACBWM_PLUGIN_URL . 'includes/admin/views/emails/template-2.html' ?>">
                                <?php esc_html_e('Use template', ACBWM_TEXT_DOMAIN); ?>
                            </button>
                        </div>
                    </div>
                    <div class="col col-3">
                        <div class="card">
                            <img src="<?php echo acbwm_get_assets_url('images/template-2-preview.jpg') ?>"
                                 alt="template 1" width="100%"/>
                            <button type="button" class="button acbwm-get-template w100p"
                                    data-uri="<?php echo ACBWM_PLUGIN_URL . 'includes/admin/views/emails/template-1.html' ?>">
                                <?php esc_html_e('Use template', ACBWM_TEXT_DOMAIN); ?>
                            </button>
                        </div>
                    </div>
                </div>
                <br>
                <?php
                acbwm_html_editor(acbwm_maybe_base64_decode($mail_details['email_body']), 'email_body');
                ?>
                <b>{{cart.items_table}}</b> - <?php esc_html_e('Display user cart', ACBWM_TEXT_DOMAIN); ?><br>
                <b>{{cart.recovery_url}}</b> - <?php esc_html_e('Include cart recovery url', ACBWM_TEXT_DOMAIN); ?><br>
                <b>{{coupon_code}}</b> - <?php esc_html_e('Display coupon code', ACBWM_TEXT_DOMAIN); ?>(PRO)<br>
                <b>{{expire_time}}</b> - <?php esc_html_e('Show the coupon expiry time', ACBWM_TEXT_DOMAIN); ?>(PRO)<br>
                <b>{{coupon_value}}</b> - <?php esc_html_e('Display coupon value', ACBWM_TEXT_DOMAIN); ?>(PRO)<br>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Use the woocommerce template style', ACBWM_TEXT_DOMAIN); ?></th>
            <td>
                <?php
                $use_woocommerce_style = ($mail_details['use_woocommerce_style'] == 1);
                ?>
                <label>
                    <input type="radio" value="1" id="use_woocommerce_style"
                           name="use_woocommerce_style" <?php if ($use_woocommerce_style) {
                        echo "checked";
                    } ?>>
                    <?php esc_html_e('Yes', ACBWM_TEXT_DOMAIN); ?>
                </label>
                <label>
                    <input type="radio" value="0" id="use_woocommerce_style"
                           name="use_woocommerce_style" <?php if (!$use_woocommerce_style) {
                        echo "checked";
                    } ?>>
                    <?php esc_html_e('No', ACBWM_TEXT_DOMAIN); ?>
                </label>
                <br class="clear">
            </td>
        </tr>
        <tr id="acbwm_woo_email_heading" class=" <?php if (!$use_woocommerce_style) {
            echo "hidden";
        } ?>">
            <th scope="row"><?php esc_html_e('Email heading', ACBWM_TEXT_DOMAIN); ?></th>
            <td>
                <label for="heading">
                    <input name="heading" type="text" id="heading"
                           value="<?php echo $mail_details['heading'] ?>"
                           class="regular-text" required>
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row"></th>
            <td>
                <br class="clear">
                <label>
                    <input type="text" value="" class="regular-text" id="acbwm_email_template_email">
                </label>
                <input type="button" class="button" id="acbwm_email_template_send_mail"
                       value="<?php esc_html_e('Send a test email', ACBWM_TEXT_DOMAIN); ?>">
                &nbsp;(<?php esc_html_e('OR', ACBWM_TEXT_DOMAIN); ?>)&nbsp;
                <a href="#acbwm_preview_holder" id="acbwm_view_template_preview" rel="modal:open"
                   class="button"><?php esc_html_e('View preview', ACBWM_TEXT_DOMAIN); ?></a>
                <br class="clear">
                <span class="description">
                    <?php esc_html_e('If you made any changes in template, please save modified template before sending test email or viewing preview!', ACBWM_TEXT_DOMAIN); ?><br>
                </span>
            </td>
        </tr>
        </tbody>
    </table>
    <?php
    do_action(ACBWM_HOOK_PREFIX . 'admin_edit_email_template_extra', $mail_details);
    ?>
    <p class="submit">
        <input type="submit" name="submit" id="submit" class="button"
               value="<?php esc_html_e('Save', ACBWM_TEXT_DOMAIN); ?>">
    </p>
</form>
<div class="hidden" id="acbwm_preview_holder">

</div>