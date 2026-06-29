<span style="display:none !important; visibility:hidden; opacity:0; color:transparent; height:0; width:0;">Your <?= $discount ?> savings have been successfully applied to your subscription</span>
<!doctype html>
<html>

<body>
    <div data-no-translation
        style='background-color:#FDF9ED;color:#262626;font-family:"Helvetica Neue", "Arial Nova", "Nimbus Sans", Arial, sans-serif;font-size:16px;font-weight:400;letter-spacing:0.15008px;line-height:1.5;margin:0;padding:32px 0;min-height:100%;width:100%'>
        <table
            align="center"
            width="100%"
            style="margin:0 auto;max-width:600px;background-color:#FFFFFF;border-radius:12px"
            role="presentation"
            cellspacing="0"
            cellpadding="0"
            border="0">
            <tbody>
                <tr style="width:100%">
                    <td>
                        <div style="padding:16px 24px 16px 24px;text-align:center">
                            <img
                                alt="Japan Rail Logo"
                                src="https://lofpoghg4ckxgpnx.public.blob.vercel-storage.com/projects/27162abc-0ca7-4e04-8db5-678b4be25227/080f10b8-ee8b-43d3-b805-80ed0d3b2db0.svg"
                                height="80"
                                style="height:80px;outline:none;border:none;text-decoration:none;vertical-align:middle;display:inline-block;max-width:100%" />
                        </div>
                        <h1
                            style="font-weight:bold;text-align:center;margin:0;font-size:32px;padding:16px 24px 0px 24px">
                            A Discount Coupon Activated!
                        </h1>
                        <div
                            style="font-size:14px;font-weight:normal;text-align:center;padding:4px 60px 4px 60px">


                            <p><b>Omiyage Snack Box Subscription Details</b></p>
                            <p style="background-color:#F5F5F5;padding:16px;margin-top:24px;border-radius:8px;text-align:left">
                                Subscription Summary:<br /><br />
                                <strong>Customer</strong>: <?= $name ?><br />
                                <strong>Email</strong>: <?= $email ?><br />
                                <strong>Plan</strong>: <?= $plan ?> Omiyage Snack Box<br />
                                <strong>Renewal Date</strong>: <?= $effective_from_n ?><br />
                                <strong>Renewal Price</strong>: <?= $discounted_price ?>&nbsp;(U.P. <?= $original_price ?>)<br />
                                <strong>You Saved</strong>: <?= $savings ?><br />
                            </p>

                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</body>

</html>