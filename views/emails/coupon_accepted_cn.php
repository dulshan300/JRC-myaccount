<span style="display:none !important; visibility:hidden; opacity:0; color:transparent; height:0; width:0;">您的 <?= $discount ?> 優惠已成功套用到訂閱方案。</span>
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
                                src="https://www.japanrailclub.com/wp-content/uploads/2024/07/Japan-Rail-Club-Email-Logo.jpg"
                                height="80"
                                style="height:80px;outline:none;border:none;text-decoration:none;vertical-align:middle;display:inline-block;max-width:100%" />
                        </div>
                        <h1
                            style="font-weight:bold;text-align:center;margin:0;font-size:32px;padding:16px 24px 0px 24px">
                            🎉 您的優惠折扣已啟動！
                        </h1>
                        <div style="font-size:14px;font-weight:normal;text-align:left;padding:4px 60px 4px 60px">
                            <p>您好 <?= $name ?>!</p>
                            <p>
                                感謝您選擇我們的服務！您的專屬優惠折扣已成功套用至您的訂閱方案。
                            </p>
                            <p>
                                您無需再進行任何操作，只需放鬆心情，期待下一次送達的正宗日本美味點心禮盒！
                            </p>
                            <div style="text-align:center">
                                <img alt="Japan Rail Logo" src="https://www.japanrailclub.com/wp-content/uploads/2025/07/Japan-Rail-Club-Omiyage-Box.webp" height="200" style="height:200px;outline:none;border:none;text-decoration:none;vertical-align:middle;display:inline-block;max-width:100%" />
                            </div>

                            <p><b>Omiyage Snack Box 訂閱詳情</b></p>
                            <p style="background-color:#fefaf1;padding:16px;margin-top:24px;border-radius:8px;text-align:left">
                                訂閱摘要:<br /><br />
                                <strong>訂閱方案</strong>: <?= $plan ?> Omiyage Snack Box<br />
                                <strong>續訂日期</strong>: <?= $effective_from_n ?><br />
                                <strong>續訂費用</strong>: <?= $discounted_price ?>&nbsp;(U.P. <?= $original_price ?>)<br />
                                <strong>節省費用:</strong>: <?= $savings ?><br />
                            </p>
                            <p>
                                <b>希望更新您的訂閱、付款方式或送貨地址嗎？</b>
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
                    [endif]--></span><span>管理訂閱</span><span><!--[if mso
                      ]><i
                        style="letter-spacing: 20px;mso-font-width:-100%"
                        hidden
                        >&nbsp;</i
                      ><!
                    [endif]--></span></a>
                        </div>
                        <div
                            style="font-size:14px;font-weight:normal;text-align:left;padding:4px 60px 4px 60px">
                            <p>
                                JAPAN RAIL CLUB衷心感謝您一直以來的支持，並期待與您分享更多日本的極致美味。
                            </p>
                            <p>
                                有任何疑問歡迎隨時透過
                                <a href="mailto:info@japanrailclub.com" target="_blank">info@japanrailclub.com</a> 聯絡我們。
                            </p>
                            <p>
                                再次感謝您選擇 JAPAN RAIL CLUB
                            </p>
                        </div>
                        <div style="padding:16px 40px 40px 40px">
                            <hr
                                style="width:100%;border:none;border-top:1px solid #CCCCCC;margin:0" />
                        </div>
                        <div
                            style="font-size:10px;font-weight:normal;text-align:center;padding:16px 24px 16px 24px">
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