<?php
/*
 * Copyright (C) 2016 eMerchantPay Ltd.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @author	  eMerchantPay
 * @copyright   2016 eMerchantPay Ltd.
 * @license	 http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

if (!class_exists('ControllerPaymentEmerchantPayBase')) {
	require_once DIR_APPLICATION . "controller/payment/emerchantpay/base_controller.php";
}

/**
 * Backend controller for the "eMerchantPay Checkout" module
 *
 * @package EMerchantPayCheckout
 */
class ControllerPaymentEmerchantPayCheckout extends ControllerPaymentEmerchantPayBase
{
	/**
	 * Module Name (Used in View - Templates)
	 *
	 * @var string
	 */
	protected $module_name = 'emerchantpay_checkout';

	/**
	 * Used to find out if the payment method requires SSL
	 *
	 * @return bool
	 */
	protected function isModuleRequiresSsl()
	{
		return false;
	}
}