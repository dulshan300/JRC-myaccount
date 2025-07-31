<!doctype html>
<html>

<body>
    <div
        style='background-color:#FDF9ED;color:#262626;font-family:"Helvetica Neue", "Arial Nova", "Nimbus Sans", Arial, sans-serif;font-size:16px;font-weight:400;letter-spacing:0.15008px;line-height:1.5;margin:0;padding:32px 0;min-height:100%;width:100%'>
        <table
            align="center"
            width="100%"
            style="margin:0 auto;max-width:600px;background-color:#FAFAFA;border-radius:12px"
            role="presentation"
            cellspacing="0"
            cellpadding="0"
            border="0">
            <tbody>
                <tr style="width:100%">
                    <td style="text-align: center;">
                        <div style="padding:16px 24px 16px 24px;text-align:center">
                            <img
                                alt="Japan Rail Logo"
                                src="https://www.japanrailclub.com/wp-content/uploads/2024/07/Japan-Rail-Club-Email-Logo.jpg"
                                height="80"
                                style="height:80px;outline:none;border:none;text-decoration:none;vertical-align:middle;display:inline-block;max-width:100%" />
                        </div>
                        <h1
                            style="font-weight:bold;text-align:center;margin:0;font-size:32px;padding:0px 24px 16px 24px">
                            Your Subscription Has Been Updated!
                        </h1>
                        <div style="padding:16px 40px 16px 40px">
                            <hr
                                style="width:100%;border:none;border-top:1px solid #CCCCCC;margin:0" />
                        </div>
                        <div style="font-weight:normal;padding:16px 24px 16px 24px">
                            Hi <?= $customer_name ?>,
                        </div>
                        <div style="font-weight:normal;padding:4px 24px 4px 24px">
                            <p>
                                Great news—your JAPAN RAIL CLUB subscription has been
                                successfully updated!
                            </p>
                            <div style="margin:0 -20px">
                                <div style="background-color: #efefef; padding:10px 30px; text-align: left;">

                                    <p>Here are the details of your new plan:</p>
                                    <p>
                                        <strong>Plan</strong>: Omiyage Snack Box Subscription<br />
                                        <strong>Updated Plan Price</strong>: <?= $price ?><br />
                                        <strong>Updated Plan Duration</strong>: <?= $new_plan ?><br />
                                        <strong>Effective From</strong>: <?= $end_date ?>
                                    </p>

                                </div>
                            </div>

                            <p>
                                Your current plan will remain active until <?= $end_date ?>.
                                After that, your new plan will kick in automatically—no action
                                needed on your part.
                            </p>
                            <p>
                                If you have any questions or need assistance, feel free to
                                reach out to us—we're always happy to help!
                            </p>
                        </div>
                        <div style="text-align:center;padding:16px 24px 16px 24px">
                            <a
                                href="https://www.japanrailclub.com/my-account/"
                                style="color:#FFFFFF;font-size:16px;font-weight:bold;background-color:#001a43;border-radius:4px;display:inline-block;padding:12px 20px;text-decoration:none"
                                target="_blank"><span><!--[if mso
                      ]><i
                        style="letter-spacing: 20px;mso-font-width:-100%;mso-text-raise:30"
                        hidden
                        >&nbsp;</i
                      ><!
                    [endif]--></span><span>View My Subscription</span><span><!--[if mso
                      ]><i
                        style="letter-spacing: 20px;mso-font-width:-100%"
                        hidden
                        >&nbsp;</i
                      ><!
                    [endif]--></span></a>
                        </div>
                        <div style="font-weight:normal;padding:4px 24px 16px 24px">
                            <p>
                                Thank you for being a part of the JAPAN RAIL CLUB family!
                                We're excited to keep delivering the flavours of Japan to your
                                doorstep!
                            </p>
                            <p>
                                Warm regards,<br />
                                <b>The JAPAN RAIL CLUB Team</b>
                            </p>
                        </div>
                        <div style="padding:16px 40px 40px 40px">
                            <hr
                                style="width:100%;border:none;border-top:1px solid #CCCCCC;margin:0" />
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</body>

</html>