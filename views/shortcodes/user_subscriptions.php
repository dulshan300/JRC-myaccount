<?php
$user_id = get_current_user_id();
// $user_id = 1446;



if (empty($user_id)) {
    echo 'Please login to see your subscription';

    return;
}

// get all the subs with id, plan,prepaid_data and renewal

global $wpdb;

// _subscription_renewal | wp_wc_orders_meta | for renewal orders
// _ps_scheduled_to_be_cancelled | wp_wc_orders_meta | for cancelled orders

$sql = "SELECT
    od.id,
    od.status,
    'Omiyage Snack Box Subscription' as product,
    od.parent_order_id,
    (
        SELECT
            meta_value
        FROM
            wp_wc_orders_meta
        WHERE
            meta_key = '_subscription_renewal_order_ids_cache'
            AND order_id = od.id
    ) as renewal_ids,
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
    ) AS plan,
    COALESCE(
        (
            SELECT
                meta_value
            FROM
                wp_wc_orders_meta
            WHERE
                order_id = od.id
                AND meta_key = '_ps_prepaid_renewals_available'
            LIMIT
                1
        ), 1
    ) AS to_ship,
    COALESCE(
        (
            SELECT
                meta_value
            FROM
                wp_wc_orders_meta
            WHERE
                order_id = od.id
                AND meta_key = '_ps_prepaid_fulfilled_orders'
            LIMIT
                1
        ), 'a:0:{}'
    ) AS fullfilled,
    (
        SELECT
            meta_value
        FROM
            wp_wc_orders_meta
        WHERE
            order_id = od.id
            AND meta_key = '_schedule_next_payment'
        LIMIT
            1
    ) AS next_shipment_date,
    (
        SELECT
            meta_value
        FROM
            wp_wc_orders_meta
        WHERE
            order_id = od.id
            AND meta_key = '_ps_scheduled_to_be_cancelled'
            AND meta_value = 'yes'
    ) AS prepaid_cancel
    
from
    wp_wc_orders od
WHERE
    od.`type` = 'shop_subscription'
        AND od.customer_id = $user_id
ORDER BY od.date_created_gmt DESC";

$res = $wpdb->get_results($sql);

$out_data = [];

$w_countries = new WC_Countries;
$all_countries = $w_countries->get_countries();

$orders_history = [];

function get_tracking($order)
{
    if ($order->tracking == 404) {
        $order->tracking = 'Tracking pending';
    } else {

        // $pattern_JP = '/\b([A-Z0-9]{13})\b/';
        $pattern_JP = '/RN([\d+]+)JP/';
        $pattern_US = '/\b(\d+)\b/';

        if (preg_match($pattern_JP, $order->tracking, $matches)) {
            $trackingNumber = $matches[0]; // The captured tracking number            
            $link = 'https://trackings.post.japanpost.jp/services/srv/search/?requestNo1=%20' . $trackingNumber . '&search.x=68&search.y=17&search=Tracking+start&locale=ja&startingUrlPatten=';
            $order->tracking = "<a href='{$link}' target='_blank'>{$trackingNumber}</a>";
        } else if (preg_match($pattern_US, $order->tracking, $matches)) {
            // us
            $trackingNumber = $matches[1]; // The captured tracking number            
            $us_link = 'https://parcelsapp.com/en/tracking/' . $trackingNumber;
            $order->tracking = "<a href='{$us_link}' target='_blank'>{$trackingNumber}</a>";
        }

        /*

        // some of traking code is here
        $traking = $order->tracking;
        $search = "Tracking number(s):";
        $traking = str_replace("<br/>", '', $traking);

        $str_pos = strpos($traking, $search);
        $traking = substr($traking, $str_pos);
        $traking = str_replace($search, '', $traking);
        $traking = str_replace("<br/>", '', $traking);
        $traking = trim($traking);

        $order->tracking = $traking;

        if ($order->country == 'US') {
            $us_link = 'https://parcelsapp.com/en/tracking/' . $traking;
            $order->tracking = "<a href='{$us_link}' target='_blank'>{$order->tracking}</a>";
        } else {
            $link = 'https://trackings.post.japanpost.jp/services/srv/search/?requestNo1=%20' . $traking . '&search.x=68&search.y=17&search=Tracking+start&locale=ja&startingUrlPatten=';
            $order->tracking = "<a href='{$link}' target='_blank'>{$order->tracking}</a>";
        }
            */
    }

    return $order->tracking;
}

