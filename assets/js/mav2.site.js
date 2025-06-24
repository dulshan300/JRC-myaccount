
window.vueLoader = (properties) => {
    console.log('vueLoader init...');

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




let selected_subscription_id = null;
let selected_cancle_option = null;
let selected_plan_id = null;
let plan_list = null;

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

        if (selected_cancle_option == 9 && $('#feedback_box').val().trim().length < 6) {
            // need a feedback
            $('#va').show().text('Feedback should contain atleast 6 characters');
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

    async function get_update_data(id) {

        $('#sub_update_loading').fadeIn();
        $('#sub_update_content').hide();
        $('#sub_update_content_details').empty();

        try {
            let data = {
                'action': 'mav2_get_subscription_details',
                id: id
            };

            let res = await _ajax(data);

            console.log(res.data);

            if (res.data.success == false) {
                update_panel.goTo(4);
                $('#error_text').text(res.data.data);
                return;

            }

            console.log(res.data);
            plan_list = res.data;

            $('#active_date').text(res.data.next_renew_at);

            const plans = res.data.plans;

            let table = $('<table></table>').addClass('plans-table');

            plans.forEach(p => {
                const row = $('<tr></tr>');
                // add name

                const nameCell = $('<td></td>').html(`${p.name} ${p.has_saving ? `<span class="discount">${p.discount}%</span>` : ''}`);

                // add price
                const priceCell = $('<td></td>');
                const price_wrap = $('<div class="price-wrap"></div>');
                const price = $('<span class="price"></span>').html(p.price_per_month + (p.plan > 1 ? '/mo' : ''));
                price_wrap.append(price);
                if (p.has_saving) {
                    const saving = ($('<span class="saving"></span>').html(`<span>your saving</span> ${p.save}</span>`));
                    price_wrap.append(saving);
                }

                priceCell.append(price_wrap);

                const actionCell = $('<td></td>');

                if (p.is_current) {
                    actionCell.html('<span>Current Plan</span>');
                } else {
                    if (p.update_pending) {
                        const action_wrap = $('<span class="ac-wrap"></span>');

                        action_wrap.append('<span>Pending Request</span>');
                        const cancelButton = $('<button type="button"></button>').text('Cancel').addClass('cancel-plan-button').data('plan', p.id);
                        action_wrap.append(cancelButton);
                        actionCell.append(action_wrap);
                    } else {
                        const button = $('<button type="button"></button>').text('Select').addClass('select-plan-button').data('plan', p.id);
                        $(button).on('click', function () {
                            $('#selected_plan_cost').html(p.price);
                            $('#total_saving').html(p.save);
                            $('#selected_plan_name').text(p.name);
                            selected_plan_id = p.id;

                            update_panel.goTo(2);
                        })
                        actionCell.append(button);
                    }

                }

                row.append(nameCell).append(priceCell).append(actionCell);
                $(table).append(row);
            })

            $('#cycle_end').text(res.data.next_renew_at);

            $('#sub_update_content_details').append(table);

            $('#sub_update_loading').hide();
            $('#sub_update_content').fadeIn();


        } catch (error) {
            console.log(error);
        }

    }

    $(document).on('click', '.arrow', function () {
        $(this).toggleClass('flip');
        $(this).parent().parent().find('.order_history_list').slideToggle();
    })

    const update_panel = $('#sub_update_panels').panels();

    $(document).on('click', '.sub_update_button', async function () {
        const id = $(this).data('sub');

        // initiate first panel
        update_panel.goTo(1);

        // get update details


        selected_subscription_id = id;

        get_update_data(id);


    })

    $(document).on('click', '#confirm_update_plan', async function () {

        if (selected_subscription_id == null || selected_plan_id == null) {
            return;
        }

        update_panel.goTo(3);


        try {
            let data = {
                'action': 'mav2_update_subscription_plan',
                id: selected_subscription_id,
                plan: selected_plan_id,
                nonce: mav2.nonce
            };

            let res = await _ajax(data);

            get_update_data(selected_subscription_id);

        } catch (error) {
            console.log(error);
        }

        update_panel.goTo(1);

        $('.select-plan-button').prop('disabled', false);
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


window.vueLoader(['createApp', 'computed', 'onMounted', 'onUpdated', 'watch', 'ref']);

const subscription_app = createApp({
    setup(){

        const msg = ref('hello world');

        return {
            msg,
        }

    }
}).mount('#subscription_app');