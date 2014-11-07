<?php

/**
* Paymentwall PW Pro
*
* @package paymentMethod
* @copyright Copyright 2014 Paymentwall Inc.
* @version v1.1.0
*/

require 'includes/application_top.php';
require 'paymentwall_api/lib/paymentwall.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

	Paymentwall_Base::setProApiKey(MODULE_PAYMENT_PAYMENTWALL_API_KEY);
	$parameters = $_POST;

	$cardInfo = array(
		'amount'			=> $_SESSION['order_amount'],
		'currency'			=> $_SESSION['order_currency'],
		'email'				=> $parameters['cc-email'],
		'card[number]'		=> $parameters['cc-number'],
		'card[cvv]'			=> $parameters['cc-cvv'],
		'card[exp_month]'	=> $parameters['cc-expmonth'],
		'card[exp_year]'	=> $parameters['cc-expyear'],
		'browser_domain'	=> $parameters['browser_domain']
	);

	$charge = new Paymentwall_Pro_Charge($cardInfo);

	$_SESSION['purchase'] = (!$charge->isCaptured()) ? false : true;

	die(json_encode($charge->getPublicData()));
}