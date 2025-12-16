<?php
global $wpdb;
$user = wp_get_current_user();

$user_id = $user->ID;

// 1,3,6,12 months coupons
$cancelling_coupons = [
    JRC_Helper::get_setting('cancelling_coupon_1m', ''),
    JRC_Helper::get_setting('cancelling_coupon_3m', ''),
    JRC_Helper::get_setting('cancelling_coupon_6m', ''),
    JRC_Helper::get_setting('cancelling_coupon_12m', ''),
];
// validating coupon

$eligible_coupons = [false, false, false, false];

$test_accounts = [
    "xianliangtestinginjuly@gmail.com",
    "xianliangt319@gmail.com",
    "tanxianliang47@gmail.com",
    "xianliangt319@gmail.com",
    "dulshan@webpotato.sg",
    "webpotatosg@gmail.com",
    "d1madusanka@gmail.com"
];

// for testing
// if (in_array($user->user_email, $test_accounts)) {

foreach ($cancelling_coupons as $key => $coupon_code) {
    $coupon = new WC_Coupon($coupon_code);
    $coupon_id = $coupon->get_id();
    if (!$coupon_id) {
        $eligible_coupons[$key] = false;
    } else {
        $eligible_coupons[$key] = true;
    }
}
// }

?>

<template id="panel-template">

    <div class="jrc_popup">

        <div class="jrc_popup_panel">

            <button v-if="canClose" @click.prevent="$emit('close')" type="button" class="jrc_popup_panel_header_control_close">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x-icon lucide-x">
                    <path d="M18 6 6 18" />
                    <path d="m6 6 12 12" />
                </svg>
            </button>

            <div v-if="$slots.icon" class="jrc_popup_panel_header_control">
                <i class="icon">
                    <slot name="icon" />
                </i>
            </div>

            <div v-if="$slots.title" class="jrc_popup_panel_header_text">
                <slot name="title" />
            </div>

            <div class="jrc_popup_panel_content">
                <slot />
            </div>

            <div v-if="$slots.footer" class="jrc_popup_panel_footer">
                <slot name="footer" />
            </div>
        </div>

    </div>

</template>


