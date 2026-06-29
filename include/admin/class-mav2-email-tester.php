<?php
if (!defined('ABSPATH')) exit;

class MAV2_Email_Tester
{
    // -----------------------------------------------------------------------
    // Core renderer
    // -----------------------------------------------------------------------

    public static function send($to, $subject, $template, $data = [])
    {
        $template_path = MAV2_PATH . "views/emails/{$template}.php";

        if (!file_exists($template_path)) {
            throw new \Exception("MAV2 email template not found: {$template}");
        }

        ob_start();
        extract($data);
        include $template_path;
        $message = ob_get_clean();

        $headers = ['Content-Type: text/html; charset=UTF-8'];
        wp_mail($to, $subject, $message, $headers);
    }

    // -----------------------------------------------------------------------
    // Per-template test methods
    // -----------------------------------------------------------------------

    public static function send_cancel_test($sub_id, $to, $lang = 'en')
    {
        $subscription = wcs_get_subscription($sub_id);
        if (!$subscription) throw new \Exception("Subscription $sub_id not found.");

        $current_plan = max(1, intval($subscription->get_meta('_ps_prepaid_pieces')));
        $date = new DateTime('now', new DateTimeZone('Asia/Singapore'));

        if ($lang === 'cn') {
            $cancelled_date = $date->format('Y年m月d日 H:i T');
            $plan    = "{$current_plan} 个月套餐";
            $subject = "我們很難過您要離開！ 😢您的點心禮盒訂閱已取消。";
        } else {
            $cancelled_date = $date->format('M d, Y H:i T');
            $plan    = $current_plan === 1 ? "{$current_plan} Month Plan" : "{$current_plan} Months Plan";
            $subject = "We're sad to see you go! 😢Your Omiyage Snack Box Subscription is cancelled.";
        }

        $data = [
            'name'           => trim($subscription->get_shipping_first_name() . ' ' . $subscription->get_shipping_last_name()),
            'cancelled_date' => $cancelled_date,
            'plan'           => $plan,
        ];

        self::send($to, $subject, "subscription_cancel_$lang", $data);
    }

    public static function send_address_update_test($sub_id, $to, $lang = 'en')
    {
        // template is static HTML with no PHP variables
        $subjects = ['en' => 'Address Updated', 'cn' => '地址已更新'];
        self::send($to, $subjects[$lang] ?? $subjects['en'], "customer_address_update/$lang");
    }

    public static function send_upgrade_confirm_test($sub_id, $to, $lang = 'en')
    {
        $subscription = wcs_get_subscription($sub_id);
        if (!$subscription) throw new \Exception("Subscription $sub_id not found.");

        $current_plan = max(1, intval($subscription->get_meta('_ps_prepaid_pieces')));
        $plans        = self::get_prepaid_details($subscription->get_currency());
        $plan_numbers = array_values(array_unique(array_column($plans, 'plan')));
        sort($plan_numbers);

        // pick a different plan to simulate an upgrade/downgrade request
        $idx          = array_search($current_plan, $plan_numbers);
        $new_plan_num = ($idx !== false && isset($plan_numbers[$idx + 1]))
            ? $plan_numbers[$idx + 1]
            : $plan_numbers[0];

        $new_plan_obj = null;
        foreach ($plans as $p) {
            if ((int) $p['plan'] === (int) $new_plan_num) {
                $new_plan_obj = $p;
                break;
            }
        }

        $currency    = self::get_formatted_currency($subscription);
        $renew_dates = self::get_subscription_renewal_date($subscription);

        $data = [
            'name'     => trim($subscription->get_shipping_first_name() . ' ' . $subscription->get_shipping_last_name()),
            'new_plan' => $new_plan_num . ' Month' . ($new_plan_num > 1 ? 's' : ''),
            'price'    => $currency . ($new_plan_obj['price'] ?? '0.00'),
            'end_date' => $renew_dates['next_renew_At'],
        ];

        $subject = $lang === 'cn' ? "您的訂閱已更新！" : "Your Subscription Has Been Updated!";
        self::send($to, $subject, "customer_sub_upgrade_confirm_$lang", $data);
    }

