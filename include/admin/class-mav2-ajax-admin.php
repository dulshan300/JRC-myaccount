<?php

/**
 * Prevent direct access to the file.
 */
if (! defined('ABSPATH')) {
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
        $billing_email = sanitize_email($_POST['billing_email']);
        $billing_phone = sanitize_text_field($_POST['billing_phone']);
        $billing_address_1 = sanitize_text_field($_POST['billing_address_1']);
        $billing_address_2 = sanitize_text_field($_POST['billing_address_2']);
        $billing_city = sanitize_text_field($_POST['billing_city']);
        $billing_postcode = sanitize_text_field($_POST['billing_postcode']);
        $billing_country = sanitize_text_field($_POST['billing_country']);

        // validation errors
        $validation_erros = [];

        if (strlen($billing_first_name) < 3) {
            $validation_erros['billing_first_name'] = 'First name is required';
        }

        if (strlen($billing_last_name) < 3) {
            $validation_erros['billing_last_name'] = 'Last name is required';
        }

        if (! filter_var($billing_email, FILTER_VALIDATE_EMAIL)) {
            $validation_erros['billing_email'] = 'Email is required';
        }

        $pattern = '/^\+?[0-9]{1,3}(\s*|-)?\(?\d{1,4}\)?(\s*|-)?[0-9]{1,5}(\s*|-)?[0-9]{1,5}(\s*|-)?[0-9]{1,5}$/';

        if (preg_match($pattern, $billing_phone)) {
        } else {
            $validation_erros['billing_phone'] = 'Phone is required';
        }

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

        if (! empty($validation_erros)) {
            wp_send_json($validation_erros, 422);

            return;
        }

        update_user_meta($user_id, 'billing_first_name', $billing_first_name);
        update_user_meta($user_id, 'billing_last_name', $billing_last_name);
        update_user_meta($user_id, 'billing_email', $billing_email);
        update_user_meta($user_id, 'billing_phone', $billing_phone);
        update_user_meta($user_id, 'billing_address_1', $billing_address_1);
        update_user_meta($user_id, 'billing_address_2', $billing_address_2);
        update_user_meta($user_id, 'billing_city', $billing_city);
        update_user_meta($user_id, 'billing_postcode', $billing_postcode);
        update_user_meta($user_id, 'billing_country', $billing_country);



        update_user_meta($user_id, 'shipping_first_name', $billing_first_name);
        update_user_meta($user_id, 'shipping_last_name', $billing_last_name);
        update_user_meta($user_id, 'shipping_email', $billing_email);
        update_user_meta($user_id, 'shipping_phone', $billing_phone);
        update_user_meta($user_id, 'shipping_address_1', $billing_address_1);
        update_user_meta($user_id, 'shipping_address_2', $billing_address_2);
        update_user_meta($user_id, 'shipping_city', $billing_city);
        update_user_meta($user_id, 'shipping_postcode', $billing_postcode);
        update_user_meta($user_id, 'shipping_country', $billing_country);

        // also need to update all the subscription billing / shipping address

        // collect all active subscriptions
        $sql = "SELECT id FROM wp_wc_orders WHERE`type`='shop_subscription'and customer_id = $user_id";
        $subscriptions = $wpdb->get_results($sql);

        $customer = new WC_Customer($user_id);

        $order_ids = [];

        foreach ($subscriptions as $sub) {
            $sub_id = $sub->id;
            $sub = wc_get_order($sub_id);
            $sub->set_address($customer->get_billing(), 'billing');
            $sub->set_address($customer->get_billing(), 'shipping');
            $sub->save();
            $order_ids[] = $sub->get_id();
        }

        wp_send_json_success($order_ids);
    }

    public function user_account_details_update_form()
    {
        $user = wp_get_current_user();

        if (! is_user_logged_in()) {
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

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $validation_erros['ua_email'] = 'Email is invalid';
        }

        // check if email already exists

        $email_check = email_exists($email);

        if (! $email_check === false & $email_check !== $user_id) {
            $validation_erros['ua_email'] = 'Email already exists';
        }

        if (! empty($validation_erros)) {
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

        if (! is_user_logged_in()) {
            wp_send_json_error('Please login');

            return;
        }

        $current_password = sanitize_text_field($_POST['current_password']);
        $new_password = sanitize_text_field($_POST['new_password']);
        $confirm_password = sanitize_text_field($_POST['confirm_password']);

        $validation_erros = [];
        // validating password
        // check current password

        if (! wp_check_password($current_password, $user->user_pass, $user->ID)) {

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

    private function user_get_payment_methods()
    {

        $user = wp_get_current_user();
        $tokens = WC_Payment_Tokens::get_customer_tokens($user->ID);

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

        if (! empty($customer_id)) {

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

        $pt = new WC_Payment_Token_CC;
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

        if (! $has_stripe) {
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

        $currency =  strtoupper($currency);
        $val = floatval($val);
        if (isset($rates[$currency])) {
            return $val * $rates[$currency];
        }
        return $val;
    }

    private function get_prepaid_details()
    {
        global $wpdb;
        $product_id = 198;

        $wc_product = wc_get_product($product_id);
        $prepaid_plans = get_post_meta($product_id, '_ps_prepaid_plans', true);
        $currency = do_shortcode('[yaycurrency-currency]', true);
        $per_month_price = floatval($wc_product->get_price());
        $per_month_price = $this->_get_exchange_rate($per_month_price, $currency);


        $final_v_list = [];

        $default_plan =  [
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

            $final_v_list[] =  [
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
                'plan' => $pieces
            ];
        }

        $final_v_list = array_reverse($final_v_list);
        return $final_v_list;
    }

    public function get_subscription_details()
    {

        global $wpdb;

        // count($this->user_get_payment_methods()) == 0

        if (count($this->user_get_payment_methods()) == 0) {
            wp_send_json_error('Sorry, This feature is only available for users who have a payment method. Please add a payment method to your account.');
            return;
        }


        $name = do_shortcode('[yaycurrency-currency]', true);
        $symbol = do_shortcode('[yaycurrency-symbol]', true);

        $currency = $symbol;

        if ($name != 'TWD') $currency = $name . $currency;

        $sub_id = intval($_POST['id']);
        $subscription = wcs_get_subscription($sub_id);

        if (!$subscription) {
            wp_send_json_error('Subscription not found');
            return;
        }

        $plans = $this->get_prepaid_details();

        $current_plan = max(1, intval($subscription->get_meta('_ps_prepaid_pieces')));

        $update_requests = get_option('webp_subscription_update_request', []);

        if (isset($update_requests[$sub_id])) {
            $update_requests = $update_requests[$sub_id];
        } else {
            $update_requests = null;
        }

        $available_plans = array_map(function ($v) use ($current_plan, $currency, $update_requests) {
            $v['is_current'] = $v['plan'] == $current_plan;
            $v['update_pending'] = $update_requests && $update_requests['new_plan'] == $v['plan'];
            $v['price'] = $currency . $v['price'];
            $v['price_per_month'] = $currency . $v['price_per_month'];
            $v['save'] = $currency . $v['save'];
            return $v;
        }, $plans);

        $sub_start_date = $subscription->get_date('last_order_date_paid');

        $current_plan = array_values(array_filter($plans, function ($v) use ($current_plan) {
            return $v['plan'] == $current_plan;
        }))[0];

        wp_send_json([
            'next_renew_at' => date('Y-m-03', strtotime($sub_start_date . ' + ' . $current_plan['plan'] . ' month')),
            'current_plan' => $current_plan,
            'plans' => $available_plans,
            'meta' => $subscription->get_meta_data(),
            'prepaid_plans' => $this->get_prepaid_details(),
        ]);
    }

    public function update_subscription_plan()
    {

        $headers = getallheaders();
        if (isset($headers['X-From']) && $headers['X-From'] === 'admin-support') {
        } else if (!check_ajax_referer('mav2-nonce', 'nonce', false)) {
            return wp_send_json_error('Invalid nonce');
        }


        global $wpdb;

        // getting post data
        $sub_id = intval($_POST['id']);
        $subscription = wcs_get_subscription($sub_id);

        $plan = $_POST['plan'];
        $user_id = $subscription->customer_id;
        $user = get_user_by('id', $user_id);

        // get selected plan from the prepaid plans
        $plans = $this->get_prepaid_details();

        $selected_plan = array_values(array_filter($plans, function ($v) use ($plan) {
            return $v['id'] == $plan;
        }))[0];

        if (!$selected_plan) {
            return wp_send_json_error('Invalid plan');
        }


        // check if the user has a default payment method
        $sql_get_tokens = "SELECT * 
            FROM wp_woocommerce_payment_tokens 
            WHERE user_id = $user_id
            AND gateway_id = 'stripe'                       
            LIMIT 1";

        $pm_token = $wpdb->get_row($sql_get_tokens);

        if (!$pm_token) {
            return wp_send_json_error('No default payment method found');
        }

        // check if the user selected same plan as the current plan

        $current_plan = max(1, intval($subscription->get_meta('_ps_prepaid_pieces')));
        $new_plan = $selected_plan['plan'];

        if ($current_plan == $new_plan) {
            return wp_send_json_error('Already on this plan');
        }

        $ch_list = ['TW', 'HK', 'CN'];
        $country = $subscription->get_shipping_country();
        $sub_start_date = $subscription->get_date('last_order_date_paid');

        $user_data = [
            'customer_name' => $subscription->get_shipping_first_name() . " " . $subscription->get_shipping_last_name(),
            'customer_email' => $user->user_email,
            'current_plan' => $current_plan,
            'new_plan' => $new_plan,
            'user_id' => $user_id,
            'lang' => in_array($country, $ch_list) ? 'cn' : 'en',
            'end_date' => date('Y-m-03', strtotime($sub_start_date . ' + ' . $current_plan . ' month')),
        ];

        $update_dir = $new_plan > $current_plan ? 'upgrade' : 'downgrade';

        // updating the subscription plan update request
        $values = get_option('webp_subscription_update_request', []);

        $values[$sub_id] = [
            'sub_id' => $sub_id,
            'current_plan' => $current_plan,
            'new_plan' => $new_plan,
            'pm_token' => $pm_token->token_id,
            'email' => $user->user_email
        ];

        update_option('webp_subscription_update_request', $values);

        $admin_email = get_option('admin_email');
        $user_email = $subscription->billing_email;

        // send notification mail to admin
        $mail_sent = $this->send_html_email($admin_email, "Subscription $update_dir request", 'sub_upgrade_admin', $user_data);

        // send notification mail to users
        if (in_array($country, $ch_list)) {
            $mail_sent = $this->send_html_email($user_email, "Subscription $update_dir request scheduled. ", 'sub_upgrade_customer_cn', $user_data);
        } else {
            $mail_sent = $this->send_html_email($user_email, "Subscription $update_dir request scheduled. ", 'sub_upgrade_customer_en', $user_data);
        }

        return wp_send_json_success('Subscription updated');
    }

    public function subscription_upgrade_cancel()
    {

        $headers = getallheaders();
        if (isset($headers['X-From']) && $headers['X-From'] === 'admin-support') {
        } else if (!check_ajax_referer('mav2-nonce', 'nonce', false)) {
            return wp_send_json_error('Invalid nonce');
        }

        // Retrieve the subscription ID from the POST request
        $sub_id = $_POST['id'];

        // Get the current update requests
        $values = get_option('webp_subscription_update_request', []);

        // Remove the subscription update request if it exists
        if (isset($values[$sub_id])) {
            unset($values[$sub_id]);
            update_option('webp_subscription_update_request', $values);
        }

        // Return a success response
        wp_send_json_success('Subscription updated');
    }


    private function send_html_email($email, $subject, $template, $data = [])
    {
        // Get the template content
        $template_path = MAV2_PATH . "views/emails/{$template}.php";

        if (!file_exists($template_path)) {
            return new WP_Error('email_template_missing', __('Email template not found.', 'your-plugin-textdomain'));
        }

        ob_start();
        extract($data);
        include $template_path;
        $message = ob_get_clean();

        // Set email headers
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
        ];

        // Send email
        return wp_mail($email, $subject, $message, $headers);
    }
}
