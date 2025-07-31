<?php
$user_id = get_current_user_id();
// $user_id = 1482;
// $user_id = 1791;

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
    od.currency,
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

function mav2_get_tracking($order)
{
    if ($order->tracking == 404) {
        $order->tracking = 'Tracking pending';
    } else {

        // $pattern_JP = '/\b([A-Z0-9]{13})\b/';
        $pattern_JP = '/([A-Z]+)([\d+]+)JP/';
        $pattern_US = '/\b(\d+)\b/';

        if (preg_match($pattern_JP, $order->tracking, $matches)) {
            $trackingNumber = $matches[0]; // The captured tracking number            
            $link = 'https://trackings.post.japanpost.jp/services/srv/search/?requestNo1=' . $trackingNumber . '&search.x=68&search.y=17&search=Tracking+start&locale=ja&startingUrlPatten=';
            $order->tracking = '<a href="' . $link . '" target="_blank">' . $trackingNumber . '</a>';
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
    $temp['plan_raw'] = $sub->plan;
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
    $renew_orders = array_reverse($renew_orders);
    $sub_orders = array_merge($sub_orders, $renew_orders);

    // var_dump($sub_orders);

    $last_sub = end($sub_orders);

    $str_ids = implode(',', $sub_orders);

    // getting order details

    /*
        old version: subtoal calculation
        wp_woocommerce_order_itemmeta _line_subtotal save as SGD and wp_wc_orders_meta has yay_currency_order_rate key
        for save the rate of SGD to relevent currency at that time. so calculation was _line_subtotal * yay_currency_order_rate;

        new version: subtotal calculation (2025-06-24)
        wp_woocommerce_order_itemmeta _line_subtotal save as the selected currency
        so subtotal calculation is _line_subtotal
    */

    $osql = "SELECT od.id, od.currency, od.date_created_gmt AS created_at, od.total_amount, oi1.order_item_name AS coupon, meta_discount.meta_value AS discount, meta_subtotal.meta_value AS subtotal FROM wp_wc_orders od LEFT JOIN wp_woocommerce_order_items oi1 ON oi1.order_id = od.id AND oi1.order_item_type IN ('coupon','fee') LEFT JOIN wp_woocommerce_order_items oi2 ON oi2.order_id = od.id AND oi2.order_item_type IN ('line_item') LEFT JOIN wp_woocommerce_order_itemmeta meta_discount ON oi1.order_item_id = meta_discount.order_item_id AND meta_discount.meta_key ='discount_amount'LEFT JOIN wp_woocommerce_order_itemmeta meta_subtotal ON oi2.order_item_id = meta_subtotal.order_item_id AND meta_subtotal.meta_key ='_line_subtotal'WHERE od.id IN ($str_ids) AND ( meta_discount.meta_value > 0 OR od.total_amount > 0 ) ORDER BY od.date_created_gmt DESC LIMIT 1";

    $odata = $wpdb->get_row($osql);
    $temp['created_at'] = date('j F Y', strtotime($odata->created_at . ' + 8 hours'));
    $temp['product_value'] = number_format(floatval($odata->subtotal), 2);
    $temp['shipping'] = number_format(floatval($odata->shipping), 2);
    $temp['discount'] = floatval($odata->discount) > 0 ? number_format(floatval($odata->discount), 2) . " ({$odata->coupon})" : '0.00';
    $temp['total'] = number_format(floatval($odata->total_amount), 2);

    $name = $sub->currency;
    $symbol = get_woocommerce_currency_symbol($name);
    $currency = $symbol;
    if ($name != 'TWD') $currency = $name . $currency;

    $temp['currency'] = $currency;

    // get last order status
    $last_order_id = end($sub_orders);



    $orders_history = $sub_orders;

    if ($sub->plan != 1) {
        $al = unserialize($sub->fullfilled);
        $last_order_id = end($al);
        $orders_history = unserialize($sub->fullfilled);
    }

    $lo_sql = "SELECT od.id, od.status,od.date_updated_gmt,od.date_created_gmt, COALESCE( ( SELECT comment_content from wp_comments WHERE comment_post_ID = od.id AND comment_content LIKE '%%Tracking number%%'ORDER BY comment_date_gmt DESC LIMIT 1 ),404) as tracking, (SELECT country FROM wp_wc_order_addresses WHERE order_id=od.id LIMIT 1) country from wp_wc_orders od WHERE od.id=%s";

    $lo_q = $wpdb->prepare($lo_sql, $last_order_id);

    $lo_data = $wpdb->get_row($lo_q);

    $lo_data->tracking = mav2_get_tracking($lo_data);

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
            'tracking' => mav2_get_tracking($q_data)
        ];

        $temp_history[] = $hd;
    }

    // echo '<pre>';
    // print_r($temp_history);


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

    $last_date = strtotime($lo_q->date_updated_gmt . ' + 8 hours');
    $order = wc_get_order($last_box);

    $temp['next_payment'] = date('j F Y', strtotime(date('Y-m-03', $last_date) . ' +' . ($sub->plan > 1 ? $sub->to_ship + 1 : 1) . ' month'));

    $out_data[] = $temp;
}

