<?php
$user_id = get_current_user_id();

if (empty($user_id)) {
    echo 'Please login to see your subscription';

    return;
}

// Simplified card-only version of user_subscriptions.php: fetches only the
// fields the grouped subscription cards render (id, product, plan, price,
// currency, status, image). Clicking a card switches to the my-subscriptions
// tab and hands off the subscription id to that shortcode's Vue app instead
// of showing any detail/edit panels here.

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

$_product_img = get_the_post_thumbnail_url(198, 'medium') ?: '';

foreach ($res as $sub) {
    $temp = [];
    $temp['id'] = $sub->id;
    $temp['product_img'] = $_product_img;
    $temp['status'] = $sub->status;
    $temp['product'] = $sub->product;
    $temp['plan_raw'] = $sub->plan;

    $sub_orders = [$sub->parent_order_id];
    // meta can be missing (new subs) so unserialize may return false
    $renew_orders = unserialize((string) $sub->renewal_ids, ['allowed_classes' => false]);
    $renew_orders = is_array($renew_orders) ? array_reverse($renew_orders) : [];
    $sub_orders = array_merge($sub_orders, $renew_orders);

    $sub_orders = array_filter(array_map('intval', $sub_orders));

    $str_ids = implode(',', $sub_orders);

    // latest shipped order (prepaid plans track fulfilment separately), same
    // selection logic as user_subscriptions.php
    $last_order_id = end($sub_orders);
    if ($sub->plan != 1) {
        $al = unserialize((string) $sub->fullfilled, ['allowed_classes' => false]);
        $al = is_array($al) ? $al : [];
        $last_order_id = end($al);
    }

    $tracking = mav2_get_order_tracking_code($last_order_id);
    $temp['tracking'] = ($last_order_id && $tracking === '') ? 'Tracking pending' : $tracking;

    // find the most recent order with an actual charge, to show as the card price
    $osql = "SELECT od.id, od.currency, od.total_amount FROM wp_wc_orders od LEFT JOIN wp_woocommerce_order_items oi1 ON oi1.order_id = od.id AND oi1.order_item_type IN ('coupon','fee') LEFT JOIN wp_woocommerce_order_itemmeta meta_discount ON oi1.order_item_id = meta_discount.order_item_id AND meta_discount.meta_key ='discount_amount'
    WHERE od.id IN ($str_ids) AND ( meta_discount.meta_value > 0 OR od.total_amount > 0 ) ORDER BY od.date_created_gmt DESC LIMIT 1";

    // query can return no row (filters on discount/total), so guard before reading
    $odata = $str_ids !== '' ? $wpdb->get_row($osql) : null;
    $temp['total'] = number_format($odata ? floatval($odata->total_amount) : 0, 2);

    $name = $sub->currency;
    $symbol = get_woocommerce_currency_symbol($name);
    $currency = $symbol;
    if ($name != 'TWD') {
        $currency = $name . $currency;
    }

    $temp['currency'] = $currency;

    $out_data[] = $temp;
}

// unique per instance so multiple placements of this shortcode on one page
// don't collide on DOM id / mount selector
$container_id = wp_unique_id('mav2_subscription_simple_app_');

?>

<div id="<?php echo esc_attr($container_id); ?>">

    <!-- tab bar -->
    <div class="sub-tabs">
        <button :class="['sub-tab', { active: activeTab === 'active' }]"
                @click="activeTab = 'active'">Active</button>
        <button :class="['sub-tab', { active: activeTab === 'inactive' }]"
                @click="activeTab = 'inactive'">Inactive</button>
    </div>

    <!-- generate html -->
    <div id="sub_cards">

        <template v-for="sub in activeTab === 'active' ? activeSubscriptions : inactiveSubscriptions">

            <!-- compact list card -->
            <div class="sub-list-card" @click="goToSubscription(sub.id)">
                <div class="sub-thumb-wrap">
                    <img
                        :src="sub.product_img"
                        class="sub-thumb"
                        alt="Subscription image"
                    />
                </div>

                <div class="sub-list-body">
                    <div class="sub-list-top">
                        <span class="sub-name">{{ sub.product }}</span>
                        <span class="sub-freq-badge">
                            {{ sub.plan_raw == 1 ? 'Every month' : 'Every ' + sub.plan_raw + ' months' }}
                        </span>
                    </div>
                    <div class="sub-list-price" v-html="sub.currency + sub.total"></div>
                    <div class="sub-list-tracking" v-if="sub.tracking">Tracking No: {{ sub.tracking }}</div>
                    <a class="sub-view-link" @click.stop="goToSubscription(sub.id)">View plan</a>
                </div>

                <div class="sub-list-arrow">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 18l6-6-6-6"/>
                    </svg>
                </div>
            </div>

        </template>

        <!-- if no subscriptions in current tab -->
        <p v-if="activeTab === 'active' && !activeSubscriptions.length">No active subscriptions found.</p>
        <p v-if="activeTab === 'inactive' && !inactiveSubscriptions.length">No inactive subscriptions found.</p>

    </div>

</div>

<?php include MAV2_PATH . 'assets/js/vue_subscription_app_simple.js.php'; ?>
