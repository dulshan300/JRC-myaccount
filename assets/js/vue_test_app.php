<script>
    const {
        createApp,
        reactive
    } = Vue;
    const test_app = createApp({
        setup() {

            const state = reactive({
                msg: 'Hello Vue 3'
            })


            return {
                state
            }
        }
    }).mount('#test_app')
</script>