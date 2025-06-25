
window.vueLoader = (properties) => {
    console.log(Vue.version + ' ' + 'vueLoader init...');

    properties.forEach(prop => {
        if (typeof window[prop] === 'undefined') {
            window[prop] = Vue[prop];
        }
    });
};




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

// small jquery plugin for handle panels

(function ($) {
    $.fn.panels = function () {
        const panels = this.find('.panel');
        panels.hide();
        panels.first().show();

        return {
            goTo: function (number) {
                number = number - 1;

                if (number < 0 || number >= panels.length) {
                    console.error('Invalid panel number');
                    return;
                }
                panels.hide();
                $(panels[number]).show();
            }
        };
    };
})(jQuery);

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

    $(document).on('click', '.cancel-plan-button', async function () {
        if (selected_subscription_id == null) {
            return;
        }

        update_panel.goTo(3);
        try {
            let data = {
                'action': 'mav2_subscription_upgrade_cancel',
                id: selected_subscription_id,
                nonce: mav2.nonce
            };

            let res = await _ajax(data);

            get_update_data(selected_subscription_id);

        } catch (error) {
            console.log(error);
        }

        update_panel.goTo(1);
    });

    $(document).on('click', '.up-go', function () {
        const panel = $(this).data('to');
        update_panel.goTo(panel);
    })


})(jQuery)




// test app

