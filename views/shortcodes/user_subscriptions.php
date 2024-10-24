<?php
$user_id = get_current_user_id();

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

$w_countries = new WC_Countries();
$all_countries = $w_countries->get_countries();

foreach ($res as $sub) {
    $temp = [];
    $temp['id'] = $sub->id;
    $temp['status'] = $sub->prepaid_cancel === 'yes' ? 'wc-cancelled' : $sub->status;
    $temp['prepaid_cancel'] = $sub->prepaid_cancel;
    $temp['product'] = $sub->product;
    $temp['plan'] = (intval($sub->plan) > 1 ? $sub->plan . ' Months' : $sub->plan . ' Month') . " Plan";

    if ($sub->plan == 1) {
        $temp['shipped'] = '1' . " of " . $sub->plan;
    } else {

        $temp['shipped'] = intval($sub->plan) - intval($sub->to_ship) . " of " . $sub->plan;
    }

    $temp['next_shipment_date'] = date('Y-m-03', strtotime($sub->next_shipment_date));

    $sub_orders = [$sub->parent_order_id];
    $sub_orders = array_merge($sub_orders, unserialize($sub->renewal_ids));
    $last_sub = end($sub_orders);


    // getting order details
    $osql = "SELECT
    od.id,
    od.currency as currency,
    od.date_created_gmt as created_at,
    (
        SELECT
            om.meta_value * omm.meta_value
        FROM
            wp_woocommerce_order_items oi
            left JOIN wp_woocommerce_order_itemmeta om ON om.order_item_id = oi.order_item_id
            left JOIN wp_wc_orders_meta omm ON omm.order_id = oi.order_id
            AND omm.meta_key = 'yay_currency_order_rate'
        WHERE
            oi.order_id = od.id
            AND om.meta_key = '_line_subtotal'
    ) as subtotal,
    COALESCE(
        (
            SELECT
                sum(oim.meta_value)
            FROM
                wp_woocommerce_order_items oi
                LEFT JOIN wp_woocommerce_order_itemmeta oim ON oim.order_item_id = oi.order_item_id
                AND oim.meta_key = 'cost'
            WHERE
                oi.order_id = od.id
                AND oi.order_item_type = 'shipping'
        ),
        0
    ) as 'shipping',
    COALESCE(
        ABS(
            (
                SELECT
                    meta_value
                from
                    wp_woocommerce_order_itemmeta
                WHERE
                    order_item_id =(
                        SELECT
                            order_item_id
                        FROM
                            wp_woocommerce_order_items oi
                        WHERE
                            (
                                oi.order_item_type = 'coupon'
                                OR oi.order_item_type = 'fee'
                            )
                            AND order_id = od.id
                        LIMIT
                            1
                    )
                    AND (
                        meta_key = 'discount_amount'
                        OR meta_key = '_fee_amount'
                    )
            )
        ),
        0
    ) as 'discount',
    od.total_amount,
    COALESCE(
        REPLACE (
            (
                SELECT
                    item.order_item_name
                FROM
                    wp_wc_orders orders
                    LEFT JOIN wp_woocommerce_order_items item ON item.order_id = orders.id
                WHERE
                    item.order_id = od.parent_order_id
                    AND item.order_item_type IN ('coupon', 'fee')
                LIMIT
                    1
            ), 'Discount: ', ''
        ), ''
    ) AS 'coupon'
    from
        wp_wc_orders od
    WHERE
        od.id = $last_sub
    ";

    $odata = $wpdb->get_row($osql);
    $temp['created_at'] = date('d F Y', strtotime($odata->created_at . ' + 8 hours'));
    $temp['product_value'] = number_format(floatval($odata->subtotal), 2);
    $temp['shipping'] = number_format(floatval($odata->shipping), 2);
    $temp['discount'] = floatval($odata->discount) > 0 ? number_format(floatval($odata->discount), 2) . " ({$odata->coupon})" : "0.00";
    $temp['total'] = number_format(floatval($odata->total_amount), 2);
    $temp['currency'] = get_woocommerce_currency_symbol($odata->currency);


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
        if (!empty($shppng_data[$k])) {
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
        $last_date = $order->get_date_paid()->date;
    }

    $temp['next_payment'] = date('03 F Y', strtotime(date('Y-m-03', $last_date) . ' +' . ($sub->plan > 1 ? $sub->to_ship + 1 : 1) . ' month'));

    $out_data[] = $temp;
}

