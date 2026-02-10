<?php

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

final class MAV2_Admin
{
    public function init()
    {
        // add_action('admin_menu', [$this, 'admin_menu']);

        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
        add_action('wp_enqueue_scripts', [$this, 'site_enqueue_scripts']);
        add_action('get_stripe_keys', [$this, 'get_stripe_keys_callback'], 10, 1);

        // register ajax
        $this->register_ajax_handlers();
        $this->register_shortcode();
    }

    public function get_stripe_keys_callback($type)
    {
        // Replace with your logic to retrieve Stripe keys based on $type
        $keys = $this->get_stripe_keys();

        return isset($keys[$type]) ? $keys[$type] : '';
    }

    public function admin_menu()
    {
        add_menu_page(
            'MY Account V2',
            'MY Account V2',
            'manage_options',
            'mav2',
            [$this, 'admin_page'],
            'dashicons-analytics',
            5
        );
    }

    public function admin_enqueue_scripts($hook)
    {

        if ($hook !== 'toplevel_page_mav2') {
            return;
        }

        error_log('Loading Admin Scripts for MAV2');

        wp_enqueue_style('mav2-admin-css', MAV2_ASSETS_URL . 'css/mav2.admin.css', [], MAV2_VERSION);

        wp_enqueue_script('mav2-vuejs', MAV2_ASSETS_URL . 'vendors/vue.js', ['jquery'], MAV2_VERSION, true);
        wp_enqueue_script('mav2-axios', MAV2_ASSETS_URL . 'vendors/axios.js', ['mav2-vuejs'], MAV2_VERSION, true);
        wp_enqueue_script('mav2-admin-js', MAV2_ASSETS_URL . 'js/mav2.admin.js', ['mav2-vuejs', 'mav2-axios'], MAV2_VERSION, true);

        wp_localize_script('mav2-admin-js', 'JRCAnalytics', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('jrc-analytics'),
        ]);

        error_log('Scripts and styles enqueued');
        error_log('MAV2_ASSETS_URL: ' . MAV2_ASSETS_URL);
        error_log('MAV2_VERSION: ' . MAV2_VERSION);
    }

    private function get_timestamp($file)
    {
        $file_path = MAV2_ASSETS_DIR . $file;
        if (file_exists($file_path)) {
            return filemtime($file_path);
        }

        return MAV2_VERSION;
    }

    public function site_enqueue_scripts($hook)
    {

        if (is_page('short-code-test') || is_page('my-account')) {

            wp_enqueue_style('mav2-site-css', MAV2_ASSETS_URL . 'css/mav2.site.css', [], $this->get_timestamp('css/mav2.site.css'));

            wp_enqueue_script('mav2-axios', MAV2_ASSETS_URL . 'vendors/axios.js', [], $this->get_timestamp('vendors/axios.js'), true);
            wp_enqueue_script('mav2-vuejs', MAV2_ASSETS_URL . 'vendors/vue.js', ['jquery'], $this->get_timestamp('vendors/vue.js'), false);
            wp_enqueue_script('mav2-site-js', MAV2_ASSETS_URL . 'js/mav2.site.js', ['jquery', 'mav2-axios', 'mav2-vuejs'], $this->get_timestamp('js/mav2.site.js'), true);

            wp_localize_script('mav2-site-js', 'mav2', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mav2-nonce'),
            ]);
        }
    }

    // get stripe keys
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

    public function register_ajax_handlers()
    {
        $ajax_instance = new MAV2_Ajax_Admin();
        $reflection = new ReflectionClass($ajax_instance);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            $action = 'wp_ajax_mav2_' . $method->name;
            add_action($action, [$ajax_instance, $method->name]);

            $action_nopriv = 'wp_ajax_nopriv_mav2_' . $method->name;
            add_action($action_nopriv, [$ajax_instance, $method->name]);
        }
    }

    public function register_shortcode()
    {
        $short_codes = new MAV2ShortCode();
        $reflection = new ReflectionClass($short_codes);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            $action = 'mav2_' . $method->name;
            add_shortcode($action, [$short_codes, $method->name]);
        }
    }

    public function admin_page()
    {
        ob_start();
        include_once MAV2_PATH . 'views/admin.php';
        echo ob_get_clean();
        ob_flush();
    }
}
