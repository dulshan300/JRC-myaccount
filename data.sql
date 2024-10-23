-- for active subscribers section
SELECT
    (
        SELECT
            COUNT(*) as active_subscribers
        FROM
            (
                SELECT
                    customer_id
                FROM
                    wp_wc_orders
                WHERE
                    type = 'shop_subscription'
                    AND status = 'wc-active'
                GROUP BY
                    customer_id
            ) as active_subscribers
    ) as active_subscribers,
    (
        SELECT
            COUNT(*) as new_subscribers
        FROM
            (
                SELECT
                    customer_id
                FROM
                    wp_wc_orders
                WHERE
                    type = 'shop_subscription'
                    AND status = 'wc-active'
                    AND date_created_gmt >= '2024-07-01'
                GROUP BY
                    customer_id
            ) as new_subscribers
    ) as new_subscribers,
    (
        SELECT
            COUNT(*) as prepaid_cancel
        FROM
            (
                SELECT
                    od.id
                FROM
                    wp_wc_orders od
                    LEFT JOIN wp_wc_orders_meta om on om.order_id = od.id
                WHERE
                    od.type = 'shop_subscription'
                    AND od.status = 'wc-active'
                    AND om.meta_key = '_ps_scheduled_to_be_cancelled'
                    AND om.meta_value = 'yes'
            ) as prepaid_cancel
    ) as prepaid_cancel,
    (
        SELECT
            COUNT(*) as cancelled
        FROM
            (
                SELECT
                    customer_id
                FROM
                    wp_wc_orders
                WHERE
                    type = 'shop_subscription'
                    AND status = 'wc-cancelled'
                    AND date_updated_gmt >= '2024-08-01'
                GROUP BY
                    customer_id
            ) as new_subscribers
    ) as cancelled,
    (
        SELECT
            COUNT(*) as on_hold
        FROM
            (
                SELECT
                    id
                FROM
                    wp_wc_orders
                WHERE
                    type = 'shop_subscription'
                    AND status = 'wc-on-hold'
            ) as on_hold
    ) as on_hold,
    (
        SELECT
            COUNT(*) as refund
        FROM
            (
                SELECT
                    id
                FROM
                    wp_wc_orders
                WHERE
                    type = 'shop_order'
                    AND status = 'wc-refunded'
            ) as refund
    ) as refund
    -- for prepaid section
select
    COALESCE(md.meta_value, 1) as plan,
    count(od.id) as count,
    sum(od.total_amount) total,
    od.currency
from
    wp_wc_orders od
    left join wp_wc_orders_meta md on md.order_id = od.id
    AND md.meta_key = '_ps_prepaid_pieces'
where
    od.total_amount > 0
    and od.type = 'shop_order'
GROUP BY
    currency,
    plan
    -- for prepaid section
select
    COALESCE(md.meta_value, 1) as plan,
    count(od.id) as count,
    sum(od.total_amount) as total,
    od.currency
from
    wp_wc_orders od
    left join wp_wc_orders_meta md on md.order_id = od.id
    AND md.meta_key = '_ps_prepaid_pieces'
where
    od.total_amount > 0
    and od.type = 'shop_order'
    and od.date_created_gmt >= '2024-08-01'
GROUP BY
    currency,
    plan
    -- for prepaid section
SELECT
    COALESCE(md.meta_value, 1) AS plan,
    od.id,
    od.total_amount,
    od.currency
FROM
    wp_wc_orders od
    LEFT JOIN wp_wc_orders_meta md ON md.order_id = od.id
    AND md.meta_key = '_ps_prepaid_pieces'
    INNER JOIN wp_woocommerce_order_items oi ON oi.order_id = od.id
    AND oi.order_item_type = 'line_item'
    INNER JOIN wp_woocommerce_order_itemmeta oim ON oim.order_item_id = oi.order_item_id
    AND oim.meta_key = '_product_id'
    AND oim.meta_value = '198'
WHERE
    od.total_amount > 0
    AND od.type = 'shop_order'
    AND od.date_created_gmt >= '2024-08-01'
    AND od.currency = 'SGD'
    -- for active subscribers section
SELECT
    od.id,
    md.meta_value AS plan,
    od.total_amount,
    od.currency
