<?php
/*
Plugin Name: My Account V2 (WooCommerce Custome Update)
Description: A Updated version of WooCommerce My Account.
Version: 1.0
Author: Web Potato Pte Ltd
*/

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('MAV2_PATH', plugin_dir_path(__FILE__));
define('MAV2_URL', plugin_dir_url(__FILE__));
define('MAV2_ASSETS_URL', MAV2_URL . 'assets/');
define('MAV2_ASSETS_DIR', MAV2_PATH . 'assets/');
define('MAV2_VERSION', '1.0');
define('MAV2_ASSIST_VER', '1.2.1.37');

// autoload classess
spl_autoload_register(function ($class_name) {
    if (strpos($class_name, 'Stripe') === 0) {
        $path_data = explode("\\", $class_name);
        array_shift($path_data);
        $path = implode('/', $path_data);

        $file = MAV2_PATH . 'vendor/stripe/stripe-php/lib/' . $path . '.php';


        if (file_exists($file)) {
            require_once $file;
            error_log($file);
        }
    }
});

require_once MAV2_PATH . 'include/admin/class-mav2-admin.php';
require_once MAV2_PATH . 'include/admin/class-mav2-ajax-admin.php';
require_once MAV2_PATH . 'include/admin/class-mav2-short-code.php';

function mav2_init()
{

    $mav2 = new MAV2_Admin;
    $mav2->init();
}

add_action('plugins_loaded', 'mav2_init');
