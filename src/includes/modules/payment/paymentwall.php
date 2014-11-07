<?php

/**
* Paymentwall osCommerce module
*
* @package paymentMethod
* @copyright Copyright 2014 Paymentwall Inc.
* @version v1.1.0
*/

class paymentwall {

	function paymentwall() {
		global $order;

		$this->code				= 'paymentwall';
		$this->title			= MODULE_PAYMENT_PAYMENTWALL_TEXT_TITLE;
		$this->description		= MODULE_PAYMENT_PAYMENTWALL_TEXT_DESCRIPTION;
		$this->sort_order		= defined('MODULE_PAYMENT_PAYMENTWALL_SORT_ORDER') ? MODULE_PAYMENT_PAYMENTWALL_SORT_ORDER : 0;
		$this->enabled			= defined('MODULE_PAYMENT_PAYMENTWALL_STATUS') && (MODULE_PAYMENT_PAYMENTWALL_STATUS == 'True') ? true : false;

		$this->order_status		= defined('MODULE_PAYMENT_PAYMENTWALL_ORDER_STATUS_ID') && ((int)MODULE_PAYMENT_PAYMENTWALL_ORDER_STATUS_ID > 0) ? (int)MODULE_PAYMENT_PAYMENTWALL_ORDER_STATUS_ID : 0;

		if ($this->enabled && isset($order) && is_object($order)) {
			$this->update_status();
		}
	}

