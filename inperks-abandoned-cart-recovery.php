<?php
/**
 * Plugin Name: Abandoned cart recovery for WooCommerce by Inperks
 * Plugin URI: https://inperks.org/product/abandoned-carts-recovery-pro-for-woocommerce/
 * Description: Recover lost sales immediately by sending the series of reminder emails to the customers.
 * Version: 1.0.1
 * Slug: inperks-abandoned-cart-recovery
 * Author: inperks
 * Author URI: https://inperks.org/
 * License: GPL3 or Later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: inperks-abandoned-cart-recovery
 * Domain Path: /i18n/languages/
 * WC requires at least: 3.7.0
 * WC tested up to: 4.3.1
 */
//TODO:un subscribe,
defined('ABSPATH') || exit;
if (!defined('ACBWM_PLUGIN_FILE')) {
    define('ACBWM_PLUGIN_FILE', __FILE__);
}
if (!defined('ACBWM_PLUGIN_SLUG')) {
    define('ACBWM_PLUGIN_SLUG', 'abandoned-cart-recovery-for-woocommerce-by-inperks');
}
// Include the main inperks class.
if (!class_exists('Acbwm', false)) {
    include_once dirname(ACBWM_PLUGIN_FILE) . '/includes/class-acbwm.php';
}
/**
 * Returns the main instance of ACBWM.
 *
 * @return Acbwm
 */
function ACBWM()
{
    return Acbwm::instance();
}

global $inperks_abandoned_carts;
// Global for backwards compatibility.
$inperks_abandoned_carts = ACBWM();