<script>
    window.vueLoader = (properties) => {
        console.log(Vue.version + ' ' + 'vueLoader init...');

        properties.forEach(prop => {
            if (typeof window[prop] === 'undefined') {
                window[prop] = Vue[prop];
            }
        });
    };

    window.vueLoader(['createApp', 'computed', 'onMounted', 'onUpdated', 'watch', 'ref', 'reactive']);


    // define vPopup component
    const vPopup = {
        template: '#panel-template',
        props: {
            canClose: {
                type: Boolean,
                default: true
            }
        },

        data() {
            return {
                msg: 'Hello Vue 3'
            }
        }

    }

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
                NONE: 0,
                LOADING: 100,
                ERROR: 404,
                CHANGE_PLAN: 200,
                CHANGE_PLAN_CONFIRM: 201,
                CANCEL_OPEN: 202,
                CANCEL_WAIT: 203,
                CANCEL_NOTE: 204,
                COUPON_APPLY: 205,
                COUPON_APPLY_SUCCESS: 206,
            }

            const reasons = ref([{
                    id: 1,
                    text: "The subscription cost is too high",
                    action: "switch-plan"
                },
                {
                    id: 2,
                    text: "The variety of snacks is limited",
                    action: null
                },
                {
                    id: 3,
                    text: "Too many snacks delivered each month",
                    action: null
                },
                {
                    id: 4,
                    text: "Poor snack quality",
                    action: "feedback"
                },
                {
                    id: 5,
                    text: "I only wanted a one-time subscription",
                    action: "discount"
                },
                {
                    id: 6,
                    text: "Frequent delivery delays",
                    action: null
                },
                {
                    id: 7,
                    text: "I've lost interest in receiving regular snacks",
                    action: null
                },
                {
                    id: 8,
                    text: "I've moved to a new address",
                    action: "address"
                },
                {
                    id: 9,
                    text: "Others or additional feedback",
                    action: "feedback"
                }
            ]);

            const msg = ref('hello world');

            const subscription_data = ref([..._subscription_data]);
            // [1,3,6,12]
            const eligible_coupons_plans = ref(<?php echo json_encode($eligible_coupons); ?>);
            const coupon_box = ref({});


            const show_sub_edit_popup = ref(false);
            const current_panel = ref(PANELS.NONE);
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

            const setProcessing = (status = false, text = 'Processing...', ) => {
                processing_text.value = text;
                processing.value = status;
                if (status) setPanel(PANELS.LOADING);
            }

            const setPanel = (panel) => {
                current_panel.value = panel; // Update refs directly using .value
                show_sub_edit_popup.value = true;
            }

            const closePopup = () => {
                current_panel.value = PANELS.NONE;
                setTimeout(() => {

                    show_sub_edit_popup.value = false;
                    next_renew_at.value = "";
                    selected_subscription_id.value = null;
                    selected_plan.value = {};
                    selected_plan_id.value = "";
                    current_plan.value = {};
                    plan_selection.value = [];

                }, 500);

            }

            const selectPlan = (id) => {
                // check if the id belongs for a current pending update
                let update_pending = false;

                plan_selection.value.forEach((plan) => {
                    if (plan.id == id && plan.update_pending == true) {
                        update_pending = true;
                    }
                });

                if (!update_pending) selected_plan_id.value = id;
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

                try {

                    let {
                        data
                    } = await _ajax(param);

                    if (data.success == false) {
                        error_text.value = data.data;
                        setPanel(PANELS.ERROR);
                        return;
                    }


                    // get data
                    next_renew_at.value = data.data.next_renew_At_n;
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

                } catch (error) {
                    error_text.value = "Please Try Again...";
                    setPanel(PANELS.ERROR);
                }

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


            const selected_sub_plan = ref(null);

            const showCancleOpenPopup = async (id, plan) => {

                selected_subscription_id.value = id;
                selected_sub_plan.value = plan;


                setPanel(PANELS.CANCEL_OPEN);

            }

            const cancel_anyway_handler = async () => {

                const plans_raw = [1, 3, 6, 12];
                const plan_index = plans_raw.indexOf(parseInt(selected_sub_plan.value));

                if (eligible_coupons_plans.value[plan_index]) {
                    // get order details
                    setProcessing(true, 'Getting Subscription Details...');

                    try {
                        // check for coupon usage
                        let param = {
                            'action': 'mav2_check_coupon_usage',
                            id: selected_subscription_id.value
                        };

                        const usage = await _ajax(param);

                        if (usage.data.remain === 0) {
                            setPanel(PANELS.CANCEL_WAIT);
                            return;
                        }

                        param = {
                            'action': 'mav2_get_subscription_details',
                            id: selected_subscription_id.value
                        };

                        let {
                            data
                        } = await _ajax(param);


                        if (data.success === false) {
                            error_text.value = data.data;
                            setPanel(PANELS.ERROR);
                            return;
                        }

                        console.log(data);

                        const plans = data.data.plans;
                        const _current_plan = plans.find(p => p.is_current == true);

                        let saving = _current_plan.raw_price * (_current_plan.special_discount / 100);
                        saving = parseInt(saving * 100);
                        saving = saving / 100;
                        saving = saving.toFixed(2);

                        let price = _current_plan.raw_price - saving;
                        price = parseInt(price * 100);
                        price = price / 100;
                        price = price.toFixed(2);

                        coupon_box.value = {
                            plan: _current_plan.name,
                            price: `${_current_plan.currency}${price}`,
                            discount: _current_plan.special_discount,
                            renew_at: data.data.next_renew_At_n,
                            original_price: `${_current_plan.price}`,
                            saving: `${_current_plan.currency}${saving}`,
                        }

                        setPanel(PANELS.COUPON_APPLY);

                    } catch (error) {

                    }


                } else {

                    setPanel(PANELS.CANCEL_WAIT);
                }

            }

            const acceptCouponOffer = async () => {

                setProcessing(true, 'Accepting Coupon Offer...');

                try {
                    const payload = {
                        id: selected_subscription_id.value,
                        action: 'mav2_accept_coupon_offer'
                    }

                    const {
                        data
                    } = await _ajax(payload);

                    if (data.status == 'error') {
                        error_text.value = data.message;
                        setPanel(PANELS.ERROR);
                        return;
                    }



                    setPanel(PANELS.COUPON_APPLY_SUCCESS);

                } catch (error) {

                }
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

            const cancel_plan_change = async () => {
                console.log('cancel_plan_change');
                setProcessing(true, 'Cancelling plan change...')

                let data = {
                    'action': 'mav2_subscription_upgrade_cancel',
                    id: selected_subscription_id.value,
                    nonce: mav2.nonce
                };

                let res = await _ajax(data);

                showUpdatePopup();
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
                coupon_box,
                selectPlan,
                setPanel,
                closePopup,
                showUpdatePopup,
                confirmUpdate,
                processCancel,
                cancel_plan_change,
                acceptCouponOffer,
                cancel_anyway_handler
            }
        }
    });
    subscription_app.component('v-popup', vPopup)

    subscription_app.mount('#subscription_app');
</script>