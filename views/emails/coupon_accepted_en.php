<span style="display:none !important; visibility:hidden; opacity:0; color:transparent; height:0; width:0;">Your <?= $discount ?> savings have been successfully applied to your subscription</span>
<!doctype html>
<html>

<body>
    <div data-no-translation style='background-color:#FDF9ED;color:#262626;font-family:"Helvetica Neue", "Arial Nova", "Nimbus Sans", Arial, sans-serif;font-size:16px;font-weight:400;letter-spacing:0.15008px;line-height:1.5;margin:0;padding:32px 0;min-height:100%;width:100%'>
        <table align="center" width="100%" style="margin:0 auto;max-width:600px;background-color:#FFFFFF;border-radius:12px" role="presentation" cellspacing="0" cellpadding="0" border="0">
            <tbody>
                <tr style="width:100%">
                    <td>
                        <div style="padding:16px 24px 16px 24px;text-align:center">
                            <img alt="Japan Rail Logo" src="https://www.japanrailclub.com/wp-content/uploads/2024/07/Japan-Rail-Club-Email-Logo.jpg" height="80" style="height:80px;outline:none;border:none;text-decoration:none;vertical-align:middle;display:inline-block;max-width:100%" />
                        </div>
                        <h1 style="font-weight:bold;text-align:center;margin:0;font-size:32px;padding:16px 24px 0px 24px">
                            🎉 You've Activated Your Discount!
                        </h1>
                        <div style="font-size:14px;font-weight:normal;text-align:left;padding:4px 60px 4px 60px">
                            <p>Dear <?=$name ?>,</p>
                            <p>
                                Thank you for staying with us! Your exclusive discount has been successfully applied to your subscription.
                            </p>
                            <p>
                                There's nothing more you need to do — simply sit back, relax, and look forward to your next <strong>Omiyage Snack Box,</strong> filled with authentic treats from Japan.
                            </p>
                            <div style="text-align:center">
                                <img alt="Japan Rail Logo" src="https://www.japanrailclub.com/wp-content/uploads/2025/07/Japan-Rail-Club-Omiyage-Box.webp" height="200" style="height:200px;outline:none;border:none;text-decoration:none;vertical-align:middle;display:inline-block;max-width:100%" />
                            </div>

                            <p style="background-color:#fefaf1;padding:16px;margin-top:24px;border-radius:8px;text-align:left">
                                <strong>Subscription Summary</strong><br /><br />
                                <strong>Plan</strong>: <?= $plan ?><br />
                                <strong>Renewal Date</strong>: <?= $effective_from_n ?><br />
                                <strong>Renewal Price</strong>: <?= $discounted_price ?>&nbsp;(U.P. <?= $original_price ?>)<br />
                                <strong>You Saved</strong>: <?= $savings ?><br />
                            </p>
                            <p>
                                Wish to update your subscription, payment method, or delivery preferences?
                            </p>
                        </div>
                        <div style="text-align:center;padding:16px 24px 16px 24px">
                            <a href="https://www.japanrailclub.com/my-account/" style="color:#FFFFFF;font-size:16px;font-weight:bold;background-color:#001a43;border-radius:4px;display:inline-block;padding:12px 20px;text-decoration:none" target="_blank"><span>
                                    <!--[if mso
                      ]><i
                        style="letter-spacing: 20px;mso-font-width:-100%;mso-text-raise:30"
                        hidden
                        >&nbsp;</i
                      ><!
                    [endif]-->
                                </span><span>Manage Subscription</span><span>
                                    <!--[if mso
                      ]><i
                        style="letter-spacing: 20px;mso-font-width:-100%"
                        hidden
                        >&nbsp;</i
                      ><!
                    [endif]-->
                                </span></a>
                        </div>
                        <div style="font-size:14px;font-weight:normal;text-align:left;padding:4px 60px 4px 60px">
                            <p>
                                We truly appreciate your continued support and look forward to sharing more of Japan’s finest flavours with you.
                            </p>
                            <p>
                                Questions? Reach us anytime at
                                <a href="mailto:info@japanrailclub.com" target="_blank">info@japanrailclub.com</a>
                            </p>
                            <p>
                                Thank you again for choosing JAPAN RAIL CLUB.
                            </p>
                        </div>
                        <div style="padding:16px 40px 40px 40px">
                            <hr style="width:100%;border:none;border-top:1px solid #CCCCCC;margin:0" />
                        </div>
                        <div style="font-size:10px;font-weight:normal;text-align:center;padding:16px 24px 16px 24px">
                            You have received this email as a registered user of JR East
                            Business Development SEA Ptd. Ltd.
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</body>

</html>