<?php

/**
* Paymentwall widget
*
* @package paymentMethod
* @copyright Copyright 2014 Paymentwall Inc.
* @version v1.0.0
*/

require 'includes/application_top.php';

//tep_db_query("delete from" . TABLE_CUSTOMERS_BASKET . " where customers_id = " . );
if($_SESSION['order'] && $_SESSION['insert_id']) {
  require 'paymentwall_api/lib/paymentwall.php';

  Paymentwall_Base::setApiType(Paymentwall_Base::API_GOODS);
  Paymentwall_Base::setAppKey(MODULE_PAYMENT_PAYMENTWALL_APP_KEY);       // available in your Paymentwall merchant area
  Paymentwall_Base::setSecretKey(MODULE_PAYMENT_PAYMENTWALL_SECRET_KEY); // available in your Paymentwall merchant area

  $order = (array)unserialize(base64_decode($_SESSION['order']));

  if(filter_var($order['customer']['email_address'], FILTER_VALIDATE_EMAIL)) {
    $customer_id = tep_db_query("select customers_id from " . TABLE_CUSTOMERS . " where customers_email_address = '" . $order['customer']['email_address'] . "'");
  } else {
    die('email is not valid');
  }
	
	tep_db_query("delete from customers_basket where customers_id = " . mysqli_fetch_all($customer_id)[0][0]);

  $products_names = array();
  foreach ($order['products'] as $key => $value) {
    if(!in_array($value['name'], $products_names))
      array_push($products_names, $value['name']);
  }

  $widget = new Paymentwall_Widget(
    $order['customer']['email_address'],        // id of the end-user who's making the payment
    MODULE_PAYMENT_PAYMENTWALL_WIDGET_CODE,     // widget code, e.g. p1; can be picked inside of your merchant account
    array(                                      // product details for Flexible Widget Call. To let users select the product on Paymentwall's end, leave this array empty
      new Paymentwall_Product(
        (int)$_SESSION['insert_id'],            // id of the product in your system
        $order['info']['total'],                // price
        $order['info']['currency'],             // currency code
        implode(', ', $products_names),         // product name
        Paymentwall_Product::TYPE_FIXED         // this is a time-based product; for one-time products, use Paymentwall_Product::TYPE_FIXED and omit the following 3 array elements
      )
    ),
    array(
      'email' => $order['customer']['email_address'],
      'success_url' => strval(MODULE_PAYMENT_PAYMENTWALL_SUCCESS_URL),
      'test_mode' => ((MODULE_PAYMENT_PAYMENTWALL_STATUS_TEST == 'True') ? 1 : 0)
    )                                           // additional parameters
  );

  echo $widget->getHtmlCode();
} else {
  tep_redirect('/');
}

$_SESSION = array();