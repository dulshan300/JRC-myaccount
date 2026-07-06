<?php

/**
 * Customer Subscription Change Notification Email Template
 *
 * @param string $customer_name
 * @param string $customer_email
 * @param string $current_plan
 * @param string $new_plan
 * @param string $end_date
 */
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Subscription Change Scheduled</title>
</head>

<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.6;">
    <p>Dear <strong><?php echo esc_html($customer_name); ?></strong>,</p>

    <p>We have received your request to change your subscription plan.</p>

    <p><strong>Current Plan:</strong> <?php echo esc_html($current_plan); ?></p>
    <p><strong>New Plan:</strong> <?php echo esc_html($new_plan); ?></p>

    <p>Your plan change will take effect at the end of your current subscription cycle on <strong><?php echo esc_html($end_date); ?></strong>.</p>

    <p>If you have any questions, feel free to contact our support team.</p>

    <p>Thank you for being with us!</p>

    <p>Best regards,</p>
    <p><strong>Your Site Name</strong></p>
</body>

</html>