FROM
    wp_wc_orders od
    left JOIN wp_wc_orders_meta md ON md.order_id = od.id
    AND md.meta_key = '_ps_prepaid_pieces'
WHERE
    od.total_amount > 0
    AND od.type = 'shop_subscription'
    AND od.date_created_gmt >= '2024-08-01'
    and od.status in ('wc-active')
    -- for active subscribers section
SELECT
    count(id) as count
from
    wp_wc_orders
where
    type = 'shop_subscription'
    and status = 'wc-active'
    -- for active subscribers section
SELECT
    od.id,
    od.status,
    od.customer_id,
    COALESCE(
        (
            SELECT
                meta_value
            FROM
                wp_wc_orders_meta
            WHERE
                order_id = od.id
                AND meta_key = '_ps_prepaid_pieces'
        ),
        1
    ) as plan,
    COALESCE(
        (
            SELECT
                meta_value
            FROM
                wp_wc_orders_meta
            WHERE
                order_id = od.id
                AND meta_key = '_ps_prepaid_renewals_available'
        ),
        1
    ) as remaining_pieces,
    (
        SELECT
            meta_value
        FROM
            wp_wc_orders_meta
        WHERE
            order_id = od.id
            AND meta_key = '_schedule_next_payment'
    ) as next_renewal_date,
    od.date_updated_gmt as updated_at,
    ad.email as customer_email,
    CONCAT (ad.first_name, ' ', ad.last_name) as customer_name,
    ad.phone
FROM
    wp_wc_orders od
    JOIN wp_wc_order_addresses ad ON od.id = ad.order_id
    and ad.address_type = 'billing'
WHERE
    od.type = 'shop_subscription'
    AND od.status = 'wc-active';

-- for active subscribers section
SELECT
    od.id,
    od.status,
    od.customer_id,
    COALESCE(
        (
            SELECT
                meta_value
            FROM
                wp_wc_orders_meta
            WHERE
                order_id = od.id
                AND meta_key = '_ps_prepaid_pieces'
            LIMIT
                1
        ),
        1
    ) AS plan,
    COALESCE(
        (
            SELECT
                meta_value
            FROM
                wp_wc_orders_meta
            WHERE
                order_id = od.id
                AND meta_key = '_ps_prepaid_renewals_available'
            LIMIT
                1
        ),
        0
    ) AS remaining_pieces,
    (
        SELECT
            meta_value
        FROM
            wp_wc_orders_meta
        WHERE
            order_id = od.id
            AND meta_key = '_schedule_next_payment'
        LIMIT
            1
    ) AS next_shipment_date,
    CASE
        WHEN COALESCE(
            (
                SELECT
                    meta_value
                FROM
                    wp_wc_orders_meta
                WHERE
                    order_id = od.id
                    AND meta_key = '_ps_prepaid_pieces'
                LIMIT
                    1
            ),
            1
        ) > 1
        AND COALESCE(
            (
                SELECT
                    meta_value
                FROM
                    wp_wc_orders_meta
                WHERE
                    order_id = od.id
                    AND meta_key = '_ps_prepaid_renewals_available'
                LIMIT
                    1
            ),
            0
        ) > 0 THEN DATE_ADD (
            (
                SELECT
                    meta_value
                FROM
                    wp_wc_orders_meta
                WHERE
                    order_id = od.id
                    AND meta_key = '_schedule_next_payment'
                LIMIT
                    1
            ),
            INTERVAL COALESCE(
                (
                    SELECT
                        meta_value
                    FROM
                        wp_wc_orders_meta
                    WHERE
                        order_id = od.id
                        AND meta_key = '_ps_prepaid_renewals_available'
                    LIMIT
                        1
                ),
                1
            ) MONTH
        )
        ELSE (
            SELECT
                meta_value
            FROM
                wp_wc_orders_meta
            WHERE
                order_id = od.id
                AND meta_key = '_schedule_next_payment'
            LIMIT
                1
        )
    END AS next_renewal_date,
    od.date_updated_gmt AS updated_at,
    ad.email AS customer_email,
    CONCAT (ad.first_name, ' ', ad.last_name) AS customer_name,
    ad.phone
