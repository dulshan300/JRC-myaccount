async function _ajax(data) {
    const fd = new FormData();

    for (const [key, value] of Object.entries(data)) {
        fd.append(key, value);
    }

    fd.append('nonce', mav2.nonce);

    return axios.post(mav2.ajaxurl, fd, {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    });
}

async function _file_download(data) {

    const link = document.createElement('a');
    link.style.display = 'none';
    document.body.appendChild(link);

    // Set href attribute to CSV URL and set download attribute
    link.href = data.url;
    link.setAttribute('download', data.file_name);

    // Trigger the click event to initiate download
    link.click();

    // Clean up: remove the anchor element
    document.body.removeChild(link);
}


(function async($) {

    function mav2_show_success(form) {
        $(form).find('.mav2_success_alert').fadeIn();

        setTimeout(() => {
            $(form).find('.mav2_success_alert').fadeOut();
        }, 3000);
    }

    function show_processing(parent) {
        $(parent).find('.processing').fadeIn();
    }

    function hide_processing(parent) {
        $(parent).find('.processing').fadeOut();
    }

    $(document).on('click', '.sub_plan_cancel_sub', async function () {

        $(this).prop('disabled', true);
        try {
            const data = {
                'action': 'mav2_cancel_subscription',
                'id': $(this).data('sub')
            };
            let res = await _ajax(data)
            location.reload();
        } catch (error) {
            $(this).prop('disabled', false);
        }
    })

    // user_address_update_form

    $(document).on('submit', '#user_address_update_form', async function (e) {
        e.preventDefault();
        let formData = new FormData(this);
        $(this).find('button').prop('disabled', true);
        $('.mav2_error').hide().text('');

        try {
            let data = {
                'action': 'mav2_user_address_update'
            };

            for (const [key, value] of formData.entries()) {
                data[key] = value;
            }

            let res = await _ajax(data);

            // location.reload();

            mav2_show_success(this);
            $(this).find('button').prop('disabled', false);

        } catch (error) {
            console.log(error);
            $(this).find('button').prop('disabled', false);

            if (error.response?.status == 422) {
                let errors = error.response.data;

                for (const [key, value] of Object.entries(errors)) {
                    $(`#${key}_error`).show().text(value);
                }
            }
        }
    })

    // user_account_details_update_form

    $(document).on('submit', '#user_account_details_update_form', async function (e) {
        e.preventDefault();
        let formData = new FormData(this);
        $('.mav2_error').hide().text('');
        $(this).find('button').prop('disabled', true);


        try {
            let data = {
                'action': 'mav2_user_account_details_update_form'
            };

            for (const [key, value] of formData.entries()) {
                data[key] = value;
            }

            let res = await _ajax(data);

            // location.reload();

            mav2_show_success(this);
            $(this).find('button').prop('disabled', false);

        } catch (error) {
            console.log(error);
            $(this).find('button').prop('disabled', false);

            if (error.response?.status == 422) {
                let errors = error.response.data;

                for (const [key, value] of Object.entries(errors)) {
                    $(`#${key}_error`).show().text(value);
                }
            }
        }

    })

    // for password update icons
    $(document).on('click', '.mav2_icon', async function () {

        let id = $(this).data('eye');

        if ($(this).hasClass('mav2_eye')) {
            $(this).removeClass('mav2_eye');
            $(this).addClass('mav2_eye_close');

            $('#' + id).attr('type', 'text');
        } else {
            $(this).removeClass('mav2_eye_close');
            $(this).addClass('mav2_eye');

            $('#' + id).attr('type', 'password');
        }
    })

    // user_password_update_form submittion

    $(document).on('submit', '#user_password_update_form', async function (e) {
        e.preventDefault();
        let formData = new FormData(this);

        $('.mav2_error').hide().text('');
        $(this).find('button').prop('disabled', true);

        try {
            let data = {
                'action': 'mav2_user_password_update_form'
            };

            for (const [key, value] of formData.entries()) {
                data[key] = value;
            }

            let res = await _ajax(data);

            mav2_show_success(this);

            $(this).find('button').prop('disabled', false);

        } catch (error) {
            console.log(error);
            if (error.response?.status == 422) {
                let errors = error.response.data;

                for (const [key, value] of Object.entries(errors)) {
                    $(`#${key}_error`).show().text(value);
                }
            }

            $(this).find('button').prop('disabled', false);
        }
    })



})(jQuery)