foreach ($res as $sub) {
    $temp = [];
    $temp['id'] = $sub->id;
    $temp['status'] = $sub->prepaid_cancel === 'yes' ? 'wc-cancelled' : $sub->status;
    $temp['prepaid_cancel'] = $sub->prepaid_cancel;
    $temp['product'] = $sub->product;
    $temp['plan'] = (intval($sub->plan) > 1 ? $sub->plan . ' Months' : $sub->plan . ' Month') . ' Plan';
    $temp['order_history'] = [];

    if ($sub->plan == 1) {
        $temp['shipped'] = '1' . ' of ' . $sub->plan;
    } else {

        $temp['shipped'] = intval($sub->plan) - intval($sub->to_ship) . ' of ' . $sub->plan;
    }

    $temp['next_shipment_date'] = date('Y-m-03', strtotime($sub->next_shipment_date));

    $sub_orders = [$sub->parent_order_id];
    // if renewal orders
    $renew_orders = unserialize($sub->renewal_ids);
    $renew_orders= array_reverse($renew_orders);
    $sub_orders = array_merge($sub_orders, $renew_orders);
    $last_sub = end($sub_orders);

    // getting order details
    $osql = "SELECT od.id, od.currency as currency, od.date_created_gmt as created_at, ( SELECT om.meta_value*omm.meta_value FROM wp_woocommerce_order_items oi left JOIN wp_woocommerce_order_itemmeta om ON om.order_item_id = oi.order_item_id left JOIN wp_wc_orders_meta omm ON omm.order_id = oi.order_id AND omm.meta_key ='yay_currency_order_rate'WHERE oi.order_id = od.id AND om.meta_key ='_line_subtotal') as subtotal, COALESCE( ( SELECT sum(oim.meta_value) FROM wp_woocommerce_order_items oi LEFT JOIN wp_woocommerce_order_itemmeta oim ON oim.order_item_id = oi.order_item_id AND oim.meta_key ='cost'WHERE oi.order_id = od.id AND oi.order_item_type ='shipping'), 0 ) as'shipping', COALESCE( ABS( ( SELECT meta_value from wp_woocommerce_order_itemmeta WHERE order_item_id =( SELECT order_item_id FROM wp_woocommerce_order_items oi WHERE ( oi.order_item_type ='coupon'OR oi.order_item_type ='fee') AND order_id = od.id LIMIT 1 ) AND ( meta_key ='discount_amount'OR meta_key ='_fee_amount') ) ), 0 ) as'discount', od.total_amount, COALESCE( REPLACE ( ( SELECT item.order_item_name FROM wp_wc_orders orders LEFT JOIN wp_woocommerce_order_items item ON item.order_id = orders.id WHERE item.order_id = od.id AND item.order_item_type IN ('coupon','fee') LIMIT 1 ),'Discount: ',''),'') AS'coupon'from wp_wc_orders od WHERE od.id = $last_sub";

    $odata = $wpdb->get_row($osql);
    $temp['created_at'] = date('d F Y', strtotime($odata->created_at . ' + 8 hours'));
    $temp['product_value'] = number_format(floatval($odata->subtotal), 2);
    $temp['shipping'] = number_format(floatval($odata->shipping), 2);
    $temp['discount'] = floatval($odata->discount) > 0 ? number_format(floatval($odata->discount), 2) . " ({$odata->coupon})" : '0.00';
    $temp['total'] = number_format(floatval($odata->total_amount), 2);
    $temp['currency'] = get_woocommerce_currency_symbol($odata->currency);

    // get last order status
    $last_order_id = end($sub_orders);

    $orders_history = $sub_orders;

    if ($sub->plan != 1) {
        $al = unserialize($sub->fullfilled);
        $last_order_id = end($al);
        $orders_history = unserialize($sub->fullfilled);
    }

    $lo_sql = "SELECT od.id, od.status,od.date_created_gmt, COALESCE( ( SELECT comment_content from wp_comments WHERE comment_post_ID = od.id AND comment_content LIKE '%%Tracking number%%'ORDER BY comment_date_gmt DESC LIMIT 1 ),404) as tracking, (SELECT country FROM wp_wc_order_addresses WHERE order_id=od.id LIMIT 1) country from wp_wc_orders od WHERE od.id=%s";

    $lo_q = $wpdb->prepare($lo_sql, $last_order_id);

    $lo_data = $wpdb->get_row($lo_q);

    $lo_data->tracking = get_tracking($lo_data);

    $temp_history = [];

    // process order history
    foreach ($orders_history as $order_id) {
        $q = $wpdb->prepare($lo_sql, $order_id);
        $q_data = $wpdb->get_row($q);

        $format      = "Y-m-d h:s a";
        $check_stamp = date_i18n($format, $q_data->date_created_gmt);

        $hd = [
            'id' => $q_data->id,
            'status' => str_replace('wc-', '', $q_data->status),
            'date' => $q_data->date_created_gmt,
            'date_loc' => $check_stamp,
            'tracking' => get_tracking($q_data)
        ];

        $temp_history[] = $hd;
    }


    $temp['order_history'] = array_reverse($temp_history);

    $temp['last_order_details'] = $lo_data;



    // get order shipping address
    $shppng_sql = "SELECT
    concat(ad.first_name, ' ', ad.last_name) as full_name,
    ad.company,
    ad.address_1,
    ad.address_2,
    ad.city,
    ad.`state`,
    ad.postcode,
    ad.country
    FROM
        wp_wc_orders od
        LEFT JOIN wp_wc_order_addresses ad ON ad.order_id = od.id
        AND ad.address_type = 'shipping'
    WHERE
        od.id = {$sub->id}";

    $shppng_data = $wpdb->get_row($shppng_sql, ARRAY_A);
    $address = [];
    $keys = array_keys($shppng_data);

    foreach ($keys as $k) {
        if (! empty($shppng_data[$k])) {
            if ($k === 'country') {
                $shppng_data[$k] = $all_countries[$shppng_data[$k]];
            }

            $address[] = $shppng_data[$k];
        }
    }

    $temp['address'] = $address;

    // getting next payement details
    $fullfilled = unserialize($sub->fullfilled);
    $last_box = end($fullfilled);

    $last_date = strtotime($odata->created_at . ' + 8 hours');
    $order = wc_get_order($last_box);

    if ($order) {
        // $last_date = $order->get_date_paid()->date;
    }

    $temp['next_payment'] = date('03 F Y', strtotime(date('Y-m-03', $last_date) . ' +' . ($sub->plan > 1 ? $sub->to_ship + 1 : 1) . ' month'));

    $out_data[] = $temp;
}

