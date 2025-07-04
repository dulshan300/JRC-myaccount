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
                            您的訂閱已更新！
                        </h1>
                        <div style="padding:16px 40px 16px 40px">
                            <hr
                                style="width:100%;border:none;border-top:1px solid #CCCCCC;margin:0" />
                        </div>
                        <div style="font-weight:normal;padding:16px 24px 16px 24px">
                            您好 <?= $customer_name ?>,
                        </div>
                        <div style="font-weight:normal;padding:4px 24px 4px 24px">
                            <p>
                                好消息！ 您的JAPAN RAIL CLUB 訂閱已成功更新。
                            </p>
                            <div style="margin:0 -20px">
                                <div style="background-color: #efefef; padding:10px 30px;text-align: left;">

                                    <p>以下是您更新方案的詳細資訊：</p>
                                    <p>
                                        <strong>方案</strong>： OMIYAGE點心禮盒訂閱<br />
                                        <strong>更新方案費用</strong>： <?= $price ?><br />
                                        <strong>方案期限</strong>： <?= $new_plan ?><br />
                                        <strong>生效日期</strong>： <?= $end_date ?>
                                    </p>
                                </div>
                            </div>
                            <p>

                                您目前的方案將持續有效至「<?= $end_date ?>」。在此之後，新方案將自動生效，您無需額外操作。

                            </p>
                            <p>
                                如果您有任何疑問或需要協助，歡迎隨時與我們聯繫——我們隨時樂意為您服務！
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
                    [endif]--></span><span>查看我的訂閱</span><span><!--[if mso
                      ]><i
                        style="letter-spacing: 20px;mso-font-width:-100%"
                        hidden
                        >&nbsp;</i
                      ><!
                    [endif]--></span></a>
                        </div>
                        <div style="font-weight:normal;padding:4px 24px 16px 24px">
                            <p>
                                "感謝您成為 JAPAN RAIL CLUB 的一員！
                                我們很高興能持續將日本的美味送到您家門口！"

                            </p>
                            <p>
                                祝一切順心美好，<br />
                                <b> JAPAN RAIL CLUB 團隊</b>
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