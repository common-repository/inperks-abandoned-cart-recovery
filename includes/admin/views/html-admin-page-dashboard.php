<?php
/**
 * Admin View: Page - dashboard
 * @var $total_carts int
 * @var $total_abandoned_carts_count int
 * @var $total_recoverable_carts_count int
 * @var $total_recovered_carts_count int
 * @var $total_recovered_carts_value int
 * @var $total_recoverable_carts_value int
 * @var $total_abandoned_carts_value int
 * @var $start string
 * @var $end string
 * @var $duration string
 */
defined('ABSPATH') || exit;
?>
<div class="wrap acbwm">
    <h1 class="wp-heading"><?php esc_attr_e('Dashboard', ACBWM_TEXT_DOMAIN); ?></h1>
    <div class="row">
        <div class="col col-12">
            <form method="get">
                <input type="hidden" name="page" value="acbwm">
                <label for="acbwm-dashboard-date-range"><?php esc_attr_e('Select date range', ACBWM_TEXT_DOMAIN); ?></label>
                &nbsp;
                <select id="acbwm-dashboard-date-range" class="regular-text" name="duration">
                    <?php
                    $durations = acbwm_durations();
                    foreach ($durations as $duration_key => $duration_text) {
                        ?>
                        <option value="<?php echo $duration_key; ?>" <?php echo ($duration == $duration_key) ? 'selected' : ''; ?>><?php echo $duration_text ?></option>
                        <?php
                    }
                    ?>
                </select>
                <button type="submit"
                        class="button"><?php esc_html_e('Go', ACBWM_TEXT_DOMAIN); ?></button>
            </form>
        </div>
    </div>
    <h3><?php esc_attr_e('Cart details', ACBWM_TEXT_DOMAIN); ?></h3>
    <div class="row">
        <div class="col col-3">
            <div class="card">
                <div class="heading"><?php esc_html_e('Total abandoned carts', ACBWM_TEXT_DOMAIN); ?></div>
                <div class="content"><?php echo $total_abandoned_carts_count; ?></div>
                <div>
                    <p><?php esc_html_e('Total number carts abandoned by user.', ACBWM_TEXT_DOMAIN); ?></p>
                </div>
            </div>
        </div>
        <div class="col col-3">
            <div class="card">
                <div class="heading"><?php esc_html_e('Total recoverable carts', ACBWM_TEXT_DOMAIN); ?></div>
                <div class="content"><?php echo $total_recoverable_carts_count; ?></div>
                <div>
                    <p><?php esc_html_e('Total number carts contains email.', ACBWM_TEXT_DOMAIN); ?></p>
                </div>
            </div>
        </div>
        <div class="col col-3">
            <div class="card">
                <div class="heading"><?php esc_html_e('Total recovered carts', ACBWM_TEXT_DOMAIN); ?></div>
                <div class="content"><?php echo $total_recovered_carts_count; ?></div>
                <div>
                    <p><?php esc_html_e('Total number carts get recovered.', ACBWM_TEXT_DOMAIN); ?></p>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col col-3">
            <div class="card">
                <div class="heading"><?php esc_html_e('Total abandoned carts value', ACBWM_TEXT_DOMAIN); ?></div>
                <div class="content"><?php echo wc_price($total_abandoned_carts_value); ?></div>
                <div>
                    <p><?php esc_html_e('Total value of abandoned carts.', ACBWM_TEXT_DOMAIN); ?></p>
                </div>
            </div>
        </div>
        <div class="col col-3">
            <div class="card">
                <div class="heading"><?php esc_html_e('Total recoverable carts value', ACBWM_TEXT_DOMAIN); ?></div>
                <div class="content"><?php echo wc_price($total_recoverable_carts_value); ?></div>
                <div>
                    <p><?php esc_html_e('Total value of recoverable carts.', ACBWM_TEXT_DOMAIN); ?></p>
                </div>
            </div>
        </div>
        <div class="col col-3">
            <div class="card">
                <div class="heading"><?php esc_html_e('Total Recovered Value', ACBWM_TEXT_DOMAIN); ?></div>
                <div class="content"><?php echo wc_price($total_recovered_carts_value); ?></div>
                <div>
                    <p><?php esc_html_e('Total value of recovered carts.', ACBWM_TEXT_DOMAIN); ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php
    do_action(ACBWM_HOOK_PREFIX . 'admin_dashboard_extra', $start, $end);
    ?>
</div>