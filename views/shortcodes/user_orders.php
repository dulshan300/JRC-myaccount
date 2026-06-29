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
    /* ── Table view ── */
    #mav2-orders-table { width: 100%; border-collapse: collapse; }
    #mav2-orders-table th,
    #mav2-orders-table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
    #mav2-orders-table th { font-weight: 600; background: #f9fafb; }
    #mav2-orders-table td a.mav2-order-link { color: inherit; text-decoration: underline; cursor: pointer; }
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

    /* ── Detail view ── */
    #mav2-detail-view { display: none; }
    #mav2-back-btn { display: inline-flex; align-items: center; gap: 6px; background: none; border: none; cursor: pointer; font-size: 14px; color: #374151; padding: 0 0 16px; font-weight: 500; }
    #mav2-back-btn:hover { color: #000; }
    .mav2-detail-header { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 8px; margin-bottom: 20px; }
    .mav2-detail-header h2 { margin: 0 0 4px; font-size: 20px; }
    .mav2-detail-header .mav2-detail-meta { font-size: 13px; color: #6b7280; }
    .mav2-detail-header .mav2-detail-meta span { margin-right: 16px; }
    .mav2-detail-badges { display: flex; gap: 8px; align-items: center; }
    .mav2-detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px; }
    @media (max-width: 600px) { .mav2-detail-grid { grid-template-columns: 1fr; } }
    .mav2-detail-section h4 { font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; color: #9ca3af; margin: 0 0 8px; }
    .mav2-detail-section p { margin: 0 0 4px; font-size: 14px; }
    .mav2-detail-items-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
    .mav2-detail-items-table th,
    .mav2-detail-items-table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #e5e7eb; font-size: 14px; vertical-align: middle; }
    .mav2-detail-items-table th { font-weight: 600; background: #f9fafb; font-size: 12px; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280; }
    .mav2-detail-items-table th:not(:first-child),
    .mav2-detail-items-table td:not(:first-child) { text-align: right; }
    .mav2-item-name-cell { display: flex; align-items: center; gap: 12px; }
    .mav2-item-img { width: 48px; height: 48px; object-fit: cover; border-radius: 6px; background: #f3f4f6; flex-shrink: 0; }
    .mav2-item-placeholder { width: 48px; height: 48px; border-radius: 6px; background: #f3f4f6; flex-shrink: 0; }
    .mav2-summary-table { width: 100%; max-width: 320px; margin-left: auto; margin-bottom: 24px; }
    .mav2-summary-table td { padding: 6px 0; font-size: 14px; }
    .mav2-summary-table td:last-child { text-align: right; }
    .mav2-summary-table .mav2-total-row td { font-weight: 700; font-size: 15px; border-top: 1px solid #e5e7eb; padding-top: 10px; }
    .mav2-detail-loading { padding: 40px; text-align: center; color: #6b7280; font-size: 14px; }
    .mav2-detail-actions { margin-top: 8px; }
</style>

<div id="order_cards">

<div id="mav2-table-view">
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
                        <a href="#" class="mav2-order-link" data-id="<?= esc_attr($order['id']) ?>">
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
</div><!-- /#mav2-table-view -->

<div id="mav2-detail-view">
    <button id="mav2-back-btn">&#8592; Back to Orders</button>
    <div id="mav2-detail-loading" class="mav2-detail-loading">Loading order details&hellip;</div>
    <div id="mav2-detail-content"></div>
</div>

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

    /* ── Pagination ── */
    const PER_PAGE   = 5;
    let currentPage  = 1;
    const rows       = Array.from(document.querySelectorAll('.mav2-order-row'));
    const totalPages = Math.max(1, Math.ceil(rows.length / PER_PAGE));
    const prevBtn    = document.getElementById('mav2-prev-page');
    const nextBtn    = document.getElementById('mav2-next-page');
    const pageInfo   = document.getElementById('mav2-page-info');

    function renderTable() {
        const start = (currentPage - 1) * PER_PAGE;
        const end   = start + PER_PAGE;
        rows.forEach(function (row, i) {
            row.style.display = (i >= start && i < end) ? '' : 'none';
        });
        if (pageInfo) pageInfo.textContent = 'Page ' + currentPage + ' of ' + totalPages;
        if (prevBtn)  prevBtn.disabled     = currentPage === 1;
        if (nextBtn)  nextBtn.disabled     = currentPage === totalPages;
    }

    if (prevBtn) prevBtn.addEventListener('click', function () {
        if (currentPage > 1) { currentPage--; renderTable(); }
    });
    if (nextBtn) nextBtn.addEventListener('click', function () {
        if (currentPage < totalPages) { currentPage++; renderTable(); }
    });

    renderTable();

    /* ── Detail view ── */
    const tableView  = document.getElementById('mav2-table-view');
    const detailView = document.getElementById('mav2-detail-view');
    const detailLoad = document.getElementById('mav2-detail-loading');
    const detailBody = document.getElementById('mav2-detail-content');
    const backBtn    = document.getElementById('mav2-back-btn');

    function esc(str) {
        var d = document.createElement('div');
        d.textContent = str != null ? String(str) : '';
        return d.innerHTML;
    }

    function buildAddressLines(addr) {
        var parts = [addr.name, addr.company, addr.address_1, addr.address_2,
                     addr.city, addr.state, addr.postcode, addr.country];
        return parts.filter(function (p) { return p && p.trim(); })
                    .map(function (p) { return '<p>' + esc(p) + '</p>'; })
                    .join('');
    }

    function renderDetail(d) {
        var itemsHTML = d.items.map(function (item) {
            var imgHTML = item.image
                ? '<img class="mav2-item-img" src="' + esc(item.image) + '" alt="' + esc(item.name) + '">'
                : '<div class="mav2-item-placeholder"></div>';
            return '<tr>'
                + '<td><div class="mav2-item-name-cell">' + imgHTML + '<span>' + esc(item.name) + '</span></div></td>'
                + '<td>' + esc(item.quantity) + '</td>'
                + '<td>' + esc(item.unit_price) + '</td>'
                + '<td>' + esc(item.line_total) + '</td>'
                + '</tr>';
        }).join('');

        var couponHTML = d.coupons.length
            ? '<span style="font-size:12px;color:#6b7280;margin-left:4px;">(' + d.coupons.map(esc).join(', ') + ')</span>'
            : '';

        var planHTML = d.subscription_plan !== '-'
            ? '<span>' + esc(d.subscription_plan) + '</span>'
            : '';

        var html = ''
            + '<div class="mav2-detail-header">'
            +   '<div>'
            +     '<h2>Order #' + esc(d.id) + '</h2>'
            +     '<div class="mav2-detail-meta">'
            +       '<span>' + esc(d.date) + '</span>'
            +       (planHTML ? '<span>' + planHTML + '</span>' : '')
            +     '</div>'
            +   '</div>'
            +   '<div class="mav2-detail-badges">'
            +     '<span class="mav2-badge ' + esc(d.payment_status.class) + '">' + esc(d.payment_status.label) + '</span>'
            +     '<span class="mav2-badge ' + esc(d.fulfillment_status.class) + '">' + esc(d.fulfillment_status.label) + '</span>'
            +   '</div>'
            + '</div>'

            + '<div class="mav2-detail-grid">'
            +   '<div class="mav2-detail-section">'
            +     '<h4>Customer</h4>'
            +     '<p>' + esc(d.customer_name) + '</p>'
            +     '<p>' + esc(d.customer_email) + '</p>'
            +   '</div>'
            +   '<div class="mav2-detail-section">'
            +     '<h4>' + esc(d.address.type) + '</h4>'
            +     buildAddressLines(d.address)
            +   '</div>'
            + '</div>'

            + '<table class="mav2-detail-items-table">'
            +   '<thead><tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>'
            +   '<tbody>' + itemsHTML + '</tbody>'
            + '</table>'

            + '<table class="mav2-summary-table">'
            +   '<tbody>'
            +     '<tr><td>Subtotal</td><td>' + esc(d.subtotal) + '</td></tr>'
            +     '<tr><td>Shipping</td><td>' + esc(d.shipping) + '</td></tr>'
            +     '<tr><td>Discount' + couponHTML + '</td><td>-' + esc(d.discount) + '</td></tr>'
            +     '<tr><td>Tax</td><td>' + esc(d.tax) + '</td></tr>'
            +   '</tbody>'
            +   '<tfoot>'
            +     '<tr class="mav2-total-row"><td>Total</td><td>' + esc(d.total) + '</td></tr>'
            +   '</tfoot>'
            + '</table>'

            + '<div class="mav2-detail-actions">'
            +   '<button type="button" data-id="' + esc(d.id) + '" class="invoice_download"><span></span>Download Invoice</button>'
            + '</div>';

        detailBody.innerHTML = html;
    }

    function showDetail(orderId) {
        tableView.style.display  = 'none';
        detailView.style.display = 'block';
        detailLoad.style.display = 'block';
        detailBody.innerHTML     = '';

        var formData = new FormData();
        formData.append('action', 'mav2_get_order_details');
        formData.append('id', orderId);
        formData.append('nonce', mav2.nonce);

        fetch(mav2.ajaxurl, { method: 'POST', body: formData })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                detailLoad.style.display = 'none';
                if (res.success) {
                    renderDetail(res.data);
                } else {
                    detailBody.innerHTML = '<p style="color:#991b1b;">Could not load order details.</p>';
                }
            })
            .catch(function () {
                detailLoad.style.display = 'none';
                detailBody.innerHTML = '<p style="color:#991b1b;">Could not load order details.</p>';
            });
    }

    document.getElementById('order_cards').addEventListener('click', function (e) {
        var link = e.target.closest('.mav2-order-link');
        if (link) {
            e.preventDefault();
            showDetail(link.dataset.id);
        }
    });

    backBtn.addEventListener('click', function () {
        detailView.style.display = 'none';
        tableView.style.display  = 'block';
    });

})();
</script>
