
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
        },

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

    $(document).on('click', '.invoice_download', async function () {
        const order_id = $(this).data('id');
        console.log(order_id);

        const formData = new FormData();
        formData.append('action', 'mav2_prepair_invoice');
        formData.append('id', order_id);
        formData.append('nonce', mav2.nonce);

        $('#order_processing').fadeIn(300);

        fetch(mav2.ajaxurl, {
            method: 'POST', // Use POST if you are sending invoice data
            body: formData, // Example data

        })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                $('#order_processing').fadeOut(200);
                return response.blob(); // Convert the response to a Blob
            })
            .then(blob => {
                // Create a local URL for the binary data
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');

                a.style.display = 'none';
                a.href = url;

                // Set the filename based on the source data (e.g., Invoice 12345)
                a.download = `Invoice_${order_id}.pdf`;

                document.body.appendChild(a);
                a.click(); // Trigger the download

                // Cleanup
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                $('#order_processing').fadeOut(200);
            })
            .catch(error => { console.error('Download failed:', error); $('#order_processing').fadeOut(200); });

    })

    $(document).on('click', '.view_items_button', async function (e) {
        e.preventDefault();
        const order_id = $(this).data('id');

        const data = {
            action: 'mav2_get_invoice',
            id: order_id
        }

        $('#order_processing').fadeIn(300);

        const res = await _ajax(data);

        const invoiceData = res.data;


        // 1. Fill basic text fields
        $('#display-id').text(invoiceData.id);
        $('#display-date').text(invoiceData.date);
        $('#display-address').html(invoiceData.address); // Use .html() for <br> support
        $('#display-subtotal').html(invoiceData.subtotal); // Use .html() for HTML entities like &#36;
        $('#display-shipping').html(invoiceData.shipping);
        $('#display-discount').html(invoiceData.discount);
        $('#display-tax').html(invoiceData.tax);
        $('#display-total').html(invoiceData.total);

        // 2. Clear and fill the items table
        let itemsHtml = '';
        invoiceData.items.forEach(function (item) {
            itemsHtml += `
                <tr>
                    <td>${item.name}</td>
                    <td class="text-center">${item.quantity}</td>
                    <td class="text-right">${item.unit_price}</td>
                    <td class="text-right">${item.price}</td>
                </tr>`;
        });
        $('#display-items').html(itemsHtml);
        $('#order_processing').fadeOut(200);
        $('#invoiceModal').fadeIn(300);

        console.log(res);

    })

    $(document).on('click', '.close-modal', function () {
        $('#invoiceModal').fadeOut(200);
    });

    $(window).on('click', function (event) {
        if ($(event.target).is('#invoiceModal')) {
            $('#invoiceModal').fadeOut(200);
        }
    });




})(jQuery)




// test app

