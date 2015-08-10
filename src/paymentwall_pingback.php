<?php

require 'paymentwall_init.php';

include(DIR_WS_CLASSES . 'order.php');

$pingback = new Paymentwall_Pingback($_GET, $_SERVER['REMOTE_ADDR']);

if ($pingback->validate()) {

    $productId = $pingback->getProduct()->getId();
    $order = new order($productId);
    $_info = array_filter($order->info);

    if (!empty($_info)) {
        if ($pingback->isDeliverable()) {

            if (MODULE_PAYMENT_PAYMENTWALL_DELIVERY == 'True') {
                $delivery = new Paymentwall_GenerericApiObject('delivery');
                $response = $delivery->post(getDeliveryConfirmationData($order, $pingback->getReferenceId()));
            }

            $status = MODULE_PAYMENT_PAYMENTWALL_ORDER_STATUS_ID_SUCCESS;
        } else if ($pingback->isCancelable()) {
            $status = MODULE_PAYMENT_PAYMENTWALL_ORDER_STATUS_ID_FAIL;
        }

        // Add order status history
        $sql_data_array = array(
            'orders_id' => intval($productId),
            'orders_status_id' => $status,
            'date_added' => 'now()',
            'customer_notified' => '0',
            'comments' => 'Processed by Paymentwall'
        );
        tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

        // Update order status
        tep_db_query("update " . TABLE_ORDERS . " set orders_status = " . $status . " where orders_id = " . intval($productId));

        echo DEFAULT_PINGBACK_RESPONSE; // Paymentwall expects response to be OK, otherwise the pingback will be resent
    } else {
        echo 'Error, order not found';
    }
} else {
    echo $pingback->getErrorSummary();
}