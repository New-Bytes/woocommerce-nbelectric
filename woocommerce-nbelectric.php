<?php
/*
Plugin Name: Conector NBElectric
Description: Sincroniza los productos del catálogo de NBElectric con WooCommerce.
Author: NBElectric
Author URI: https://nbe.com.ar
Version: 1.0.0
*/

if (!defined('ABSPATH')) {
    exit;
}

define('API_URL_NBE', 'https://api.nbe.com.ar/v1');
define('VERSION_NBE', '1.0.0');
// Repositorio GitHub usado por el auto-actualizador (owner/repo).
if (!defined('NBE_GITHUB_REPO')) {
    define('NBE_GITHUB_REPO', 'New-Bytes/woocommerce-nbelectric');
}

// Incluye los archivos necesarios
require_once plugin_dir_path(__FILE__) . 'includes/admin-hooks.php';
require_once plugin_dir_path(__FILE__) . 'includes/helpers.php';
require_once plugin_dir_path(__FILE__) . 'includes/utils.php';
require_once plugin_dir_path(__FILE__) . 'includes/product-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/product-mapper.php';
require_once plugin_dir_path(__FILE__) . 'includes/product-warranty.php';
require_once plugin_dir_path(__FILE__) . 'includes/cron-hooks.php';
require_once plugin_dir_path(__FILE__) . 'includes/cron-health.php';
require_once plugin_dir_path(__FILE__) . 'includes/rest-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/product-sync.php';
require_once plugin_dir_path(__FILE__) . 'includes/product-delete.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings-v3.php';
require_once plugin_dir_path(__FILE__) . 'includes/logs-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/logs-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/sync-ajax.php';
require_once plugin_dir_path(__FILE__) . 'includes/description-sync.php';

// Hooks de activación y desactivación

add_action('wp_ajax_nbe_update_description_products', 'nbe_update_description_products');
add_action('wp_ajax_nbe_delete_products', 'nbe_delete_products');
add_action('wp_ajax_nbe_get_log_data', 'nbe_get_log_data');
add_action('wp_ajax_nbe_download_log', 'nbe_download_log');
add_action('wp_ajax_nbe_cleanup_logs', 'nbe_cleanup_logs');
add_action('admin_post_nbe_delete_products', 'nbe_delete_products');
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'nbe_plugin_action_links');
add_action('admin_menu', 'nbe_menu');
add_action('admin_init', 'nbe_register_settings');

register_activation_hook(__FILE__, 'nbe_activation');
register_deactivation_hook(__FILE__, 'nbe_deactivation');
register_uninstall_hook(__FILE__, 'nbe_uninstall');
