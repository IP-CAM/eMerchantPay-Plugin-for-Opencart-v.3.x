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
 * @author      eMerchantPay
 * @copyright   2016 eMerchantPay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

/**
 * Backend model for the "eMerchantPay Direct" module
 *
 * @package EMerchantPayDirect
 */
class ModelPaymentEmerchantPayDirect extends Model
{
    /**
     * Holds the current module version
     * Will be displayed on Admin Settings Form
     *
     * @var string
     */
    protected $module_version = "1.3.0";

    /**
     * Perform installation logic
     *
     * @return void
     */
    public function install()
    {
        $this->db->query("
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "emerchantpay_direct_transactions` (
			  `unique_id` VARCHAR(255) NOT NULL,
			  `reference_id` VARCHAR(255) NOT NULL,
			  `order_id` INT(11) NOT NULL,
			  `type` CHAR(32) NOT NULL,
			  `mode` CHAR(255) NOT NULL,
			  `timestamp` DATETIME NOT NULL,
			  `status` CHAR(32) NOT NULL,
			  `message` VARCHAR(255) NULL,
			  `technical_message` VARCHAR(255) NULL,
			  `amount` DECIMAL( 10, 2 ) DEFAULT NULL,
			  `currency` CHAR(3) NULL,
			  PRIMARY KEY (`unique_id`)
			) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;
		");
    }

    /**
     * Perform uninstall logic
     *
     * @return void
     */
    public function uninstall()
    {
        // Keep transaction data
        //$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "emerchantpay_direct_transactions`;");

        $this->load->model('setting/setting');

        $this->model_setting_setting->deleteSetting('emerchantpay_direct');
    }

    /**
     * Get saved transaction by id
     *
     * @param string $reference_id UniqueId of the transaction
     *
     * @return mixed bool on fail, row on success
     */
    public function getTransactionById($reference_id)
    {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "emerchantpay_direct_transactions` WHERE `unique_id` = '" . $this->db->escape($reference_id) . "' LIMIT 1");

        if ($query->num_rows) {
            return reset($query->rows);
        }

