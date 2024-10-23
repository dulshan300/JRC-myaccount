<?php

$user = wp_get_current_user();

$user_id = $user->ID;

if (empty($user_id)) {
    echo 'Please login';
    return;
}

$first_name = get_user_meta($user_id, 'first_name', true);
$last_name = get_user_meta($user_id, 'last_name', true);
$email = $user->user_email;
$display_name = $user->display_name;

?>


<div id="account_details">


    <form id="user_account_details_update_form">

        <div class="mav2_fg_row">

            <div class="mav2_fg">
                <label for="first_name">First name *</label>
                <input type="text" name="first_name" id="first_name" autocomplete="given-name" value="<?= $first_name; ?>" required>
                <span id="first_name_error" style="display: none;" class="mav2_error"></span>
            </div>

            <div class="mav2_fg">
                <label for="last_name">Last name *</label>
                <input type="text" name="last_name" id="last_name" autocomplete="family-name" value="<?= $last_name; ?>" required>
                <span id="last_name_error" style="display: none;" class="mav2_error"></span>
            </div>

        </div>

        <div class="mav2_fg_row">
            <div class="mav2_fg">
                <label for="display_name">Display name *</label>
                <input type="text" name="display_name" id="display_name" value="<?= $display_name; ?>" required>
                <span id="display_name_error" style="display: none;" class="mav2_error"></span>
            </div>

            <div class="mav2_fg">
                <label for="email">Email *</label>
                <input type="text" name="email" id="email" value="<?= $email; ?>" required>
                <span id="ua_email_error" style="display: none;" class="mav2_error"></span>
            </div>

        </div>


        <button type="submit">Submit</button>

        <div class="mav2_success_alert" style="display: none;">Successfully Updated</div>
    </form>



</div>