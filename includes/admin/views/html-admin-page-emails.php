<?php
/**
 * Admin View: Page - Settings
 *
 * @var $settings Acbwm_settings
 */
defined('ABSPATH') || exit;
$page_url = admin_url('admin.php?page=acbwm-reminders');
$current_tab = sanitize_text_field(isset($_GET['tab']) ? $_GET['tab'] : 'reminders-list');
?>
<div class="wrap acbwm">
    <h1 class="wp-heading"><?php esc_attr_e('Reminders', ACBWM_TEXT_DOMAIN); ?></h1>
    <div class="nav-tab-wrapper acbwm-nav-tab-wrapper">
        <a class="nav-tab <?php if (in_array($current_tab, array('reminders-list', 'add-new-email-reminder', 'edit-email-template', 'delete-email-template'))): ?>nav-tab-active<?php endif; ?>"
           href="<?php echo add_query_arg('tab', 'reminders-list', $page_url) ?>"><?php esc_attr_e('Reminders Template', ACBWM_TEXT_DOMAIN); ?></a>
        <a class="nav-tab <?php if (in_array($current_tab, array('sent-reminders'))): ?>nav-tab-active<?php endif; ?>"
           href="<?php echo add_query_arg('tab', 'sent-reminders', $page_url) ?>"><?php esc_attr_e('Reminders Log', ACBWM_TEXT_DOMAIN); ?></a>
    </div>
    <div class="wrap">
        <?php do_action(ACBWM_HOOK_PREFIX . 'admin_page_emails_tab_' . $current_tab); ?>
    </div>
</div>