	function update_status() {
		global $order;

		if ($this->enabled && ((int)MODULE_PAYMENT_PAYMENTWALL_ZONE > 0)) {
			$check_flag	= false;
			$check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_PAYMENTWALL_ZONE . "' and zone_country_id = '" . $order->delivery['country']['id'] . "' order by zone_id");
			while ($check = tep_db_fetch_array($check_query)) {
				if (($check['zone_id'] < 1) || ($check['zone_id'] == $order->delivery['zone_id'])) {
					$check_flag = true;
					break;
				}
			}

			if (!$check_flag) {
				$this->enabled = false;
			}
		}
	}

	function javascript_validation() {
		return false;
	}

	function selection() {
		return array(
			'id'		=> $this->code,
			'module'	=> $this->title
		);
	}

	function pre_confirmation_check() {
		return false;
	}

	/**
	 * Internal function for getting PW Pro form from file
	 *
	 * @param array $params
	 *
	 * @return string
	 */
	protected function getForm($params) {
		$form = file_get_contents('public/html/pwpro.html');
		$params = array(
			'PW_CONST_PUBLIC_KEY'		=> str_replace(' ', '', MODULE_PAYMENT_PAYMENTWALL_PUBLIC_KEY),
			'PW_CONST_AMOUNT'			=> $params['amount'],
			'PW_CONST_CURRENCY'			=> $params['currency'],
			'PW_CONST_SUCCESS_URL'		=> MODULE_PAYMENT_PAYMENTWALL_SUCCESS_URL
		);

		foreach ($params as $key => $value) {
			$form = str_replace($key, $value, $form);
		}

		return $form;
	}

	function confirmation() {
		if (MODULE_PAYMENT_PAYMENTWALL_MODE != 'PW Local') {
			global $order;

			$order = (array)$order;

			$_SESSION['order_amount'] = $order['info']['total'];
			$_SESSION['order_currency'] = $order['info']['currency'];

			$html = $this->getForm(array(
				'amount' => $order['info']['total'],
				'currency' => $order['info']['currency'],
			));

			return array('title' => $html);
		}
	}

	function process_button() {
		return false;
	}

	function before_process() {
		return false;
	}

	function after_process() {
		global $cart, $insert_id, $order;

		tep_db_query("update " . TABLE_ORDERS . " set orders_status = " . (int)MODULE_PAYMENT_PAYMENTWALL_ORDER_STATUS_ID . " where orders_id = " . intval($insert_id));
		if (MODULE_PAYMENT_PAYMENTWALL_MODE == 'PW Local') {
			$order = (array)$order;
			
			$_SESSION['insert_id'] = $insert_id;

			$order->info['payment_method'] = 'Paymentwall';
			$order->info['payment_module_code'] = 'paymentwall';

			$_SESSION['order'] = base64_encode(serialize($order));

			tep_redirect('paymentwall_widget.php');
		} else {
			//$cart->reset(true);
			if (!$_SESSION['purchase']) { 
				tep_db_query("update " . TABLE_ORDERS . " set orders_status = " . MODULE_PAYMENT_PAYMENTWALL_ORDER_STATUS_ID_FAIL . " where orders_id = " . intval($insert_id));
			}
		}
	}

	function check() {
		if (!isset($this->_check)) {
			$check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_PAYMENTWALL_STATUS'");
			$this->_check = tep_db_num_rows($check_query);
		}

		return $this->_check;
	}

	function install() {
		tep_db_query("insert into " . TABLE_CONFIGURATION . 
		" (configuration_title, configuration_key, configuration_value, configuration_description, 
			configuration_group_id, sort_order, set_function, date_added) 
			values ('Enable Paymentwall Module', 'MODULE_PAYMENT_PAYMENTWALL_STATUS', 'True', 
				'Do you want to accept payments via Paymentwall?', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");

		tep_db_query("insert into " . TABLE_CONFIGURATION . 
		" (configuration_title, configuration_key, configuration_value, configuration_description, 
			configuration_group_id, sort_order, set_function, date_added) 
			values ('Enable PW Local', 'MODULE_PAYMENT_PAYMENTWALL_MODE', 'PW Local', 
				'Do you want enable PW Local?', '6', '2', 'tep_cfg_select_option(array(\'PW Local\', \'PW Pro\'), ', now())");

		tep_db_query("insert into " . TABLE_CONFIGURATION . 
		" (configuration_title, configuration_key, configuration_value, configuration_description, 
			configuration_group_id, sort_order, use_function, set_function, date_added) 
			values ('Payment Zone', 'MODULE_PAYMENT_PAYMENTWALL_ZONE', '0', 
				'If a zone is selected, only enable this payment method for that zone.', '6', '2', 'tep_get_zone_class_title', 'tep_cfg_pull_down_zone_classes(', now())");

		tep_db_query("insert into " . TABLE_CONFIGURATION . 
		" (configuration_title, configuration_key, configuration_value, configuration_description, 
			configuration_group_id, sort_order, date_added) 
			values ('Application key. (Only for PW Local)', 'MODULE_PAYMENT_PAYMENTWALL_APP_KEY', '', 
				'Set your application key.', '6', '4', now())");

		tep_db_query("insert into " . TABLE_CONFIGURATION . 
		" (configuration_title, configuration_key, configuration_value, configuration_description, 
			configuration_group_id, sort_order, date_added) 
			values ('Secret key. (Only for PW Local)', 'MODULE_PAYMENT_PAYMENTWALL_SECRET_KEY', '', 
				'Set your secret key.', '6', '5', now())");

		tep_db_query("insert into " . TABLE_CONFIGURATION . 
		" (configuration_title, configuration_key, configuration_value, configuration_description, 
			configuration_group_id, sort_order, date_added) 
			values ('Public key. (Only for PW Pro)', 'MODULE_PAYMENT_PAYMENTWALL_PUBLIC_KEY', '', 
				'Set your secret key.', '6', '5', now())");

		tep_db_query("insert into " . TABLE_CONFIGURATION . 
		" (configuration_title, configuration_key, configuration_value, configuration_description, 
			configuration_group_id, sort_order, date_added) 
			values ('API key. (Only for PW Pro)', 'MODULE_PAYMENT_PAYMENTWALL_API_KEY', '', 
				'Set your secret key.', '6', '5', now())");

		tep_db_query("insert into " . TABLE_CONFIGURATION . 
		" (configuration_title, configuration_key, configuration_value, configuration_description, 
			configuration_group_id, sort_order, date_added) 
			values ('Widget code.', 'MODULE_PAYMENT_PAYMENTWALL_WIDGET_CODE', '', 
				'Set your widget code.', '6', '6', now())");

		tep_db_query("insert into " . TABLE_CONFIGURATION . 
		" (configuration_title, configuration_key, configuration_value, configuration_description, 
			configuration_group_id, sort_order, date_added) 
			values ('Success url.', 'MODULE_PAYMENT_PAYMENTWALL_SUCCESS_URL', '', 
				'URL, when user complate purshare.', '6', '7', now())");

		tep_db_query("insert into " . TABLE_CONFIGURATION . 
		" (configuration_title, configuration_key, configuration_value, configuration_description, 
			configuration_group_id, sort_order, set_function, use_function, date_added) 
			values ('Set Order Status before pingback', 'MODULE_PAYMENT_PAYMENTWALL_ORDER_STATUS_ID', '0', 
				'Set the status of orders before pingback', '6', '8', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");

		tep_db_query("insert into " . TABLE_CONFIGURATION . 
		" (configuration_title, configuration_key, configuration_value, configuration_description, 
			configuration_group_id, sort_order, set_function, use_function, date_added) 
			values ('Set Order Status after success pingback', 'MODULE_PAYMENT_PAYMENTWALL_ORDER_STATUS_ID_SUCCESS', '0', 
				'Set the status of orders after success pingback', '6', '9', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");

		tep_db_query("insert into " . TABLE_CONFIGURATION . 
		" (configuration_title, configuration_key, configuration_value, configuration_description, 
			configuration_group_id, sort_order, set_function, use_function, date_added) 
			values ('Set Order Status after cancel pingback', 'MODULE_PAYMENT_PAYMENTWALL_ORDER_STATUS_ID_FAIL', '0', 
				'Set the status of orders after cancel pingback', '6', '10', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");

		tep_db_query("insert into " . TABLE_CONFIGURATION . 
		" (configuration_title, configuration_key, configuration_value, configuration_description, 
			configuration_group_id, sort_order, date_added) 
			values ('Sort order of display.', 'MODULE_PAYMENT_PAYMENTWALL_SORT_ORDER', '0', 
				'Sort order of display. Lowest is displayed first.', '6', '3', now())");

		tep_db_query("insert into " . TABLE_CONFIGURATION . 
		" (configuration_title, configuration_key, configuration_value, configuration_description, 
			configuration_group_id, sort_order, set_function, date_added) 
			values ('Do you want enable test_mode', 'MODULE_PAYMENT_PAYMENTWALL_STATUS_TEST', 'False', 
				'Do you want enable test_mode?', '6', '11', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
	}

	function remove() {
		tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
	}

	function keys() {
		return array(
			'MODULE_PAYMENT_PAYMENTWALL_STATUS',
			'MODULE_PAYMENT_PAYMENTWALL_MODE',
			'MODULE_PAYMENT_PAYMENTWALL_APP_KEY',
			'MODULE_PAYMENT_PAYMENTWALL_SECRET_KEY',
			'MODULE_PAYMENT_PAYMENTWALL_PUBLIC_KEY',
			'MODULE_PAYMENT_PAYMENTWALL_API_KEY',
			'MODULE_PAYMENT_PAYMENTWALL_WIDGET_CODE',
			'MODULE_PAYMENT_PAYMENTWALL_SUCCESS_URL',
			'MODULE_PAYMENT_PAYMENTWALL_ZONE',
			'MODULE_PAYMENT_PAYMENTWALL_SORT_ORDER',
			'MODULE_PAYMENT_PAYMENTWALL_ORDER_STATUS_ID',
			'MODULE_PAYMENT_PAYMENTWALL_ORDER_STATUS_ID_SUCCESS',
			'MODULE_PAYMENT_PAYMENTWALL_ORDER_STATUS_ID_FAIL',
			'MODULE_PAYMENT_PAYMENTWALL_STATUS_TEST'
		);
	}
}
