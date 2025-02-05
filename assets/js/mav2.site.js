

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


let selected_subscription_id = null;
let selected_cancle_option = null;

const reasons = [
    { id: 1, text: "The subscription cost is too high", action: "switch-plan" },
    { id: 2, text: "The variety of snacks is limited", action: null },
    { id: 3, text: "Too many snacks delivered each month", action: null },
    { id: 4, text: "Poor snack quality", action: "feedback" },
    { id: 5, text: "I only wanted a one-time subscription", action: "discount" },
    { id: 6, text: "Frequent delivery delays", action: null },
    { id: 7, text: "I've lost interest in receiving regular snacks", action: null },
    { id: 8, text: "I've moved to a new address", action: "address" },
    { id: 9, text: "Others or additional feedback", action: "feedback" }
];

(function async($) {


    // init models
    $('.mav2_popup').each(function () {
        const popup = $(this);
        $(this).find('.close-popup').on('click', function () {
            $(popup).fadeOut();
        })
    });

    $(document).on('click', '[data-popup]', function () {
        const popup = $(this).data('popup');
        $(`#${popup}`).fadeIn();
    })


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

    $(document).on('click', '.close-popup, .btn-close', function () {
        $('.sub_cancel_popup').fadeOut();
        $('.panel').hide();
        $('.panel').each(function (i) {
            if (i == 0) {
                $(this).show();
            }
        });
    });

    $(window).on('click', function (e) {
        if ($(e.target).hasClass('sub_cancel_popup')) {
            $('.sub_cancel_popup').fadeOut();
            selected_cancle_option = null;
            selected_subscription_id = null;

            $('.sub_plan_cancel_sub').prop('disabled', false);
        }
    });

    $(document).on('click', '.btn-next', function () {

        const current_panel = $(this).data('current');
        const next_panel = $(this).data('next');

        $(`[data-panel=${current_panel}]`).hide();
        $(`[data-panel=${next_panel}]`).show();
    })

    $(document).on('click', '.sub_plan_cancel_sub', async function () {

        $('#reasons_container').empty();

        reasons.forEach(r => {
            const html = $(`<label class="reason-item">
                        <input type="radio" name="cancel_reason" value="${r.id}">
                        <span>${r.text}</span>
                        </label>`)
            $(html).on('click', function () {

                selected_cancle_option = r.id;

                if (r.id == 9) {
                    $('.feedback-box').fadeIn();
                } else {
                    $('.feedback-box').fadeOut();
                }

            });

            $('#reasons_container').append(html);

        })

        $('.sub_cancel_popup').fadeIn();
        selected_subscription_id = $(this).data('sub');

    })

    $(document).on('submit', '#cancellationForm', async function (e) {
        e.preventDefault();

        if (!selected_cancle_option || !selected_subscription_id) {
            return;
        }

        if (selected_cancle_option == 9 && $('#feedback_box').val() == "") {
            return;
        }

        const reson = reasons.find(r => r.id == selected_cancle_option);

        try {
            $('.btn-confirm').prop('disabled', true);
            const data = {
                'action': 'mav2_cancel_subscription',
                id: selected_subscription_id,
                rid: selected_cancle_option,
                r_text: selected_cancle_option == 9 ? $('#feedback_box').val() : reson.text
            };
            let res = await _ajax(data)
            location.reload();
        } catch (error) {
            $('.btn-confirm').prop('disabled', false);
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


    $(document).on('click', '.arrow', function () {
        $(this).toggleClass('flip');
        $(this).parent().parent().find('.order_history_list').slideToggle();
    })


    $(document).on('click', '.sub_update_button', async function () {
        const id = $(this).data('sub');
        // get update details
        try {
            let data = {
                'action': 'mav2_get_subscription_update',
                id: id
            };

            let res = await _ajax(data);

            console.log(res.data);



        } catch (error) {
            console.log(error);
        }
    })



})(jQuery)