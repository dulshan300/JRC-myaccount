<?php
$this_month = date('M');
?>

<div id="jrca_app" class="wrap">

    <template v-if="is_process">

        <div class="lw">
            <div class="lb">
                <div class="cm_spinner"></div>
                <p>{{process_message}}</p>
            </div>
        </div>

    </template>

    <div id="jrca_main">


        <h1 class="ns-bold">JAPAN RAIL CLUB Dashboard</h1>

        <div class="filter_bar">
            <div class="left">

                <label for="from">From <input v-model="stat_date_from" type="date"></label>
                <label for="to">To <input v-model="stat_date_to" type="date" max="<?= date('Y-m-d') ?>"></label>

                <button @click="do_stat_filter" class="btn_apply_filter">Apply</button>

                <button @click="do_stat_filter_reset" class="btn_reset_filter">
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 3.75V0.75L5.25 4.5L9 8.25V5.25C11.4825 5.25 13.5 7.2675 13.5 9.75C13.5 12.2325 11.4825 14.25 9 14.25C6.5175 14.25 4.5 12.2325 4.5 9.75H3C3 13.065 5.685 15.75 9 15.75C12.315 15.75 15 13.065 15 9.75C15 6.435 12.315 3.75 9 3.75Z" fill="#EA0234" />
                    </svg>

                    Reset Filter

                </button>


            </div>
            <div class="right">
                <button @click="download_snack_box_analytics_report" class="btn_download">
                    Export
                    <svg width="12" height="14" viewBox="0 0 12 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M6 1.16667V12.8333M6 12.8333L11 7.83334M6 12.8333L1 7.83334" stroke="#FCFCFC" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>


                </button>
                <button @click="download_financial_report" class="btn_download">
                    Financial Report
                    <svg width="12" height="14" viewBox="0 0 12 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M6 1.16667V12.8333M6 12.8333L11 7.83334M6 12.8333L1 7.83334" stroke="#FCFCFC" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>


                </button>
            </div>
        </div>

        <template v-if="true">

            <div class="stat_grid">

                <div class="stat_card">
                    <h2 class="stat_title ns-semi-bold">Active Subscribers</h2>
                    <h3 class="stat_main_val">
                        {{summery.active_subscribers}}
                        <span class="sub_data">
                            ({{summery.total_subscriptions}} Subscriptions)
                        </span>
                    </h3>
                    <ul class="stat_ul">
                        <li class="ns-semi-bold">New Subscribers: <span>{{summery.new_subscribers}}</span></li>
                        <li class="ns-semi-bold">Unfulfilled & Cancelled: <span>{{summery.cancelled_unfulfilled}}</span></li>
                        <li class="ns-semi-bold">Fulfilled & Cancelled: <span>{{summery.cancelled_fulfilled}}</span></li>
                        <li class="ns-semi-bold">Cancelled: <span>{{summery.cancelled}}</span></li>
                    </ul>
                </div>
                <div class="stat_card">
                    <h2 class="stat_title ns-semi-bold">Total Revenue</h2>
                    <h3 class="stat_main_val">SGD ${{summery.total_revenue}}</h3>
                    <ul class="stat_ul">
                        <li v-for="item in Object.keys(summery.rev_by_currency)" class="ns-semi-bold">{{item}} <span>{{summery.rev_by_currency[item]}}</span></li>
                    </ul>
                </div>
                <div class="stat_card">
                    <h2 class="stat_title ns-semi-bold">Revenue by Plans</h2>

                    <table>
                        <tr v-for="item in summery.rev_by_plan">
                            <td style="padding: 6px 0;"><strong class="ns-bold">{{item.plan}} Month(s):</strong></td>
                            <td style="padding: 6px 0; text-align: right;"><span>SGD${{item.rev}} | {{item.count}}</span></td>
                        </tr>
                    </table>

                </div>
                <div class="stat_card">
                    <h2 class="stat_title ns-semi-bold">Total Fulfilment</h2>
                    <h3 class="stat_main_val">{{summery.fulfilment.total}}</h3>
                    <table>
                        <tr>
                            <td style="padding: 6px 0;"><strong class="ns-bold">Processing:</strong></td>
                            <td style="padding: 6px 0; text-align: right;"><span>{{summery.fulfilment["wc-processing"]}}</span></td>
                        </tr>
                        <tr>
                            <td style="padding: 6px 0;"><strong class="ns-bold">Completed:</strong></td>
                            <td style="padding: 6px 0; text-align: right;"><span>{{summery.fulfilment["wc-completed"]}}</span></td>
                        </tr>
                    </table>
                </div>
                <div class="stat_card">
                    <h2 class="stat_title ns-semi-bold">On Hold</h2>
                    <h3 class="stat_main_val">{{summery.on_hold}}</h3>
                </div>
                <div class="stat_card">
                    <h2 class="stat_title ns-semi-bold">Refunds</h2>
                    <h3 class="stat_main_val">{{summery.refund}}</h3>
                </div>
                <div class="stat_card">
                    <h2 class="stat_title ns-semi-bold">Boxes to Ship Next Month</h2>
                    <h3 class="stat_main_val">{{summery.next_month_boxes}}</h3>
                </div>
                <div class="stat_card">
                    <h2 class="stat_title ns-semi-bold">No. of Renewal Next Month</h2>
                    <h3 class="stat_main_val">{{summery.next_month_renew}}</h3>
                </div>

            </div>
        </template>


        <!-- Add your analytics data display code here -->

        <!-- jrca_col -->
        <!-- jrca_row -->

        <template>
            <div class="jrca_container">

                <div class="title_bar">
                    <h3 class="title">Analytics</h3>

                    <div class="filters">
                        <label for="from">From <input v-model="stat_date_from" type="date"></label>
                        <label for="to">To <input v-model="stat_date_to" type="date"></label>
                        <button @click="do_stat_filter" class="button">Filter</button>
                        <button @click="do_stat_filter_reset" class="button">
                            <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#2271b1">
                                <path d="M18.5374 19.5674C16.7844 21.0831 14.4993 22 12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12C22 14.1361 21.3302 16.1158 20.1892 17.7406L17 12H20C20 7.58172 16.4183 4 12 4C7.58172 4 4 7.58172 4 12C4 16.4183 7.58172 20 12 20C14.1502 20 16.1022 19.1517 17.5398 17.7716L18.5374 19.5674Z"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="jrca_row">
                    <div class="jrca_col">
                        <div class="jrca_card">
                            <div class="jrca_card_title">Orders (Processing)</div>
                            <div class="jrca_card_content">
                                <div class="vh-center">
                                    <div class="info">
                                        <h3>{{summery.orders }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="jrca_col">
                        <div class="jrca_card">
                            <div class="jrca_card_title">Total Subscribers</div>
                            <div class="jrca_card_content">
                                <div class="vh-center">

                                    <div class="info">
                                        <h3>{{summery.subscribers_total }}</h3>
                                    </div>

                                    <div class="info this_month">
                                        <span><?= $this_month ?></span>
                                        <h3>{{summery.subscribers_this_month}}</h3>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="jrca_col">
                        <div class="jrca_card">
                            <div class="jrca_card_title">Total Cancellations</div>
                            <div class="jrca_card_content">
                                <div class="vh-center">

                                    <div class="info">
                                        <h3>500</h3>
                                    </div>


                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="jrca_col">
                        <div class="jrca_card">
                            <div class="jrca_card_title">Total revenue</div>
                            <div class="jrca_card_content">
                                <div class="vh-center">

                                    <div class="info">
                                        <h3>500</h3>
                                    </div>

                                    <div class="info this_month">
                                        <span><?= $this_month ?></span>
                                        <h3>20</h3>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>


                </div>

                <div class="jrca_row">
                    <div class="jrca_col">
                        <div class="jrca_row">
                            <div class="jrca_col">
                                <div class="slot"></div>
                            </div>
                            <div class="jrca_col">
                                <div class="slot"></div>
                            </div>
                        </div>
                    </div>
                    <div class="jrca_col">
                        <div class="slot"></div>
                    </div>

                </div>

                <div class="title_bar">
                    <h3 class="title">Reports</h3>

                    <div class="filters">
                        <label for="from">From <input v-model="stat_date_from" type="date"></label>
                        <label for="to">To <input v-model="stat_date_to" type="date"></label>
                        <button class="button">Filter</button>
                        <button class="button">
                            <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#2271b1">
                                <path d="M18.5374 19.5674C16.7844 21.0831 14.4993 22 12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12C22 14.1361 21.3302 16.1158 20.1892 17.7406L17 12H20C20 7.58172 16.4183 4 12 4C7.58172 4 4 7.58172 4 12C4 16.4183 7.58172 20 12 20C14.1502 20 16.1022 19.1517 17.5398 17.7716L18.5374 19.5674Z"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="jrca_row">
                    <div class="jrca_col">
                        <div class="jrca_card">
                            <div class="jrca_card_title">Snack Box Analytics</div>
                            <div class="jrca_card_content">
                                <div class="vh-center">
                                    <button @click="download_snack_box_analytics_report" class="button button-primary">Download Report</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="jrca_col">
                        <div class="slot"></div>
                    </div>
                    <div class="jrca_col">
                        <div class="slot"></div>
                    </div>
                    <div class="jrca_col">
                        <div class="slot"></div>
                    </div>
                    <div class="jrca_col">
                        <div class="slot"></div>
                    </div>
                </div>

            </div>
        </template>

    </div>

</div>