?>

<script>
    const _subscription_data = <?php echo json_encode($out_data); ?>;
</script>

<div id="subscription_app">

    <div v-if="false" class="loading" style="width: 100%;">
        <div class="spinner-mini"></div>
        Loading subscriptions. please wait...
    </div>


    <template v-if="true">

        <!-- generate html -->
        <div id="sub_cards">

            <template v-for="sub in subscription_data">

                <div class="sub_card">
                    <div class="sub_card_header">
                        <div class="left">
                            <span class="sub_id"><span>Subscription ID:</span> #{{sub.id}}</span>
                            <h3 class="sub_product_name">{{sub.product}}</h3>
                            <h4 class="sub_plan_name">{{sub.plan}}</h4>
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
                                    <span v-html="sub.last_order_details.tracking"> </span>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="sub_card_details">

                        <!-- plan details -->
                        <div class="sub_plan_details">
                            <span class="sub_plan_shipped"><span>No. of Boxes Shipped:&nbsp; </span> {{sub.shipped}}</span>
                            <span class="sub_plan_created"><span>Subscription Date:&nbsp;</span> {{sub.created_at}}</span>
                            <span v-if="sub.status != 'wc-cancelled'" class="sub_plan_next_renew"><span>Next Payment Renewal Date:&nbsp;</span> {{sub.next_payment}}</span>


                            <div class="sub_plan_status">
                                <div class="sub_action_buttons">
                                    <button v-if="sub.status == 'wc-active'" type="button" @click.prevent="showCancleOpenPopup(sub.id,sub.plan_raw)" class="sub_button sub_plan_cancel_sub">Cancel Subscription</button>
                                    <button v-if="sub.status == 'wc-active'" type="button" @click.prevent="showUpdatePopup(sub.id)" class="sub_button sub_plan_cancel_sub">Change Subscription</button>

                                    <span v-else-if="sub.status == 'wc-cancelled'" class="sub_status sub_plan_inactive">Cancelled</span>

                                    <span v-else class="sub_status sub_plan_pending">Pending</span>

                                </div>

                                <span class="sub_plan_cancel_note">Note: Cancellation & Update will take effect only after your current cycle ends.</span>
                            </div>
                        </div>

                        <!-- product values -->
                        <div class="sub_product_values">
                            <span class="sub_product_value"><span>Product Value:&nbsp;</span> <span v-html="sub.currency + sub.product_value"> </span></span>
                            <span class="sub_product_subtotal"><span>Subtotal:&nbsp;</span> <span v-html="sub.currency + sub.product_value"> </span></span>
                            <span class="sub_product_shipping"><span>Shipping:&nbsp;</span> <span v-html="sub.currency + sub.shipping"> </span></span>
                            <span class="sub_product_discount"><span>Discount:&nbsp;</span> <span v-html="sub.currency + sub.discount"> </span></span>
                            <span class="sub_product_total"><span>Total:&nbsp;</span> <span v-html="sub.currency + sub.total"> </span></span>
                        </div>

                        <!-- address details -->

                        <div class="sub_plan_shipping">
                            <ul>

                                <li v-for="link in sub.address" class="sub_address_line">{{line}}</li>

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
                            <table class="order_history_table">
                                <tr>
                                    <th>Order ID</th>
                                    <th>Date (GMT)</th>
                                    <th>Tracking No.</th>
                                </tr>

                                <tr v-for="order in sub.order_history" class="order_history_item">
                                    <td class="order_status">{{ order.id }}</td>
                                    <td class="order_date">{{ order.date }}</td>
                                    <td class="order_tracking" v-html="order.tracking"></td>
                                </tr>


                            </table>

                        </div>
                    </div>

                </div>

            </template>

            <!-- if no subscriptions -->
            <p v-if="!subscription_data.length">No Subscriptions Found</p>


        </div>

        <!-- Data processing and loading panel -->
        <Transition name="fade">
            <v-popup id="loading_data" v-show="current_panel==PANELS.LOADING" :can-close="false">
                <div class="loading" style="width: 100%;height:100px">
                    <div class="spinner-mini"></div>
                    <p>{{processing_text}}</p>
                </div>
            </v-popup>
        </Transition>

        <!-- error view panel -->
        <Transition name="fade">
            <v-popup v-show="current_panel==PANELS.ERROR" @close="closePopup" id="erro_view">

                <div class="loading error" style="width: 100%;height:100px">

                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="48px" height="48px">
                        <path fill="#f44336" d="M44,24c0,11.045-8.955,20-20,20S4,35.045,4,24S12.955,4,24,4S44,12.955,44,24z" />
                        <path fill="#fff" d="M29.656,15.516l2.828,2.828l-14.14,14.14l-2.828-2.828L29.656,15.516z" />
                        <path fill="#fff" d="M32.484,29.656l-2.828,2.828l-14.14-14.14l2.828-2.828L32.484,29.656z" />
                    </svg>

                    <p>{{error_text}}</p>
                </div>
            </v-popup>
        </Transition>

        <!-- Change Plan Panle -->
        <Transition name="fade">
            <v-popup v-show="current_panel==PANELS.CHANGE_PLAN" id="change_plan" @close="closePopup">

                <template v-slot:icon>
                    <svg width="52" height="52" viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <g filter="url(#filter0_d_2770_14901)">
                            <rect x="2" y="1" width="48" height="48" rx="10" fill="white" />
                            <rect x="2.5" y="1.5" width="47" height="47" rx="9.5" stroke="#E9EAEB" />
                            <path d="M36 23H16M25 32L32.8 32C33.9201 32 34.4802 32 34.908 31.782C35.2843 31.5903 35.5903 31.2843 35.782 30.908C36 30.4802 36 29.9201 36 28.8V21.2C36 20.0799 36 19.5198 35.782 19.092C35.5903 18.7157 35.2843 18.4097 34.908 18.218C34.4802 18 33.9201 18 32.8 18H31M25 32L27 34M25 32L27 30M21 32H19.2C18.0799 32 17.5198 32 17.092 31.782C16.7157 31.5903 16.4097 31.2843 16.218 30.908C16 30.4802 16 29.9201 16 28.8V21.2C16 20.0799 16 19.5198 16.218 19.092C16.4097 18.7157 16.7157 18.4097 17.092 18.218C17.5198 18 18.0799 18 19.2 18H27M27 18L25 20M27 18L25 16" stroke="#414651" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </g>
                        <defs>
                            <filter id="filter0_d_2770_14901" x="0" y="0" width="52" height="52" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                                <feFlood flood-opacity="0" result="BackgroundImageFix" />
                                <feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha" />
                                <feOffset dy="1" />
                                <feGaussianBlur stdDeviation="1" />
                                <feColorMatrix type="matrix" values="0 0 0 0 0.0392157 0 0 0 0 0.0509804 0 0 0 0 0.0705882 0 0 0 0.05 0" />
                                <feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_2770_14901" />
                                <feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_2770_14901" result="shape" />
                            </filter>
                        </defs>
                    </svg>
                </template>

                <template v-slot:title>
                    <h3 class="header_text_title">Choose Your Plan</h3>
                    <p class="header_text_sub_title">If your needs have changed, there's a plan that fits just right.</p>
                </template>

                <ul class="jrc_plans">
                    <li class="jrc_plan current_plan">
                        <p class="plan_summery"><strong>{{current_plan.name}}</strong> <span v-html="current_plan.price_per_month"></span>/month</p>
                        <span>Current Plan</span>

                        <div class="plan_check">
                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-icon lucide-check">
                                <path d="M20 6 9 17l-5-5" />
                            </svg>
                        </div>

                    </li>

                    <li
                        @click.prevent="selectPlan(plan.id)"
                        :key="plan.id" class="jrc_plan"
                        :class="{'selected_plan': selected_plan_id==plan.id,'pending_plan':plan.update_pending}"
                        v-for="plan in plan_selection">

                        <p class="plan_summery"><strong>{{plan.name}} {{plan.plan==12?'- Best Value':''}}</strong> <span v-html="plan.price_per_month"></span>/month</p>
                        <p v-if="plan.note !=''" class="plan_note">{{plan.note}}</p>
                        <div v-if="plan.has_saving" class="price_sec">
                            <span class="total" v-html="'Total: '+plan.price"></span>
                            <span class="saving" v-html="`You save ${plan.save}`"></span>
                        </div>
                        <!-- if this is a pending plan -->
                        <div v-if="plan.update_pending" class="plan_pending_update">

                            <svg width="20" height="20" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <g clip-path="url(#clip0_2829_94)">
                                    <path d="M5.00004 9.16671C7.30123 9.16671 9.16671 7.30123 9.16671 5.00004C9.16671 2.69885 7.30123 0.833374 5.00004 0.833374C2.69885 0.833374 0.833374 2.69885 0.833374 5.00004C0.833374 7.30123 2.69885 9.16671 5.00004 9.16671Z" stroke="white" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M5 6.66667V5" stroke="white" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M5 3.33337H5.00417" stroke="white" stroke-linecap="round" stroke-linejoin="round" />
                                </g>
                                <defs>
                                    <clipPath id="clip0_2829_94">
                                        <rect width="10" height="10" fill="white" />
                                    </clipPath>
                                </defs>
                            </svg>

                            <p>Your selected new plan will begin automatically on the {{next_renew_at}}. Select another plan & confirm to change. <a @click.prevent="cancel_plan_change" href="">Click here to keep my current plan</a></p>


                        </div>
                        <div class="plan_check">
                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-icon lucide-check">
                                <path d="M20 6 9 17l-5-5" />
                            </svg>
                        </div>
                    </li>


                </ul>

                <template v-slot:footer>
                    <p><strong>IMPORTANT</strong>: Changes to your subscription will take effect after your current cycle ends on {{next_renew_at}}.</p>
                    <div class="jrc_popup_panel_footer_buttons">
                        <button type="button" @click.prevent="closePopup" class="jrc_popup_panel_btn jrc_popup_panel_btn_secondary">Cancel</button>
                        <button
                            :disabled="selected_plan_id==''"
                            type="button"
                            @click.prevent="confirmUpdate"
                            class="jrc_popup_panel_btn jrc_popup_panel_btn_primary">Confirm</button>

                    </div>

                </template>

            </v-popup>
        </Transition>

        <!-- Upgrade Plan Success Panel -->
        <Transition name="fade">
            <v-popup v-show="current_panel==PANELS.CHANGE_PLAN_CONFIRM" id="upgrade_plan_success" @close="closePopup">


                <template v-slot:icon>
                    <svg width="56" height="56" viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="4" y="4" width="48" height="48" rx="24" fill="#D1FADF" />
                        <rect x="4" y="4" width="48" height="48" rx="24" stroke="#ECFDF3" stroke-width="8" />
                        <path d="M23.5 28L26.5 31L32.5 25M38 28C38 33.5228 33.5228 38 28 38C22.4772 38 18 33.5228 18 28C18 22.4772 22.4772 18 28 18C33.5228 18 38 22.4772 38 28Z" stroke="#039855" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </template>



                <template v-slot:title>
                    <h3 class="header_text_title">Your Subscription Has Been Updated!</h3>
                    <p class="header_text_sub_title">Thank you for updating your Omiyage Snack Box Subscription Plan. Here are the details of your new subscription:</p>
                </template>



                <ul>
                    <li>📦 <strong> Selected Plan</strong>: {{selected_plan.name}} Plan</li>
                    <li>📅 <strong> Effective From</strong>: {{next_renew_at}}</li>
                    <li>💳 <strong> Updated Plan Price</strong>: <span v-html="selected_plan.price"></span></li>
                    <li v-if="selected_plan.has_saving">💰 <strong> Total Savings</strong>: <span v-html="selected_plan.save"></span></li>
                </ul>

                <p>You'll also receive an email confirmation with these details for your records.</p>

                <p>Thank you for being part of the JAPAN RAIL CLUB family!</p>



                <template v-slot:footer>
                    <div class="jrc_popup_panel_footer_buttons">
                        <button @click.prevent="closePopup" type="button" class="jrc_popup_panel_btn jrc_popup_panel_btn_primary">Back to My Account</button>

                    </div>
                </template>

            </v-popup>
        </Transition>

        <!-- Coupone deal befor cancel -->
        <Transition name="fade">
            <v-popup v-show="current_panel==PANELS.COUPON_APPLY" id="coupon_deal_for_cancel" @close="closePopup">

                <template v-slot:title>
                    <h3 class="header_text_title">Stay with JAPAN RAIL CLUB and get a {{coupon_box.discount}}% discount.</h3>
                </template>

                <p>Your current plan: {{coupon_box.plan}}</p>
                <p>If you take up our offer, you save Total: <span v-html="coupon_box.saving"></span> on the {{coupon_box.renew_at}}</p>

                <template v-slot:footer>
                    <div class="jrc_popup_panel_footer_buttons col">
                        <button @click.prevent="acceptCouponOffer" type="button" class="jrc_popup_panel_btn jrc_popup_panel_btn_primary">Yes, I'll take the {{coupon_box.discount}}% OFF</button>
                        <button @click.prevent="current_panel=PANELS.CANCEL_WAIT" type="button" class="jrc_popup_panel_btn cancel">No, continue cancelling</button>

                    </div>

                </template>

            </v-popup>
        </Transition>

        <!-- Apply Coupon Success Panel -->
        <Transition name="fade">
            <v-popup v-show="current_panel==PANELS.COUPON_APPLY_SUCCESS" id="upgrade_plan_success" @close="closePopup">


                <template v-slot:icon>
                    <svg width="56" height="56" viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="4" y="4" width="48" height="48" rx="24" fill="#D1FADF" />
                        <rect x="4" y="4" width="48" height="48" rx="24" stroke="#ECFDF3" stroke-width="8" />
                        <path d="M23.5 28L26.5 31L32.5 25M38 28C38 33.5228 33.5228 38 28 38C22.4772 38 18 33.5228 18 28C18 22.4772 22.4772 18 28 18C33.5228 18 38 22.4772 38 28Z" stroke="#039855" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </template>


                <template v-slot:title>
                    <h3 class="header_text_title">Coupon Applied Successfully!</h3>
                    <p class="header_text_sub_title">Thank you for using Omiyage Snack Box Subscription Plan. Here is the Summery for the next renewal:</p>
                </template>

                <ul>
                    <li>📦 <strong> Plan</strong>: {{coupon_box.plan}} Plan</li>
                    <li>📅 <strong> Effective From</strong>: {{coupon_box.renew_at}}</li>
                    <li>💳 <strong> Discounted Price</strong>: <span v-html="coupon_box.price"></span></li>
                    <li>💰 <strong> Saving</strong>: <span v-html="coupon_box.saving"></span></li>
                </ul>

                <p>You'll also receive an email confirmation with these details for your records.</p>

                <p>Thank you for being part of the JAPAN RAIL CLUB family!</p>



                <template v-slot:footer>
                    <div class="jrc_popup_panel_footer_buttons">
                        <button @click.prevent="closePopup" type="button" class="jrc_popup_panel_btn jrc_popup_panel_btn_primary">Back to My Account</button>

                    </div>
                </template>

            </v-popup>
        </Transition>


        <!-- Cancel Open Panel -->
        <Transition name="fade">
            <v-popup v-show="current_panel==PANELS.CANCEL_OPEN" id="cancel_open" @close="closePopup">

                <template v-slot:title>
                    <h3 class="header_text_title">Wait! Before You Cancel...</h3>
                </template>

                <p>Did you know you can change your Omiyage Snack Box subscription anytime?</p>
                <p>Would you like to switch to another subscription plan instead?</p>



                <template v-slot:footer>
                    <div class="jrc_popup_panel_footer_buttons col">
                        <button @click.prevent="showUpdatePopup()" type="button" class="jrc_popup_panel_btn jrc_popup_panel_btn_primary">Change Plan</button>
                        <button @click.prevent="cancel_anyway_handler" type="button" class="jrc_popup_panel_btn cancel">Cancel anyways</button>
                        <!-- <button @click.prevent="current_panel=PANELS.CANCEL_WAIT" type="button" class="jrc_popup_panel_btn cancel">Cancel anyways</button> -->

                    </div>

                </template>

            </v-popup>
        </Transition>

        <!-- Cancel Wait -->
        <Transition name="fade">
            <v-popup v-show="current_panel==PANELS.CANCEL_WAIT" id="cancel_wait" @close="closePopup">

                <template v-slot:title>
                    <h3 class="header_text_title">Wait! Are You Sure You Want to Cancel?</h3>
                    <p class="header_text_sub_title">You're about to miss out on:</p>
                </template>



                <div class="icon-text">
                    <span>🎁</span>
                    <p>Exclusive Monthly Omiyage Boxes filled with unique Japanese snacks and treats.</p>
                </div>
                <div class="icon-text">
                    <span>🌸</span>
                    <p>Access to Exclusive Events featuring authentic Japanese experiences.</p>
                </div>
                <div class="icon-text">
                    <span>🚄</span>
                    <p>Special JR EAST Deals curated just for members.</p>
                </div>



                <template v-slot:footer>
                    <div class="jrc_popup_panel_footer_buttons">
                        <button @click.prevent="closePopup" class="jrc_popup_panel_btn jrc_popup_panel_btn_primary">Back to My Account</button>
                        <button @click.prevent="current_panel=PANELS.CANCEL_NOTE" class="jrc_popup_panel_btn jrc_popup_panel_btn_secondary">Proceed to cancel</button>

                    </div>

                </template>

            </v-popup>
        </Transition>

        <!-- Cancel Note -->
        <Transition name="fade">
            <v-popup v-show="current_panel==PANELS.CANCEL_NOTE" id="cancel_note" @close="closePopup">

                <template v-slot:title>
                    <h3 class="header_text_title">We are sad to see you go!</h3>
                    <p class="header_text_sub_title">Please select your reason for cancellation:</p>
                </template>

                <div id="reasons_container" class="reasons-container">
                    <template v-for="reason in reasons" :key="reason.id">

                        <label :for="'r_' + reason.id" class="reason-item">
                            <input
                                type="radio"
                                :id="'r_' + reason.id"
                                v-model="cancel_reason"
                                :value="reason.id" />
                            <span>{{ reason.text }}</span>
                        </label>

                    </template>

                    <!-- for other reason   -->
                    <div v-if="cancel_reason === 9" class="feedback-box">
                        <textarea
                            v-model="other_reasons"
                            id="feedback_box"
                            placeholder="Please share with us your thoughts"></textarea>

                        <div v-if="other_reasons_error" style="color:#fd4747;font-size:12px">Feedback should contain atleast 6 characters</div>
                    </div>

                </div>

                <template v-slot:footer>
                    <div class="jrc_popup_panel_footer_buttons">
                        <button @click.prevent="closePopup" class="jrc_popup_panel_btn jrc_popup_panel_btn_primary">Back to My Account</button>
                        <button @click.prevent="processCancel" class="jrc_popup_panel_btn jrc_popup_panel_btn_secondary">Proceed to cancel</button>

                    </div>

                </template>

            </v-popup>
        </Transition>

    </template>
</div>


<?php include MAV2_PATH . 'assets/js/vue_subscription_app.js.php'; ?>