<?php
/**
 * Paymentwall pingback
 *
 * @package paymentMethod
 * @copyright Copyright 2015 Paymentwall Inc.
 * @version v1.1.1
 */

require 'includes/application_top.php';
require 'paymentwall-php/lib/paymentwall.php';

define('DEFAULT_PINGBACK_RESPONSE', 'OK');

// Init Paymentwall Configs
Paymentwall_Config::getInstance()->set(array(
    'api_type' => Paymentwall_Config::API_GOODS,
    'public_key' => MODULE_PAYMENT_PAYMENTWALL_APP_KEY,
    'private_key' => MODULE_PAYMENT_PAYMENTWALL_SECRET_KEY
));