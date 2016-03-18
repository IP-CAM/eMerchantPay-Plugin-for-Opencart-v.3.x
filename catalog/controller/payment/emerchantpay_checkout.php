<?php
/*
 * Copyright (C) 2015 eMerchantPay Ltd.
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
 * @author      eMerchantPay
 * @copyright   2015 eMerchantPay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

/**
 * Front-end controller for the "eMerchantPay Checkout" module
 *
 * @package EMerchantPayCheckout
 */
class ControllerPaymentEmerchantPayCheckout extends Controller
{
    /**
     * Init
     *
     * @param $registry
     */
    public function __construct($registry)
    {
        parent::__construct($registry);

        $this->isUserLoggedIn();
    }

    /**
     * Entry-point
     *
     * @return mixed
     */
    public function index()
    {
        $this->load->language('payment/emerchantpay_checkout');

        $data = array(
            'text_title'     => $this->language->get('text_title'),
            'text_loading'   => $this->language->get('text_loading'),

            'button_confirm' => $this->language->get('button_confirm'),
            'button_target'  => $this->url->link('payment/emerchantpay_checkout/send', '', 'SSL'),

            'scripts'        => $this->document->getScripts()
        );

        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/emerchantpay_checkout.tpl')) {
            return $this->load->view($this->config->get('config_template') . '/template/payment/emerchantpay_checkout.tpl', $data);
        } else {
            return $this->load->view('payment/emerchantpay_checkout.tpl', $data);
        }
    }

    /**
     * Process order confirmation
     *
     * @return void
     */
    public function send()
    {
        $this->load->model('checkout/order');
        $this->load->model('payment/emerchantpay_checkout');

        $this->load->language('payment/emerchantpay_checkout');

        try {
            $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

            $data = array(
                'transaction_id'     => $this->model_payment_emerchantpay_checkout->genTransactionId(),

                'remote_address'     => $this->request->server['REMOTE_ADDR'],

                'usage'              => $this->model_payment_emerchantpay_checkout->getUsage(),
                'description'        => $this->model_payment_emerchantpay_checkout->getOrderProducts(
                    $this->session->data['order_id']
                ),

                'language'           => $this->model_payment_emerchantpay_checkout->getLanguage(),

                'currency'           => $this->getCurrencyCode(),
                'amount'             => $order_info['total'],

                'customer_email'     => $order_info['email'],
                'customer_phone'     => $order_info['telephone'],

                'notification_url'   => $this->url->link('payment/emerchantpay_checkout/callback', '', 'SSL'),
                'return_success_url' => $this->url->link('payment/emerchantpay_checkout/success', '', 'SSL'),
                'return_failure_url' => $this->url->link('payment/emerchantpay_checkout/failure', '', 'SSL'),
                'return_cancel_url'  => $this->url->link('payment/emerchantpay_checkout/cancel', '', 'SSL'),

                'billing'            => array(
                    'first_name' => $order_info['payment_firstname'],
                    'last_name'  => $order_info['payment_lastname'],
                    'address1'   => $order_info['payment_address_1'],
                    'address2'   => $order_info['payment_address_2'],
                    'zip'        => $order_info['payment_postcode'],
                    'city'       => $order_info['payment_city'],
                    'state'      => $order_info['payment_zone_code'],
                    'country'    => $order_info['payment_iso_code_2'],
                ),

                'shipping'           => array(
                    'first_name' => $order_info['shipping_firstname'],
                    'last_name'  => $order_info['shipping_lastname'],
                    'address1'   => $order_info['shipping_address_1'],
                    'address2'   => $order_info['shipping_address_2'],
                    'zip'        => $order_info['shipping_postcode'],
                    'city'       => $order_info['shipping_city'],
                    'state'      => $order_info['shipping_zone_code'],
                    'country'    => $order_info['shipping_iso_code_2'],
                )
            );

            $transaction = $this->model_payment_emerchantpay_checkout->create($data);

            if (isset($transaction->unique_id)) {
                $timestamp = ($transaction->timestamp instanceof \DateTime)
                    ? $transaction->timestamp->format('c')
                    : $transaction->timestamp;

                $data = array(
                    'type'              => 'checkout',
                    'reference_id'      => '0',
                    'order_id'          => $order_info['order_id'],
                    'unique_id'         => $transaction->unique_id,
                    'mode'              => $transaction->mode,
                    'status'            => $transaction->status,
                    'amount'            => $transaction->amount,
                    'currency'          => $transaction->currency,
                    'message'           => isset($transaction->message) ? $transaction->message : '',
                    'technical_message' => isset($transaction->technical_message) ? $transaction->technical_message : '',
                    'timestamp'         => $timestamp,
                );

                $this->model_payment_emerchantpay_checkout->populateTransaction($data);

                $this->model_checkout_order->addOrderHistory(
                    $this->session->data['order_id'],
                    $this->config->get('emerchantpay_checkout_order_status_id'),
                    $this->language->get('text_payment_status_initiated'),
                    true
                );

                $json = array(
                    'redirect' => $transaction->redirect_url
                );
            } else {
                $json = array(
                    'error' => $this->language->get('text_payment_system_error')
                );
            }
        } catch (\Exception $exception) {
            $json = array(
                'error' => ($exception->getMessage())
                    ? $exception->getMessage()
                    : $this->language->get('text_payment_system_error')
            );

            $this->model_payment_emerchantpay_checkout->logEx($exception);
        }

        $this->response->addHeader('Content-Type: application/json');

        $this->response->setOutput(
            json_encode($json)
        );
    }

    /**
     * Process Gateway Notification
     *
     * @return void
     */
    public function callback()
    {
        $this->load->model('checkout/order');
        $this->load->model('payment/emerchantpay_checkout');

        $this->load->language('payment/emerchantpay_checkout');

        try {
            $this->model_payment_emerchantpay_checkout->bootstrap();

            $notification = new \Genesis\API\Notification(
                $this->request->post
            );

            if ($notification->isAuthentic()) {
                $notification->initReconciliation();

                $wpf_reconcile = $notification->getReconciliationObject();

                $timestamp = ($wpf_reconcile->timestamp instanceof \DateTime)
                    ? $wpf_reconcile->timestamp->format('c')
                    : $wpf_reconcile->timestamp;

                $data = array(
                    'unique_id' => $wpf_reconcile->unique_id,
                    'status'    => $wpf_reconcile->status,
                    'currency'  => $wpf_reconcile->currency,
                    'amount'    => $wpf_reconcile->amount,
                    'timestamp' => $timestamp,
                );

                $this->model_payment_emerchantpay_checkout->populateTransaction($data);

                $transaction = $this->model_payment_emerchantpay_checkout->getTransactionById(
                    $wpf_reconcile->unique_id
                );

                if (isset($transaction['order_id']) && abs((int)$transaction['order_id']) > 0) {
                    if (isset($wpf_reconcile->payment_transaction)) {

                        $payment_transaction = $wpf_reconcile->payment_transaction;

                        $timestamp = ($payment_transaction->timestamp instanceof \DateTime)
                            ? $payment_transaction->timestamp->format('c')
                            : $payment_transaction->timestamp;

                        $data = array(
                            'order_id'          => $transaction['order_id'],
                            'reference_id'      => $wpf_reconcile->unique_id,
                            'unique_id'         => $payment_transaction->unique_id,
                            'type'              => $payment_transaction->transaction_type,
                            'mode'              => $payment_transaction->mode,
                            'status'            => $payment_transaction->status,
                            'currency'          => $payment_transaction->currency,
                            'amount'            => $payment_transaction->amount,
                            'timestamp'         => $timestamp,
                            'terminal_token'    => isset($payment_transaction->terminal_token) ? $payment_transaction->terminal_token : '',
                            'message'           => isset($payment_transaction->message) ? $payment_transaction->message : '',
                            'technical_message' => isset($payment_transaction->technical_message) ? $payment_transaction->technical_message : '',
                        );

                        $this->model_payment_emerchantpay_checkout->populateTransaction($data);
                    }

                    switch ($wpf_reconcile->status) {
                        case \Genesis\API\Constants\Transaction\States::APPROVED:
                            $this->model_checkout_order->addOrderHistory(
                                $transaction['order_id'],
                                $this->config->get('emerchantpay_checkout_order_status_id'),
                                $this->language->get('text_payment_status_successful'),
                                true
                            );
                            break;
                        case \Genesis\API\Constants\Transaction\States::DECLINED:
                        case \Genesis\API\Constants\Transaction\States::ERROR:
                            $this->model_checkout_order->addOrderHistory(
                                $transaction['order_id'],
                                $this->config->get('emerchantpay_checkout_order_failure_status_id'),
                                $this->language->get('text_payment_status_unsuccessful'),
                                true
                            );
                            break;
                    }
                }

                $this->response->addHeader('Content-Type: text/xml');

                $this->response->setOutput(
                    $notification->generateResponse()
                );
            }
        } catch (\Exception $exception) {
            $this->model_payment_emerchantpay_checkout->logEx($exception);
        }
    }

    /**
     * Handle client redirection for successful status
     *
     * @return void
     */
    public function success()
    {
        $this->response->redirect($this->url->link('checkout/success', '', 'SSL'));
    }

    /**
     * Handle client redirection for failure status
     *
     * @return void
     */
    public function failure()
    {
        $this->load->language('payment/emerchantpay_checkout');

        $this->session->data['error'] = $this->language->get('text_payment_failure');

        $this->response->redirect($this->url->link('checkout/checkout', '', 'SSL'));
    }

    /**
     * Handle client redirection for cancelled status
     *
     * @return void
     */
    public function cancel()
    {
        $this->load->language('payment/emerchantpay_checkout');

        $this->session->data['error'] = $this->language->get('text_payment_cancelled');

        $this->response->redirect($this->url->link('checkout/checkout', '', 'SSL'));
    }

    /**
     * Redirect the user (to the login page), if they are not logged-in
     */
    protected function isUserLoggedIn()
    {
        $isCallback = strpos((string)$this->request->get['route'], 'callback') !== false;

        if (!$this->customer->isLogged() && !$isCallback) {
            $this->response->redirect($this->url->link('account/login', '', 'SSL'));
        }
    }

    /**
     * Get current Currency Code
     * @return string
     */
    protected function getCurrencyCode() {
        return $this->session->data['currency'];
    }
}