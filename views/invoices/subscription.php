<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Plus Jakarta Sans", sans-serif;
            color: #1a1a1a;
            background-color: #F8F7F2;
            /* Cream/Off-white background */
            margin: 0;
            padding: 10px;

        }

        .invoice-title {
            font-family: "Lora", serif;
            color: #1a1a1a;
        }

        table {
            border-collapse: collapse;
        }

        .text-left {
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .p {
            padding: 5px 0;
        }
    </style>

    <!-- new version -->

    <!-- hader -->
    <table style="width: 100%;">
        <tr>
            <td style="width: 33.33%;"><img
                    src="https://japanrailclub.com/wp-content/uploads/2024/06/Japan-Rail-Club-Logo-Blue.svg"
                    class="logo" width="100" alt="Logo" /></td>
            <td style="width: 30%;">
                <h1 class="invoice-title" style="font-size: 3rem;">INVOICE</h1>
            </td>
            <td style="width: 35.33%; text-align: right;">
                <strong style="font-size: 14px;">JR East Business Development SEA Pte. Ltd.</strong><br>
                <p style="font-size: 13px;">20 Anson Road, #11-01 Twenty Anson, Singapore 079912<br>
                    UEN No. 201840125Z<br>
                    Email: info@japanrailclub.com</p>
            </td>
        </tr>
    </table>

    <!-- billing details -->
    <table style="width: 100%; margin-top: 20px;">
        <tr>
            <td style="width: 33.33%;">
                <strong>BILLED TO:</strong><br>
                <?= $address ?>
            </td>
            <td style="width: 33.33%;"></td>
            <td style="vertical-align: middle;width: 33.33%;">
                <table>
                    <tr>
                        <td style="text-align: right;"><strong>INVOICE NO.:</strong></td>
                        <td style="text-align: right;">
                            <?= $id ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align: right;"><strong>DATE:</strong></td>
                        <td style="text-align: right;">
                            <?=$date?>
                        </td>
                </table>

            </td>
        </tr>
    </table>

    <table style="width: 100%; margin-top: 20px;">
        <thead>
            <tr>
                <th style="width:40%; text-align: left; border-top: 2px solid black; border-bottom: 2px solid black;"
                    class="text-left">Description</th>
                <th style="width:20%;border-top: 2px solid black; border-bottom: 2px solid black;" class="text-left">
                    Quantity</th>
                <th style="width:20%;text-align:right;border-top: 2px solid black; border-bottom: 2px solid black;"
                    class="text-right">Unit Price</th>
                <th style="width:20%;text-align:right;border-top: 2px solid black; border-bottom: 2px solid black;"
                    class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>

            <tr>
                <td class="p">
                    <?= $items[0]['name'] ?><br>
                    <strong style="padding-top: 5px">Plan : </strong>
                    <?= $plan ?><br>
                </td>
                <td class="text-left p">
                    <?= $items[0]['quantity'] ?>
                </td>
                <td class="text-right p ">
                    <?= $items[0]['unit_price'] ?>
                </td>
                <td class="text-right p ">
                    <?= $items[0]['price'] ?>
                </td>
            </tr>

        </tbody>
    </table>

    <div class="totals-container">
        <table class="totals-table" style="width:100%;font-size: .8rem;">
            <tr>
                <td style="width:40%; padding-top: 5px; padding-bottom: 5px;">&nbsp;</td>
                <td style="width:20%; padding-top: 5px; padding-bottom: 5px;">&nbsp;</td>
                <td style="width:20%;font-weight: bold; padding-top: 5px; padding-bottom: 5px;" class="text-right">
                    Subtotal</td>
                <td class="text-right" style="padding-top: 5px; padding-bottom: 5px;">
                    <?= $subtotal ?>
                </td>
            </tr>
            <tr>
                <td style="width:40%; padding-top: 5px; padding-bottom: 5px;">&nbsp;</td>
                <td style="width:20%; padding-top: 5px; padding-bottom: 5px;">&nbsp;</td>
                <td style="width:20%; padding-top: 5px; padding-bottom: 5px;" class="text-right">Discount</td>
                <td class="text-right" style="padding-top: 5px; padding-bottom: 5px;">
                    <?= $discount ?>
                </td>
            </tr>
            <tr>
                <td style="width:40%; padding-top: 5px; padding-bottom: 5px;">&nbsp;</td>
                <td style="width:20%; padding-top: 5px; padding-bottom: 5px;">&nbsp;</td>
                <td style="width:20%;font-weight: bold; padding-top: 5px; padding-bottom: 5px;" class="text-right">
                    Subtotal after Discount</td>
                <td class="text-right" style="padding-top: 5px; padding-bottom: 5px;">
                    <?= $subtotal_after_discount ?>
                </td>
            </tr>
            <?php if (!$is_virtual): ?>
            <tr>
                <td style="width:40%; padding-top: 5px; padding-bottom: 5px;">&nbsp;</td>
                <td style="width:20%; padding-top: 5px; padding-bottom: 5px;">&nbsp;</td>
                <td style="width:20%; padding-top: 5px; padding-bottom: 5px;" class="text-right">Shipping</td>
                <td class="text-right" style="padding-top: 5px; padding-bottom: 5px;">
                    <?= $shipping ?>
                </td>
            </tr>
            <?php endif ?>

            <tr>
                <td style="width:40%; padding-top: 5px; padding-bottom: 5px;">&nbsp;</td>
                <td style="width:20%; padding-top: 5px; padding-bottom: 5px;">&nbsp;</td>
                <td style="width:20%;border-bottom: 5px double black;padding-top: 5px; padding-bottom: 5px;"
                    class="text-right">Tax (0%)</td>
                <td class="text-right" style="border-bottom: 5px double black;padding-top: 5px; padding-bottom: 5px;">
                    <?= $tax ?>
                </td>
            </tr>
            <tr class="total-row">
                <td style="width:40%; padding-top: 5px; padding-bottom: 5px;">&nbsp;</td>
                <td style="width:20%; padding-top: 5px; padding-bottom: 5px;">&nbsp;</td>
                <td class="text-right"
                    style="font-weight:bold; font-size: 1.1rem; white-space: nowrap; border-bottom: 1px solid black; padding-top: 5px; padding-bottom: 5px;">
                    Total</td>
                <td class="text-right"
                    style="font-weight:bold; font-size: 1.1rem; white-space: nowrap; border-bottom: 1px solid black; padding-top: 5px; padding-bottom: 5px;">
                    <?= $total ?>
                </td>
            </tr>
        </table>
    </div>

    <div style="clear: both;"></div>

    <table class="" style="margin-top: 50px;">
        <tr>
            <td class="">
                <span style="font-weight: bold;font-size: 3rem;">Thank you!</span>
            </td>

        </tr>
    </table>
</body>

</html>