    public static function send_coupon_accepted_test($sub_id, $to, $lang = 'en')
    {
        $subscription = wcs_get_subscription($sub_id);
        if (!$subscription) throw new \Exception("Subscription $sub_id not found.");

        $current_plan = max(1, intval($subscription->get_meta('_ps_prepaid_pieces')));
        $coupon_code  = JRC_Helper::get_setting('cancelling_coupon_' . $current_plan . 'm', '');

        if (!$coupon_code) {
            throw new \Exception("No cancellation coupon configured for {$current_plan}m plan.");
        }

        $coupon        = new WC_Coupon($coupon_code);
        $coupon_amount = $coupon->get_amount();
        $is_percent    = strpos($coupon->get_discount_type(), 'percent') !== false;

        $plan_details = self::get_prepaid_plan_by_sub($subscription);
        $currency     = self::get_formatted_currency($subscription);
        $price        = floatval($plan_details['price'] ?? 0);
        $saving       = $is_percent
            ? number_format($price * $coupon_amount / 100, 2)
            : $coupon_amount;

        $renew_dates = self::get_subscription_renewal_date($subscription);

        $data = [
            'name'             => $subscription->get_billing_first_name(),
            'plan'             => $plan_details['name'] ?? ($current_plan . ' Month Plan'),
            'discount'         => $is_percent ? $coupon_amount . '%' : $currency . $coupon_amount,
            'original_price'   => $currency . number_format($price, 2),
            'discounted_price' => $currency . number_format($price - $saving, 2),
            'savings'          => $currency . $saving,
            'effective_from'   => $renew_dates['next_renew_At'],
            'effective_from_n' => $renew_dates['next_renew_At_n'],
        ];

        $subject = $lang === 'cn' ? "🎉 您的優惠折扣已啟動！" : "🎉 You've Activated Your Discount!";
        self::send($to, $subject, "coupon_accepted_$lang", $data);
    }

    public static function send_admin_upgrade_confirm_test($sub_id, $to)
    {
        $subscription = wcs_get_subscription($sub_id);
        if (!$subscription) throw new \Exception("Subscription $sub_id not found.");

        $current_plan = max(1, intval($subscription->get_meta('_ps_prepaid_pieces')));
        $plans        = self::get_prepaid_details($subscription->get_currency());
        $plan_numbers = array_values(array_unique(array_column($plans, 'plan')));
        sort($plan_numbers);

        $idx          = array_search($current_plan, $plan_numbers);
        $new_plan_num = ($idx !== false && isset($plan_numbers[$idx + 1]))
            ? $plan_numbers[$idx + 1]
            : $plan_numbers[0];

        $new_plan_obj = null;
        foreach ($plans as $p) {
            if ((int) $p['plan'] === (int) $new_plan_num) {
                $new_plan_obj = $p;
                break;
            }
        }

        $currency    = self::get_formatted_currency($subscription);
        $renew_dates = self::get_subscription_renewal_date($subscription);
        $update_dir  = $new_plan_num > $current_plan ? 'upgrade' : 'downgrade';

        $data = [
            'customer_name'  => trim($subscription->get_shipping_first_name() . ' ' . $subscription->get_shipping_last_name()),
            'customer_email' => $subscription->get_billing_email(),
            'current_plan'   => $current_plan . ' Month' . ($current_plan > 1 ? 's' : ''),
            'new_plan'       => $new_plan_num . ' Month' . ($new_plan_num > 1 ? 's' : ''),
            'price'          => $currency . ($new_plan_obj['price'] ?? '0.00'),
            'end_date'       => $renew_dates['next_renew_At'],
        ];

        self::send($to, "Subscription $update_dir request", 'admin_sub_upgrade_confirm_en', $data);
    }

    public static function send_admin_upgrade_cancelled_test($sub_id, $to)
    {
        $subscription = wcs_get_subscription($sub_id);
        if (!$subscription) throw new \Exception("Subscription $sub_id not found.");

        // use a real pending request if one exists, otherwise use dummy values
        $pending      = get_option('webp_subscription_update_request', []);
        $request      = $pending[$sub_id] ?? null;
        $current_plan = max(1, intval($subscription->get_meta('_ps_prepaid_pieces')));

        $cp_num   = $request ? intval($request['current_plan']) : $current_plan;
        $np_num   = $request ? intval($request['new_plan'])     : ($current_plan === 1 ? 3 : 1);

        $data = [
            'name'         => trim($subscription->get_billing_first_name() . ' ' . $subscription->get_billing_last_name()),
            'email'        => $request['email'] ?? $subscription->get_billing_email(),
            'current_plan' => $cp_num . ' Month' . ($cp_num > 1 ? 's' : ''),
            'new_plan'     => $np_num . ' Month' . ($np_num > 1 ? 's' : ''),
        ];

        self::send($to, "Customer Cancelled Change of Plan Request", 'admin_sub_upgrade_cancelled', $data);
    }

