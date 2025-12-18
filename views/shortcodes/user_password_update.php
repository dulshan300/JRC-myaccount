<div id="user_password_update">

    <form id="user_password_update_form" method="post">
        <div class="mav2_fg">
            <label for="current_password">Current password <span class="astric">*</span></label>
            <div class="with_icon">
                <input id="current_password" type="password" name="current_password" id="current_password" required>
                <span data-eye="current_password" class="mav2_icon mav2_eye"></span>
            </div>
            <span id="current_password_error" style="display: none;" class="mav2_error"></span>
        </div>
        <div class="mav2_fg">
            <label for="new_password">New password <span class="astric">*</span></label>
            <div class="with_icon">
                <input id="new_password" type="password" name="new_password" id="new_password" required>
                <span data-eye="new_password" class="mav2_icon mav2_eye"></span>
            </div>
            <span id="new_password_error" style="display: none;" class="mav2_error"></span>
        </div>
        <div class="mav2_fg">
            <label for="confirm_password">Confirm password <span class="astric">*</span></label>
            <div class="with_icon">
                <input id="confirm_password" type="password" name="confirm_password" id="confirm_password" required>
                <span data-eye="confirm_password" class="mav2_icon mav2_eye"></span>
            </div>
            <span id="confirm_password_error" style="display: none;" class="mav2_error"></span>
        </div>

        <div class="">
            <button type="submit">Submit</button>
        </div>

        <div class="mav2_success_alert" style="display: none;">Successfully Updated</div>
    </form>



</div>