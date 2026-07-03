<?php
// This file is included (not include_once) once per shortcode instance so the
// same shortcode can be placed more than once on a page. The shared helper
// function below is only defined once per page via a global guard flag; the
// per-instance init call at the bottom runs every time.
$mav2_is_first_simple_subscription_app = empty($GLOBALS['mav2_subscription_simple_app_bootstrapped']);
$GLOBALS['mav2_subscription_simple_app_bootstrapped'] = true;
?>

<script>
<?php if ($mav2_is_first_simple_subscription_app) : ?>
    // reuse the shared vueLoader helper if the full user_subscriptions app
    // already defined it on this page, otherwise define it here
    if (typeof window.vueLoader === 'undefined') {
        window.vueLoader = (properties) => {
            properties.forEach(prop => {
                if (typeof window[prop] === 'undefined') {
                    window[prop] = Vue[prop];
                }
            });
        };
    }

    window.vueLoader(['createApp', 'computed', 'ref']);

    // shared factory so every instance of this shortcode on the page gets its
    // own Vue app + state, instead of colliding on global consts/ids
    window.mav2InitSubscriptionSimpleApp = function(containerSelector, subscriptionData) {

        const subscription_app = createApp({
            setup() {

                const subscription_data = ref([...subscriptionData]);

                const activeTab = ref('active');

                const activeSubscriptions = computed(() =>
                    subscription_data.value.filter(s => s.status === 'wc-active')
                );
                const inactiveSubscriptions = computed(() =>
                    subscription_data.value.filter(s => s.status !== 'wc-active')
                );

                // switch to the my-subscriptions tab and hand off the
                // subscription id so that shortcode's app opens its detail panel
                const goToSubscription = (id) => {
                    document.querySelector('[data-tab="my-subscriptions"] a.elementor-button')?.click();

                    window.dispatchEvent(new CustomEvent('mav2:open-subscription-detail', {
                        detail: { id }
                    }));
                };

                return {
                    subscription_data,
                    activeTab,
                    activeSubscriptions,
                    inactiveSubscriptions,
                    goToSubscription
                }
            }
        });

        subscription_app.mount(containerSelector);

        return subscription_app;
    };
<?php endif; ?>

    window.mav2InitSubscriptionSimpleApp(
        '#<?php echo esc_js($container_id); ?>',
        <?php echo json_encode($out_data); ?>
    );
</script>