?>


<!-- generate html -->
<div id="sub_cards">
    <?php foreach ($out_data as $sub) { ?>
        <div class="sub_card">
            <div class="sub_card_header">
                <div class="left">
                    <span class="sub_id"><span>Subscription ID:</span> #<?php echo $sub['id']; ?></span>
                    <h3 class="sub_product_name"><?php echo $sub['product']; ?></h3>
                    <h4 class="sub_plan_name"><?php echo $sub['plan']; ?></h4>
                </div>
                <div class="right">
                    <div class="traking">
                        <!-- truck svg -->

                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M16 3H1V16H16V3Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M16 8H20L23 11V16H16V8Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M5.5 21C6.88071 21 8 19.8807 8 18.5C8 17.1193 6.88071 16 5.5 16C4.11929 16 3 17.1193 3 18.5C3 19.8807 4.11929 21 5.5 21Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M18.5 21C19.8807 21 21 19.8807 21 18.5C21 17.1193 19.8807 16 18.5 16C17.1193 16 16 17.1193 16 18.5C16 19.8807 17.1193 21 18.5 21Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>

                        <!-- end truck svg -->

                        <div class="tracking_number">
                            <span>Track My Latest Order:</span>
                            <span>
                                <?= $sub['last_order_details']->tracking; ?>
                            </span>
                        </div>

                    </div>
                </div>
            </div>

            <div class="sub_card_details">

                <!-- plan details -->
                <div class="sub_plan_details">
                    <span class="sub_plan_shipped"><span>No. of Boxes Shipped:</span> <?php echo $sub['shipped']; ?></span>
                    <span class="sub_plan_created"><span>Subscription Date:</span> <?php echo $sub['created_at']; ?></span>
                    <?php if ($sub['status'] != 'wc-cancelled') { ?>
                        <span class="sub_plan_next_renew"><span>Next Payment Renewal Date:</span> <?php echo $sub['next_payment']; ?></span>
                    <?php } ?>

                    <div class="sub_plan_status">
                        <div class="sub_action_buttons">


                            <?php if ($sub['status'] === 'wc-active') { ?>
                                <button type="button" data-sub="<?php echo $sub['id']; ?>" class="sub_button sub_plan_cancel_sub">Cancel Subscription</button>
                            <?php } elseif ($sub['status'] === 'wc-cancelled') { ?>
                                <span class="sub_status sub_plan_inactive">Cancelled</span>
                            <?php } else { ?>
                                <span class="sub_status sub_plan_pending">Pending</span>
                            <?php } ?>

                            <button type="button" data-popup="sub_update" data-sub="<?php echo $sub['id']; ?>" class="sub_button sub_update_button">Update Subscription</button>
                        </div>

                        <span class="sub_plan_cancel_note">Note: Cancellation & Update will take effect only after your current cycle ends.</span>
                    </div>
                </div>

                <!-- product values -->
                <div class="sub_product_values">
                    <span class="sub_product_value"><span>Product Value:</span> <?php echo $sub['currency'] . $sub['product_value']; ?></span>
                    <span class="sub_product_subtotal"><span>Subtotal:</span> <?php echo $sub['currency'] . $sub['product_value']; ?></span>
                    <span class="sub_product_shipping"><span>Shipping:</span> <?php echo $sub['currency'] . $sub['shipping']; ?></span>
                    <span class="sub_product_discount"><span>Discount:</span> <?php echo $sub['currency'] . $sub['discount']; ?></span>
                    <span class="sub_product_total"><span>Total:</span> <?php echo $sub['currency'] . $sub['total']; ?></span>
                </div>

                <!-- address details -->

                <div class="sub_plan_shipping">
                    <ul>
                        <?php foreach ($sub['address'] as $line) { ?>
                            <li class="sub_address_line"><?= $line ?></li>
                        <?php } ?>
                    </ul>
                </div>

            </div>

            <div class="order_history">
                <div class="oh_header">
                    <span class="oh_title">Order History</span>
                    <button type="button" class="arrow">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M11.9999 13.1714L16.9497 8.22168L18.3639 9.63589L11.9999 15.9999L5.63599 9.63589L7.0502 8.22168L11.9999 13.1714Z"></path>
                        </svg>
                    </button>
                </div>

                <div style="display: none;" class="order_history_list">
                    <table>
                        <tr>
                            <th>Order ID</th>
                            <th>Date (GMT)</th>
                            <th>Tracking No.</th>
                        </tr>
                        <?php foreach ($sub['order_history'] as $order) { ?>
                            <tr class="order_history_item">
                                <td class="order_status"><?= $order['id'] ?></td>
                                <td class="order_date"><?= $order['date'] ?></td>
                                <td class="order_tracking"><?= $order['tracking'] ?></td>
                            </tr>
                        <?php } ?>

                    </table>

                </div>
            </div>




        </div>

    <?php } ?>

    <!-- if no subscriptions -->
    <?php if (empty($out_data)) { ?>

        <p>No Subscriptions Found</p>

    <?php } ?>
