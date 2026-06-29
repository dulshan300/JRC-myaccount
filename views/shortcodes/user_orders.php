<?php
$user_id = get_current_user_id();

if (empty($user_id)) {
    echo 'Please login to see your orders';
    return;
}

global $wpdb;

$sql = $wpdb->prepare(
    "SELECT
        od.id,
        od.type,
        od.status,
        od.currency,
        od.date_created_gmt,
        od.total_amount,
        (
            SELECT meta_value
            FROM wp_wc_orders_meta
            WHERE order_id = od.id
              AND meta_key = '_ps_prepaid_pieces'
            LIMIT 1
        ) AS subscription_plan
    FROM wp_wc_orders od
    WHERE od.customer_id = %d
      AND od.type IN ('shop_order', 'shop_subscription', 'shop_order_renewal')
      AND od.total_amount > 0
    ORDER BY od.date_created_gmt DESC",
    $user_id
);

$rows = $wpdb->get_results($sql);

if (!function_exists('mav2_orders_payment_status')) {
    function mav2_orders_payment_status($status)
    {
        $paid    = ['wc-completed', 'wc-processing', 'wc-active', 'wc-pending-cancel'];
        $failed  = ['wc-failed', 'wc-cancelled', 'wc-refunded', 'wc-expired'];
        if (in_array($status, $paid))   return ['label' => 'Paid',    'class' => 'mav2-badge-paid'];
        if (in_array($status, $failed)) return ['label' => 'Failed',  'class' => 'mav2-badge-failed'];
        return ['label' => 'Pending', 'class' => 'mav2-badge-pending'];
    }
}

if (!function_exists('mav2_orders_fulfillment_status')) {
    function mav2_orders_fulfillment_status($status)
    {
        if ($status === 'wc-completed') return ['label' => 'Completed', 'class' => 'mav2-badge-completed'];
        return ['label' => 'Processing', 'class' => 'mav2-badge-processing'];
    }
}

if (!function_exists('mav2_orders_subscription_plan')) {
    function mav2_orders_subscription_plan($plan_raw)
    {
        if (is_null($plan_raw) || $plan_raw === '') return '-';
        $n = intval($plan_raw);
        if ($n === 1) return 'Monthly';
        return $n . ' months';
    }
}

$orders = [];
$myaccount_url = wc_get_page_permalink('myaccount');

foreach ($rows as $row) {
    $date = new DateTime($row->date_created_gmt, new DateTimeZone('GMT'));
    $date->setTimezone(new DateTimeZone('Asia/Singapore'));

    $orders[] = [
        'id'                 => $row->id,
        'date'               => $date->format('d M Y'),
        'subscription_plan'  => mav2_orders_subscription_plan($row->subscription_plan),
        'payment_status'     => mav2_orders_payment_status($row->status),
        'fulfillment_status' => mav2_orders_fulfillment_status($row->status),
        'total'              => number_format(floatval($row->total_amount), 2),
        'currency'           => get_woocommerce_currency_symbol($row->currency),
        'order_url'          => wc_get_endpoint_url('view-order', $row->id, $myaccount_url),
    ];
}
?>

