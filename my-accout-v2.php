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
define('MAV2_VERSION', '1.0');
define('MAV2_ASSIST_VER', '1.2.1.6');

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

// subcription time hook



// Add a custom monthly cron schedule
add_filter('cron_schedules', 'add_monthly_cron_schedule');

function add_monthly_cron_schedule($schedules)
{
    $schedules['monthly'] = [
        'interval' => 30 * DAY_IN_SECONDS, // Approx 1 month
        'display'  => __('Once a Month'),
    ];
    return $schedules;
}

// Schedule the monthly event
add_action('init', 'schedule_monthly_subscription_update');

function schedule_monthly_subscription_update()
{
    error_log('Scheduling monthly subscription update...');

    if (! wp_next_scheduled('update_subscription_renewal_time_monthly')) {
        // Calculate the timestamp for the next 3rd of the month at 8 AM
        $now = new DateTime('now', new DateTimeZone('UTC'));
        $next_month = new DateTime('first day of next month', new DateTimeZone('UTC'));
        $next_month->setDate($next_month->format('Y'), $next_month->format('m'), 1);
        $next_month->setTime(0, 0, 0); // 8 A SGT

        // Schedule the event
        wp_schedule_event($next_month->getTimestamp(), 'monthly', 'update_subscription_renewal_time_monthly');
    }
}

// Hook the custom function to the cron event
add_action('update_subscription_renewal_time_monthly', 'update_all_subscription_renewal_times');

function update_all_subscription_renewal_times()
{
    global $wpdb;
    $sub_sql = "SELECT ID FROM wp_wc_orders WHERE type = 'shop_subscription' AND status = 'wc-active'";

    $sub_ids = $wpdb->get_results($sub_sql);

    // Get all active subscriptions

    foreach ($sub_ids as $post) {

        $subscription = wcs_get_subscription($post->ID);

        // Get the next payment date
        $next_payment_date = $subscription->get_date('next_payment', 'gmt');

        if ($next_payment_date) {
            // Set renewals to 8:00 AM SGT (00:00 UTC)
            $desired_time = '00:00:00'; // UTC time is midnight, equivalent to 8:00 AM SGT
            $next_payment_date = date('Y-m-03', strtotime($next_payment_date)) . ' ' . $desired_time;

            // Update the subscription's next payment date
            $subscription->update_dates([
                'next_payment' => $next_payment_date,
            ], 'gmt');
        }
    }
}


