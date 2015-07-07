<?php

require 'paymentwall_init.php';

$pingback = new Paymentwall_Pingback($_GET, $_SERVER['REMOTE_ADDR']);
if ($pingback->validate()) {
    $productId = $pingback->getProduct()->getId();
    $id = tep_db_query("select orders_id from " . TABLE_ORDERS . " where orders_id = '" . (int)$productId . "'");

    if (mysqli_fetch_all($id)[0][0]) {
        if ($pingback->isDeliverable()) {
            $status = MODULE_PAYMENT_PAYMENTWALL_ORDER_STATUS_ID_SUCCESS;
        } else if ($pingback->isCancelable()) {
            $status = MODULE_PAYMENT_PAYMENTWALL_ORDER_STATUS_ID_FAIL;
        }

        tep_db_query("update " . TABLE_ORDERS . " set orders_status = " . $status . " where orders_id = " . intval($productId));
        echo DEFAULT_PINGBACK_RESPONSE; // Paymentwall expects response to be OK, otherwise the pingback will be resent
    } else {
        echo 'Error, order not found';
    }
} else {
    echo $pingback->getErrorSummary();
}