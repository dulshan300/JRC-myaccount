<?php
// get current user

use function PHPSTORM_META\type;

$user = wp_get_current_user();

if (empty($user)) {
    echo 'Please login to see your subscription';

    return;
}

$user_id = $user->ID;
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

?>

<div id="payment_methods">

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


    <script src="https://js.stripe.com/v3/"></script>

    <script>
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

        jQuery(document).ready(function($) {

            var stripe = Stripe('<?= apply_filters('get_stripe_keys', 'publishable_key') ?>');
            var elements = stripe.elements();
            var card = elements.create('card');
            card.mount('#card-element');

            card.addEventListener('change', function(event) {
                var displayError = document.getElementById('card-errors');
                if (event.error) {
                    displayError.textContent = event.error.message;
                } else {
                    displayError.textContent = '';
                }
            });

            function fill_table(tokens) {
                let body_data = "";
                tokens.forEach(function(token) {

                    body_data += `<tr>
                                            <td>${token.card_type}</td>
                                            <td>${token.last4}</td>
                                            <td>${token.expiry}</td>
                                            <td><button type="button" class="remove_token" data-id="${token.id}">Remove</button></td>
                                        </tr>`
                })

                $('#pm_tokens_table_body').html(body_data);
            }


            var form = document.getElementById('payment-form');

            form.addEventListener('submit', function(event) {
                event.preventDefault();

                stripe.createPaymentMethod({
                    type: 'card',
                    card: card,
                    billing_details: {
                        name: '<?= $user->display_name ?>',
                    },
                }).then(function(result) {
                    if (result.error) {
                        var errorElement = document.getElementById('card-errors');
                        errorElement.textContent = result.error.message;
                    } else {
                        show_processing('#payment_methods');
                        $.ajax({
                            type: "POST",
                            url: mav2.ajaxurl,
                            data: {
                                action: 'mav2_user_add_payment_method',
                                nonce: mav2.nonce,
                                token: result.paymentMethod,
                            },
                            success: function(tokens) {
                                hide_processing('#payment_methods');
                                fill_table(tokens)
                                mav2_show_success_from('#payment_methods');
                                card.clear();
                            }
                        })
                    }
                });



            });

            $(document).on('click', '.remove_token', function() {
                var token_id = $(this).data('id');
                const conf = confirm('Are you sure?');
                if (!conf) {
                    return;
                }

                show_processing('#payment_methods');

                try {

                    $.ajax({
                        type: "POST",
                        url: mav2.ajaxurl,
                        data: {
                            action: 'mav2_user_delete_payment_method',
                            nonce: mav2.nonce,
                            id: token_id
                        },
                        success: function(tokens) {
                            hide_processing('#payment_methods');
                            mav2_show_success_from('#payment_methods');

                            if (tokens.success == false) {
                                alert(tokens.data);
                                return;
                            }

                            fill_table(tokens.data)
                        },
                        error: function(xhr, error) {
                            hide_processing('#payment_methods');
                            console.log([xhr, error]);

                        }

                    })

                } catch (error) {
                    console.log(error);

                }



            })


        })

        function stripeTokenHandler(token) {

            const keys = Object.keys(token.card);

            var form = document.getElementById('payment-form');
            var hiddenInput = document.createElement('input');
            hiddenInput.setAttribute('type', 'hidden');
            hiddenInput.setAttribute('name', 'stripeToken');
            hiddenInput.setAttribute('value', token.id);
            form.appendChild(hiddenInput);
            form.submit();
        }
    </script>

</div>