</div>


<!-- subcription cancel notes -->
<div id="sub_cancel" class="sub_cancel_popup">
    <div class="popup_wrapper">
        <div class="cancel-popup-content">
            <div data-panel="1" class="panel">

                <div class="popup-header">
                    <h3>Wait! Are You Sure You Want to Cancel?</h3>
                    <button class="close-popup">&times;</button>
                </div>

                <div class="popup-content">
                    <p>You're about to miss out on:</p>

                    <div class="icon-text"><span>🎁</span>
                        <p>Exclusive Monthly Omiyage Boxes filled with unique Japanese snacks and treats.</p>
                    </div>
                    <div class="icon-text"><span>🌸</span>
                        <p>Access to Exclusive Events featuring authentic Japanese experiences.</p>
                    </div>
                    <div class="icon-text"><span>🚄</span> Special JR EAST Deals curated just for members.</div>
                </div>

                <div class="popup-footer">
                    <button type="button" class="btn-close btn-blue">Back to account</button>
                    <button type="button" data-current="1" data-next="2" class="btn-next btn-gray">Proceed to cancel</button>
                </div>

            </div>
            <div data-panel="2" style="display: none;" class="panel">
                <div class="popup-header">
                    <div class="">
                        <h3>We are sad to see you go!</h3>
                        <p>Please select your reason for cancellation:</p>
                    </div>
                    <button class="close-popup">&times;</button>
                </div>

                <form id="cancellationForm">
                    <div id="reasons_container" class="reasons-container">
                        <label class="reason-item">
                            <input type="radio" name="cancel_reason" value="1">
                            <span>The subscription cost is too high.</span>
                        </label>

                        <label class="reason-item">
                            <input type="radio" name="cancel_reason" value="2">
                            <span>Limited variety of snacks</span>
                        </label>

                        <label class="reason-item">
                            <input type="radio" name="cancel_reason" value="3">
                            <span>Too many snacks delivered each month</span>
                        </label>

                        <label class="reason-item">
                            <input type="radio" name="cancel_reason" value="4">
                            <span>Poor snack quality</span>

                        </label>

                        <label class="reason-item">
                            <input type="radio" name="cancel_reason" value="5">
                            <span>I only wanted a one-time subscription</span>
                        </label>

                        <label class="reason-item">
                            <input type="radio" name="cancel_reason" value="6">
                            <span>Frequent delivery delays</span>
                        </label>

                        <label class="reason-item">
                            <input type="radio" name="cancel_reason" value="7">
                            <span>I've lost interest in receiving regular snacks</span>
                        </label>

                        <label class="reason-item">
                            <input type="radio" name="cancel_reason" value="8">
                            <span>I've moved to a new address</span>
                        </label>

                        <label class="reason-item">
                            <input type="radio" name="cancel_reason" value="9">
                            <span>Others</span>
                        </label>
                    </div>

                    <div class="feedback-box" style="display:none;">
                        <textarea id="feedback_box" placeholder="Please share with us your thoughts"></textarea>
                    </div>

                    <div class="popup-footer">
                        <button type="button" class="btn-close btn-cancel">Cancel</button>
                        <button type="submit" class="btn-confirm">Confirm Cancellation</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<div id="sub_update" class="mav2_popup">
    <div class="popup_wrapper">
        <div class="popup-content">
            <div class="popup-header">
                <h3>Update Subscription</h3>
                <button class="close-popup">&times;</button>
            </div>
            <div class="popup-body">


            </div>
        </div>
    </div>

</div>

<!-- end subcription cancel notes -->