FROM
    wp_wc_orders od
    JOIN wp_wc_order_addresses ad ON od.id = ad.order_id
    AND ad.address_type = 'billing'
WHERE
    od.type = 'shop_subscription'
    AND od.status = 'wc-active';

-- for static download function
SELECT
    od.id,
    od.status,
    od.customer_id,
    COALESCE(
        (
            SELECT
                meta_value
            FROM
                wp_wc_orders_meta
            WHERE
                order_id = od.id
                AND meta_key = '_ps_prepaid_pieces'
            LIMIT
                1
        ),
        1
    ) AS plan,
    COALESCE(
        (
            SELECT
                meta_value
            FROM
                wp_wc_orders_meta
            WHERE
                order_id = od.id
                AND meta_key = '_ps_prepaid_renewals_available'
            LIMIT
                1
        ),
        0
    ) AS remaining_pieces,
    (
        SELECT
            meta_value
        FROM
            wp_wc_orders_meta
        WHERE
            order_id = od.id
            AND meta_key = '_schedule_next_payment'
        LIMIT
            1
    ) AS next_shipment_date,
    CASE
                    WHEN COALESCE(
                        (
                            SELECT
                                meta_value
                            FROM
                                wp_wc_orders_meta
                            WHERE
                                order_id = od.id
                                AND meta_key = '_ps_prepaid_pieces'
                            LIMIT 1
                        ),
                        1
                    ) > 1
                    AND COALESCE(
                        (
                            SELECT
                                meta_value
                            FROM
                                wp_wc_orders_meta
                            WHERE
                                order_id = od.id
                                AND meta_key = '_ps_prepaid_renewals_available'
                            LIMIT 1
                        ),
                        0
                    ) > 0 THEN DATE_ADD(
                        (
                            SELECT
                                meta_value
                            FROM
                                wp_wc_orders_meta
                            WHERE
                                order_id = od.id
                                AND meta_key = '_schedule_next_payment'
                            LIMIT 1
                        ),
                        INTERVAL COALESCE(
                            (
                                SELECT
                                    meta_value
                                FROM
                                    wp_wc_orders_meta
                                WHERE
                                    order_id = od.id
                                    AND meta_key = '_ps_prepaid_renewals_available'
                                LIMIT 1
                            ),
                            1
                        ) MONTH
                    )
                    ELSE (
                        SELECT
                            meta_value
                        FROM
                            wp_wc_orders_meta
                        WHERE
                            order_id = od.id
                            AND meta_key = '_schedule_next_payment'
                        LIMIT 1
                    )
                END AS next_renewal_date,
    od.date_updated_gmt AS updated_at,
    ad.email AS customer_email,
    CONCAT (ad.first_name, ' ', ad.last_name) AS customer_name,
    COALESCE(
        (
            SELECT
                comment_date_gmt
            FROM
                wp_comments
            WHERE
                comment_post_ID = od.id
                AND (
                    comment_content LIKE '%User cancelled%'
                    OR comment_content LIKE '%Pending Cancellation%'
                    OR comment_content LIKE '%待取消%'
                )
            LIMIT
                1
        ),
        ''
    ) AS 'Cancellation date',
    COALESCE(
        (
            SELECT
                comment_content
            FROM
                wp_comments
            WHERE
                comment_post_ID = od.id
                AND (
                    comment_content LIKE '%User cancelled%'
                    OR comment_content LIKE '%Pending Cancellation%'
                    OR comment_content LIKE '%待取消%'
                )
            LIMIT
                1
        ),
        ''
    ) AS 'Cancellation note',
    REPLACE (
        (
            SELECT
                item.order_item_name
            FROM
                wp_wc_orders orders
                LEFT JOIN wp_woocommerce_order_items item ON item.order_id = orders.id
            WHERE
                item.order_id = od.parent_order_id
                AND item.order_item_type IN ('coupon', 'fee')
            LIMIT
                1
        ),
        'Discount: ',
        ''
    ) AS 'Discount applied',
    ad.phone,
    ad.country,
    od.date_created_gmt,
    od.total_amount,
    od.currency
FROM
    wp_wc_orders od
    JOIN wp_wc_order_addresses ad ON od.id = ad.order_id
    AND ad.address_type = 'billing'
WHERE
    od.type = 'shop_subscription';