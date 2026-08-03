<?php

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Shared tracking-number helpers, moved out of views/shortcodes/user_subscriptions.php
// so the orders shortcode and the order-details AJAX handler can reuse them.

if (!function_exists('mav2_get_tracking')) {
    function mav2_get_tracking($order)
    {
        if ($order->tracking == 404) {
            $order->tracking = 'Tracking pending';
        } else {

            // Regex patterns
            $pattern_JP = '/([A-Z]+)([\d+]+)JP/';
            $pattern_US = '/\b(\d+)\b/';

            if (preg_match($pattern_JP, $order->tracking, $matches)) {
                $trackingNumber = $matches[0];

                // Build the URL
                $link = 'https://trackings.post.japanpost.jp/services/srv/search/?requestNo1=' . $trackingNumber . '&search.x=68&search.y=17&search=Tracking+start&locale=ja&startingUrlPatten=';

                // 1. Use escaped double quotes (\") for HTML attributes
                // 2. Added rel="noopener noreferrer" for security with target="_blank"
                $order->tracking = "<a href=\"{$link}\" target=\"_blank\" rel=\"noopener noreferrer\">{$trackingNumber}</a>";

                // 2026-05-08 temporty using just tracking number
                $order->tracking = $trackingNumber;


            } elseif (preg_match($pattern_US, $order->tracking, $matches)) {
                // US Tracking
                $trackingNumber = $matches[1];
                $us_link = 'https://parcelsapp.com/en/tracking/' . $trackingNumber;

                $order->tracking = "<a href=\"{$us_link}\" target=\"_blank\" rel=\"noopener noreferrer\">{$trackingNumber}</a>";
                // 2026-05-08 temporty using just tracking number
                $order->tracking = $trackingNumber;
            }
        }

        return $order->tracking;
    }
}

if (!function_exists('mav2_get_order_tracking_code')) {
    /**
     * Latest tracking code for an order, parsed to the bare number.
     * Returns '' when there is no order id or no "Tracking number" note yet.
     */
    function mav2_get_order_tracking_code($order_id)
    {
        global $wpdb;

        $order_id = intval($order_id);
        if (!$order_id) {
            return '';
        }

        $note = $wpdb->get_var($wpdb->prepare(
            "SELECT comment_content FROM wp_comments
            WHERE comment_post_ID = %d AND comment_content LIKE '%%Tracking number%%'
            ORDER BY comment_date_gmt DESC LIMIT 1",
            $order_id
        ));

        if (empty($note)) {
            return '';
        }

        $holder = new stdClass();
        $holder->tracking = $note;

        return wp_strip_all_tags((string) mav2_get_tracking($holder));
    }
}
