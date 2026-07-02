<?php
// get current user
$user = wp_get_current_user();

if (empty($user)) {
    echo 'Please login to see your subscription';

    return;
}

$user_id = $user->ID;
$user_email = $user->user_email;
// $user_id = 1791;

// get woocommerce customer
$customer = new WC_Customer($user_id);

// get payment methods
$tokens = WC_Payment_Tokens::get_customer_tokens($user_id);

$token_details = [];

foreach ($tokens as $token) {

    if (get_class($token) == 'WC_Stripe_Payment_Token_CC') {

        $token_details[] = [
            'id' => $token->get_id(),
            'card_type' => ucfirst($token->get_card_type()),
            'last4' => $token->get_last4(),
            'expiry' => $token->get_expiry_month() . '/' . $token->get_expiry_year(),
            'user_id' => $token->get_user_id(),
        ];
    } elseif (get_class($token) == 'WC_Payment_Token_Link') {

        $token_details[] = [
            'id' => $token->get_id(),
            'card_type' => ucfirst($token->get_type()),
            'last4' => "-",
            'expiry' => '-',
            'user_id' => $token->get_user_id(),
        ];

        // $token->delete();
    } else {
        error_log('[PMError]: Token class not recognized.' . print_r([
            'uid' => $user_id,
            'class_name' => get_class($token),
        ], true));
    }
}
$user_locale = get_user_locale();
$lang = substr($user_locale, 0, 2);


$show_waring = false;
$email_list = [
    'ultima1229@gmail.com',
    "vincentwws1988@gmail.com",
    "wuchengzhe227@gmail.com",
    "hungpo_huang@yahoo.com.tw",
    "s9160801@gmail.com",
    "ansonnth1128@gmail.com",
    "kenjo28@hotmail.com",
    "z112z112@yahoo.com.tw",
    "a0929862832@gmail.com",
    "idkevinjuang@gmail.com",
    "m197877@gmail.com",
    "qqaway0516@hotmail.com",
    "leo910117660@gmail.com",
    "awankana@gmail.com",
    "truss.tw@gmail.com",
    "miles_lai@hotmail.com",
    "0215aldrich@gmail.com",
    "hippoace@hotmail.com",
    "wen511206@yahoo.com.tw",
    "wenfang12608891@gmail.com",
    "snailmd@hotmail.com",
    "thehours74@gmail.com",
    "mayt3ng@hotmail.com",
    "nervous.tests-0o@icloud.com",
    "blackdeviljack@gmail.com",
    "j12031226@gmail.com",
    "anthonyhungyulin@gmail.com",
    "jc.enewsletter@gmail.com",
    "jinyao.lin@gmail.com",
    "webpotatosg@gmail.com"
];

if (count($token_details) == 0 && array_search($user_email, $email_list) !== true) {
    $show_waring = true;
}

// unique per instance so this shortcode can be placed more than once on a
// page without both copies fighting over the same Stripe elements / ids
$container_id = wp_unique_id('mav2_payment_methods_');

// the shared helper functions + Stripe library script only need to load once
$mav2_is_first_payment_methods = empty($GLOBALS['mav2_payment_methods_bootstrapped']);
$GLOBALS['mav2_payment_methods_bootstrapped'] = true;

?>