?>

<!-- default sass

.sub_card {
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        list-style: none;
    }
    margin-bottom: 20px;
    display: flex;
    flex-direction: column;

    .sub_card_details {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .sub_plan_details,.sub_product_values,.sub_plan_status {
        display: flex;
        flex-direction: column;
    }
}



-->


<!-- default style -->
<!-- <style>
    .sub_card {
        margin-bottom: 20px;
        display: flex;
        flex-direction: column;
    }

    .sub_card * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        list-style: none;
    }

    .sub_card .sub_card_details {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .sub_card .sub_plan_details,
    .sub_card .sub_product_values,
    .sub_card .sub_plan_status {
        display: flex;
        flex-direction: column;
    }
</style> -->

<!-- generate html -->
<div id="sub_cards">
    <?php foreach ($out_data as $sub): ?>

        <div class="sub_card">
            <div class="sub_card_header">
                <span class="sub_id">Subscription ID: #<?php echo $sub['id']; ?></span>
                <h3 class="sub_product_name"><?php echo $sub['product']; ?></h3>
                <h4 class="sub_plan_name"><?php echo $sub['plan']; ?></h4>
            </div>

            <div class="sub_card_details">

                <!-- plan details -->
                <div class="sub_plan_details">
                    <span class="sub_plan_shipped">No. of Boxes Shipped: <?php echo $sub['shipped']; ?></span>
                    <span class="sub_plan_created">Subscription Date: <?php echo $sub['created_at']; ?></span>
                    <?php if ($sub['status'] != 'wc-cancelled'): ?>
                        <span class="sub_plan_next_renew">Next Payment Renewal Date: <?php echo $sub['next_payment']; ?></span>
                    <?php endif ?>

                    <div class="sub_plan_status">
                        <?php if ($sub['status'] === 'wc-active'): ?>
                            <button data-sub="<?php echo $sub['id']; ?>" class="sub_button sub_plan_cancel_sub">Cancel Subscription</button>
                        <?php elseif ($sub['status'] === 'wc-cancelled'): ?>
                            <span class="sub_status sub_plan_inactive">Cancelled</span>
                        <?php else: ?>
                            <span class="sub_status sub_plan_pending">Pending</span>
                        <?php endif; ?>

                        <span class="sub_plan_cancel_note">Note: Cancellation will take effect after your plan ends</span>
                    </div>
                </div>

                <!-- product values -->
                <div class="sub_product_values">
                    <span class="sub_product_value">Product Value: <?php echo $sub['currency'] . $sub['product_value']; ?></span>
                    <span class="sub_product_subtotal">Subtotal: <?php echo $sub['currency'] . $sub['product_value']; ?></span>
                    <span class="sub_product_shipping">Shipping: <?php echo $sub['currency'] . $sub['shipping']; ?></span>
                    <span class="sub_product_discount">Discount: <?php echo $sub['currency'] . $sub['discount']; ?></span>
                    <span class="sub_product_total">Total: <?php echo $sub['currency'] . $sub['total']; ?></span>
                </div>

                <!-- address details -->

                <div class="sub_plan_shipping">
                    <ul>
                        <?php foreach ($sub['address'] as $line): ?>
                            <li class="sub_address_line"><?= $line ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

            </div>


        </div>

    <?php endforeach; ?>

    <!-- if no subscriptions -->
    <?php if (empty($out_data)): ?>

        <p>No Subscriptions Found</p>

    <?php endif; ?>
</div>