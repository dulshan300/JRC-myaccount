<?php

// use mav2_{function_name} as action with js ajax

final class MAV2_Ajax_Admin
{
    static private $_currency_recored = null;

    public function mav2_test()
    {
        error_log("Testing AJAX call...");

        wp_send_json_success(["success"]);
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

        if (intval($sub->plan) > 1) {
            // prepaid cancel
            $subscription->set_status('active');
            $subscription->update_meta_data('_ps_scheduled_to_be_cancelled', 'yes');
            $subscription->save();
        } else {
            // sub cancel
            $subscription->set_status('cancelled');
            $subscription->save();
        }

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

        if (!filter_var($billing_email, FILTER_VALIDATE_EMAIL)) {
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

        if (strlen($billing_city) < 3) {
            $validation_erros['billing_city'] = 'City is required';
        }

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
        update_user_meta($user_id, 'billing_email', $billing_email);
        update_user_meta($user_id, 'billing_phone', $billing_phone);
        update_user_meta($user_id, 'billing_address_1', $billing_address_1);
        update_user_meta($user_id, 'billing_address_2', $billing_address_2);
        update_user_meta($user_id, 'billing_city', $billing_city);
        update_user_meta($user_id, 'billing_postcode', $billing_postcode);
        update_user_meta($user_id, 'billing_country', $billing_country);

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

        wp_send_json_success(["success"]);
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

        wp_send_json_success(["success"]);
    }

    public function user_add_payment_method_sql()
    {
        global $wpdb;

        $keys = $this->get_stripe_keys();

        $stripe = new \Stripe\StripeClient($keys['secret_key']);

        $token = $_POST['token'];
        $card = $token['card'];

        $user = wp_get_current_user();

        // get user options
        $customer_id = get_user_meta(get_current_user_id(), 'wp__stripe_customer_id', true);

        if (empty($customer_id)) {
            $customer = $stripe->customers->create([
                'name' => $user->display_name,
                'email' => $user->user_email,
                'description' => 'Customer for ' . $user->display_name
            ]);

            update_user_meta(get_current_user_id(), 'wp__stripe_customer_id', $customer->id);

            $customer_id = $customer->id;
        }

        $customer = $stripe->customers->retrieve($customer_id);
        // create payment method
        error_log('Creating payment method');
        $pm =  $stripe->paymentMethods->create([
            'type' => 'card',
            'card' => [
                'token' => $token['id']
            ]
        ]);

        // add payment method to woocommerce
        error_log('Adding payment method to woocommerce');

        $cc = [
            'gateway_id' => 'stripe',
            'token' => $pm->id,
            'user_id' => $user->ID,
            'type' => 'CC',
            'is_default' => 0
        ];

        // insert this in to 'wp_woocommerce_payment_tokens'
        $wpdb->insert('wp_woocommerce_payment_tokens', $cc);

        $token_id = $wpdb->insert_id;

        $cc_meta = [
            'card_type' => $card['brand'],
            'last4' => $card['last4'],
            'expiry_month' => $card['exp_month'],
            'expiry_year' => $card['exp_year'],
        ];

        // insert this in to 'wp_woocommerce_payment_tokenmeta'
        foreach ($cc_meta as $key => $value) {
            $wpdb->insert('wp_woocommerce_payment_tokenmeta', ['payment_token_id' => $token_id, 'meta_key' => $key, 'meta_value' => $value]);
        }


        // attach payment method to customer
        error_log('Attaching payment method to customer');
        $stripe->paymentMethods->attach(
            $pm->id,
            ['customer' => $customer_id]
        );

        wp_send_json_success([$token_id, $card]);
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
                'description' => 'Customer for ' . $user->display_name
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
        $stripe_sources  = $stripe_customer->get_sources();


        if (class_exists('WC_Stripe_Payment_Tokens')) {
            $stripe_tokens_instance = WC_Stripe_Payment_Tokens::get_instance();

            // Remove the action for retrieving customer payment tokens
            add_action('woocommerce_get_customer_payment_tokens', [$stripe_tokens_instance, 'woocommerce_get_customer_payment_tokens'], 10, 3);

            add_action('woocommerce_payment_token_deleted', [$stripe_tokens_instance, 'woocommerce_payment_token_deleted'], 10, 2);

            // Optionally, log or confirm that the action has been removed
            error_log('Add Stripe woocommerce_get_customer_payment_tokens action.');
        }

        wp_send_json_success($stripe_sources);
    }



    public function user_delete_payment_method()
    {
        $keys = $this->get_stripe_keys();
        $stripe = new \Stripe\StripeClient($keys['secret_key']);

        // get payment token
        $token_id = $_POST['id'];

        $pt = WC_Payment_Tokens::get($token_id);


        // delete token

        $stripe->paymentMethods->detach($pt->get_token());


        $pt->delete();


        wp_send_json_success();
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
                'publishable_key' => false
            ];
        }

        $strip_options = get_option('woocommerce_stripe_settings');
        $test_mode = $strip_options['testmode'] == 'yes' ? true : false;
        $secret_key = "";
        $publishable_key = "";

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
            'publishable_key' => $publishable_key
        ];
    }
}
