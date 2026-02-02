<?php

/**
 * Prevent direct access to the file.
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// use mav2_{function_name} as action with js ajax

final class MAV2_Ajax_Admin
{
    private static $_currency_recored = null;

    public function mav2_test()
    {
        error_log('Testing AJAX call...');

        wp_send_json_success(['success']);
    }

    public function cancel_subscription()
    {
        $user_id = get_current_user_id();

        if (empty($user_id)) {
            wp_send_json_error('Please login');

            return;
        }

        global $wpdb;

        $subId = $_POST['id'];
        $note = sanitize_text_field($_POST['r_text']);

        $sql = "SELECT
                od.id,
                od.status,
                COALESCE(
                    (
                        SELECT
                            meta_value
                        FROM
                            wp_wc_orders_meta
                        WHERE
                            order_id = od.id
                            AND meta_key = '_ps_prepaid_pieces'
                        LIMIT
                            1
                    ), 1
                ) AS plan
            from
                wp_wc_orders od
            WHERE
                od.id = $subId";

        $sub = $wpdb->get_row($sql);

        $subscription = wcs_get_subscription($subId);

        $note_text = "";

        if (intval($sub->plan) > 1) {
            // prepaid cancel
            // $subscription->set_status('active');
            $subscription->update_meta_data('_ps_scheduled_to_be_cancelled', 'yes');
            $note_text = 'Subscription scheduled to be cancelled by the user.';
        } else {
            // sub cancel
            // $subscription->set_status('cancelled');
            $subscription->update_status('pending-cancel');
            $note_text = 'Subscription cancelled by the user.';
        }

        // for system note saving
        $subscription->save();

        // for custom note saving
        $subscription->add_order_note($note_text . ' Reason: ' . $note);

        $subscription->save();

        // send mail to the user
        // Prepare user data for emails
        $country = $subscription->get_shipping_country();

        $lang = JRC_Helper::get_lang($country);

        $user_email = $subscription->get_billing_email();

        $user_data = [
            'name' => $subscription->get_shipping_first_name() . " " . $subscription->get_shipping_last_name(),
        ];

        $subject = $lang == 'cn' ? "我們很難過您要離開！ 😢您的點心禮盒訂閱已取消。" : "We're sad to see you go! 😢Your Omiyage Snack Box Subscription is cancelled.";

        $this->send_html_email(
            $user_email,
            $subject,
            "subscription_cancel_$lang",
            $user_data
        );


        wp_send_json_success('Successfully Cancelled');
    }

    public function user_address_update()
    {
        global $wpdb;

        $user_id = get_current_user_id();

        if (empty($user_id)) {
            wp_send_json_error('Please login');

            return;
        }

        // wp_send_json($_POST);

        $billing_first_name = sanitize_text_field($_POST['billing_first_name']);
        $billing_last_name = sanitize_text_field($_POST['billing_last_name']);
        // $billing_email = sanitize_email($_POST['billing_email']);
        // $billing_phone = sanitize_text_field($_POST['billing_phone']);
        $billing_address_1 = sanitize_text_field($_POST['billing_address_1']);
        // $billing_address_2 = sanitize_text_field($_POST['billing_address_2']);
        $billing_city = sanitize_text_field($_POST['billing_city']);
        $billing_postcode = sanitize_text_field($_POST['billing_postcode']);
        $billing_country = sanitize_text_field($_POST['billing_country']);
        $pccc = sanitize_text_field($_POST['pccc']);

        // validation errors
        $validation_erros = [];

        if (strlen($billing_first_name) < 3) {
            $validation_erros['billing_first_name'] = 'First name is required';
        }

        if (strlen($billing_last_name) < 3) {
            $validation_erros['billing_last_name'] = 'Last name is required';
        }

        // if (! filter_var($billing_email, FILTER_VALIDATE_EMAIL)) {
        //     $validation_erros['billing_email'] = 'Email is required';
        // }

        $pattern = '/^\+?[0-9]{1,3}(\s*|-)?\(?\d{1,4}\)?(\s*|-)?[0-9]{1,5}(\s*|-)?[0-9]{1,5}(\s*|-)?[0-9]{1,5}$/';

        // if (preg_match($pattern, $billing_phone)) {
        // } else {
        //     $validation_erros['billing_phone'] = 'Phone is required';
        // }

        if (strlen($billing_address_1) < 3) {
            $validation_erros['billing_address_1'] = 'Address is required';
        }

        // if (strlen($billing_city) < 3) {
        //     $validation_erros['billing_city'] = 'City is required';
        // }

        $pattern = '/^[a-zA-Z0-9\s\-]{3,10}$/';

        if (preg_match($pattern, $billing_postcode)) {
        } else {
            $validation_erros['billing_postcode'] = 'Postcode is required';
        }

        if (empty($billing_country)) {
            $validation_erros['billing_country'] = 'Country is required';
        }

        if (!empty($validation_erros)) {
            wp_send_json($validation_erros, 422);

            return;
        }

        update_user_meta($user_id, 'billing_first_name', $billing_first_name);
        update_user_meta($user_id, 'billing_last_name', $billing_last_name);
        // update_user_meta($user_id, 'billing_email', $billing_email);
        // update_user_meta($user_id, 'billing_phone', $billing_phone);
        update_user_meta($user_id, 'billing_address_1', $billing_address_1);
        // update_user_meta($user_id, 'billing_address_2', $billing_address_2);
        update_user_meta($user_id, 'billing_city', $billing_city);
        update_user_meta($user_id, 'billing_postcode', $billing_postcode);
        update_user_meta($user_id, 'billing_country', $billing_country);

        update_user_meta($user_id, 'shipping_first_name', $billing_first_name);
        update_user_meta($user_id, 'shipping_last_name', $billing_last_name);
        // update_user_meta($user_id, 'shipping_email', $billing_email);
        // update_user_meta($user_id, 'shipping_phone', $billing_phone);
        update_user_meta($user_id, 'shipping_address_1', $billing_address_1);
        // update_user_meta($user_id, 'shipping_address_2', $billing_address_2);
        update_user_meta($user_id, 'shipping_city', $billing_city);
        update_user_meta($user_id, 'shipping_postcode', $billing_postcode);
        update_user_meta($user_id, 'shipping_country', $billing_country);

        // also need to update all the subscription billing / shipping address

        // collect all active subscriptions
        $sql = "SELECT id FROM wp_wc_orders WHERE`type`='shop_subscription'and customer_id = $user_id";
        $subscriptions = $wpdb->get_results($sql);

        $customer = new WC_Customer($user_id);

        foreach ($subscriptions as $sub) {
            $sub_id = $sub->id;
            $sub = wc_get_order($sub_id);
            $sub->set_address($customer->get_billing(), 'billing');
            $sub->set_address($customer->get_billing(), 'shipping');
            $sub->save();
            $order_ids[] = $sub->get_id();
        }

        // update pccc

        $sql = "SELECT id FROM wp_wc_orders WHERE customer_id = $user_id";

        $orders = $wpdb->get_results($sql);

        $order_ids = [];

        foreach ($orders as $sub) {
            $sub_id = $sub->id;
            $sub = wc_get_order($sub_id);
            $sub->update_meta_data('pccc-dual', $pccc);
            $sub->update_meta_data('sk_pccc', $pccc);
            $sub->save();
        }

        // send email
        $email = $customer->get_email();
        $country = $customer->get_billing_country();

        $lang = JRC_Helper::get_lang($country);
        $subjects = [
            'en' => 'Address Updated',
            'cn' => '地址已更新'
        ];
        $subject = $subjects[$lang];

        $this->send_html_email($email, $subject, "customer_address_update/$lang");

        wp_send_json_success('ok');
    }

    public function user_account_details_update_form()
    {
        $user = wp_get_current_user();

        if (!is_user_logged_in()) {
            wp_send_json_error('Please login');

            return;
        }

        $user_id = $user->ID;

        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['email']);
        $display_name = sanitize_text_field($_POST['display_name']);

        // validation errors
        $validation_erros = [];

        if (strlen($first_name) < 3) {
            $validation_erros['first_name'] = 'First name is required';
        }

        if (strlen($display_name) < 3) {
            $validation_erros['display_name'] = 'Display name is required';
        }

        if (strlen($last_name) < 3) {
            $validation_erros['last_name'] = 'Last name is required';
        }

        if (empty($email)) {
            $validation_erros['ua_email'] = 'Email is required';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $validation_erros['ua_email'] = 'Email is invalid';
        }

        // check if email already exists

        $email_check = email_exists($email);

        if (!$email_check === false & $email_check !== $user_id) {
            $validation_erros['ua_email'] = 'Email already exists';
        }

        if (!empty($validation_erros)) {
            wp_send_json($validation_erros, 422);

            return;
        }

        update_user_meta($user_id, 'first_name', $first_name);
        update_user_meta($user_id, 'last_name', $last_name);

        $data = ['ID' => $user_id, 'display_name' => $display_name, 'user_email' => $email];

        wp_update_user($data);

        wp_send_json_success(['success']);
    }

    public function user_password_update_form()
    {
        $user = wp_get_current_user();

        if (!is_user_logged_in()) {
            wp_send_json_error('Please login');

            return;
        }

        $current_password = sanitize_text_field($_POST['current_password']);
        $new_password = sanitize_text_field($_POST['new_password']);
        $confirm_password = sanitize_text_field($_POST['confirm_password']);

        $validation_erros = [];
        // validating password
        // check current password

        if (!wp_check_password($current_password, $user->user_pass, $user->ID)) {

            $validation_erros['current_password'] = 'Current password is incorrect';
        }

        // check password strength

        if (strlen($new_password) < 8) {
            $validation_erros['new_password'] = 'Password must be at least 8 characters';
        } elseif ($new_password != $confirm_password) {
            // check confirm password match
            $validation_erros['confirm_password'] = 'Confirm password does not match';
        }

        if (count($validation_erros) > 0) {
            wp_send_json($validation_erros, 422);
        }

        // if all ok update user password

        wp_set_password($new_password, $user->ID);

        wp_send_json_success(['success']);
    }

    private function user_get_payment_methods($uid = null)
    {

        $user_id = $uid;
        if ($user_id == null || empty($user_id)) {
            $user_id = get_current_user_id();
        }

        $tokens = WC_Payment_Tokens::get_customer_tokens($user_id);

        error_log("tokens: " . print_r($tokens, true));

        $token_details = [];

        foreach ($tokens as $token) {
            // delete token
            $token_details[] = [
                'id' => $token->get_id(),
                'card_type' => ucfirst($token->get_card_type()),
                'last4' => $token->get_last4(),
                'expiry' => $token->get_expiry_month() . '/' . $token->get_expiry_year(),
                'user_id' => $token->get_user_id(),
            ];
        }

        return $token_details;
    }

    public function user_add_payment_method()
    {
        /*
        woocommerce-gateway-stripe\includes\class-wc-stripe-payment-tokens.php
        function: woocommerce_get_customer_upe_payment_tokens
        line: 337
        code to remove all the token not exist on stripe

        woocommerce_get_customer_payment_tokens_legacy
        line: 160
        */
        global $wpdb;
        $keys = $this->get_stripe_keys();
        $stripe = new \Stripe\StripeClient($keys['secret_key']);
        $source = $_POST['token'];
        $card = $source['card'];
        $payment_method_id = $source['id'];

        $user = wp_get_current_user();

        // get user options
        $customer_id = get_user_meta(get_current_user_id(), 'wp__stripe_customer_id', true);

        if (!empty($customer_id)) {

            try {
                // check customer in stripe account
                $customer = $stripe->customers->retrieve($customer_id);
            } catch (\Exception $ex) {
                $customer_id = '';
            }
        }

        if (empty($customer_id)) {

            $customer = $stripe->customers->create([
                'name' => $user->display_name,
                'email' => $user->user_email,
                'description' => 'Customer for ' . $user->display_name,
            ]);

            update_user_meta(get_current_user_id(), 'wp__stripe_customer_id', $customer->id);

            $customer_id = $customer->id;
        }

        error_log('Customer ID: ' . $customer_id);
        error_log('Token ID: ' . $payment_method_id);

        // attach payment method to customer
        error_log('Attaching payment method to customer');

        $stripe->paymentMethods->attach(
            $payment_method_id,
            ['customer' => $customer_id]
        );

        // getting payment method form stripe
        $pm = $stripe->paymentMethods->retrieve($payment_method_id);
        error_log('Payment method form stripe: ' . print_r($pm, true));

        // add payment method to woocommerce
        error_log('Adding payment method to woocommerce');

        if (class_exists('WC_Stripe_Payment_Tokens')) {
            $stripe_tokens_instance = WC_Stripe_Payment_Tokens::get_instance();

            // Remove the action for retrieving customer payment tokens
            remove_action('woocommerce_get_customer_payment_tokens', [$stripe_tokens_instance, 'woocommerce_get_customer_payment_tokens'], 10, 3);

            remove_action('woocommerce_payment_token_deleted', [$stripe_tokens_instance, 'woocommerce_payment_token_deleted'], 10, 2);

            // Optionally, log or confirm that the action has been removed
            error_log('Removed Stripe woocommerce_get_customer_payment_tokens action.');
        }

        $pt = new WC_Payment_Token_CC();
        $pt->set_token($payment_method_id);
        $pt->set_gateway_id(WC_Stripe_UPE_Payment_Gateway::ID);
        $pt->set_card_type(strtolower($card['brand']));
        $pt->set_last4($card['last4']);
        $pt->set_expiry_month($card['exp_month']);
        $pt->set_expiry_year($card['exp_year']);
        $pt->set_user_id($user->ID);
        $pt->set_default(true);

        if ($pt->validate()) {
            error_log('Saving payment method to woocommerce');
            $pt->save();
        } else {
            error_log(print_r($pt->get_error_messages(), true));
        }

        $stripe_customer = new WC_Stripe_Customer($user->ID);
        $stripe_sources = $stripe_customer->get_sources();

        if (class_exists('WC_Stripe_Payment_Tokens')) {
            $stripe_tokens_instance = WC_Stripe_Payment_Tokens::get_instance();

            // Remove the action for retrieving customer payment tokens
            add_action('woocommerce_get_customer_payment_tokens', [$stripe_tokens_instance, 'woocommerce_get_customer_payment_tokens'], 10, 3);

            add_action('woocommerce_payment_token_deleted', [$stripe_tokens_instance, 'woocommerce_payment_token_deleted'], 10, 2);

            // Optionally, log or confirm that the action has been removed
            error_log('Add Stripe woocommerce_get_customer_payment_tokens action.');
        }

        // update existing subscriptions with new payment method
        $sql = "SELECT * FROM wp_wc_orders
        WHERE type = 'shop_subscription' 
        AND customer_id = %d";

        $subscriptions = $wpdb->get_results($wpdb->prepare($sql, get_current_user_id()));

        foreach ($subscriptions as $subscription) {
            $order = wc_get_order($subscription->id);
            $order->set_payment_method('stripe');
            $order->update_meta_data('_payment_method_title', 'Credit Card (Stripe)');
            $order->update_meta_data('_stripe_customer_id', $customer_id);
            $order->update_meta_data('_stripe_source_id', $payment_method_id);
            $order->set_requires_manual_renewal(false);
            $order->save();
        }

        wp_send_json($this->user_get_payment_methods());
    }

    public function user_delete_payment_method()
    {

        $tokens = $this->user_get_payment_methods();
        if (count($tokens) < 2) {
            return wp_send_json_error('You need at least two payment methods to delete one.');
        }

        $keys = $this->get_stripe_keys();
        $stripe = new \Stripe\StripeClient($keys['secret_key']);

        // get payment token
        $token_id = $_POST['id'];

        $pt = WC_Payment_Tokens::get($token_id);

        // delete token
        try {
            $stripe->paymentMethods->detach($pt->get_token());
        } catch (\Exception $e) {
        }

        $pt->delete();

        wp_send_json_success($this->user_get_payment_methods());
    }

    private function get_stripe_keys()
    {
        // check if stripe is installed
        $has_stripe = false;
        $installed_payment_methods = WC()->payment_gateways()->payment_gateways();
        foreach ($installed_payment_methods as $key => $value) {
            if ($key == 'stripe') {
                $has_stripe = true;
            }
        }

        if (!$has_stripe) {
            return [
                'has_stripe' => $has_stripe,
                'secret_key' => false,
                'publishable_key' => false,
            ];
        }

        $strip_options = get_option('woocommerce_stripe_settings');
        $test_mode = $strip_options['testmode'] == 'yes' ? true : false;
        $secret_key = '';
        $publishable_key = '';

        if ($test_mode) {
            $secret_key = $strip_options['test_secret_key'];
            $publishable_key = $strip_options['test_publishable_key'];
        } else {
            $secret_key = $strip_options['secret_key'];
            $publishable_key = $strip_options['publishable_key'];
        }

        return [
            'has_stripe' => $has_stripe,
            'secret_key' => $secret_key,
            'publishable_key' => $publishable_key,
        ];
    }

    private function _get_exchange_rate($val = 0, $currency = 'SGD')
    {
        $currency_list = get_option('_transient_yay-currencies-transient', []);
        $rates = [];
        foreach ($currency_list as $c) {
            $r = get_post_meta($c->ID, 'rate', true);
            $rates[$c->post_title] = floatval($r);
        }

        $currency = strtoupper($currency);
        $val = floatval($val);
        if (isset($rates[$currency])) {
            return $val * $rates[$currency];
        }

        return $val;
    }

    private function get_prepaid_details($_currency = "")
    {
        global $wpdb;
        $product_id = 198;

        $wc_product = wc_get_product($product_id);
        $prepaid_plans = get_post_meta($product_id, '_ps_prepaid_plans', true);
        // $currency = do_shortcode('[yaycurrency-currency]', true);
        $per_month_price = floatval($wc_product->price);
        $per_month_price = $this->_get_exchange_rate($per_month_price, $_currency);
        // $per_month_price = ceil($per_month_price);

        $final_v_list = [];

        $default_plan = [
            'id' => 'XPCgf',
            'product_id' => $product_id,
            'type' => 1, // 1 = simple 2 = prepaid
            'name' => '1 Month',
            'plan' => 1,
            'price' => number_format($per_month_price, 2, '.', ''),
            'price_per_month' => number_format($per_month_price, 2, '.', ''),
            'has_saving' => false,
            'save' => '0.00',
            'discount' => number_format(0, 0, '.', '')
        ];

        $final_v_list[] = $default_plan;

        foreach ($prepaid_plans as $v) {
            $pieces = intval($v['prepaid_pieces']);
            $discount = floatval($v['discount']);
            $factor = 1.00 - ($discount / 100);
            $price = $per_month_price * $pieces * $factor;
            $save = round($per_month_price * $pieces - $price, 2);

            $final_v_list[] = [
                'id' => $v['slug'],
                'type' => 2,
                'product_id' => $product_id,
                'name' => $v['plan_name'],
                'price' => number_format($price, 2, '.', ''),
                'price_per_month' => number_format($per_month_price * $factor, 2, '.', ''),
                'has_saving' => $save > 0,
                'save' => number_format($save, 2, '.', ''),
                'discount' => number_format($discount, 0, '.', ''),
                'price_no_discount' => number_format(round($per_month_price * $pieces), 2),
                'plan' => $pieces,
            ];
        }

        $final_v_list = array_reverse($final_v_list);

        return $final_v_list;
    }

    public function get_subscription_details()
    {

        $sub_id = intval($_POST['id']);
        $subscription = wcs_get_subscription($sub_id);
        $isAdmin = isset($_POST['sender']) && $_POST['sender'] == 'admin';

        if (!$subscription) {
            wp_send_json_error('Sorry, subscription not found');

            return;
        }

        $payment_details = $this->user_get_payment_methods($subscription->get_user_id());

        if (count($payment_details) == 0) {
            wp_send_json_error('Sorry, This feature is only available for users who have a payment method. Please add a payment method to your account.');

            return;
        }

        // check if there is discount_coupon applied
        $webp_coupon_acceptance_list = get_option('webp_coupon_acceptance_list', []);
        if (isset($webp_coupon_acceptance_list[$sub_id]) && !$isAdmin) {
            wp_send_json_error('You have opted in for all renewal discount. Please contact our store administrator to request for a change of plan type.');
        }

        $currency = $this->get_formatted_currency($subscription);

        $name = $subscription->currency;
        $plans = $this->get_prepaid_details($name);

        $current_plan = max(1, intval($subscription->get_meta('_ps_prepaid_pieces')));

        $update_requests = get_option('webp_subscription_update_request', []);

        if (isset($update_requests[$sub_id])) {
            $update_requests = $update_requests[$sub_id];
        } else {
            $update_requests = null;
        }

        $notes = [
            1 => "",
            3 => "",
            12 => '✅ Enjoy a full year of authentic Japanese snacks at the lowest monthly rate!',
            6 => '✅ Great option for flexibility and savings.',
        ];

        $available_plans = array_map(function ($v) use ($current_plan, $currency, $update_requests, $notes) {

            // cancel coupon data
            $cancelling_coupon_code = JRC_Helper::get_setting('cancelling_coupon_' . $v['plan'] . 'm', '');
            $coupon = new WC_Coupon($cancelling_coupon_code);
            $coupon_id = $coupon->get_id();
            $special_discount = 0;

            if ($coupon_id) {
                $special_discount = floatval($coupon->get_amount());
            }

            $v['is_current'] = $v['plan'] == $current_plan;
            $v['update_pending'] = $update_requests && $update_requests['new_plan'] == $v['plan'];
            $v['currency'] = $currency;
            $v['raw_price'] = floatval($v['price']);
            $v['price'] = $currency . $v['price'];
            $v['note'] = $notes[$v['plan']];
            $v['price_per_month'] = $currency . $v['price_per_month'];
            $v['save'] = $currency . $v['save'];
            $v['special_discount'] = $special_discount;

            return $v;
        }, $plans);

        $current_plan = array_values(array_filter($plans, function ($v) use ($current_plan) {
            return $v['plan'] == $current_plan;
        }))[0];

        $renewal_days = $this->get_subscription_renewal_date($subscription);

        wp_send_json_success([
            'next_renew_at' => $renewal_days["next_renew_At"],
            'next_renew_At_n' => $renewal_days["next_renew_At_n"],
            'current_plan' => $current_plan,
            'plans' => $available_plans,
            'discount_coupons' => $this->get_cancelling_coupon_usage($subscription)
            // 'meta' => $subscription->get_meta_data(),
            // 'prepaid_plans' => $this->get_prepaid_details(),
            // 'remain' => $remaining_peases + 1,
            // 'start_date' => $sub_start_date
        ]);
    }

    public function update_subscription_plan()
    {
        // Validate request
        $headers = getallheaders();
        if (!isset($headers['X-From'])) {
            if (!check_ajax_referer('mav2-nonce', 'nonce', false)) {
                return wp_send_json_error('Invalid nonce');
            }
        } elseif ($headers['X-From'] !== 'admin-support') {
            return wp_send_json_error('Unauthorized');
        }

        // Validate and get subscription data
        if (!isset($_POST['id'], $_POST['plan'])) {
            return wp_send_json_error('Missing required data');
        }

        // getting post data
        $sub_id = intval($_POST['id']);
        $subscription = wcs_get_subscription($sub_id);

        if (!$subscription) {
            return wp_send_json_error('Invalid subscription');
        }

        $user_id = $subscription->customer_id;
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return wp_send_json_error('Invalid user');
        }

        // Get and validate selected plan
        $plans = $this->get_prepaid_details($subscription->currency);
        $selected_plan = null;

        foreach ($plans as $plan) {
            if ($plan['id'] == $_POST['plan']) {
                $selected_plan = $plan;
                break;
            }
        }

        if (!$selected_plan) {
            return wp_send_json_error('Invalid plan');
        }

        // Check payment method
        global $wpdb;
        $pm_token = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM wp_woocommerce_payment_tokens 
         WHERE user_id = %d AND gateway_id = 'stripe' LIMIT 1",
            $user_id
        ));

        if (!$pm_token) {
            return wp_send_json_error('No default payment method found');
        }

        // check if the user selected same plan as the current plan

        $current_plan = max(1, intval($subscription->get_meta('_ps_prepaid_pieces')));
        $remaining_peases = intval($subscription->get_meta('_ps_prepaid_renewals_available'));
        $parent_order_id = $subscription->get_parent_id();
        $fullfilled = $subscription->get_meta('_subscription_renewal_order_ids_cache');
        $fullfilled = is_array($fullfilled) ? $fullfilled : [];
        // reverse
        $fullfilled = array_reverse($fullfilled);
        $fullfilled = array_merge([$parent_order_id], $fullfilled);

        $new_plan = $selected_plan['plan'];

        if ($current_plan == $new_plan) {
            return wp_send_json_error('Already on this plan');
        }

        // Prepare user data for emails
        $country = $subscription->get_shipping_country();

        $lang = JRC_Helper::get_lang($country);
        // currency
        $symbol = get_woocommerce_currency_symbol($subscription->currency);
        $name = $subscription->currency;
        $currency = $symbol;
        if ($name != 'TWD') {
            $currency = $name . $currency;
        }

        $last_order = wc_get_order(end($fullfilled));
        $sub_start_date = $last_order->get_date_created()->date('Y-m-d H:i:s');
        // get first day of the month
        $sub_start_date = date('Y-m-01', strtotime($sub_start_date));
        $next_renew_At = date('3 F Y', strtotime($sub_start_date . ' + ' . ($remaining_peases) . ' month'));
        // $next_renew_At = date('Y-m-01', strtotime($sub_start_date));

        $user_data = [
            'customer_name' => $subscription->get_shipping_first_name() . " " . $subscription->get_shipping_last_name(),
            'customer_email' => $user->user_email,
            'current_plan' => $current_plan,
            'new_plan' => $new_plan . ' Month' . ($new_plan > 1 ? 's' : ''),
            'user_id' => $user_id,
            'lang' => $lang,
            'end_date' => $next_renew_At,
            'price' => $currency . $selected_plan['price']
        ];

        // updating the subscription plan update request
        $values = get_option('webp_subscription_update_request', []);

        $values[$sub_id] = [
            'sub_id' => $sub_id,
            'current_plan' => $current_plan,
            'new_plan' => $new_plan,
            'pm_token' => $pm_token->token_id,
            'email' => $user->user_email,
            'requested_at' => date('Y-m-d H:i:s')
        ];

        update_option('webp_subscription_update_request', $values);

        // send notification mail to admin

        $admin_emails = [];

        $admin_emails = JRC_Helper::get_setting('admin_emails');
        $admin_emails = explode(',', $admin_emails);
        $admin_emails = array_map('trim', $admin_emails);
        $admin_emails = array_filter($admin_emails, 'is_email');

        $update_dir = $new_plan > $current_plan ? 'upgrade' : 'downgrade';

        $user_email = $subscription->billing_email;

        // send notification mail to admin

        $this->send_html_email(
            $admin_emails,
            "Subscription $update_dir request",
            'admin_sub_upgrade_confirm_en',
            $user_data
        );


        $subject = ($lang == 'cn') ? "您的訂閱已更新！" : "Your Subscription Has Been Updated!";
        $template = "customer_sub_upgrade_confirm_$lang";

        // send notification mail to users

        $this->send_html_email(
            $user_email,
            $subject,
            $template,
            $user_data
        );

        JRC_Helper::set_readme($sub_id, "Rquested to change from $current_plan to $new_plan");

        return wp_send_json_success('Subscription updated');
    }

    public function subscription_upgrade_cancel()
    {

        $headers = getallheaders();
        if (isset($headers['X-From']) && $headers['X-From'] === 'admin-support') {
        } elseif (!check_ajax_referer('mav2-nonce', 'nonce', false)) {
            return wp_send_json_error('Invalid nonce');
        }

        // Retrieve the subscription ID from the POST request
        $sub_id = $_POST['id'];

        // Get the current update requests
        $values = get_option('webp_subscription_update_request', []);

        // Remove the subscription update request if it exists


        $request = $values[$sub_id];
        if (isset($values[$sub_id])) {
            unset($values[$sub_id]);
            update_option('webp_subscription_update_request', $values);

            JRC_Helper::set_readme($sub_id, "Cancelled to change of plan");
        }

        $sub = wcs_get_subscription($sub_id);
        $name = $sub->get_billing_first_name() . ' ' . $sub->get_billing_last_name();
        $email = $request['email'];
        $current_plan = intval($request['current_plan']) > 1 ? intval($request['current_plan']) . ' Months' : intval($request['current_plan']) . ' Month';
        $new_plan = intval($request['new_plan']) > 1 ? intval($request['new_plan']) . ' Months' : intval($request['new_plan']) . ' Month';


        $data = [
            'name' => $name,
            'email' => $email,
            'current_plan' => $current_plan,
            'new_plan' => $new_plan,
        ];

        $template = "admin_sub_upgrade_cancelled";

        $this->send_admin_emails($template, "Customer Cancelled Change of Plan Request", $data);

        /*
        'sub_id' => $sub_id,
            'current_plan' => $current_plan,
            'new_plan' => $new_plan,
            'pm_token' => $pm_token->token_id,
            'email' => $user->user_email
            */



        // Return a success response
        wp_send_json_success('Subscription updated');
    }

    /**
     * Checks coupon usage for a subscription and returns remaining usage count.
     * Processes AJAX request to check how many times a coupon can still be used
     * for subscription cancellation. Returns remaining usage count as JSON.
     */
    public function check_coupon_usage()
    {
        // Verify nonce for security
        if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
            wp_send_json_error('Invalid subscription ID', 400);

            return;
        }

        $out = [
            'remain' => 0,
            'discount' => 0
        ];

        $subscription_id = $_POST['id'];

        // check if plan change reqeust
        $values = get_option('webp_subscription_update_request', []);
        if (isset($values[$subscription_id])) {
            wp_send_json($out);

            return;
        }

        // need to check any discount coupons for this plan
        $plan = $this->get_prepaid_plan_by_sub($subscription_id);
        $plan = $plan['plan'];
        $cancelling_coupon = JRC_Helper::get_setting('cancelling_coupon_' . $plan . 'm', '');

        if (empty($cancelling_coupon)) {
            wp_send_json($out);

            return;
        }

        $coupon_usages = get_option('cancelling_coupons_usages', []);
        $current_usage = isset($coupon_usages[$subscription_id]) ? (int) $coupon_usages[$subscription_id] : 0;

        // Get max usage from settings with default 0
        $max_usage = (int) JRC_Helper::get_setting('cancelling_coupon_max_usage', 0);

        // Calculate remaining usage (ensuring it doesn't go below 0)
        $remaining_usage = max(0, $max_usage - $current_usage);

        // if current coupon alrady used then set remain to 0;
        if ($current_usage > 0) {

            $subscription = wcs_get_subscription($subscription_id);
            $current_plan = max(1, intval($subscription->get_meta('_ps_prepaid_pieces')));
            $cancelling_coupon = JRC_Helper::get_setting('cancelling_coupon_' . $current_plan . 'm', '');
            $acceptance_list = get_option('webp_coupon_acceptance_list', []);
            $coupon_accepted = isset($acceptance_list[$subscription_id]) ? $acceptance_list[$subscription_id] : false;
            if ($coupon_accepted == $cancelling_coupon) {
                $remaining_usage = 0;
            }
        }

        $out['remain'] = $remaining_usage;

        wp_send_json([
            'remain' => $remaining_usage
        ]);
    }

    // this function is for add coupon to selected subscription
    // at when user try to cancel it.
    public function accept_coupon_offer()
    {
        // Validate and sanitize input
        $sub_id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';
        $coupon = isset($_POST['coupon']) ? sanitize_text_field($_POST['coupon']) : false;

        if (empty($sub_id)) {
            wp_send_json(['status' => 'error', 'message' => 'Invalid subscription ID']);

            return;
        }

        // Get options in one go
        $options = [
            'cancelling_coupons_usages' => get_option('cancelling_coupons_usages', []),
            'webp_coupon_acceptance_list' => get_option('webp_coupon_acceptance_list', [])
        ];

        $subscription = wcs_get_subscription($sub_id);

        if (!$subscription) {
            wp_send_json(['status' => 'error', 'message' => 'Invalid subscription']);

            return;
        }
        $cancelling_coupon = $coupon;
        if ($coupon === false) {
            $current_plan = max(1, intval($subscription->get_meta('_ps_prepaid_pieces')));
            $cancelling_coupon = JRC_Helper::get_setting('cancelling_coupon_' . $current_plan . 'm', '');
        }


        if (empty($cancelling_coupon)) {
            error_log("MAV2 ERROR: Invalid Coupon $cancelling_coupon");
            wp_send_json(['status' => 'error', 'message' => 'Invalid Coupon']);

            return;
        }

        // Check if coupon is already accepted
        if (isset($options['webp_coupon_acceptance_list'][$sub_id]) && $options['webp_coupon_acceptance_list'][$sub_id] == $cancelling_coupon) {
            wp_send_json(['status' => 'error', 'message' => 'Coupon already accepted']);

            return;
        }

        // Update options
        $options['webp_coupon_acceptance_list'][$sub_id] = $cancelling_coupon;
        $options['cancelling_coupons_usages'][$sub_id] = intval($options['cancelling_coupons_usages'][$sub_id] ?? 0) + 1;

        // Batch update options
        update_option('webp_coupon_acceptance_list', $options['webp_coupon_acceptance_list']);
        // update_option('cancelling_coupons_usages', $options['cancelling_coupons_usages']);

        // send email to user;
        $data = [];
        if (class_exists('JRC_Helper')) {
            $name = $subscription->get_billing_first_name();
            $email = $subscription->get_billing_email();
            $country = $subscription->get_billing_country();
            $lang = JRC_Helper::get_lang($country);
            $subject = $lang == 'en' ? "🎉 You've Activated Your Discount!" : "🎉 您的優惠折扣已啟動！";
            $template = "coupon_accepted_$lang";

            $plan_details = $this->get_prepaid_plan_by_sub($subscription);
            $coupon = new WC_Coupon($cancelling_coupon);
            $coupon_amount = $coupon->get_amount();
            $coupon_type = $coupon->get_discount_type();
            $is_percent_type = strpos($coupon_type, 'percent') !== false;

            $formatted_currency = $this->get_formatted_currency($subscription);

            $price = $plan_details['price'];
            $saving = 0;
            if ($is_percent_type) {
                $saving = number_format($price * $coupon_amount / 100, 2);
            } else {
                $saving = $coupon_amount;
            }

            $data['name'] = $name;
            $data['email'] = $email;
            $data['plan'] = $plan_details['name'];
            // renew dates
            $renew_dates = $this->get_subscription_renewal_date($subscription);

            $data['discount'] = ($is_percent_type ? $coupon_amount . '%' : $formatted_currency . $coupon_amount);
            $data['discounted_price'] = $formatted_currency . number_format($price - $saving, 2);
            $data['original_price'] = $formatted_currency . number_format($price, 2);
            $data['effective_from'] = $renew_dates['next_renew_At'];
            $data['effective_from_n'] = $renew_dates['next_renew_At_n'];
            $data['savings'] = $formatted_currency . $saving;

            // for user
            $this->send_html_email($email, $subject, $template, $data);

            // for admin          

            $template = "coupon_accepted_admin";
            $this->send_admin_emails($template, "Renew Coupon Accepted", $data);
        }

        wp_send_json_success($data);
    }

    private function get_cancelling_coupon_usage($subscription)
    {
        if (!class_exists('WC_Subscription')) {
            throw new Exception('WooCommerce Subscription is not installed');
        }

        if (!($subscription instanceof WC_Subscription)) {
            $subscription = wcs_get_subscription($subscription);
        }

        $out = ['remain' => 0];

        $plan = $this->get_prepaid_plan_by_sub($subscription);
        $plan = $plan['plan'];
        $cancelling_coupon = JRC_Helper::get_setting('cancelling_coupon_' . $plan . 'm', '');

        if (empty($cancelling_coupon)) {
            return $out;
        }

        $coupon_usages = get_option('cancelling_coupons_usages', []);
        $current_usage = isset($coupon_usages[$subscription->get_id()]) ? (int) $coupon_usages[$subscription->get_id()] : 0;

        // Get max usage from settings with default 0
        $max_usage = (int) JRC_Helper::get_setting('cancelling_coupon_max_usage', 0);

        // Calculate remaining usage (ensuring it doesn't go below 0)
        $remaining_usage = max(0, $max_usage - $current_usage);

        $coupon = new WC_Coupon($cancelling_coupon);
        $coupon_amount = $coupon->get_amount();

        $out['remain'] = $remaining_usage;
        $out['amount'] = $coupon_amount;

        return $out;
    }

    private function get_subscription_renewal_date($subscription)
    {
        if (!class_exists('WC_Subscription')) {
            throw new Exception('WooCommerce Subscription is not installed');
        }

        if (!($subscription instanceof WC_Subscription)) {
            $subscription = wcs_get_subscription($subscription);
        }

        $lang = 'en';
        $ch_list = ['TW', 'HK', 'CN'];
        $ko_list = ['KO'];

        $country = $subscription->get_shipping_country();
        if (in_array($country, $ch_list)) {
            $lang = 'ch';
        } else if (in_array($country, $ko_list)) {
            $lang = 'ko';
        }

        $remaining_peases = intval($subscription->get_meta('_ps_prepaid_renewals_available'));
        $parent_order_id = $subscription->get_parent_id();

        $fullfilled = $subscription->get_meta('_ps_prepaid_fulfilled_orders');
        $fullfilled = is_array($fullfilled) ? $fullfilled : [];
        $fullfilled = array_merge([$parent_order_id], $fullfilled);

        $last_order = wc_get_order(end($fullfilled));

        $sub_start_date = $last_order->get_date_created()->date('Y-m-01');        

        $_next_renew = date('Y-m-d', strtotime($sub_start_date . ' + ' . ($remaining_peases + 1) . ' month'));
        $next_renew_At_n = date('3 F Y', strtotime($_next_renew));
        $next_renew_At = date('jS \of F Y', strtotime($_next_renew));

        if ($lang == 'ch') {
            $next_renew_At = date('Y年m月3日', strtotime($_next_renew));
            $next_renew_At_n = date('Y年m月3日', strtotime($_next_renew));
        } elseif ($lang == 'ko') {
            $next_renew_At = date('Y년m월3일', strtotime($_next_renew));
            $next_renew_At_n = date('Y년m월3일', strtotime($_next_renew));
        }

        return [
            'next_renew_At' => $next_renew_At,
            'next_renew_At_n' => $next_renew_At_n,
        ];
    }

    private function get_invoice_data($order_id)
    {

        // prep order data
        // order id
        // custore billing info
        // order date
        // order total
        // order subtotal
        // order tax
        // order shipping
        // order discount
        // order items

        $order = wc_get_order($order_id);

        if (empty($order)) {
            return [];
        }

        $data_collection = [];

        $data_collection['id'] = $order_id;


        // check if its a virtual order or not
        $data_collection['is_virtual'] = !$order->has_shipping_address();

        $shipping_data = [
            $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            $order->get_billing_address_1(),
            $order->get_billing_address_2(),
            $order->get_billing_city(),
            $order->get_billing_postcode(),
            $order->get_billing_country(),
        ];

        $shipping_data = array_filter($shipping_data, (function ($val) {
            if (trim($val) != "") {
                return $val;
            }
        }));

        $currency = $order->get_currency();
        $symbol = get_woocommerce_currency_symbol($currency);

        $currency_format = $currency != 'TWD' ? $currency . $symbol : $symbol;

        $data_collection['currency'] = $currency_format;

        $data_collection['address'] = implode('<br>', $shipping_data);

        $data_collection['date'] = $order->get_date_created()->format('d M Y H:i a');

        $data_collection['subtotal'] = $currency_format . number_format($order->get_subtotal(), 2);

        $data_collection['discount'] = $currency_format . number_format($order->get_total_discount(), 2);

        $data_collection['total'] = $currency_format . number_format($order->get_total(), 2);

        $data_collection['tax'] = $currency_format . number_format($order->get_total_tax(), 2);

        $data_collection['shipping'] = $order->get_shipping_total() > 0 ? $currency_format . number_format($order->get_shipping_total(), 2) : 'Free';

        $order_items = $order->get_items();

        foreach ($order_items as $item) {
            $temp = [
                'name' => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'unit_price' => $currency_format . number_format($item->get_subtotal() / $item->get_quantity(), 2),
                'price' => $currency_format . number_format($item->get_total(), 2),
                'subtotal' => $currency_format . number_format($item->get_subtotal(), 2),
            ];

            $data_collection['items'][] = $temp;
        }

        return $data_collection;
    }

    public function get_invoice()
    {
        $order_id = $_POST['id'];
        $data_collection = $this->get_invoice_data($order_id);
        wp_send_json($data_collection);
    }


    public function prepair_invoice()
    {
        $order_id = $_POST['id'];
        // $order = wc_get_order('56556'); 
        $data_collection = $this->get_invoice_data($order_id);

        $template_path = MAV2_PATH . 'views/invoices/default.php';

        ob_start();
        extract($data_collection);
        include $template_path;
        $html = ob_get_contents();
        ob_end_clean();

        if (empty(trim($html))) {
            wp_send_json_error('Generated HTML is empty. Check your template logic.');
            exit;
        }

        try {

            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'autoScriptToLang' => true,
                'autoLangToFont' => true,
                'format' => 'A4',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 15,
                'margin_bottom' => 15,
            ]);

            $mpdf->WriteHTML($html);
            $string = $mpdf->Output("", 'S');

            // Clear buffer to prevent corruption
            if (ob_get_length())
                ob_end_clean();

            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $order_id . '.pdf"');
            header('Content-Length: ' . strlen($string));
            echo $string;
        } catch (\Exception $e) {
            wp_send_json_error('mPDF Error: ' . $e->getMessage());
        }
        exit;
    }

    public function download_subscription_invoice()
    {
        global $wpdb;

        $order_id = $_POST['id'];
        $subscription = wcs_get_subscription($order_id);
        $parent_order_id = $subscription->get_parent_id();
        $current_plan = max(1, intval($subscription->get_meta('_ps_prepaid_pieces')));
        $renewals = $subscription->get_meta('_subscription_renewal_order_ids_cache');
        $renewals[] = $parent_order_id;

        $renews_ids_str = implode(',', $renewals);
        $sql_latest_renew = "SELECT id FROM {$wpdb->prefix}wc_orders WHERE id IN ({$renews_ids_str}) AND total_amount>0 ORDER BY date_created_gmt DESC limit 1";
        $result = $wpdb->get_var($sql_latest_renew);

        $invoice_data = $this->get_invoice_data($result);
        $invoice_data['id'] = $order_id;
        $renew_dates = $this->get_subscription_renewal_date($subscription);

        $invoice_data['plan'] = $current_plan . ($current_plan > 1 ? ' Months' : ' Month');
        $invoice_data['renewal_date'] = $renew_dates['next_renew_At_n'];



        $template_path = MAV2_PATH . 'views/invoices/subscription.php';

        ob_start();
        extract($invoice_data);
        include $template_path;
        $html = ob_get_contents();
        ob_end_clean();

        if (empty(trim($html))) {
            wp_send_json_error('Generated HTML is empty. Check your template logic.');
            exit;
        }

        try {

            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'autoScriptToLang' => true,
                'autoLangToFont' => true,
                'format' => 'A4',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 15,
                'margin_bottom' => 15,
            ]);

            $mpdf->WriteHTML($html);
            $string = $mpdf->Output("", 'S');

            // Clear buffer to prevent corruption
            if (ob_get_length())
                ob_end_clean();

            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $order_id . '.pdf"');
            header('Content-Length: ' . strlen($string));
            echo $string;
        } catch (\Exception $e) {
            wp_send_json_error('mPDF Error: ' . $e->getMessage());
        }
        exit;

    }


    /*
     * Get prepaid plan by subscription
     * @param WC_Subscription $subscription
     * @return array (plan)
     */
    private function get_prepaid_plan_by_sub($subscription)
    {
        if (!class_exists('WC_Subscription')) {
            throw new Exception('WooCommerce Subscription is not installed');
        }

        if (!($subscription instanceof WC_Subscription)) {
            $subscription = wcs_get_subscription($subscription);
        }

        $current_plan = max(1, intval($subscription->get_meta('_ps_prepaid_pieces')));
        $plans = $this->get_prepaid_details($subscription->get_currency());
        $plan = array_find($plans, function ($p) use ($current_plan) {
            return $p["plan"] === $current_plan;
        });

        return $plan;
    }

    private function get_formatted_currency($subscription)
    {
        if (!class_exists('WC_Subscription')) {
            throw new Exception('WooCommerce Subscription is not installed');
        }

        if (!($subscription instanceof WC_Subscription)) {
            $subscription = wcs_get_subscription($subscription);
        }

        $symbol = get_woocommerce_currency_symbol($subscription->currency);
        $name = $subscription->currency;

        $currency = $symbol;

        if ($name != 'TWD') {
            $currency = $name . " " . $currency;
        }

        return $currency;
    }

    private function send_html_email($to, $subject, $template, $data = [])
    {
        // Get the template content
        $template_path = MAV2_PATH . "views/emails/{$template}.php";

        error_log('sending email to: ' . $to);

        if (!file_exists($template_path)) {
            return new WP_Error('email_template_missing');
        }


        ob_start();
        extract($data);
        include $template_path;
        $message = ob_get_clean();

        // Set email headers
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
        ];

        if (is_array($to)) {
            $primary = array_pop($to);
            $headers[] = "Bcc: " . implode(', ', $to);
            $to = $primary;
        }

        // Send email
        return wp_mail($to, $subject, $message, $headers);
    }
    private function send_admin_emails($template, $subject, $data)
    {
        $admin_emails = [];
        $admin_emails = JRC_Helper::get_setting('admin_emails');
        $admin_emails = explode(',', $admin_emails);
        $admin_emails = array_map('trim', $admin_emails);
        $admin_emails = array_filter($admin_emails, 'is_email');

        $this->send_html_email($admin_emails, $subject, $template, $data);
    }
}
