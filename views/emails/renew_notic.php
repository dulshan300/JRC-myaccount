<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Renewal Notification</title>
</head>

<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <!-- Main Email Container -->
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);">
        <!-- Header Section -->
        <tr>
            <td align="center" bgcolor="#007BFF" style="padding: 20px; color: #ffffff;">
                <h1 style="margin: 0; font-size: 24px;">Your Subscription is Renewing Soon</h1>
            </td>
        </tr>
        <!-- Content Section -->
        <tr>
            <td style="padding: 20px; color: #333333; line-height: 1.6;">
                <p style="margin: 0 0 16px;">Hi <?= $email ?>,</p>
                <p style="margin: 0 0 16px;">We hope you're enjoying your subscription with us! This is a friendly reminder that your subscription plan is set to renew on <strong>3rd [Upcoming Month]</strong>.</p>
                <p style="margin: 0 0 16px;">If you wish to continue enjoying our services, no action is required. Your <?= $plan ?> Month(s) subscription will automatically renew, and the payment will be processed using your current payment method.</p>
                <p style="margin: 0 0 16px;">If you'd like to make any changes to your subscription or update your payment details, please click the button below:</p>
                <table border="0" cellpadding="0" cellspacing="0" style="margin: 20px 0;">
                    <tr>
                        <td align="center">
                            <a href="[Link to Manage Subscription]" style="display: inline-block; padding: 12px 24px; background-color: #007BFF; color: #ffffff; text-decoration: none; border-radius: 4px; font-size: 16px;">Manage Subscription</a>
                        </td>
                    </tr>
                </table>
                <p style="margin: 0 0 16px;">If you have any questions or need assistance, feel free to reach out to our support team at <a href="mailto:support@example.com" style="color: #007BFF; text-decoration: none;">support@example.com</a>.</p>
                <p style="margin: 0 0 16px;">Thank you for being a valued customer!</p>
                <p style="margin: 0;">Best regards,<br>[Your Company Name]</p>
            </td>
        </tr>
        <!-- Footer Section -->
        <tr>
            <td align="center" bgcolor="#f4f4f4" style="padding: 20px; font-size: 12px; color: #777777;">
                <p style="margin: 0 0 8px;">You are receiving this email because you have an active subscription with [Your Company Name].</p>
                <p style="margin: 0;">If you no longer wish to receive these emails, you can <a href="[Unsubscribe Link]" style="color: #007BFF; text-decoration: none;">unsubscribe here</a>.</p>
            </td>
        </tr>
    </table>
</body>

</html>