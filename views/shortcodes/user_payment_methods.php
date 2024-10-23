<?php
// get current user
$user = wp_get_current_user();

if (empty($user)) {
    echo 'Please login to see your subscription';
    return;
}

// get woocommerce customer
$customer = new WC_Customer($user->ID);

// get payment methods
$tokens = WC_Payment_Tokens::get_customer_tokens($user->ID);

// remove all tokens

$token_details = [];

foreach ($tokens as $token) {
    // delete token
    $token_details[] = [
        'id' => $token->get_id(),
        'card_type' => $token->get_card_type(),
        'last4' => $token->get_last4(),
        'expiry' => $token->get_expiry_month() . '/' . $token->get_expiry_year(),
        'user_id' => $token->get_user_id()
    ];

    // $token->delete();
}

?>

<table style="width: 100%;">
    <thead>
        <tr>
            <th>Card Type</th>
            <th>Last 4</th>
            <th>Expiry</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($token_details as $token) : ?>
            <tr>
                <td><?= $token['card_type'] ?></td>
                <td><?= $token['last4'] ?></td>
                <td><?= $token['expiry'] ?></td>
                <td><button type="button" class="remove_token" data-id="<?= $token['id'] ?>">Remove</button></td>
            </tr>
        <?php endforeach; ?>
        <!-- show no payment method -->
        <?php if (count($token_details) == 0) : ?>
            <tr>
                <td colspan="4" style='text-align:center'>No payment method</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<form id="payment-form">
    <div id="card-element">
        <!-- A Stripe Element will be inserted here. -->
    </div>
    <div id="card-errors" role="alert"></div>
    <button class="strip_submit" type="submit">Update Payment Details</button>
</form>

<script src="https://js.stripe.com/v3/"></script>

<script>
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
                    console.log(result);
                    // stripeTokenHandler(result.token);                    
                    $.ajax({
                        type: "POST",
                        url: mav2.ajaxurl,
                        data: {
                            action: 'mav2_user_add_payment_method',
                            nonce: mav2.nonce,
                            token: result.paymentMethod,
                        },
                        success: function(data) {

                            console.log(data);
                        }
                    })
                }
            });



        });

        $('.remove_token').on('click', function() {
            var token_id = $(this).data('id');
            const conf = confirm('Are you sure?');
            if (!conf) {
                return;
            }

            $.ajax({
                type: "POST",
                url: mav2.ajaxurl,
                data: {
                    action: 'mav2_user_delete_payment_method',
                    nonce: mav2.nonce,
                    id: token_id
                },
                success: function(data) {

                    console.log(data);
                    location.reload();
                }
            })

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