<?php
/**
 * Paymentwall
 *
 * @package paymentMethod
 * @copyright Copyright 2015 Paymentwall Inc.
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

/**
 * Retrieve user data for User Profile API
 * @param $order
 * @return array
 */
function getUserProfileData($order){
    $customer = $order['customer'];
    return array(
        'customer[city]' => $customer['city'],
        'customer[state]' => $customer['state'],
        'customer[address]' => $customer['street_address'],
        'customer[country]' => $customer['country']['title'],
        'customer[zip]' => $customer['postcode'],
        'customer[username]' => $customer['email_address'],
        'customer[firstname]' => $customer['firstname'],
        'customer[lastname]' => $customer['lastname'],
    );
}

function getDeliveryConfirmationData($order, $ref)
{
    $delivery = $order->delivery;
    return array(
        'payment_id' => $ref,
        'type' => 'digital',
        'status' => 'delivered',
        'estimated_delivery_datetime' => date('Y/m/d H:i:s'),
        'estimated_update_datetime' => date('Y/m/d H:i:s'),
        'refundable' => 'yes',
        'details' => 'Item will be delivered via email by ' . date('Y/m/d H:i:s'),
        'shipping_address[email]' => $order->customer['email_address'],
        'shipping_address[firstname]' => $delivery['firstname'],
        'shipping_address[lastname]' => $delivery['lastname'],
        'shipping_address[country]' => $delivery['country']['title'],
        'shipping_address[street]' => $delivery['street_address'],
        'shipping_address[state]' => $delivery['state'],
        'shipping_address[zip]' => $delivery['postcode'],
        'shipping_address[city]' => $delivery['city'],
        'reason' => 'none',
        'is_test' => ((MODULE_PAYMENT_PAYMENTWALL_STATUS_TEST == 'True') ? 1 : 0),
    );
}