        return false;
    }

    /**
     * Get the sum of the ammount for a list of transaction types and status
     * @param int $order_id
     * @param string $reference_id
     * @param array $types
     * @param string $status
     * @return decimal
     */
    public function getTransactionsSumAmount($order_id, $reference_id, $types, $status) {
        $transactions = $this->getTransactionsByTypeAndStatus($order_id, $reference_id, $types, $status);
        $totalAmount = 0;

        /** @var $transaction */
        foreach ($transactions as $transaction) {
            $totalAmount +=  $transaction['amount'];
        }

        return $totalAmount;
    }

    /**
     * Get the detailed transactions list of an order for transaction types and status
     * @param int $order_id
     * @param string $reference_id
     * @param array $transaction_types
     * @param string $status
     * @return array
     */

    public function getTransactionsByTypeAndStatus($order_id, $reference_id, $transaction_types, $status) {
        $query = $this->db->query("SELECT
                                      *
                                    FROM `" . DB_PREFIX . "emerchantpay_direct_transactions` as t
                                    WHERE (t.`order_id` = '" . abs(intval($order_id)) . "') and " .
            (!empty($reference_id)	? " (t.`reference_id` = '" . $reference_id . "') and " : "") . "
                                        (t.`type` in ('" . (is_array($transaction_types) ? implode("','", $transaction_types) : $transaction_types) . "')) and
                                        (t.`status` = '" . $status . "')
                                    ");

        if ($query->num_rows) {
            return $query->rows;
        }

        return false;

    }

    /**
     * Get saved transactions by order id
     *
     * @param int $order_id OrderId
     *
     * @return mixed bool on fail, rows on success
     */
    public function getTransactionsByOrder($order_id)
    {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "emerchantpay_direct_transactions` WHERE `order_id` = '" . intval($order_id) . "'");

        if ($query->num_rows) {
            return $query->rows;
        }

        return false;
    }

    /**
     * Add transaction to the database
     *
     * @param $data array
     */
    public function addTransaction($data)
    {
        try {
            $fields = implode(', ', array_map(
                                      function ($v, $k) {
                                          return sprintf('`%s`', $k);
                                      },
                                      $data,
                                      array_keys($data)
                                  )
            );

            $values = implode(', ', array_map(
                                      function ($v) {
                                          return sprintf("'%s'", $v);
                                      },
                                      $data,
                                      array_keys($data)
                                  )
            );

            $this->db->query("
				INSERT INTO
					`" . DB_PREFIX . "emerchantpay_direct_transactions` (" . $fields . ")
				VALUES
					(" . $values . ")
			");
        } catch (Exception $exception) {
            $this->logEx($exception);
        }
    }

    /**
     * Update existing transaction in the database
     *
     * @param $data array
     */
    public function updateTransaction($data)
    {
        try {
            $fields = implode(', ', array_map(
                                      function ($v, $k) {
                                          return sprintf("`%s` = '%s'", $k, $v);
                                      },
                                      $data,
                                      array_keys($data)
                                  )
            );

            $this->db->query("
				UPDATE
					`" . DB_PREFIX . "emerchantpay_direct_transactions`
				SET
					" . $fields . "
				WHERE
				    `unique_id` = '" . $data['unique_id'] . "'
			");
        } catch (Exception $exception) {
            $this->logEx($exception);
        }
    }

    /**
     * Sanitize transaction data and check
     * whether an UPDATE or INSERT is required
     *
     * @param array $data
     */
    public function populateTransaction($data = array())
    {
        try {
            $self = $this;

            // Sanitize the input data
            array_walk($data, function (&$column, &$value) use ($self) {
                $column = $self->db->escape($column);
                $value  = $self->db->escape($value);
            });

            // Check if transaction exists
            $insertQuery = $this->db->query("
                SELECT
                    *
                FROM
                    `" . DB_PREFIX . "emerchantpay_direct_transactions`
                WHERE
                    `unique_id` = '" . $data['unique_id'] . "'
            ");

            if ($insertQuery->rows) {
                $this->updateTransaction($data);
            } else {
                $this->addTransaction($data);
            }
        } catch (Exception $exception) {
            $this->logEx($exception);
        }
    }

    /**
     * Send Capture transaction to the Gateway
     *
     * @param string $reference_id ReferenceId
     * @param string $amount Amount to be refunded
     * @param string $currency Currency for the refunded amount
     * @param string $usage Usage (optional text)
     *
     * @return object
     */
    public function capture($reference_id, $amount, $currency, $usage = '')
    {
        try {
            $this->bootstrap();

            $genesis = new \Genesis\Genesis('Financial\Capture');

            $genesis
                ->request()
                ->setTransactionId(
                    $this->genTransactionId('ocart-')
                )
                ->setRemoteIp(
                    $this->request->server['REMOTE_ADDR']
                )
                ->setUsage($usage)
                ->setReferenceId($reference_id)
                ->setAmount($amount)
                ->setCurrency($currency);

            $genesis->execute();

            return $genesis->response()->getResponseObject();
        } catch (\Exception $exception) {
            $this->logEx($exception);

            return $exception->getMessage();
        }
    }

    /**
     * Send Refund transaction to the Gateway
     *
     * @param string $reference_id ReferenceId
     * @param string $amount Amount to be refunded
     * @param string $currency Currency for the refunded amount
     * @param string $usage Usage (optional text)
     *
     * @return object
     */
    public function refund($reference_id, $amount, $currency, $usage = '')
    {
        try {
            $this->bootstrap();

            $genesis = new \Genesis\Genesis('Financial\Refund');

            $genesis
                ->request()
                ->setTransactionId(
                    $this->genTransactionId('ocart-')
                )
                ->setRemoteIp(
                    $this->request->server['REMOTE_ADDR']
                )
                ->setUsage($usage)
                ->setReferenceId($reference_id)
                ->setAmount($amount)
                ->setCurrency($currency);

            $genesis->execute();

            return $genesis->response()->getResponseObject();
        } catch (\Exception $exception) {
            $this->logEx($exception);

            return $exception->getMessage();
        }
    }

    /**
     * Send Void transaction to the Gateway
     *
     * @param string $reference_id ReferenceId
     * @param string $usage Usage (optional text)
     *
     * @return object
     */
    public function void($reference_id, $usage = '')
    {
        try {
            $this->bootstrap();

            $genesis = new \Genesis\Genesis('Financial\Void');

            $genesis
                ->request()
                ->setTransactionId(
                    $this->genTransactionId('ocart-')
                )
                ->setRemoteIp(
                    $this->request->server['REMOTE_ADDR']
                )
                ->setUsage($usage)
                ->setReferenceId($reference_id);

            $genesis->execute();

            return $genesis->response()->getResponseObject();
        } catch (\Exception $exception) {
            $this->logEx($exception);

            return $exception->getMessage();
        }
    }

    /**
     * Get localized transaction types for Genesis
     *
     * @return array
     */
    public function getTransactionTypes()
    {
        $this->bootstrap();

        $this->load->language('payment/emerchantpay_direct');

        return array(
            \Genesis\API\Constants\Transaction\Types::AUTHORIZE    => array(
                'id'   => \Genesis\API\Constants\Transaction\Types::AUTHORIZE,
                'name' => $this->language->get('text_transaction_authorize')
            ),
            \Genesis\API\Constants\Transaction\Types::AUTHORIZE_3D => array(
                'id'   => \Genesis\API\Constants\Transaction\Types::AUTHORIZE_3D,
                'name' => $this->language->get('text_transaction_authorize_3d')
            ),
            \Genesis\API\Constants\Transaction\Types::SALE         => array(
                'id'   => \Genesis\API\Constants\Transaction\Types::SALE,
                'name' => $this->language->get('text_transaction_sale')
            ),
            \Genesis\API\Constants\Transaction\Types::SALE_3D      => array(
                'id'   => \Genesis\API\Constants\Transaction\Types::SALE_3D,
                'name' => $this->language->get('text_transaction_sale_3d')
            ),
        );
    }

    /**
     * Generate Transaction Id based on the order id
     * and salted to avoid duplication
     *
     * @param string $prefix
     *
     * @return string
     */
    public function genTransactionId($prefix = '')
    {
        $hash = md5(microtime(true) . uniqid() . mt_rand(PHP_INT_SIZE, PHP_INT_MAX));

        return (string)$prefix . substr($hash, -(strlen($hash) - strlen($prefix)));
    }

    /**
     * Bootstrap Genesis Library
     *
     * @return void
     */
    public function bootstrap()
    {
        // Look for, but DO NOT try to load via Auto-loader magic methods
        if (!class_exists('\Genesis\Genesis', false)) {
            include DIR_APPLICATION . '/model/payment/emerchantpay/genesis/vendor/autoload.php';

            \Genesis\Config::setEndpoint(
                \Genesis\API\Constants\Endpoints::EMERCHANTPAY
            );

            \Genesis\Config::setUsername(
                $this->config->get('emerchantpay_direct_username')
            );

            \Genesis\Config::setPassword(
                $this->config->get('emerchantpay_direct_password')
            );

            \Genesis\Config::setToken(
                $this->config->get('emerchantpay_direct_token')
            );

            \Genesis\Config::setEnvironment(
                $this->config->get('emerchantpay_direct_sandbox')
                    ? \Genesis\API\Constants\Environments::STAGING
                    : \Genesis\API\Constants\Environments::PRODUCTION
            );
        }
    }

    /**
     * Log Exception to a log file, if enabled
     *
     * @param $exception
     */
    public function logEx($exception)
    {
        if ($this->config->get('emerchantpay_direct_debug')) {
            $log = new Log('emerchantpay_direct.log');
            $log->write($this->jTraceEx($exception));
        }
    }

    /**
     * jTraceEx() - provide a Java style exception trace
     * @param $e Exception
     * @param $seen - array passed to recursive calls to accumulate trace lines already seen
     *                     leave as NULL when calling this function
     * @return array of strings, one entry per trace line
     */
    private function jTraceEx($e, $seen = null)
    {
        $starter = $seen ? 'Caused by: ' : '';
        $result  = array();

        if (!$seen) $seen = array();

        $trace = $e->getTrace();
        $prev  = $e->getPrevious();

        $result[] = sprintf('%s%s: %s', $starter, get_class($e), $e->getMessage());

        $file = $e->getFile();
        $line = $e->getLine();

        while (true) {
            $current = "$file:$line";
            if (is_array($seen) && in_array($current, $seen)) {
                $result[] = sprintf(' ... %d more', count($trace) + 1);
                break;
            }
            $result[] = sprintf(' at %s%s%s(%s%s%s)',
                                count($trace) && array_key_exists('class', $trace[0]) ? str_replace('\\', '.', $trace[0]['class']) : '',
                                count($trace) && array_key_exists('class', $trace[0]) && array_key_exists('function', $trace[0]) ? '.' : '',
                                count($trace) && array_key_exists('function', $trace[0]) ? str_replace('\\', '.', $trace[0]['function']) : '(main)',
                                $line === null ? $file : basename($file),
                                $line === null ? '' : ':',
                                $line === null ? '' : $line);
            if (is_array($seen))
                $seen[] = "$file:$line";
            if (!count($trace))
                break;
            $file = array_key_exists('file', $trace[0]) ? $trace[0]['file'] : 'Unknown Source';
            $line = array_key_exists('file', $trace[0]) && array_key_exists('line', $trace[0]) && $trace[0]['line'] ? $trace[0]['line'] : null;
            array_shift($trace);
        }

        $result = join("\n", $result);

        if ($prev)
            $result .= "\n" . $this->jTraceEx($prev, $seen);

        return $result;
    }

    /**
     * Retrieves the Module Method Version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->module_version;
    }
}