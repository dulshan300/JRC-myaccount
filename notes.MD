### active_subscribers
```
SELECT COUNT(*) as active_subscribers 
FROM (SELECT customer_id  FROM wp_wc_orders WHERE type = 'shop_subscription' AND status = 'wc-active' GROUP BY customer_id) as active_subscribers ;
```

### new_subscribers
```
SELECT COUNT(*) as new_subscribers
FROM (SELECT customer_id  FROM wp_wc_orders WHERE type = 'shop_subscription' AND status = 'wc-active' AND date_created_gmt>='2024-07-01' GROUP BY customer_id) as new_subscribers;
```


### prepaid_cancel
```
SELECT COUNT(*) as prepaid_cancel
FROM (SELECT od.id FROM wp_wc_orders od LEFT JOIN wp_wc_orders_meta om on om.order_id=od.id WHERE od.type = 'shop_subscription' AND od.status = 'wc-active' AND om.meta_key = '_ps_scheduled_to_be_cancelled'
AND om.meta_value = 'yes' ) as prepaid_cancel;
```


### cancelled
```
SELECT COUNT(*) as cancelled
FROM (SELECT customer_id  FROM wp_wc_orders WHERE type = 'shop_subscription' AND status = 'wc-cancelled' AND date_updated_gmt >='2024-08-01' GROUP BY customer_id) as new_subscribers;
```


# Options Keys

### webp_subscription_update_request
This key will hold an array of subscription upgrade or downgrade data. Each entry in the array will represent a specific update request, including details such as the subscription ID, new plan, the date of the request, and any other relevant information related to the subscription change process.

```
$values[$sub_id] = [
    'sub_id' => $sub_id,
    'current_plan' => $current_plan,
    'new_plan' => $new_plan,
];
```
