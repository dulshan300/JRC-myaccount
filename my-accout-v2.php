<?php
/*
Plugin Name: WooCommerce My Account V2
Description: A Updated version of WooCommerce My Account.
Version: 1.0
Author: Web Potato Pte Ltd 
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('MAV2_PATH', plugin_dir_path(__FILE__));
define('MAV2_URL', plugin_dir_url(__FILE__));
define('MAV2_ASSETS_URL', MAV2_URL . 'assets/');
define('MAV2_VERSION', '1.0');


require_once MAV2_PATH . 'include/admin/class-mav2-admin.php';
require_once MAV2_PATH . 'include/admin/class-mav2-ajax-admin.php';
require_once MAV2_PATH . 'include/admin/class-mav2-short-code.php';



function mav2_init()
{
    $mav2 = new MAV2_Admin();
    $mav2->init();
}

add_action('plugins_loaded', 'mav2_init');


add_action('woocommerce_payment_token_deleted', 'check_custom_token_removal', 10, 2);

function check_custom_token_removal($token_id, $token) {
    if ($token->get_gateway_id() === 'stripe') {
        // Log or handle the token deletion
        error_log('Custom Stripe token deleted: ' . $token_id);
    }
}
