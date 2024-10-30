<?php
/**
 * Admin View: Page - Settings
 *
 * @var $settings Acbwm_settings
 */
defined('ABSPATH') || exit;
?>
<div class="wrap acbwm">
    <h1 class="wp-heading"><?php esc_attr_e('Settings', ACBWM_TEXT_DOMAIN); ?></h1>
    <p><?php esc_html_e("Change settings for sending email notifications to Customers, to Admin, Tracking Coupons etc.", ACBWM_TEXT_DOMAIN); ?></p>
    <h3>
        <?php esc_html_e('General settings', ACBWM_TEXT_DOMAIN); ?>
    </h3>
    <form method="post" id="acbwm_settings_form">
        <input type="hidden" name="action" value="<?php echo ACBWM_HOOK_PREFIX; ?>save_acbwm_settings">
        <input type="hidden" name="security"
               value="<?php echo wp_create_nonce(ACBWM_HOOK_PREFIX . 'save_acbwm_settings'); ?>">
        <table class="form-table" role="presentation">
            <tbody>
            <tr>
                <th scope="row"><?php esc_html_e('Cart cut off time', ACBWM_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input name="settings[cart_cut_off_time]" type="number" id="cart_cut_off_time"
                               value="<?php echo $settings->get('cart_cut_off_time', 60) ?>"
                               class="regular-text" required>
                    </label>
                    <br class="clear">
                    <span class="description"><?php esc_html_e(' For users & visitors consider cart abandoned after X minutes of item being added to cart & order not placed', ACBWM_TEXT_DOMAIN); ?></span>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Track guest carts', ACBWM_TEXT_DOMAIN); ?></th>
                <td>
                    <?php
                    $need_guest_users_tracking = ($settings->get('track_guest_carts', 'yes') == 'yes');
                    ?>
                    <label>
                        <input name="settings[track_guest_carts]"
                               type="radio" <?php echo ($need_guest_users_tracking) ? "checked" : "" ?>
                               value="yes"><?php esc_html_e('Yes', ACBWM_TEXT_DOMAIN); ?>
                    </label>
                    <label>
                        <input name="settings[track_guest_carts]"
                               type="radio" <?php echo (!$need_guest_users_tracking) ? "checked" : "" ?>
                               value="no"><?php esc_html_e('No', ACBWM_TEXT_DOMAIN); ?>
                    </label>
                    <br class="clear">
                    <span class="description"><?php esc_html_e('Abandoned carts of guest users will not be tracked.', ACBWM_TEXT_DOMAIN); ?></span>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Coupon prefix', ACBWM_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input name="settings[coupon_code_prefix]" type="text" id="coupon_code_prefix"
                               value="<?php echo $settings->get('coupon_code_prefix', 'WAC') ?>"
                               class="regular-text" required>
                    </label>
                    <br class="clear">
                    <span class="description"><?php esc_html_e('', ACBWM_TEXT_DOMAIN); ?></span>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Automatically Delete Abandoned Orders after X days', ACBWM_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input name="settings[auto_delete_ac_after]" type="text" id="auto_delete_ac_after"
                               value="<?php echo $settings->get('auto_delete_ac_after', 90) ?>"
                               class="regular-text" required>
                    </label>
                    <br class="clear">
                    <span class="description"><?php esc_html_e('Automatically delete abandoned cart orders after X days.', ACBWM_TEXT_DOMAIN); ?></span>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('GDPR Compliance', ACBWM_TEXT_DOMAIN); ?></th>
                <td>
                    <a target="_blank" class="button"
                       href="<?php echo admin_url('customize.php?autofocus[section]=acbwm_gdpr_compliance'); ?>"><?php esc_html_e('Customize', ACBWM_TEXT_DOMAIN); ?></a>
                </td>
            </tr>
            </tbody>
        </table>
        <h3><?php esc_html_e("Cron Settings", ACBWM_TEXT_DOMAIN); ?></h3>
        <table class="form-table" role="presentation">
            <tbody>
            <tr>
                <th scope="row"><?php esc_html_e('The number of carts to process on each cron request', ACBWM_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input name="settings[process_carts_on_each_cron]" type="text" id="process_carts_on_each_cron"
                               value="<?php echo $settings->get('process_carts_on_each_cron', 50) ?>"
                               class="regular-text" required>
                    </label>
                    <br class="clear">
                    <span class="description"><?php esc_html_e('Number of carts can processed per cron request. Depends on the server capacity', ACBWM_TEXT_DOMAIN); ?></span>
                </td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <div class='admin-alert alert-error'>
                        <p>
                            <b><?php esc_html_e('Recommended to use external cron to send scheduled recovery emails.', ACBWM_TEXT_DOMAIN); ?></b>
                            <?php esc_html_e('One of the free cron service is', ACBWM_TEXT_DOMAIN); ?> <a
                                    href="https://cron-job.org/">https://cron-job.org</a>.</p>
                        <p><?php esc_html_e('Wordpress cron has some drawbacks. To known more about wordpress cron  ', ACBWM_TEXT_DOMAIN); ?>
                            <a
                                    href="https://developer.wordpress.org/plugins/cron/"><?php esc_html_e('Click here!', ACBWM_TEXT_DOMAIN); ?></a>.
                        </p>
                        <p>
                            <b><?php esc_html_e('Note:', ACBWM_TEXT_DOMAIN); ?></b>
                            <?php esc_html_e(' If you would like to use external cron service, then turn off the internally scheduled one. To turn off just ', ACBWM_TEXT_DOMAIN); ?>
                            <a href="<?php echo admin_url('admin-ajax.php?action=' . ACBWM_HOOK_PREFIX . 'turn_off_scheduler_manually'); ?>"><?php esc_html_e('Click here!', ACBWM_TEXT_DOMAIN); ?></a>
                        </p>
                    </div>
                    <div class="admin-alert alert-success">
                        <p>
                            <?php
                            $cron_link = acbwm_cron_send_mails_url();
                            ?>
                            <b><?php esc_html_e('Url for external cron : ', ACBWM_TEXT_DOMAIN); ?></b><a
                                    href="<?php echo $cron_link; ?>" target="_blank"><?php echo $cron_link; ?></a>
                        </p>
                    </div>
                    <?php
                    $is_cron_turned_off_manually = get_option('acbwm_cron_turned_off_manually', 'no');
                    if ($is_cron_turned_off_manually == "yes") {
                        ?>
                        <div class="admin-alert alert-error">
                            <p>
                                <?php esc_html_e('It seems you had disabled wordpress cron. To re-enable wordpress cron ', ACBWM_TEXT_DOMAIN); ?>
                                <a href="<?php echo admin_url('admin-ajax.php?action=' . ACBWM_HOOK_PREFIX . 'turn_on_scheduler_manually'); ?>"><?php esc_html_e('Click here!', ACBWM_TEXT_DOMAIN); ?></a>
                            </p>
                        </div>
                        <?php
                    }
                    ?>
                </td>
            </tr>
            </tbody>
        </table>
        <?php
        do_action(ACBWM_HOOK_PREFIX . 'admin_settings_additional_fields');
        ?>
        <p class="submit">
            <button name="save" class="button acbwm-save-button" type="submit"
                    value="<?php esc_attr_e('Save changes', ACBWM_TEXT_DOMAIN); ?>"><?php esc_html_e('Save changes', ACBWM_TEXT_DOMAIN); ?></button>
            <?php wp_nonce_field('acbwm-settings'); ?>
        </p>
    </form>
</div>