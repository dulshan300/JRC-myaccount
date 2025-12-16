<?php
$user_id = get_current_user_id();

if (empty($user_id)) {
    echo 'Please login to see your orders';

    return;
}

// echo "<pre>";

global $wpdb;



$sql = "SELECT od.id, od.currency, od.date_created_gmt,pl.order_item_id, od.customer_id, pl.product_id, od.status, ( SELECT post_title FROM wp_posts WHERE ID = pl.product_id ) AS product, ( SELECT guid FROM wp_posts WHERE ID = ( SELECT meta_value FROM wp_postmeta WHERE post_id = pl.product_id AND meta_key ='_thumbnail_id') ) AS product_image_url, COALESCE(cl.discount_amount, 0) AS discount_amount, ( SELECT post_title FROM wp_posts WHERE ID = cl.coupon_id ) AS coupon, pl.product_net_revenue, pl.product_gross_revenue, od.total_amount, -- Extract individual meta values
 MAX( CASE WHEN oim.meta_key ='Participation Date'THEN oim.meta_value END ) AS participation_date, MAX( CASE WHEN oim.meta_key ='First Name'THEN oim.meta_value END ) AS first_name, MAX( CASE WHEN oim.meta_key ='Last Name'THEN oim.meta_value END ) AS last_name, MAX( CASE WHEN oim.meta_key ='Email Address'THEN oim.meta_value END ) AS email_address, MAX( CASE WHEN oim.meta_key ='Date Of Birth'THEN oim.meta_value END ) AS date_of_birth, MAX( CASE WHEN oim.meta_key ='Country'THEN oim.meta_value END ) AS country, MAX( CASE WHEN oim.meta_key ='Telephone Number'THEN oim.meta_value END ) AS telephone_number, MAX( CASE WHEN oim.meta_key ='Gender'THEN oim.meta_value END ) AS gender, MAX( CASE WHEN oim.meta_key ='ticket-type'THEN oim.meta_value END ) AS ticket_type FROM wp_wc_orders od LEFT JOIN wp_wc_order_product_lookup pl ON pl.order_id = od.id LEFT JOIN wp_woocommerce_order_itemmeta oim ON oim.order_item_id = pl.order_item_id LEFT JOIN wp_wc_order_coupon_lookup cl ON cl.order_id = od.id WHERE pl.product_id != 198 AND od.customer_id = $user_id GROUP BY od.id, od.currency, pl.order_item_id, od.customer_id, pl.product_id, od.status, pl.product_net_revenue, pl.product_gross_revenue, od.total_amount, cl.discount_amount, cl.coupon_id, pl.variation_id ORDER BY pl.variation_id ASC";

$rows = $wpdb->get_results($sql);

$orders = [];

$_itmes_value = [];

$tickets_meta = [
    "first_name" => "First Name",
    "participation_date" => "Participation Date",
    "last_name" => "Last Name",
    "email_address" => "Email Address",
    "date_of_birth" => "Date Of Birth",
    "country" => "Country",
    "telephone_number" => "Telephone Number",
    "gender" => 'Gender',
    "ticket_type" => 'Ticket Type'
];

foreach ($rows as $row) {
    $orders[$row->id]['id'] = $row->id;
    $orders[$row->id]['status'] = ucwords(str_replace('wc-', '', $row->status));
    $orders[$row->id]['product'] = $row->product;
    $orders[$row->id]['product_image'] = $row->product_image_url;
    $orders[$row->id]['type'] = is_null($row->ticket_type) ? 'General' : 'ticket';

    // format time from gtm to sgt 03 Jnue 2024 8:00 am
    $date_created_gmt = new DateTime($row->date_created_gmt, new DateTimeZone('GMT'));
    $date_created_gmt->setTimezone(new DateTimeZone('Asia/Singapore'));
    $formatted_sgt_time = $date_created_gmt->format('d M Y g:i a T');


    $orders[$row->id]['date_created_gmt'] = $formatted_sgt_time;
    $orders[$row->id]['discount_amount'] = number_format(floatval($row->discount_amount), 2);
    $orders[$row->id]['coupon'] = $row->coupon ? '( ' . $row->coupon . ' )' : '';

    if (array_search($row->order_item_id, $_itmes_value) === false) {
        $orders[$row->id]['items_value'] += floatval($row->product_net_revenue);
        $_itmes_value[] = $row->order_item_id;
    }

    $orders[$row->id]['total'] = number_format(floatval($row->total_amount), 2);
    $orders[$row->id]['currency'] = get_woocommerce_currency_symbol($row->currency);

    // clean meta key
    $key = $row->meta_key;
    $key = str_replace('-', ' ', $key);
    $key = ucwords($key);

    $items = [];

    foreach ($tickets_meta as $key => $value) {
        if (!is_null($row->$key)) {
            $items[$value] = $row->$key;
        }
    }

    $orders[$row->id]['items'][$row->order_item_id] = $items;
}

$meta_order = ['First Name', 'Last Name', 'Email Address', 'Ticket Type', 'Participation Date', 'Date Of Birth', 'Country'];

// format items data

foreach ($orders as $oid => $order) {
    $order_items = $order['items'];

    foreach ($order_items as $id => $values) {
        $new_order = [];

        foreach ($meta_order as $key) {

            if (array_search($key, array_keys($values)) !== false) {
                $new_order[$key] = $values[$key];
            }
        }

        $full_name = [$new_order['First Name'], $new_order['Last Name']];
        $full_name = implode(' ', $full_name);
        unset($new_order['First Name']);
        unset($new_order['Last Name']);
        $_temp = [];
        $_temp['Full Name'] = $full_name;

        $new_order = array_merge($_temp, $new_order);
        $order['items'][$id] = $new_order;
    }

    $order['items_value'] = number_format($order['items_value'], 2);

    $orders[$oid] = $order;
}
?>

<div id="order_cards">

    <table>
        <thead>
            <th>Order No.</th>
            <th>Product</th>
            <th>Paid On</th>
            <th>Amount</th>
            <th> Invoice </th>
        </thead>

        <tbody>


            <?php foreach ($orders as $order) { ?>
                <tr>
                    <td>#<?= $order['id'] ?></td>
                    <td><?= $order['product'] ?></td>
                    <td><?= $order['date_created_gmt'] ?></td>
                    <td><?= $order['currency'] ?><?= $order['total'] ?></td>
                    <td><button data-id="<?= $order['id'] ?>" class="invoice_download"><span></span>Download</button></td>
                </tr>

            <?php } ?>

            <!-- if no subscriptions -->
            <?php if (empty($orders)) { ?>
                <tr>
                    <td colspan="5">
                        <p>No Order Found</p>
                    </td>
                </tr>

            <?php } ?>
        </tbody>
    </table>
</div>