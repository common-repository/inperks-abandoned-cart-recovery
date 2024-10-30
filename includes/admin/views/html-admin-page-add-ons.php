<?php
/**
 * Admin View: Page - Add-ons
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap acbwm">
    <h1 class="wp-heading"><?php esc_attr_e('Add-ons', ACBWM_TEXT_DOMAIN); ?></h1>
    <div class="row">
        <div class="col col-3">
            <div class="card">
                <img src="<?php echo acbwm_get_assets_url('images/add-to-cart-popup.png') ?>" alt="Add to cart popup"
                     style="width:100%">
                <div class="container">
                    <h4><b><?php esc_html_e('Add to cart popup', ACBWM_TEXT_DOMAIN); ?></b></h4>
                    <p><?php esc_html_e('This add-on enables a popup window that displays email collection form before an item is being add to cart.', ACBWM_TEXT_DOMAIN); ?></p>
                    <?php
                    echo apply_filters(ACBWM_HOOK_PREFIX . 'add_to_popup_customization_links', '<a target="_blank" href="https://inperks.org/product/abandoned-carts-recovery-pro-for-woocommerce/" class="button">Go Pro!</a>');
                    ?>
                </div>
            </div>
        </div>
        <div class="col col-3">
            <div class="card">
                <img src="<?php echo acbwm_get_assets_url('images/coupon-timer.png') ?>" alt="Add to cart popup"
                     style="width:100%">
                <div class="container">
                    <h4><b><?php esc_html_e('Coupon timer', ACBWM_TEXT_DOMAIN); ?></b></h4>
                    <p><?php esc_html_e('Incentivize customers by giving one use of unique coupons as a limited time offer.', ACBWM_TEXT_DOMAIN); ?></p>
                    <?php
                    echo apply_filters(ACBWM_HOOK_PREFIX . 'coupon_popup_customization_links', '<a target="_blank" href="https://inperks.org/product/abandoned-carts-recovery-pro-for-woocommerce/" class="button">Go Pro!</a>');
                    ?>
                </div>
            </div>
        </div>
        <div class="col col-3">
            <div class="card">
                <img src="<?php echo acbwm_get_assets_url('images/wheel-of-fortune.png') ?>" alt="Add to cart popup"
                     style="width:100%">
                <div class="container">
                    <h4><b><?php esc_html_e('Wheel of fortune', ACBWM_TEXT_DOMAIN); ?></b></h4>
                    <p><?php esc_html_e('Get more customers to your e-commerce store with the interactive, discount-based Wheel of Fortune(Spin to Win) form.', ACBWM_TEXT_DOMAIN); ?></p>
                    <?php
                    echo apply_filters(ACBWM_HOOK_PREFIX . 'wheel_of_fortune_customization_links', '<a target="_blank" href="https://inperks.org/product/abandoned-carts-recovery-pro-for-woocommerce/" class="button button-danger">Coming Soon!</a>');
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col col-3">
            <div class="card">
                <img src="<?php echo acbwm_get_assets_url('images/exit-intent-popup.png') ?>" alt="Add to cart popup"
                     style="width:100%">
                <div class="container">
                    <h4><b><?php esc_html_e('Exit intent popup', ACBWM_TEXT_DOMAIN); ?></b></h4>
                    <p><?php esc_html_e('Exit intent popup(Exit popup) detects a visitor is about to leave the site so you can reach them before they disappear.', ACBWM_TEXT_DOMAIN); ?></p>
                    <?php
                    echo apply_filters(ACBWM_HOOK_PREFIX . 'exit_intent_popup_customization_links', '<a target="_blank" href="https://inperks.org/product/abandoned-carts-recovery-pro-for-woocommerce/" class="button button-danger">Coming Soon!</a>');
                    ?>
                </div>
            </div>
        </div>
        <div class="col col-3">
            <div class="card">
                <img src="<?php echo acbwm_get_assets_url('images/messenger-notification.png') ?>"
                     alt="Add to cart popup"
                     style="width:100%">
                <div class="container">
                    <h4><b><?php esc_html_e('Send notification via Messenger', ACBWM_TEXT_DOMAIN); ?></b></h4>
                    <p><?php esc_html_e('Send abandoned cart notification to your customers via Facebook messenger to increase sales.', ACBWM_TEXT_DOMAIN); ?></p>
                    <?php
                    echo apply_filters(ACBWM_HOOK_PREFIX . 'fb_messenger_customization_links', '<a target="_blank" href="https://inperks.org/product/abandoned-carts-recovery-pro-for-woocommerce/" class="button button-danger">Coming Soon!</a>');
                    ?>
                </div>
            </div>
        </div>
        <div class="col col-3">
            <div class="card">
                <img src="<?php echo acbwm_get_assets_url('images/whatsapp-notification.png') ?>"
                     alt="Add to cart popup"
                     style="width:100%">
                <div class="container">
                    <h4><b><?php esc_html_e('Send notification via Whatsapp', ACBWM_TEXT_DOMAIN); ?></b></h4>
                    <p><?php esc_html_e('Send abandoned cart reminders to your customers Whatsapp number to get more revenue.', ACBWM_TEXT_DOMAIN); ?></p>
                    <?php
                    echo apply_filters(ACBWM_HOOK_PREFIX . 'whatsapp_messenger_customization_links', '<a target="_blank" href="https://inperks.org/product/abandoned-carts-recovery-pro-for-woocommerce/" class="button button-danger">Coming Soon!</a>');
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col col-3">
            <div class="card">
                <img src="<?php echo acbwm_get_assets_url('images/sms-notification.png') ?>" alt="Add to cart popup"
                     style="width:100%">
                <div class="container">
                    <h4><b><?php esc_html_e('Send notification via SMS', ACBWM_TEXT_DOMAIN); ?></b></h4>
                    <p><?php esc_html_e('Remind customers about abandoned cart using SMS(Twilio) to get more sales.', ACBWM_TEXT_DOMAIN); ?></p>
                    <?php
                    echo apply_filters(ACBWM_HOOK_PREFIX . 'sms_notification_customization_links', '<a target="_blank" href="https://inperks.org/product/abandoned-carts-recovery-pro-for-woocommerce/" class="button button-danger">Coming Soon!</a>');
                    ?>
                </div>
            </div>
        </div>
        <div class="col col-3">
        </div>
        <div class="col col-3">
        </div>
    </div>
</div>