<?php
/*
Plugin Name: Customer Migrate
Plugin URI:  https://yourwebsite.com
Description: A plugin to migrate customers.
Version:     1.0
Author:      Your Name
Author URI:  https://yourwebsite.com
License:     GPL2
*/


// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . '/vendor/autoload.php';

// Main plugin class.
class Customer_Migrate
{

    public function __construct()
    {
        // Add admin menu.
        add_action('admin_menu', array($this, 'create_admin_menu'));

        // Enqueue scripts.
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // $this->copy_page_template();

        // Ajax handler.

        add_action('wp_ajax_cm_get_customers', array($this, 'cm_get_customers'));
        add_action('wp_ajax_nopriv_cm_get_customers', array($this, 'cm_get_customers'));


        add_action('wp_ajax_customer_strip_token_save', array($this, 'customer_strip_token_save'));
        add_action('wp_ajax_nopriv_customer_strip_token_save', array($this, 'customer_strip_token_save'));


        add_action('wp_ajax_customer_migrate_invite', array($this, 'customer_migrate_invite'));
        add_action('wp_ajax_nopriv_customer_migrate_invite', array($this, 'customer_migrate_invite'));

        add_action('wp_ajax_cm_download_csv', array($this, 'cm_download_csv'));
        add_action('wp_ajax_nopriv_cm_download_csv', array($this, 'cm_download_csv'));

        add_action('wp_ajax_cm_get_active_customers', array($this, 'cm_get_active_customers'));
        add_action('wp_ajax_nopriv_cm_get_active_customers', array($this, 'cm_get_active_customers'));

        add_action('wp_ajax_cm_send_reminder', array($this, 'cm_send_reminder'));
        add_action('wp_ajax_nopriv_cm_send_reminder', array($this, 'cm_send_reminder'));

        add_shortcode('jp-payment-page', [$this, 'short_code_payment_page']);

        register_activation_hook(__FILE__, [$this, 'activate_plugin']);
    }


    public function activate_plugin()
    {
        $this->create_page_if_not_exists();
        // $this->copy_page_template();
    }