    // -----------------------------------------------------------------------
    // Helpers — static copies of the private methods in MAV2_Ajax_Admin
    // -----------------------------------------------------------------------

    private static function get_formatted_currency($subscription)
    {
        $symbol = get_woocommerce_currency_symbol($subscription->currency);
        $name   = $subscription->currency;
        return $name !== 'TWD' ? $name . ' ' . $symbol : $symbol;
    }

    private static function get_subscription_renewal_date($subscription)
    {
        $plan             = max(1, intval($subscription->get_meta('_ps_prepaid_pieces')));
        $remaining        = intval($subscription->get_meta('_ps_prepaid_renewals_available'));
        $parent_order_id  = $subscription->get_parent_id();
        $fulfilled        = $subscription->get_meta('_subscription_renewal_order_ids_cache');
        $fulfilled        = is_array($fulfilled) ? $fulfilled : [];
        $fulfilled        = array_merge($fulfilled, [$parent_order_id]);

        $last_order       = wc_get_order($fulfilled[0]);
        $sub_start_date   = $last_order->get_date_created()->date('Y-m-01');
        $months_ahead     = $plan > 1 ? $remaining + 1 : 1;
        $_next_renew      = date('Y-m-d', strtotime($sub_start_date . ' + ' . $months_ahead . ' month'));

        $country  = $subscription->get_shipping_country();
        $ch_list  = ['TW', 'HK', 'CN'];
        $ko_list  = ['KO'];

        if (in_array($country, $ch_list)) {
            $next_renew_At   = date('Y年m月3日', strtotime($_next_renew));
            $next_renew_At_n = $next_renew_At;
        } elseif (in_array($country, $ko_list)) {
            $next_renew_At   = date('Y년m월3일', strtotime($_next_renew));
            $next_renew_At_n = $next_renew_At;
        } else {
            $next_renew_At   = date('jS \of F Y', strtotime($_next_renew));
            $next_renew_At_n = date('3 F Y', strtotime($_next_renew));
        }

        return ['next_renew_At' => $next_renew_At, 'next_renew_At_n' => $next_renew_At_n];
    }

    private static function get_prepaid_details($_currency = '')
    {
        global $wpdb;
        $product_id     = 198;
        $wc_product     = wc_get_product($product_id);
        $prepaid_plans  = get_post_meta($product_id, '_ps_prepaid_plans', true);
        $per_month_price = self::get_exchange_rate(floatval($wc_product->price), $_currency);

        $list = [[
            'id'             => 'XPCgf',
            'product_id'     => $product_id,
            'type'           => 1,
            'name'           => '1 Month',
            'plan'           => 1,
            'price'          => number_format($per_month_price, 2, '.', ''),
            'price_per_month'=> number_format($per_month_price, 2, '.', ''),
            'has_saving'     => false,
            'save'           => '0.00',
            'discount'       => '0',
        ]];

        foreach ((array) $prepaid_plans as $v) {
            $pieces   = intval($v['prepaid_pieces']);
            $discount = floatval($v['discount']);
            $factor   = 1.00 - ($discount / 100);
            $price    = $per_month_price * $pieces * $factor;
            $save     = round($per_month_price * $pieces - $price, 2);

            $list[] = [
                'id'               => $v['slug'],
                'type'             => 2,
                'product_id'       => $product_id,
                'name'             => $v['plan_name'],
                'price'            => number_format($price, 2, '.', ''),
                'price_per_month'  => number_format($per_month_price * $factor, 2, '.', ''),
                'has_saving'       => $save > 0,
                'save'             => number_format($save, 2, '.', ''),
                'discount'         => number_format($discount, 0, '.', ''),
                'price_no_discount'=> number_format(round($per_month_price * $pieces), 2),
                'plan'             => $pieces,
            ];
        }

        return array_reverse($list);
    }

    private static function get_prepaid_plan_by_sub($subscription)
    {
        $current_plan = max(1, intval($subscription->get_meta('_ps_prepaid_pieces')));
        $plans        = self::get_prepaid_details($subscription->get_currency());

        foreach ($plans as $p) {
            if ((int) $p['plan'] === $current_plan) return $p;
        }

        return null;
    }

    private static function get_exchange_rate($val = 0, $currency = 'SGD')
    {
        $currency_list = get_option('_transient_yay-currencies-transient', []);
        $rates         = [];
        foreach ($currency_list as $c) {
            $rates[$c->post_title] = floatval(get_post_meta($c->ID, 'rate', true));
        }
        $currency = strtoupper($currency);
        return isset($rates[$currency]) ? floatval($val) * $rates[$currency] : floatval($val);
    }
}
