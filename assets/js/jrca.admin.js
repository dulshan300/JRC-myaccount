const { createApp, ref, onMounted, watch } = Vue;

async function _ajax(data) {
    const fd = new FormData();

    for (const [key, value] of Object.entries(data)) {
        fd.append(key, value);
    }

    fd.append('nonce', JRCAnalytics.nonce);

    return axios.post(JRCAnalytics.ajaxurl, fd);
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


createApp({

    setup() {

        const msg = ref('Hello World');
        const is_process = ref(false);
        const process_message = ref('');

        const stat_date_from = ref('');
        const stat_date_to = ref('');

        const summery = ref({
            "active_subscribers": "0",
            "new_subscribers": "0",
            "prepaid_cancel": "0",
            "cancelled": "0",
            "on_hold": "0",
            "refund": "0",
            "cancelled_unfulfilled": 0,
            "cancelled_fulfilled": 0,
            "total_revenue": "0.00",
            "rev_by_currency": {
                "SGD $": "0.00",
                "TWD $": "0.00",
                "USD $": "0.00"
            },
            "fulfilment": {
                "total": "0",
                "wc-processing": "0",
                "wc-completed": "0",
            },

            "next_month_boxes": 0,
            "next_month_renew": 0,
        });

        const set_loading = (state, message = "Working...") => {
            is_process.value = state;
            process_message.value = message;
        }

        const get_stat_data = async () => {

            set_loading(true, "Loading Data...");
            try {

                const response = await _ajax({
                    action: 'jrc_summery',
                    stat_date_from: stat_date_from.value,
                    stat_date_to: stat_date_to.value
                });

                // console.log(response.data);
                summery.value = response.data;

            } catch (error) {

            }

            set_loading(false);

        }

        const do_stat_filter = async () => {
            await get_stat_data();
        }

        const do_stat_filter_reset = async () => {
            stat_date_from.value = '';
            stat_date_to.value = '';
            await get_stat_data();
        }

        onMounted(async () => {

            await get_stat_data();

        })

        const download_snack_box_analytics_report = async () => {

            set_loading(true, "Processing...");

            try {
                const response = await _ajax({
                    action: 'download_snack_box_analytics_report'
                });


                _file_download(response.data.data);

                console.log(response.data);
            } catch (error) {

            }

            set_loading(false);

        }

        const download_financial_report = async () => {

            set_loading(true, "Processing...");

            try {

                const response = await _ajax({
                    action: 'download_finance_report'
                });

                _file_download(response.data.data);

                console.log(response.data);
                

             } catch (error) { }

             set_loading(false);

        }

        return {
            msg,
            is_process,
            process_message,
            summery,
            stat_date_from,
            stat_date_to,
            download_snack_box_analytics_report,
            download_financial_report,
            do_stat_filter,
            do_stat_filter_reset
        }

    }

}).mount('#jrca_app')