<?php
$user_id = get_current_user_id();

if (empty($user_id)) {
    echo 'Please login';

    return;
}

// get billing address

$billing_first_name = get_user_meta($user_id, 'billing_first_name', true);
$billing_last_name = get_user_meta($user_id, 'billing_last_name', true);
$billing_email = get_user_meta($user_id, 'billing_email', true);
$billing_phone = get_user_meta($user_id, 'billing_phone', true);
$billing_address_1 = get_user_meta($user_id, 'billing_address_1', true);
$billing_address_2 = get_user_meta($user_id, 'billing_address_2', true);
$billing_city = get_user_meta($user_id, 'billing_city', true);
$billing_postcode = get_user_meta($user_id, 'billing_postcode', true);
$billing_country = get_user_meta($user_id, 'billing_country', true);

$countries_obj = new WC_Countries; // Initialize the WC_Countries class
$countries = $countries_obj->get_countries(); // Get the list of countries

?>

<div id="user_address_update">
    <form id="user_address_update_form">


        <div class="mav2_fg_row">

            <div class="mav2_fg">
                <label for="billing_first_name">First name *</label>
                <input type="text" name="billing_first_name" id="billing_first_name" autocomplete="given-name" value="<?= $billing_first_name; ?>" required>
                <span id="billing_first_name_error" style="display: none;" class="mav2_error"></span>
            </div>

            <div class="mav2_fg">
                <label for="billing_last_name">Last name *</label>
                <input type="text" name="billing_last_name" id="billing_last_name" autocomplete="family-name" value="<?= $billing_last_name; ?>" required>
                <span id="billing_last_name_error" style="display: none;" class="mav2_error"></span>
            </div>

        </div>

        <div class="mav2_fg_row">

            <div class="mav2_fg">
                <label for="billing_email">Email address *</label>
                <input type="email" name="billing_email" id="billing_email" autocomplete="email" value="<?= $billing_email; ?>" required>
                <span id="fbilling_email_error" style="display: none;" class="mav2_error"></span>
            </div>

            <div class="mav2_fg">
                <label for="billing_phone">Phone *</label>
                <input type="tel" name="billing_phone" id="billing_phone" value="<?= $billing_phone; ?>" autocomplete="tel" required>
                <span id="billing_phone_error" style="display: none;" class="mav2_error"></span>
            </div>

        </div>

        <div class="mav2_fg_row">

            <div class="mav2_fg">
                <label for="billing_address_1">Street address *</label>
                <input type="text" name="billing_address_1" id="billing_address_1" autocomplete="address-line1" value="<?= $billing_address_1; ?>" required>
                <span id="billing_address_1_error" style="display: none;" class="mav2_error"></span>
                <input type="text" name="billing_address_2" id="billing_address_2" autocomplete="address-line2" value="<?= $billing_address_2; ?>" placeholder="Apartment, suite, unit etc. (optional)">
            </div>

            <div class="mav2_fg">
                <label for="billing_country">Country / Region *</label>
                <select name="billing_country" id="billing_country" required>
                    <option value="">Select a country / region…</option>
                    <?php foreach ($countries as $key => $value) { ?>
                        <option value="<?= $key; ?>" <?= $key == $billing_country ? 'selected' : ''; ?>><?= $value; ?></option>
                    <?php } ?>
                </select>
                <span id="billing_country_error" style="display: none;" class="mav2_error"></span>
            </div>

        </div>


        <div class="mav2_fg_row">
            <div class="mav2_fg">
                <label for="billing_postcode">Postcode / ZIP *</label>
                <input type="text" name="billing_postcode" id="billing_postcode" value="<?= $billing_postcode; ?>" autocomplete="postal-code" required>
                <span id="billing_postcode_error" style="display: none;" class="mav2_error"></span>
            </div>
            <div class="mav2_fg">
                <label for="billing_city">Town / City (optional)</label>
                <input type="text" name="billing_city" id="billing_city" value="<?= $billing_city; ?>" autocomplete="address-level2">
            </div>
        </div>

        <button type="submit">Submit</button>

        <div class="mav2_success_alert" style="display: none;">Successfully Updated</div>

    </form>
</div>