    public function enqueue_scripts()
    {

        global $post;
        if (is_page() && $post && $post->post_name == 'add-payment-method') {

            $options         = get_option('woocommerce_stripe_settings');
            $publishable_key = $options['publishable_key'] ?? '';

            wp_enqueue_script('customer-migrate-ajax', plugin_dir_url(__FILE__) . 'assets/js/customer-migrate.js', array('jquery'), '1.0.6', true);

            wp_localize_script('customer-migrate-ajax', 'customer_migrate_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('customer_migrate_nonce'),
                'stripe_pk' => $publishable_key,
            ));
        }
    }

    public function enqueue_admin_scripts($hook)
    {
        if ('toplevel_page_customer-migrate' !== $hook) {
            // error_log('Hook does not match');
            return;
        }

        $options         = get_option('woocommerce_stripe_settings');
        $publishable_key = $options['publishable_key'] ?? '';


        wp_enqueue_script('customer-migrate-axios', plugin_dir_url(__FILE__) . 'assets/js/axios.js', array('jquery'), null, true);
        wp_enqueue_script('customer-migrate-vue', plugin_dir_url(__FILE__) . 'assets/js/vue3.js', array('jquery'), null, true);
        wp_enqueue_script('customer-migrate-ajax', plugin_dir_url(__FILE__) . 'assets/js/customer-migrate.admin.js', array('jquery', 'customer-migrate-axios', 'customer-migrate-vue'), '1.0.11', true);

        wp_localize_script('customer-migrate-ajax', 'customer_migrate_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('customer_migrate_nonce'),
            'stripe_pk' => $publishable_key,
        ));
    }

    public function create_admin_menu()
    {
        // Add a new top-level menu.
        add_menu_page(
            'Customer Migrate',
            'Customer Migrate',
            'manage_options',
            'customer-migrate',
            array($this, 'admin_page'),
            'dashicons-migrate',

        );
    }

    function create_page_if_not_exists()
    {
        $slug = 'add-payment-method'; // The slug of the page you want to create
        $page = get_page_by_path($slug);

        if (empty($page)) {
            $new_page = array(
                'post_type' => 'page',
                'post_title' => 'Add Payment Method',
                'post_name' => $slug,
                'post_status' => 'publish',
                'post_content' => '[jp-payment-page]',
                'post_author' => 1, // Replace with the ID of the user you want to assign as the author
                'post_parent' => ''
            );

            // Insert the post into the database
            $new_page_id = wp_insert_post($new_page);

            if ($new_page_id) {
            }
        }
    }


    function cm_get_active_customers()
    {
        // Get all active customers
        global $wpdb;

        $sql = "SELECT *
                FROM (
                    SELECT od.customer_id,
                    (SELECT om.meta_value FROM wp_wc_orders_meta om WHERE om.order_id=od.id and om.meta_key='_ps_scheduled_to_be_cancelled') as canclled,
                    (SELECT om.meta_value FROM wp_wc_orders_meta om WHERE om.order_id=od.id and om.meta_key='_ps_prepaid_renewals_available') as remain,
                    (SELECT om.meta_value FROM wp_wc_orders_meta om WHERE om.order_id=od.id and om.meta_key='_ps_prepaid_pieces') as plan,
                    concat(oa.first_name ,' ', oa.last_name) as name,
                    oa.address_1,
                    oa.address_2,
                    oa.country,
                    oa.city,
                    oa.postcode,
                    oa.email
                    FROM wp_wc_orders od 
                    left JOIN wp_wc_order_addresses oa on oa.order_id=od.id and oa.address_type='billing' 
                    WHERE od.`type`='shop_subscription'
                    AND od.status='wc-active'
                ) dataset

                WHERE canclled is NULL
                and (remain is NULL or remain = 0)";

        $res = $wpdb->get_results($sql);

        $ch_list = ['TW', 'HK', 'CN'];

        $out_data = [];

        foreach ($res as $row) {
            $temp['id'] = $row->customer_id;
            $temp['name'] = $row->name;
            $temp['email'] = $row->email;
            $add = [];

            if ($row->address_1)
                $add[] = $row->address_1;

            if ($row->address_2)
                $add[] = $row->address_2;

            if ($row->city)
                $add[] = $row->city;

            if ($row->postcode)
                $add[] = $row->postcode;

            if ($row->country)
                $add[] = $row->country;

            $temp['address'] = implode(', ', $add);

            if (in_array($row->country, $ch_list)) {
                $temp['lang'] = 'cn';
            } else {
                $temp['lang'] = 'en';
            }

            $out_data[] = $temp;
        }

        $out_data = array_chunk($out_data, 20);

        wp_send_json($out_data);
    }

    function cm_send_reminder()
    {

        // wp_send_json(['success' => true]);

        $data = wp_unslash($_POST['batch']);

        $data = json_decode($data, true);

        // sending mails

        $subject['cn'] = "提醒您！下一份訂閱包裹即將到來。";
        $subject['en'] = "Heads up! Your upcoming subscription order is approaching.";

        foreach ($data as $row) {

            $user = get_user_by('ID', $row['id']);

            $this->send_wp_mail($user, 'reminder_' . $row['lang'], ['address' => $row['address'], 'name' => $row['name']], $subject[$row['lang']]);
        }

        wp_send_json('success');
    }


    private function copy_page_template()
    {
        $plugin_template_path = plugin_dir_path(__FILE__) . 'template/pages/page-add-payment-method.php'; // Path to the template file in your plugin
        $theme_template_path = get_template_directory() . '/page-add-payment-method.php'; // Path to the template file in the active theme


        if (file_exists($plugin_template_path)) {

            if (!file_exists($theme_template_path) || filemtime($plugin_template_path) > filemtime($theme_template_path)) {
                copy($plugin_template_path, $theme_template_path);
            }
        }
    }

    function short_code_payment_page()
    {
        ob_start();
        include plugin_dir_path(__FILE__) . 'template/pages/page-add-payment-method.php';
        $output = ob_get_clean();
        return $output;
    }


    function bc_cm_download_csv()
    {
        $customers_1 = get_users(array(
            'role' => 'subscriber',
            'orderby' => 'display_name',
            'order' => 'ASC'
        ));

        $customers_2 = get_users(array(
            'role' => 'customer',
            'orderby' => 'display_name',
            'order' => 'ASC'
        ));

        $customers = array_merge($customers_1, $customers_2);

        $out_data = [];

        foreach ($customers as $customer) {
            $wc_cust = new WC_Customer($customer->ID);

            // prcesss subscriptions

            $subscriptions = wcs_get_users_subscriptions($customer->ID);

            $active_subscriptions = array_filter($subscriptions, function ($subscription) {
                return $subscription->has_status(array('active', 'pending-cancel', 'cancelled'));
            });

            // get first sub if any
            $next_payment = "-";
            foreach ($active_subscriptions as $sub) {
                $start_date = $sub->get_date('start_date');
                $pieces = $sub->get_meta('_ps_prepaid_pieces', true);
                $pieces = intval($pieces) > 0 ? intval($pieces) : 1;

                $next_payment = strtotime($start_date . " +" . $pieces . " months");
                $next_payment = date('Y-m-d', $next_payment);
                // $next_payment = $start_date . " /" . intval($pieces) ?? 1 . " months";


                $display_name = strlen($customer->display_name) > 0 ? $customer->display_name : $wc_cust->get_billing_first_name() . ' ' . $wc_cust->get_billing_last_name();
            }

            $out_data[] = [
                'id' => $customer->ID,
                'name' => $display_name,
                'email' => $customer->user_email,
                'phone' => $wc_cust->get_billing_phone(),
                'country' => $wc_cust->get_billing_country(),
                'sub_id' => $sub->get_id(),
                'plan' => $pieces,
                'next_renew' => $next_payment,
                'has_pm' => count(WC_Payment_Tokens::get_customer_tokens($customer->ID)) > 0 ? 'YES' : 'NO',
                'invited' => get_option('__' . $customer->ID . '_invite', false) ? 'YES' : 'NO',
                'reminded' => get_option('__' . $customer->ID . '_reminder', false) ? 'YES' : 'NO',
            ];
        }

        $file_name = 'customer_data_' . date('Y_m_d_H_i') . '.csv';

        $csv_file = plugin_dir_path(__FILE__) . 'downloads/' . $file_name;

        $headers = array_keys($out_data[0]);

        $fp = fopen($csv_file, 'w');
        fwrite($fp, "\xEF\xBB\xBF");

        fputcsv($fp, $headers);

        foreach ($out_data as $row) {

            foreach ($row as $key => $value) {

                $row[$key] = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            }

            fputcsv($fp, $row);
        }

        fclose($fp);

        $url = plugin_dir_url(__FILE__) . 'downloads/' . $file_name;

        wp_send_json_success([
            'url' => $url,
            'file_name' => $file_name
        ]);
    }

    function cm_download_csv()
    {
        global $wpdb;

        $sql = "SELECT
                od.id,
                od.status,
                od.customer_id,
                COALESCE(
                    (
                        SELECT
                            meta_value
                        FROM
                            wp_wc_orders_meta
                        WHERE
                            order_id = od.id
                            AND meta_key = '_ps_prepaid_pieces'
                        LIMIT 1
                    ),
                    1
                ) AS plan,
                COALESCE(
                                (
                                    SELECT
                                        meta_value
                                    FROM
                                        wp_wc_orders_meta
                                    WHERE
                                        meta_key = '_ps_scheduled_to_be_cancelled'
                                        AND order_id = od.id
                                ),
                                'no'
                            ) as 'cancelled',
                COALESCE(
                    (
                        SELECT
                            meta_value
                        FROM
                            wp_wc_orders_meta
                        WHERE
                            order_id = od.id
                            AND meta_key = '_ps_prepaid_renewals_available'
                        LIMIT 1
                    ),
                    0
                ) AS remaining_pieces,
                (
                    SELECT
                        meta_value
                    FROM
                        wp_wc_orders_meta
                    WHERE
                        order_id = od.id
                        AND meta_key = '_schedule_next_payment'
                    LIMIT 1
                ) AS next_shipment_date,
                CASE
                    WHEN COALESCE(
                        (
                            SELECT
                                meta_value
                            FROM
                                wp_wc_orders_meta
                            WHERE
                                order_id = od.id
                                AND meta_key = '_ps_prepaid_pieces'
                            LIMIT 1
                        ),
                        1
                    ) > 1
                    AND COALESCE(
                        (
                            SELECT
                                meta_value
                            FROM
                                wp_wc_orders_meta
                            WHERE
                                order_id = od.id
                                AND meta_key = '_ps_prepaid_renewals_available'
                            LIMIT 1
                        ),
                        0
                    ) > 0 THEN DATE_ADD(
                        (
                            SELECT
                                meta_value
                            FROM
                                wp_wc_orders_meta
                            WHERE
                                order_id = od.id
                                AND meta_key = '_schedule_next_payment'
                            LIMIT 1
                        ),
                        INTERVAL COALESCE(
                            (
                                SELECT
                                    meta_value
                                FROM
                                    wp_wc_orders_meta
                                WHERE
                                    order_id = od.id
                                    AND meta_key = '_ps_prepaid_renewals_available'
                                LIMIT 1
                            ),
                            1
                        ) MONTH
                    )
                    ELSE (
                        SELECT
                            meta_value
                        FROM
                            wp_wc_orders_meta
                        WHERE
                            order_id = od.id
                            AND meta_key = '_schedule_next_payment'
                        LIMIT 1
                    )
                END AS next_renewal_date,
                od.date_updated_gmt AS updated_at,
                ad.email AS customer_email,
                CONCAT(ad.first_name, ' ', ad.last_name) AS customer_name,
                ad.phone,
                ad.country
            FROM
                wp_wc_orders od
            JOIN
                wp_wc_order_addresses ad ON od.id = ad.order_id
                AND ad.address_type = 'billing'
            WHERE
                od.type = 'shop_subscription'
                    
                    ";


        $res = $wpdb->get_results($sql);

        $out_data = [];

        foreach ($res as $row) {

            $next_shipment = intval($row->next_shipment_date) > 0 ? date('Y-m-3 H:i', strtotime($row->next_shipment_date)) : '-';
            $next_payment = intval($row->next_renewal_date) > 0 ? date('Y-m-3 H:i', strtotime($row->next_renewal_date)) : '-';

            $out_data[] = [
                'id' => $row->id,
                'status' => $row->status,
                'prepaid_cancelled' => $row->cancelled,
                'customer_id' => $row->customer_id,
                'name' => $row->customer_name,
                'email' => $row->customer_email,
                'phone' => $row->phone,
                'country' => $row->country,
                'plan' => $row->plan,
                'remaining_pieces' => $row->remaining_pieces,
                'next_shipment' => $next_shipment,
                'next_renew' => $next_payment,
                'has_pm' => count(WC_Payment_Tokens::get_customer_tokens($row->customer_id)) > 0 ? 'YES' : 'NO',
                'invited' => get_option('__' . $row->customer_id . '_invite', false) ? 'YES' : 'NO',
                'reminded' => get_option('__' . $row->customer_id . '_reminder', false) ? 'YES' : 'NO',
            ];
        }


        $file_name = 'customer_data_' . date('Y_m_d_H_i') . '.csv';

        $csv_file = plugin_dir_path(__FILE__) . 'downloads/' . $file_name;

        $headers = array_keys($out_data[0]);

        $fp = fopen($csv_file, 'w');
        fwrite($fp, "\xEF\xBB\xBF");

        fputcsv($fp, $headers);

        foreach ($out_data as $row) {

            foreach ($row as $key => $value) {

                $row[$key] = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            }

            fputcsv($fp, $row);
        }

        fclose($fp);

        $url = plugin_dir_url(__FILE__) . 'downloads/' . $file_name;

        wp_send_json_success([
            'url' => $url,
            'file_name' => $file_name
        ]);
    }


    // ajax handlers

    function _cm_get_customers()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'customer_migrate_nonce')) {
            die('Invalid nonce');
        }

        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20;

        $customers = get_users(array(
            'role' => 'subscriber',
            'orderby' => 'display_name',
            'order' => 'ASC'
        ));

        // Calculate the offset
        $offset = ($page - 1) * $per_page;

        // Slice the array of customers based on the pagination parameters
        $paged_customers = array_slice($customers, $offset, $per_page);

        // processing data

        $out_data = [];

        foreach ($paged_customers as $customer) {
            $wc_cust = new WC_Customer($customer->ID);

            if (count(WC_Payment_Tokens::get_customer_tokens($customer->ID)) > 0) {
                continue;
            }

            // delete_option('__' . $customer->ID . '_invite');
            // delete_option('__' . $customer->ID . '_reminder');

            // prcesss subscriptions

            $subscriptions = wcs_get_users_subscriptions($customer->ID);

            $active_subscriptions = array_filter($subscriptions, function ($subscription) {
                return $subscription->has_status(array('active', 'pending-cancel'));
            });

            // get first sub if any
            $next_payment = [];
            $plans = [];
            foreach ($active_subscriptions as $sub) {
                $start_date = $sub->get_date('start_date');
                $pieces = $sub->get_meta('_ps_prepaid_pieces', true);
                $pieces = intval($pieces) > 0 ? intval($pieces) : 1;

                $_np = strtotime($start_date . " +" . $pieces . " months");
                $_np = date('Y-m-d', $_np);
                // $_np = $start_date;// . " /" . intval($pieces) ?? 1 . " months";

                $plans[] = $pieces . ' | ' . $sub->get_status();
                $next_payment[] = $_np; //$sub->get_date('next_payment');
            }

            $display_name = strlen($customer->display_name) > 0 ? $customer->display_name : $wc_cust->get_billing_first_name() . ' ' . $wc_cust->get_billing_last_name();



            $out_data[] = [
                'id' => $customer->ID,
                'name' => $display_name,
                'email' => $customer->user_email,
                'phone' => $wc_cust->get_billing_phone(),
                'country' => $wc_cust->get_billing_country(),
                'plans' => implode(', ', $plans),
                'next_renew' => implode(', ', $next_payment),
                'has_pm' => count(WC_Payment_Tokens::get_customer_tokens($customer->ID)) > 0,
                'invited' => get_option('__' . $customer->ID . '_invite', false),
                'reminded' => get_option('__' . $customer->ID . '_reminder', false),
                'reminded_ps' => get_option('__' . $customer->ID . '_reminder_ps', false),
            ];

            usort($out_data, function ($a, $b) {
                // Convert next_renew to timestamp for comparison
                $dateA = strtotime($a['next_renew']);
                $dateB = strtotime($b['next_renew']);

                // Handle cases where the date might be empty or invalid
                if ($dateA === false) {
                    return 1;
                }
                if ($dateB === false) {
                    return -1;
                }

                return $dateA <=> $dateB;
            });

            // if (strlen($customer->display_name) == 0) {
            //     wp_update_user([
            //         'ID' => $customer->ID,
            //         'display_name' => $display_name
            //     ]);
            // }


        }


        // Prepare the response
        $total_pages =  ceil(count($customers) / $per_page);

        $pages = [];

        for ($i = 1; $i <= $total_pages; $i++) {
            if ($page - 2 == $i) {
                $pages[] = $i;
            } elseif ($page - 1 == $i) {
                $pages[] = $i;
            } elseif ($page == $i) {
                $pages[] = $i;
            } elseif ($page + 1 == $i) {
                $pages[] = $i;
            } elseif ($page + 2 == $i) {
                $pages[] = $i;
            } elseif ($page + 3 == $i) {
                $pages[] = $i;
            }
        }


        $response = array(
            'data' => $out_data,
            'total' => count($customers),
            'total_pages' => $total_pages,
            'current_page' => $page,
            'per_page' => $per_page,
            'pages' => $pages
        );



        wp_send_json($response);
    }


    function cm_get_customers()
    {
        global $wpdb;

        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20;

        $offset = ($page - 1) * $per_page;

        $sql_for_total = "SELECT count(id) as total
                FROM
                    (
                        SELECT
                            od.id,                            
                            COALESCE(
                                (
                                    SELECT
                                        meta_value
                                    FROM
                                        wp_wc_orders_meta
                                    WHERE
                                        meta_key = '_ps_scheduled_to_be_cancelled'
                                        AND order_id = od.id
                                ),
                                'no'
                            ) as 'cancelled',
                            (
                                SELECT
                                    COUNT(token_id)
                                FROM
                                    wp_woocommerce_payment_tokens tk
                                WHERE
                                    tk.user_id = od.customer_id
                            ) as payment_tokens                            
                        FROM
                            wp_wc_orders od
                        WHERE
                            od.status = 'wc-active'
                            AND od.`type` = 'shop_subscription'                        
                    ) as data_set
                WHERE
                    cancelled = 'no'
                    AND payment_tokens = 0";


        $total = $wpdb->get_row($sql_for_total);

        $sql = "SELECT
                    *,
                    DATE_ADD(
                        DATE_FORMAT(
                            DATE_ADD(last_order_at, INTERVAL remain + 1 MONTH),
                            '%Y-%m-03 08:00:00'
                        ),
                        INTERVAL 0 SECOND
                    ) AS 'renewal'
                FROM
                    (
                        SELECT
                            od.id,
                            od.customer_id,
                            (
                                SELECT
                                    concat(first_name, ' ', last_name)
                                FROM
                                    wp_wc_order_addresses
                                WHERE
                                    order_id = od.id
                                    AND address_type = 'billing'
                            ) as 'name',
                            od.billing_email as 'email',
                            (
                                SELECT
                                    phone
                                FROM
                                    wp_wc_order_addresses
                                WHERE
                                    order_id = od.id
                                    AND address_type = 'billing'
                            ) as 'phone',
                            (
                                SELECT
                                    country
                                FROM
                                    wp_wc_order_addresses
                                WHERE
                                    order_id = od.id
                                    AND address_type = 'billing'
                            ) as 'country',
                            COALESCE(
                                (
                                    SELECT
                                        meta_value
                                    FROM
                                        wp_wc_orders_meta
                                    WHERE
                                        meta_key = '_ps_prepaid_pieces'
                                        AND order_id = od.id
                                ),
                                1
                            ) as 'plan',
                            COALESCE(
                                (
                                    SELECT
                                        meta_value
                                    FROM
                                        wp_wc_orders_meta
                                    WHERE
                                        meta_key = '_ps_prepaid_renewals_available'
                                        AND order_id = od.id
                                ),
                                0
                            ) as 'remain',
                            COALESCE(
                                (
                                    SELECT
                                        meta_value
                                    FROM
                                        wp_wc_orders_meta
                                    WHERE
                                        meta_key = '_ps_scheduled_to_be_cancelled'
                                        AND order_id = od.id
                                ),
                                'no'
                            ) as 'cancelled',
                            COALESCE(
                                (
                                    SELECT
                                        date_created_gmt
                                    FROM
                                        wp_wc_orders
                                    WHERE
                                        id = COALESCE(
                                            (
                                                SELECT
                                                    SUBSTRING_INDEX(SUBSTRING_INDEX(meta_value, 'i:', -1), ';', 1)
                                                FROM
                                                    wp_wc_orders_meta
                                                WHERE
                                                    meta_key = '_ps_prepaid_fulfilled_orders'
                                                    AND order_id = od.id
                                            ),
                                            0
                                        )
                                ),
                                od.date_updated_gmt
                            ) as last_order_at,
                            (
                                SELECT
                                    COUNT(token_id)
                                FROM
                                    wp_woocommerce_payment_tokens tk
                                WHERE
                                    tk.user_id = od.customer_id
                            ) as payment_tokens
                        FROM
                            wp_wc_orders od
                        WHERE
                            od.status = 'wc-active'
                            AND od.`type` = 'shop_subscription'                        
                    ) as data_set
                WHERE
                    cancelled = 'no'
                    AND payment_tokens = 0                    
                    ORDER BY renewal ASC
                    LIMIT $per_page OFFSET $offset";


        $subscription = $wpdb->get_results($sql, ARRAY_A);

        $total_pages =  ceil(intval($total->total) / $per_page);

        $out_data = [];

        foreach ($subscription as $row) {

            $out_data[] = [
                'id' => $row['id'],
                'cust_id' => $row['customer_id'],
                'name' => $row['name'],
                'email' => $row['email'],
                'phone' => $row['phone'],
                'country' => $row['country'],
                'plans' => $row['plan'],
                'next_renew' => $row['renewal'],
                'has_pm' => 0,
                'invited' => get_option('__' . $row['customer_id'] . '_invite', false),
                'reminded' => get_option('__' . $row['customer_id'] . '_reminder', false),
                'reminded_ps' => get_option('__' . $row['customer_id'] . '_reminder_ps', false),
            ];
        }

        $pages = [];

        for ($i = 1; $i <= $total_pages; $i++) {
            if ($page - 2 == $i) {
                $pages[] = $i;
            } elseif ($page - 1 == $i) {
                $pages[] = $i;
            } elseif ($page == $i) {
                $pages[] = $i;
            } elseif ($page + 1 == $i) {
                $pages[] = $i;
            } elseif ($page + 2 == $i) {
                $pages[] = $i;
            } elseif ($page + 3 == $i) {
                $pages[] = $i;
            }
        }

        $response = array(
            'data' => $out_data,
            'total' => intval($total->total),
            'total_pages' => $total_pages,
            'current_page' => $page,
            'per_page' => $per_page,
            'pages' => $pages
        );



        wp_send_json($response);
    }

    public function customer_strip_token_save()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'customer_migrate_nonce')) {
            die('Invalid nonce');
        }

        
        $options         = get_option('woocommerce_stripe_settings');
        // live keys
        $secret_key      = $options['secret_key'] ?? '';
        $publishable_key = $options['publishable_key'] ?? '';

        // test keys
        $test_secret_key = $options['test_secret_key'] ?? '';
        $test_publishable_key = $options['test_publishable_key'] ?? '';

        $stripe = new \Stripe\StripeClient($secret_key);

        $totken_data = get_option('__customer_migrate_tokens', '[]');
        $tokens = json_decode($totken_data, true);

        $user_token = $_POST['user_token'];

        $user_id = $tokens[$user_token];

        if (!$user_id) {
            wp_send_json_error(['message' => 'Invalid token']);
        }

        $token = $_POST['token'];

        $customer = get_userdata($user_id);

        $pm =  $stripe->paymentMethods->create([
            'type' => 'card',
            'card' => [
                'token' => $token['id']
            ]
        ]);

        $st_customer = $stripe->customers->create([
            'name' => $customer->display_name,
            'email' => $customer->user_email,
            'payment_method' => $pm->id,
        ]);

        update_user_meta($user_id, 'wp__stripe_customer_id', $st_customer->id);

        $card = $stripe->paymentMethods->attach(
            $pm->id,
            ['customer' => $st_customer->id]
        );

        $card = $card->card;

        $pt = new WC_Payment_Token_CC();
        $pt->set_token($pm->id);
        // $pt->set_token($token['id']);
        $pt->set_gateway_id('stripe');
        // Add other card details if needed
        $pt->set_card_type(strtolower($card->brand));
        $pt->set_last4($card->last4);
        $pt->set_expiry_month($card->exp_month);
        $pt->set_expiry_year($card->exp_year);
        $pt->set_user_id($user_id);
        $pt->set_default(true);

        // Save the token
        $pt->save();

        // if customer has existing subscriptions we need to add strip_customer id to all subscriptions

        try {
            $subscriptions = wcs_get_users_subscriptions($customer->ID);

            foreach ($subscriptions as $subscription) {
                $subscription->update_meta_data('_stripe_customer_id', $st_customer->id);
                $subscription->update_meta_data('_stripe_source_id', $pm->id);
                $subscription->save();
            }
        } catch (\Exception $ex) {
            error_log("Subscription Update Error: " . $ex->getMessage());
        }


        // $subscription->add_meta_data('_stripe_customer_id', $_stripe_customer_id);

        // clear tokens
        unset($tokens[$user_token]);

        update_option('__customer_migrate_tokens', json_encode($tokens), false);

        $page = get_page_by_path('thank-you');
        $url =  get_permalink($page);

        wp_send_json_success(['url' => $url]);
    }

    /**
     * Handle the AJAX request for the customer migrate invite.
     *
     * @return void
     */
    public function customer_migrate_invite()
    {

        if (!wp_verify_nonce($_POST['nonce'], 'customer_migrate_nonce')) {
            die('Invalid nonce');
        }

        $ch_list = ['TW', 'HK', 'CN'];

        $user_id = $_POST['id'];

        $user = get_userdata($user_id);

        $customer = new WC_Customer($user_id);
        $billing_address = [];


        $add_1 = get_user_meta($user_id, 'billing_address_1', true);
        $add_2 = get_user_meta($user_id, 'billing_address_2', true);
        $add_3 = get_user_meta($user_id, 'billing_city', true);
        $add_4 = get_user_meta($user_id, 'billing_postcode', true);

        $billing_address[] =  get_user_meta($user_id, 'billing_first_name', true) . " " . get_user_meta($user_id, 'billing_last_name', true);

        if (strlen($add_1) > 0) {
            $billing_address[] = $add_1;
        }
        if (strlen($add_2) > 0) {
            $billing_address[] = $add_2;
        }
        if (strlen($add_3) > 0) {
            $billing_address[] = $add_3;
        }
        if (strlen($add_4) > 0) {
            $billing_address[] = $add_4;
        }

        $address = implode('<br>', $billing_address);


        $CCODE = $customer->get_billing_country();

        // wp_send_json_success( $CCODE);

        // generate token

        $token = bin2hex(random_bytes(24));

        $totken_data = get_option('__customer_migrate_tokens', '[]');

        $tokens = json_decode($totken_data, true);

        // serch for existing tokens
        $tokenExist = array_search($user->ID, $tokens);

        if (!$tokenExist) {
            $tokens[$token] = $user->ID;
        } else {
            unset($tokens[$tokenExist]);
            $tokens[$token] = $user->ID;
        }

        update_option('__customer_migrate_tokens', json_encode($tokens), false);

        $template = "";
        $subject = "";

        if (in_array($CCODE, $ch_list)) {

            $template = "email_1_ch";
            $subject = "🔔重要通知：升級並推出全新旅遊商品服務";
        } else {

            $template = "email_1_en";
            $subject = "🔔Important Announcement: We are here with a new look with new travel offerings!";
        }


        if (isset($_POST['reminder']) && $_POST['reminder'] == 1) {

            if (in_array($CCODE, $ch_list)) {

                $template = "email_2_ch";
                $subject = "🛎️重要通知：請更新您的付款資料";
            } else {

                $template = "email_2_en";
                $subject = "🛎️Important Update: Keep your payment details updated and secure";
            }

            update_option('__' . $customer->get_id() . '_reminder', date('Y-m-d H:i:s'));
        } elseif (isset($_POST['reminder']) && $_POST['reminder'] == 2) {

            if (in_array($CCODE, $ch_list)) {

                $template = "email_3_ch";
                $subject = "提醒您！下一份訂閱包裹即將到來。";
            } else {

                $template = "email_3_en";
                $subject = "Heads up! Your upcoming subscription order is approaching";
            }

            update_option('__' . $customer->get_id() . '_reminder_ps', date('Y-m-d H:i:s'));
        } else {

            update_option('__' . $customer->get_id() . '_invite', date('Y-m-d H:i:s'));
        }

        wp_send_json_success($template);

        $this->send_wp_mail($user, $template, ['user' => $user, 'token' => $token, 'address' => $address], $subject);

        wp_send_json_success([]);
    }

    /**
     * Send WP mail with a given HTML template and its placeholder data
     *
     * @param string $template_file
     * @param array $placeholder_data
     * @param string $subject
     * @return void
     */
    private function send_wp_mail($user, $template_file, $placeholder_data, $subject)
    {
        $to = $user->user_email;

        $headers = [];

        $headers[] = 'From: Strip Migration <info@' . $_SERVER['SERVER_NAME'] . '>';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';

        ob_start();

        extract($placeholder_data);
        include plugin_dir_path(__FILE__) . "template/emails/" . $template_file . ".php";

        $html_content = ob_get_clean();

        wp_mail($to, $subject, $html_content, $headers);
    }



    public function admin_page()
    {
        include plugin_dir_path(__FILE__) . '/template/admin.php';
    }
}

// Initialize the plugin.
new Customer_Migrate();
