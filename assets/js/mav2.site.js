
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


window.vueLoader(['createApp', 'computed', 'onMounted', 'onUpdated', 'watch', 'ref']);

/*

panel numbers
100 - loading
404 - Error
200 - change subscription plan
201 - confirm view
202 - success view

*/

const subscription_app = createApp({
    setup() {

        const PANELS = {
            LOADING: 100,
            ERROR: 404,
            CHANGE_PLAN: 200,
            CHANGE_PLAN_CONFIRM: 201,
            CANCEL_OPEN: 202,
            CANCEL_WAIT: 203,
            CANCEL_NOTE: 204,
        }

        const reasons = ref([
            { id: 1, text: "The subscription cost is too high", action: "switch-plan" },
            { id: 2, text: "The variety of snacks is limited", action: null },
            { id: 3, text: "Too many snacks delivered each month", action: null },
            { id: 4, text: "Poor snack quality", action: "feedback" },
            { id: 5, text: "I only wanted a one-time subscription", action: "discount" },
            { id: 6, text: "Frequent delivery delays", action: null },
            { id: 7, text: "I've lost interest in receiving regular snacks", action: null },
            { id: 8, text: "I've moved to a new address", action: "address" },
            { id: 9, text: "Others or additional feedback", action: "feedback" }
        ]);

        const msg = ref('hello world');

        const subscription_data = ref([..._subscription_data]);

        const show_sub_edit_popup = ref(false);
        const current_panel = ref(0);
        const processing = ref(false);
        const processing_text = ref('Processing...');
        const error_text = ref('');

        const next_renew_at = ref('');
        const selected_subscription_id = ref(null);
        const selected_plan_id = ref("");
        const selected_plan = ref({});
        const current_plan = ref({});
        const plan_selection = ref([]);

        const cancel_reason = ref('');
        const other_reasons = ref('');
        const other_reasons_error = ref(false);

        const setProcessing = (status = false, text = 'Processing...',) => {
            processing_text.value = text;
            processing.value = status;
            if (status) setPanel(PANELS.LOADING);
        }

        const setPanel = (panel) => {
            current_panel.value = panel;  // Update refs directly using .value
            show_sub_edit_popup.value = true;
        }

        const closePopup = () => {
            current_panel.value = 0;
            show_sub_edit_popup.value = false;
            next_renew_at.value = "";
            selected_subscription_id.value = null;
            selected_plan.value = {};
            selected_plan_id.value = "";
            current_plan.value = {};
            plan_selection.value = [];
        }

        const selectPlan = (id) => {
            selected_plan_id.value = id;
        }

        const showUpdatePopup = async (id) => {

            if (!id && !selected_subscription_id.value) {
                console.log('No subscription id found');
                error_text.value = 'No subscription id found';
                setPanel(PANELS.ERROR);
                return;
            }

            if (id) {
                selected_subscription_id.value = id;
            } else {
                id = selected_subscription_id.value;
            }

            current_plan.value = {};
            plan_selection.value = [];


            setProcessing(true, 'Loading...');

            // get subscription data
            let param = {
                'action': 'mav2_get_subscription_details',
                id: id
            };

            let { data } = await _ajax(param);

            if (data.success == false) {
                error_text.value = data.data;
                setPanel(PANELS.ERROR);
                return;
            }

            // get data
            next_renew_at.value = data.data.next_renew_at;
            const plans = data.data.plans;

            for (let i = 0; i < plans.length; i++) {
                if (plans[i].is_current) {
                    current_plan.value = plans[i];
                } else {
                    plan_selection.value.push(plans[i])
                }

            }

            setProcessing();

            setPanel(PANELS.CHANGE_PLAN)

            // You can also access other refs here if needed
            // Example: show_sub_edit_popup.value = true;
        }

        const confirmUpdate = async () => {

            setProcessing(true, 'Updating Plan...');

            selected_plan.value = plan_selection.value.find(plan => plan.id == selected_plan_id.value);

            try {
                let data = {
                    'action': 'mav2_update_subscription_plan',
                    id: selected_subscription_id.value,
                    plan: selected_plan_id.value,
                    nonce: mav2.nonce
                };

                let res = await _ajax(data);

                setPanel(PANELS.CHANGE_PLAN_CONFIRM);


            } catch (error) {
                console.log(error);
            }

        }

        const showCancleOpenPopup = (id) => {

            selected_subscription_id.value = id;
            setPanel(PANELS.CANCEL_OPEN);
        }

        const processCancel = async () => {

            if (selected_subscription_id.value == null) {
                error_text.value = 'Invalid subscription id';
                setPanel(PANELS.ERROR);
                return;
            }

            other_reasons_error.value = false;
            // get reason
            const reason = reasons.value.find(r => r.id == cancel_reason.value);

            if (reason.id == 9 && other_reasons.value.length < 6) {
                other_reasons_error.value = true;
                return;
            }
            try {

                const data = {
                    'action': 'mav2_cancel_subscription',
                    id: selected_subscription_id.value,
                    rid: cancel_reason.value,
                    r_text: cancel_reason.value == 9 ? other_reasons.value : reason.text
                };

                setProcessing(true, 'Cancelling subscription...');

                let res = await _ajax(data)
                setProcessing(true, 'Cancellation successful. Wait for a moment...');
                location.reload();


            } catch (error) {
                console.log(error);

            }








        }

        return {
            msg,
            subscription_data,
            show_sub_edit_popup,
            current_panel,
            processing,
            processing_text,
            selected_plan,
            selected_plan_id,
            error_text,
            current_plan,
            plan_selection,
            next_renew_at,
            cancel_reason,
            PANELS,
            reasons,
            other_reasons,
            other_reasons_error,
            showCancleOpenPopup,
            selectPlan,
            setPanel,
            closePopup,
            showUpdatePopup,
            confirmUpdate,
            processCancel
        }
    }
});
subscription_app.mount('#subscription_app');