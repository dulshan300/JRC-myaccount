<style>
    body {
        font-family: "DejaVu Sans", sans-serif;
        color: #1a1a1a;
        background-color: #F8F7F2;
        /* Cream/Off-white background */
        padding: 20px;
    }

    .serif {
        font-family: "DejaVu Serif", serif;
    }

    /* Header Section */
    .header-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 40px;
    }

    .logo {
        font-size: 60pt;
        font-weight: bold;
    }

    .invoice-title {
        font-size: 40pt;
        text-align: right;
        text-transform: uppercase;
        letter-spacing: 2px;
    }

    /* Info Section */
    .info-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 50px;
    }

    .billed-to {
        width: 60%;
        vertical-align: top;
    }

    .invoice-details {
        width: 40%;
        text-align: right;
        vertical-align: top;
        line-height: 1.4;
    }

    .label {
        font-weight: bold;
        text-transform: uppercase;
        font-size: 10pt;
        margin-bottom: 8px;
        display: block;
    }

    /* Items Table */
    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
        border-top: 2px solid #1a1a1a;
    }

    .items-table th {
        padding: 12px 5px;
        text-align: left;
        font-size: 10pt;
        border-bottom: 1.5px solid #1a1a1a;
    }

    .items-table td {
        padding: 15px 5px;
        border-bottom: 1px solid #d1d1d1;
        font-size: 11pt;
    }

    .text-right {
        text-align: right;
    }

    /* Totals Section */
    .totals-container {
        width: 100%;
        margin-top: 10px;
    }

    .totals-table {
        width: 35%;
        float: right;
        border-collapse: collapse;
    }

    .totals-table td {
        padding: 8px 5px;
        font-size: 11pt;
    }

    .total-row {
        font-weight: bold;
        font-size: 16pt;
        border-top: 1.5px solid #1a1a1a;
    }

    .total-row td {
        padding-top: 15px;
    }

    /* Footer Section */
    .thank-you {
        font-size: 22pt;
        margin-top: 60px;
        margin-bottom: 50px;
    }

    .footer-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 40px;
    }

    .payment-info {
        width: 60%;
        vertical-align: top;
        font-size: 10pt;
        line-height: 1.4;
    }

    .company-sig {
        width: 40%;
        text-align: right;
        vertical-align: bottom;
        font-size: 10pt;
    }

    .sig-name {
        font-size: 18pt;
        margin-bottom: 5px;
    }
</style>

<table class="header-table">
    <tr>
        <td class="logo"><img src="https://japanrailclub.com/wp-content/uploads/2024/06/Japan-Rail-Club-Logo-Blue.svg"
                class="logo" width="100" alt="Logo" /></td>
        <td class="invoice-title serif">INVOICE</td>
    </tr>
</table>

<table class="info-table">
    <tr>
        <td class="billed-to">
            <span class="label">BILLED TO:</span><br>
            <?= $address ?>
        </td>
        <td class="invoice-details">
            Invoice No. <?= $id ?><br>
            <?= $date ?>
        </td>
    </tr>
</table>

<table class="items-table">
    <thead>
        <tr>
            <th style="width:40%">Item</th>
            <th style="width:20%;text-align:right" class="text-right">Quantity</th>
            <th style="width:20%;text-align:right" class="text-right">Unit Price</th>
            <th style="width:20%;text-align:right" class="text-right">Total</th>
        </tr>
    </thead>
    <tbody>

        <tr>
            <td>
                <?= $items[0]['name'] ?><br>
                <hr>
                <strong>Plan : </strong><?= $plan ?><br>
                <strong>Renewal Date : </strong><?= $renewal_date ?><br>
            </td>
            <td class="text-right"><?= $items[0]['quantity'] ?></td>
            <td class="text-right"><?= $items[0]['unit_price'] ?></td>
            <td class="text-right"><?= $items[0]['price'] ?></td>
        </tr>

    </tbody>
</table>

<div class="totals-container">
    <table class="totals-table" style="width:100%">
        <tr>
            <td style="width:40%;">&nbsp;</td>
            <td style="width:20%">&nbsp;</td>
            <td style="width:20%;font-weight: bold;" class="text-right">Subtotal</td>
            <td class="text-right"><?= $subtotal ?></td>
        </tr>
        <?php if (!$is_virtual): ?>
            <tr>
                <td style="width:40%;">&nbsp;</td>
                <td style="width:20%">&nbsp;</td>
                <td style="width:20%;" class="text-right">Shipping</td>
                <td class="text-right"><?= $shipping ?></td>
            </tr>
        <?php endif ?>
        <tr>
            <td style="width:40%;">&nbsp;</td>
            <td style="width:20%">&nbsp;</td>
            <td style="width:20%;" class="text-right">Discount</td>
            <td class="text-right"><?= $discount ?></td>
        </tr>
        <tr>
            <td style="width:40%;">&nbsp;</td>
            <td style="width:20%;">&nbsp;</td>
            <td style="width:20%;border-bottom: 1px solid #d1d1d1;" class="text-right">Tax (0%)</td>
            <td class="text-right" style="border-bottom: 1px solid #d1d1d1;"><?= $tax ?></td>
        </tr>
        <tr class="total-row">
            <td style="width:40%;">&nbsp;</td>
            <td style="width:20%;">&nbsp;</td>
            <td class="text-right" style="font-weight:bold;font-size:1.5em">Total</td>
            <td class="text-right" style="font-weight:bold;font-size:1.5em"><?= $total ?></td>
        </tr>
    </table>
</div>

<div style="clear: both;"></div>

<div class="thank-you">Thank you!</div>

<table class="footer-table">
    <tr>
        <td class="payment-info">

        </td>
        <td class="company-sig">
            <div class="sig-name serif">Japan Railway</div>
            20 Anson Road, #11-01, Twenty Anson Singapore 079912<br>
            info@japanrailclub.com<br>
            Mon - Fri: 10:00AM - 6:00PM (SGT)
        </td>
    </tr>
</table>