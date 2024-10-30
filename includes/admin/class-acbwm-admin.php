<?php
defined('ABSPATH') || exit;

class Acbwm_Admin
{
    function __construct()
    {
        add_action('init', array($this, 'includes'));
        add_action('init', array($this, 'init_hooks'));
        add_filter('plugin_action_links_' . plugin_basename(ACBWM_PLUGIN_FILE), array($this, 'plugin_action_links'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'), 100);
        add_action('admin_notices', array($this, 'review_notice'));
        add_action('admin_notices', array($this, 'get_trial_notice'));
        add_action('admin_init', array($this, 'hide_review_notice'));
        add_action('admin_init', array($this, 'hide_trial_notice'));
        add_action('wp_ajax_' . ACBWM_HOOK_PREFIX . 'create_new_email_template', 'Acbwm_Admin_Reminders::save_new_template');
        add_action('wp_ajax_' . ACBWM_HOOK_PREFIX . 'send_emails_manually', 'Acbwm_Admin_Carts::send_emails_manually');
        add_action('wp_ajax_' . ACBWM_HOOK_PREFIX . 'edit_email_template', 'Acbwm_Admin_Reminders::save_edited_template');
        add_action('wp_ajax_' . ACBWM_HOOK_PREFIX . 'view_cart', 'Acbwm_Admin_Carts::view_cart');
        add_action('wp_ajax_' . ACBWM_HOOK_PREFIX . 'send_email', 'Acbwm_Admin_Carts::send_email');
        add_action('wp_ajax_' . ACBWM_HOOK_PREFIX . 'delete_cart', 'Acbwm_Admin_Carts::delete_cart');
        add_action('wp_ajax_' . ACBWM_HOOK_PREFIX . 'change_email_template_status', 'Acbwm_Admin_Reminders::change_template_status');
        add_action('wp_ajax_' . ACBWM_HOOK_PREFIX . 'search_coupons', 'Acbwm_Admin_Reminders::search_coupons');
        add_action('wp_ajax_' . ACBWM_HOOK_PREFIX . 'save_acbwm_settings', 'Acbwm_Admin_Settings::save_acbwm_settings');
        add_action('wp_ajax_' . ACBWM_HOOK_PREFIX . 'send_test_email', 'Acbwm_Admin_Reminders::send_test_email');
        add_action('wp_ajax_' . ACBWM_HOOK_PREFIX . 'view_email_preview', 'Acbwm_Admin_Reminders::view_email_preview');
        add_action('wp_ajax_' . ACBWM_HOOK_PREFIX . 'turn_off_scheduler_manually', 'Acbwm_Admin_Reminders::turn_off_scheduler_manually');
        add_action('wp_ajax_' . ACBWM_HOOK_PREFIX . 'turn_on_scheduler_manually', 'Acbwm_Admin_Reminders::turn_on_scheduler_manually');
    }

    /**
     * Show review wordpress
     */
    public function get_trial_notice()
    {
        if (get_option(ACBWM_PLUGIN_SLUG . '_dismiss_trial_notices', 0)) {
            return;
        }
        $name = str_replace('-', ' ', ACBWM_PLUGIN_SLUG);
        $name = ucwords($name);
        ?>
        <div class="inperks-dashboard updated" style="padding-bottom: 10px;border-left: 4px solid #2aa189">
            <div class="inperks-content">
                <form action="" method="get">
                    <p><?php echo esc_html__('Get 14 days trial for ', ACBWM_PLUGIN_SLUG) . '<strong>' . $name . '</strong>'; ?></p>
                    <p>
                        <a style="padding: 8px;text-decoration:none;color: #ffffff !important;border: 2px solid #2aa189;font-weight: bold;background: #2aa189;"
                           href="https://inperks.org/?add-to-cart=3105"
                           target="_blank"><?php esc_html_e('Get now', ACBWM_PLUGIN_SLUG) ?></a>
                        <a target="_self"
                           href="<?php echo esc_url(wp_nonce_url(@add_query_arg(), ACBWM_PLUGIN_SLUG . '_dismiss_trial_notices', '_inperks_trial_nonce')); ?>"><?php esc_html_e('Dismiss', ACBWM_PLUGIN_SLUG) ?></a>
                    </p>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Show review wordpress
     */
    public function review_notice()
    {
        if (get_option(ACBWM_PLUGIN_SLUG . '_dismiss_notices', 0)) {
            return;
        }
        if (get_transient(ACBWM_PLUGIN_SLUG . ACBWM_VERSION . '_hide_notices')) {
            return;
        }
        $name = str_replace('-', ' ', ACBWM_PLUGIN_SLUG);
        $name = ucwords($name);
        $check_review = get_option(ACBWM_PLUGIN_SLUG . '_wp_reviewed', 0);
        $check_start = get_option(ACBWM_PLUGIN_SLUG . '_start_use', 0);
        if (!$check_start) {
            update_option(ACBWM_PLUGIN_SLUG . '_start_use', 1);
            set_transient(ACBWM_PLUGIN_SLUG . ACBWM_PLUGIN_SLUG . '_hide_notices', 1, 259200);
            return;
        }
        ?>
        <div class="inperks-dashboard updated" style="border-left: 4px solid #ffba00">
            <div class="inperks-content">
                <form action="" method="get">
                    <?php if (!$check_review) { ?>
                        <p><?php echo esc_html__('Hi there! You\'ve been using ', ACBWM_PLUGIN_SLUG) . '<strong>' . $name . '</strong>' . esc_html__(' on your site for a few days - I hope it\'s been helpful. If you\'re enjoying our plugin, would you mind rating it 5-stars to help spread the word?', ACBWM_PLUGIN_SLUG) ?></p>
                    <?php } else { ?>
                        <p><?php echo esc_html__('Hi there! You\'ve been using ', ACBWM_PLUGIN_SLUG) . '<strong>' . $name . '</strong>' . esc_html__(' on your site for a few days - I hope it\'s been helpful. Would you want get more features?', ACBWM_PLUGIN_SLUG) ?></p>
                    <?php } ?>
                    <p>
                        <a href="<?php echo esc_url(wp_nonce_url(@add_query_arg(), ACBWM_PLUGIN_SLUG . '_hide_notices', '_inperks_nonce')); ?>"
                           style="padding: 8px;text-decoration:none;color: #ff6161 !important;border: 2px solid #ff6161;font-weight: bold;background: #ffffff;"><?php esc_html_e('Thanks, later', ACBWM_PLUGIN_SLUG) ?></a>
                        <?php if (!$check_review) { ?>
                            <button style="padding: 8px;text-decoration:none;color: #ffffff !important;border: 2px solid #2aa189;font-weight: bold;background: #2aa189;"><?php esc_html_e('Rate now', ACBWM_PLUGIN_SLUG) ?></button>
                            <?php wp_nonce_field(ACBWM_PLUGIN_SLUG . '_wp_reviewed', '_inperks_nonce') ?>
                        <?php } ?>
                        <a target="_self"
                           href="<?php echo esc_url(wp_nonce_url(@add_query_arg(), ACBWM_PLUGIN_SLUG . '_dismiss_notices', '_inperks_nonce')); ?>"><?php esc_html_e('Dismiss', ACBWM_PLUGIN_SLUG) ?></a>
                    </p>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Hide notices
     */
    public function hide_review_notice()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $_inperks_nonce = isset($_GET['_inperks_nonce']) ? wp_unslash(sanitize_text_field($_GET['_inperks_nonce'])) : '';
        if (empty($_inperks_nonce)) {
            return;
        }
        if (wp_verify_nonce($_inperks_nonce, ACBWM_PLUGIN_SLUG . '_dismiss_notices')) {
            update_option(ACBWM_PLUGIN_SLUG . '_dismiss_notices', 1);
        }
        if (wp_verify_nonce($_inperks_nonce, ACBWM_PLUGIN_SLUG . '_hide_notices')) {
            set_transient(ACBWM_PLUGIN_SLUG . ACBWM_VERSION . '_hide_notices', 1, 2592000);
        }
        if (wp_verify_nonce($_inperks_nonce, ACBWM_PLUGIN_SLUG . '_wp_reviewed')) {
            set_transient(ACBWM_PLUGIN_SLUG . ACBWM_VERSION . '_hide_notices', 1, 2592000);
            update_option(ACBWM_PLUGIN_SLUG . '_wp_reviewed', 1);
            ob_start();
            ob_end_clean();
            wp_redirect('https://wordpress.org/support/plugin/inperks-abandoned-cart-recovery/reviews/?rate=5#rate-response');
            die;
        }
    }

    /**
     * Hide notices
     */
    public function hide_trial_notice()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $_inperks_nonce = isset($_GET['_inperks_trial_nonce']) ? wp_unslash(sanitize_text_field($_GET['_inperks_trial_nonce'])) : '';
        if (empty($_inperks_nonce)) {
            return;
        }
        if (wp_verify_nonce($_inperks_nonce, ACBWM_PLUGIN_SLUG . '_dismiss_trial_notices')) {
            update_option(ACBWM_PLUGIN_SLUG . '_dismiss_trial_notices', 1);
        }
    }

    /**
     * links to plugin
     * @param $links
     * @return array
     */
    public function plugin_action_links($links)
    {
        $action_links = array(
            'dashboard' => '<a href="' . admin_url('admin.php?page=acbwm') . '">' . __('Dashboard', ACBWM_TEXT_DOMAIN) . '</a>',
            'carts' => '<a href="' . admin_url('admin.php?page=acbwm-carts') . '">' . __('Carts', ACBWM_TEXT_DOMAIN) . '</a>',
            'reminders' => '<a href="' . admin_url('admin.php?page=acbwm-reminders') . '">' . __('Reminders', ACBWM_TEXT_DOMAIN) . '</a>',
            'settings' => '<a href="' . admin_url('admin.php?page=acbwm-settings') . '">' . __('Settings', ACBWM_TEXT_DOMAIN) . '</a>',
            'upgrade_to_premium' => '<a href="https://inperks.org/product/abandoned-carts-recovery-pro-for-woocommerce/" style="color: red">Upgrade to PRO</a>',
        );
        $action_links = apply_filters(ACBWM_HOOK_PREFIX . 'plugin_action_links', $action_links, $links);
        return array_merge($action_links, $links);
    }

    /**
     * Include any classes we need within admin.
     */
    public function includes()
    {
        /**
         * include lists table
         */
        if (!class_exists('WP_List_Table')) {
            require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
        }
        include_once dirname(__FILE__) . '/class-acbwm-admin-menus.php';
        include_once dirname(__FILE__) . '/class-acbwm-admin-add-ons.php';
        include_once dirname(__FILE__) . '/class-acbwm-admin-settings.php';
        include_once dirname(__FILE__) . '/class-acbwm-admin-emails.php';
        include_once dirname(__FILE__) . '/class-acbwm-admin-carts.php';
        include_once dirname(__FILE__) . '/class-acbwm-admin-dashboard.php';
    }

    /**
     * Init the admin hooks
     */
    public function init_hooks()
    {
        add_action(ACBWM_HOOK_PREFIX . 'admin_page_emails_tab_reminders-list', 'Acbwm_Admin_Reminders::tab_template_lists');
        add_action(ACBWM_HOOK_PREFIX . 'admin_page_emails_tab_add-new-email-reminder', 'Acbwm_Admin_Reminders::tab_add_new_template');
        add_action(ACBWM_HOOK_PREFIX . 'admin_page_emails_tab_edit-email-template', 'Acbwm_Admin_Reminders::tab_edit_template');
        add_action(ACBWM_HOOK_PREFIX . 'admin_page_emails_tab_sent-reminders', 'Acbwm_Admin_Reminders::tab_sent_mails_lists');
        add_action(ACBWM_HOOK_PREFIX . 'admin_page_emails_tab_delete-email-template', 'Acbwm_Admin_Reminders::delete_email_template');
        add_action(ACBWM_HOOK_PREFIX . 'admin_page_cart_tab_display-all-carts', 'Acbwm_Admin_Carts::display_carts_lists');
    }

    /**
     * add the plugin related styles
     */
    function enqueue_scripts()
    {
        if (isset($_GET['page'])) {
            $page = sanitize_text_field($_GET['page']);
            if (acbwm_string_has_prefix($page, 'acbwm')) {
                wp_enqueue_style('acbwm-style', acbwm_get_assets_url('css/jquery_modal.min.css'), array(), ACBWM_VERSION);
                wp_enqueue_style('acbwm-style-jquery-model', acbwm_get_assets_url('css/admin.css'), array(), ACBWM_VERSION);
                wp_enqueue_style('acbwm-style-editor', acbwm_get_assets_url('css/jquery.cleditor.css'), array(), ACBWM_VERSION);
                wp_enqueue_script('acbwm-script-jquery-modal', acbwm_get_assets_url('js/jquery_modal.min.js'), array('jquery'), ACBWM_VERSION);
                wp_enqueue_script('acbwm-editor', acbwm_get_assets_url('js/jquery.cleditor.min.js'), array('jquery'), ACBWM_VERSION);
                wp_enqueue_script('acbwm-script', acbwm_get_assets_url('js/admin.min.js'), array('jquery'), ACBWM_VERSION);
                wp_enqueue_script('acbwm-gchart', 'https://www.gstatic.com/charts/loader.js', array(), ACBWM_VERSION);
                wp_localize_script('acbwm-script', 'acbwm_admin', array(
                    'ajax_url' => admin_url("admin-ajax.php"),
                    'prefix' => ACBWM_HOOK_PREFIX,
                    'i18n' => array(
                        'search_a_coupon' => esc_html__("Search for a coupon", ACBWM_TEXT_DOMAIN),
                        'enter_valid_email' => esc_html__("Please enter valid email", ACBWM_TEXT_DOMAIN),
                        'please_wait' => esc_html__("Please wait...", ACBWM_TEXT_DOMAIN),
                    ),
                    'security' => array(
                        'search_a_coupon' => wp_create_nonce(ACBWM_HOOK_PREFIX . 'search_a_coupon')
                    )
                ));
            }
        }
    }
}

new Acbwm_Admin();