<style>
    #mav2-orders-table { width: 100%; border-collapse: collapse; }
    #mav2-orders-table th,
    #mav2-orders-table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
    #mav2-orders-table th { font-weight: 600; background: #f9fafb; }
    #mav2-orders-table td a { color: inherit; text-decoration: underline; }
    .mav2-badge { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; }
    .mav2-badge-paid        { background: #d1fae5; color: #065f46; }
    .mav2-badge-failed      { background: #fee2e2; color: #991b1b; }
    .mav2-badge-pending     { background: #fef3c7; color: #92400e; }
    .mav2-badge-completed   { background: #dbeafe; color: #1e40af; }
    .mav2-badge-processing  { background: #ede9fe; color: #5b21b6; }
    #mav2-orders-pagination { display: flex; align-items: center; gap: 12px; margin-top: 16px; justify-content: flex-end; }
    #mav2-orders-pagination button { padding: 6px 14px; border: 1px solid #d1d5db; border-radius: 6px; background: #fff; cursor: pointer; font-size: 13px; }
    #mav2-orders-pagination button:disabled { opacity: 0.4; cursor: default; }
    #mav2-page-info { font-size: 13px; color: #6b7280; }
</style>

<div id="order_cards">

    <table id="mav2-orders-table">
        <thead>
            <tr>
                <th>Order</th>
                <th>Date</th>
                <th>Subscription Plan</th>
                <th>Payment Status</th>
                <th>Fulfillment Status</th>
                <th>Total</th>
                <th>Invoice</th>
            </tr>
        </thead>
        <tbody>

            <?php foreach ($orders as $index => $order) { ?>
                <tr class="mav2-order-row" data-row-index="<?= $index ?>">
                    <td>
                        <a href="<?= esc_url($order['order_url']) ?>" target="_blank" rel="noopener">
                            #<?= esc_html($order['id']) ?>
                        </a>
                    </td>
                    <td><?= esc_html($order['date']) ?></td>
                    <td><?= esc_html($order['subscription_plan']) ?></td>
                    <td>
                        <span class="mav2-badge <?= $order['payment_status']['class'] ?>">
                            <?= $order['payment_status']['label'] ?>
                        </span>
                    </td>
                    <td>
                        <span class="mav2-badge <?= $order['fulfillment_status']['class'] ?>">
                            <?= $order['fulfillment_status']['label'] ?>
                        </span>
                    </td>
                    <td><?= $order['currency'] ?><?= $order['total'] ?></td>
                    <td>
                        <button type="button" data-id="<?= $order['id'] ?>" class="invoice_download">
                            <span></span>Download
                        </button>
                    </td>
                </tr>
            <?php } ?>

            <?php if (empty($orders)) { ?>
                <tr>
                    <td colspan="7"><p>No Orders Found</p></td>
                </tr>
            <?php } ?>

        </tbody>
    </table>

    <?php if (!empty($orders)) { ?>
    <div id="mav2-orders-pagination">
        <button id="mav2-prev-page" disabled>&#8592; Previous</button>
        <span id="mav2-page-info"></span>
        <button id="mav2-next-page">Next &#8594;</button>
    </div>
    <?php } ?>

    <div id="order_processing" style="display: none;">
        <div class="processing">
            <div class="loader">
                <div class="mav2-custom-loader"></div>
            </div>
        </div>
    </div>

    <div id="invoiceModal" class="modal-overlay">
        <div class="modal-content">
            <span class="close-modal">&times;</span>

            <h1 class="invoice-number">Invoice: #<span id="display-id"></span></h1>
            <p class="invoice-date">Date: <span id="display-date"></span></p>

            <div class="billed-section" style="margin-bottom: 20px;">
                <strong>BILLED TO:</strong><br>
                <span id="display-address"></span>
            </div>

            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="text-center">Quantity</th>
                        <th class="text-right">Unit Price</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody id="display-items">
                </tbody>
            </table>

            <div class="totals-section">
                <div class="total-row">
                    <span>Subtotal</span>
                    <span id="display-subtotal"></span>
                </div>
                <div class="total-row">
                    <span>Shipping</span>
                    <span id="display-shipping"></span>
                </div>
                <div class="total-row">
                    <span>Discount</span>
                    <span id="display-discount"></span>
                </div>
                <div class="total-row border-bottom">
                    <span>Tax</span>
                    <span id="display-tax"></span>
                </div>
                <div class="final-total">
                    <span>Total</span>
                    <span id="display-total"></span>
                </div>
            </div>
            <div style="clear: both;"></div>
        </div>
    </div>

</div>

<script>
(function () {
    const PER_PAGE   = 5;
    let currentPage  = 1;
    const rows       = Array.from(document.querySelectorAll('.mav2-order-row'));
    const totalPages = Math.max(1, Math.ceil(rows.length / PER_PAGE));
    const prevBtn    = document.getElementById('mav2-prev-page');
    const nextBtn    = document.getElementById('mav2-next-page');
    const pageInfo   = document.getElementById('mav2-page-info');

    function render() {
        const start = (currentPage - 1) * PER_PAGE;
        const end   = start + PER_PAGE;
        rows.forEach(function (row, i) {
            row.style.display = (i >= start && i < end) ? '' : 'none';
        });
        pageInfo.textContent    = 'Page ' + currentPage + ' of ' + totalPages;
        prevBtn.disabled        = currentPage === 1;
        nextBtn.disabled        = currentPage === totalPages;
    }

    prevBtn.addEventListener('click', function () {
        if (currentPage > 1) { currentPage--; render(); }
    });
    nextBtn.addEventListener('click', function () {
        if (currentPage < totalPages) { currentPage++; render(); }
    });

    render();
})();
</script>
