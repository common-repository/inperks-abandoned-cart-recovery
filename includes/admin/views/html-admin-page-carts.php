<?php
/**
 * Admin View: Page - Carts
 */
defined('ABSPATH') || exit;
$action = sanitize_text_field(isset($_GET['tab']) ? $_GET['tab'] : 'display-all-carts');
?>
<div class="wrap acbwm">
    <h1 class="wp-heading"><?php esc_attr_e('Carts', ACBWM_TEXT_DOMAIN); ?></h1>
    <div class="wrap">
        <?php do_action(ACBWM_HOOK_PREFIX . 'admin_page_cart_tab_'.$action); ?>
    </div>
</div>