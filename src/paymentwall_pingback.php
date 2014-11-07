<?php

/**
* Paymentwall pingback
*
* @package paymentMethod
* @copyright Copyright 2014 Paymentwall Inc.
* @version v1.1.0
*/

require 'paymentwall_api/lib/paymentwall.php';
require 'includes/application_top.php';

Paymentwall_Base::setApiType(Paymentwall_Base::API_GOODS);
Paymentwall_Base::setAppKey(MODULE_PAYMENT_PAYMENTWALL_APP_KEY); // available in your Paymentwall merchant area
Paymentwall_Base::setSecretKey(MODULE_PAYMENT_PAYMENTWALL_SECRET_KEY); // available in your Paymentwall merchant area

$pingback = new Paymentwall_Pingback($_GET, $_SERVER['REMOTE_ADDR']);
if ($pingback->validate()) {
	$productId = $pingback->getProduct()->getId();
	$id = tep_db_query("select orders_id from " . TABLE_ORDERS . " where orders_id = '" . (int)$productId . "'");

	if(mysqli_fetch_all($id)[0][0]) {
		if ($pingback->isDeliverable()) {
			$status = MODULE_PAYMENT_PAYMENTWALL_ORDER_STATUS_ID_SUCCESS;
		} else if ($pingback->isCancelable()) {
			$status = MODULE_PAYMENT_PAYMENTWALL_ORDER_STATUS_ID_FAIL;
		}

	tep_db_query("update " . TABLE_ORDERS . " set orders_status = " . $status . " where orders_id = " . intval($productId));
		echo 'OK'; // Paymentwall expects response to be OK, otherwise the pingback will be resent
	} else {
		echo 'Error, order not found';
	}
} else {
	echo $pingback->getErrorSummary();
}