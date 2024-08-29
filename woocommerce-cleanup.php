<?php
/*
Plugin Name: دستیار تست انار
Description: افزونه‌ای برای حذف همه محصولات، ویژگی‌ها و دسته‌بندی‌های ووکامرس، با انحصارات دسته پیش‌فرض محصول.
Version: 1.9
Author: Hamed Movasaqpoor
Text Domain: woocommerce-cleanup
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Load plugin text domain
add_action('plugins_loaded', 'wc_cleanup_load_textdomain');

function wc_cleanup_load_textdomain() {
    load_plugin_textdomain('woocommerce-cleanup', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}


require 'puc/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;


$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/ihamedm/woocommerce-cleanup/',
    __FILE__,
    'woocommerce-cleanup'
);

//Set the branch that contains the stable release.
$myUpdateChecker->setBranch('main');

//Optional: If you're using a private repository, specify the access token like this:
//$myUpdateChecker->setAuthentication('your-token-here');

add_action('admin_enqueue_scripts', 'wc_cleanup_enqueue_scripts');
function wc_cleanup_enqueue_scripts() {
    wp_enqueue_script('wc-cleanup-script', plugin_dir_url(__FILE__) . '/assets/wc-cleanup.js', array('jquery'), null, true);
    wp_localize_script('wc-cleanup-script', 'wc_cleanup_vars', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wc_cleanup_nonce'),
    ));
}


include_once 'includes/cleanup-page.php';