<div id="<?php echo esc_attr($container_id); ?>">

    <?php if ($show_waring): ?>
        <?php if ($lang == 'en'): ?>
            <div class="alert-banner">
                <div class="alert-banner__icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2L1 21h22L12 2zm0 3.45l8.27 14.55H3.73L12 5.45zM11 10v4h2v-4h-2zm0 6v2h2v-2h-2z" />
                    </svg>
                </div>
                <div class="alert-banner__body">
                    <h3 class="alert-banner__title">Important</h3>
                    <div class="alert-banner__content">
                        <p>We've upgraded to a new payment system to serve you better.</p>
                        <p>To ensure uninterrupted delivery of your subscription, please take a moment to update your payment
                            details.</p>
                        <p>If no updates are made, your subscription will continue as usual and charges <span>may be applied to
                                your
                                current payment method</span> on file unless cancelled before your next billing date.</p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert-banner">
                <div class="alert-banner__icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2L1 21h22L12 2zm0 3.45l8.27 14.55H3.73L12 5.45zM11 10v4h2v-4h-2zm0 6v2h2v-2h-2z" />
                    </svg>
                </div>
                <div class="alert-banner__body">
                    <h3 class="alert-banner__title">Important</h3>
                    <div class="alert-banner__content">
                        <p>我們已升級新的付款系統以為您提供更好的服務。</p>
                        <p>為確保您的訂閱不受中斷請撥空更新您的付款資訊。</p>
                        <p>若未更新付款資訊，您的訂閱將照常繼續，並將於下一個計費日前自動從您目前登錄的付款方式扣款（除非您在此之前取消訂閱）。</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="dt">
        <table class="pm_tokens_table">
            <thead>
                <tr>
                    <th>Card Type</th>
                    <th>Last 4 digits</th>
                    <th>Expiry</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="pm_tokens_table_body">
                <?php foreach ($token_details as $token) { ?>
                    <tr>
                        <td><?= $token['card_type'] ?></td>
                        <td><?= $token['last4'] ?></td>
                        <td><?= $token['expiry'] ?></td>
                        <td><button type="button" class="remove_token" data-id="<?= $token['id'] ?>">Remove</button></td>
                    </tr>
                <?php } ?>
                <!-- show no payment method -->
                <?php if (count($token_details) == 0) { ?>
                    <tr>
                        <td colspan="4" style='text-align:center'>No payment method</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <div class="mav2_success_alert" style="display: none;">Successfully Updated</div>

    <div id="new_payment_add">
        <p>Add new payment method</p>

        <form id="payment-form">
            <div id="card-element">
                <!-- A Stripe Element will be inserted here. -->
            </div>
            <div id="card-errors" role="alert"></div>
            <button class="strip_submit" type="submit">Update Payment Details</button>
        </form>
    </div>

    <div style="display: none;" class="processing">

        <div class="loader">
            <div class="mav2-custom-loader"></div>
        </div>
    </div>


    <?php if ($mav2_is_first_payment_methods) : ?>
    <script src="https://js.stripe.com/v3/"></script>
    <?php endif; ?>

    <script>
    <?php if ($mav2_is_first_payment_methods) : ?>
        // shared helpers -- take the instance root element/selector as `parent`
        // so they work no matter how many times this shortcode is on the page
        function mav2_show_success_from(parent) {
            jQuery(parent).find('.mav2_success_alert').fadeIn();

            setTimeout(() => {
                jQuery(parent).find('.mav2_success_alert').fadeOut();
            }, 3000);
        }

        function show_processing(parent) {
            jQuery(parent).find('.processing').fadeIn();
        }

        function hide_processing(parent) {
            jQuery(parent).find('.processing').fadeOut();
        }

        function stripeTokenHandler(token, form) {
            var hiddenInput = document.createElement('input');
            hiddenInput.setAttribute('type', 'hidden');
            hiddenInput.setAttribute('name', 'stripeToken');
            hiddenInput.setAttribute('value', token.id);
            form.appendChild(hiddenInput);
            form.submit();
        }
    <?php endif; ?>

        jQuery(function ($) {

            // this shortcode can be placed more than once on a page, so every
            // lookup below is scoped to this instance's own container instead
            // of relying on ids being unique in the document
            var root = document.getElementById(<?php echo json_encode($container_id); ?>);

            var stripe = Stripe('<?= apply_filters('get_stripe_keys', 'publishable_key') ?>');
            var elements = stripe.elements();
            var card = elements.create('card');
            card.mount(root.querySelector('#card-element'));

            card.addEventListener('change', function (event) {
                var displayError = root.querySelector('#card-errors');
                if (event.error) {
                    displayError.textContent = event.error.message;
                } else {
                    displayError.textContent = '';
                }
            });

            function fill_table(tokens) {
                let body_data = "";
                tokens.forEach(function (token) {

                    body_data += `<tr>
                                            <td>${token.card_type}</td>
                                            <td>${token.last4}</td>
                                            <td>${token.expiry}</td>
                                            <td><button type="button" class="remove_token" data-id="${token.id}">Remove</button></td>
                                        </tr>`
                })

                $(root).find('#pm_tokens_table_body').html(body_data);
            }


            var form = root.querySelector('#payment-form');

            form.addEventListener('submit', function (event) {
                event.preventDefault();

                stripe.createPaymentMethod({
                    type: 'card',
                    card: card,
                    billing_details: {
                        name: '<?= $user->display_name ?>',
                    },
                }).then(function (result) {
                    if (result.error) {
                        var errorElement = root.querySelector('#card-errors');
                        errorElement.textContent = result.error.message;
                    } else {
                        show_processing(root);
                        $.ajax({
                            type: "POST",
                            url: mav2.ajaxurl,
                            data: {
                                action: 'mav2_user_add_payment_method',
                                nonce: mav2.nonce,
                                token: result.paymentMethod,
                            },
                            success: function (tokens) {
                                hide_processing(root);
                                fill_table(tokens)
                                mav2_show_success_from(root);
                                card.clear();
                            }
                        })
                    }
                });



            });

            $(root).on('click', '.remove_token', function () {
                var token_id = $(this).data('id');
                const conf = confirm('Are you sure?');
                if (!conf) {
                    return;
                }

                show_processing(root);

                try {

                    $.ajax({
                        type: "POST",
                        url: mav2.ajaxurl,
                        data: {
                            action: 'mav2_user_delete_payment_method',
                            nonce: mav2.nonce,
                            id: token_id
                        },
                        success: function (tokens) {
                            hide_processing(root);
                            mav2_show_success_from(root);

                            if (tokens.success == false) {
                                alert(tokens.data);
                                return;
                            }

                            fill_table(tokens.data)
                        },
                        error: function (xhr, error) {
                            hide_processing(root);
                            console.log([xhr, error]);

                        }

                    })

                } catch (error) {
                    console.log(error);

                }



            })


        })
    </script>

</div>