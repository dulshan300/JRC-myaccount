<?php

final class MAV2ShortCode
{

    public function test_code()
    {

        ob_start();
        include_once MAV2_PATH . 'views/shortcodes/test.php';
        return ob_get_clean();
    }


    public function user_subscriptions()
    {
        ob_start();
        include_once MAV2_PATH . 'views/shortcodes/user_subscriptions.php';
        return ob_get_clean();
    }

    public function user_orders()
    {
        ob_start();
        include_once MAV2_PATH . 'views/shortcodes/user_orders.php';
        return ob_get_clean();
    }

    public function user_address_update()
    {
        ob_start();
        include_once MAV2_PATH . 'views/shortcodes/user_address_update.php';
        return ob_get_clean();
    }

    public function user_account_details_update()
    {
        ob_start();
        include_once MAV2_PATH . 'views/shortcodes/user_account_details_update.php';
        return ob_get_clean();
    }

    public function user_password_update()
    {
        ob_start();
        include_once MAV2_PATH . 'views/shortcodes/user_password_update.php';
        return ob_get_clean();
    }

    public function user_payment_methods()
    {
        ob_start();
        include_once MAV2_PATH . 'views/shortcodes/user_payment_methods.php';
        return ob_get_clean();
    }
}
