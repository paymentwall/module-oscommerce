<?php

/**
* Paymentwall widget
*
* @package paymentMethod
* @copyright Copyright 2014 Paymentwall Inc.
* @version v1.1.0
*/

require 'includes/application_top.php';

if(isset($_SESSION['order']) && isset($_SESSION['insert_id'])) {
	require 'paymentwall_api/lib/paymentwall.php';

	$order = (array)unserialize(base64_decode($_SESSION['order']));
	Paymentwall_Base::setApiType(Paymentwall_Base::API_GOODS);
	Paymentwall_Base::setAppKey(MODULE_PAYMENT_PAYMENTWALL_APP_KEY);       // available in your Paymentwall merchant area
	Paymentwall_Base::setSecretKey(MODULE_PAYMENT_PAYMENTWALL_SECRET_KEY); // available in your Paymentwall merchant area

	if(filter_var($order['customer']['email_address'], FILTER_VALIDATE_EMAIL)) {
		$customer_id = tep_db_query("select customers_id from " . TABLE_CUSTOMERS . " where customers_email_address = '" . $order['customer']['email_address'] . "'");
	} else {
		die('email is not valid');
	}
	
	$currency = mysqli_fetch_assoc(tep_db_query("select currency from " . TABLE_ORDERS . " where orders_id = '" . (int)$_SESSION['insert_id'] . "'"))['currency'];
	$amount = mysqli_fetch_assoc(tep_db_query("select value from " . TABLE_ORDERS_TOTAL . " where orders_id = '" . (int)$_SESSION['insert_id'] . "' order by orders_total_id desc"))['value'];

	$products_names = array();
	foreach ($order['products'] as $key => $value) {
		if(!in_array($value['name'], $products_names))
			array_push($products_names, $value['name']);
	}

	$widget = new Paymentwall_Widget(
		$_SERVER['REMOTE_ADDR'],					// id of the end-user who's making the payment
		MODULE_PAYMENT_PAYMENTWALL_WIDGET_CODE,		// widget code, e.g. p1; can be picked inside of your merchant account
		array(										// product details for Flexible Widget Call. To let users select the product on Paymentwall's end, leave this array empty
			new Paymentwall_Product(
				(int)$_SESSION['insert_id'],			// id of the product in your system
				$amount,								// price
				$currency,								// currency code
				implode(', ', $products_names),			// product name
				Paymentwall_Product::TYPE_FIXED			// this is a time-based product; for one-time products, use Paymentwall_Product::TYPE_FIXED and omit the following 3 array elements
			)
		),
		array(
			'email'			=> $order['customer']['email_address'],
			'success_url'	=> strval(MODULE_PAYMENT_PAYMENTWALL_SUCCESS_URL),
			'test_mode'		=> ((MODULE_PAYMENT_PAYMENTWALL_STATUS_TEST == 'True') ? 1 : 0)
		)                                           // additional parameters
	);
	unset($_SESSION['order']);
	unset($_SESSION['insert_id']);

	echo $widget->getHtmlCode();
} else {
	tep_redirect('/');
}

$cart = new shoppingCart;
$cart->reset(true);
