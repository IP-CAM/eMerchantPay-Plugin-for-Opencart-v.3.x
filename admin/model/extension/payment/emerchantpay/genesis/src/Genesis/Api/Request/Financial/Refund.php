<?php

/**
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @author      emerchantpay
 * @copyright   Copyright (C) 2015-2025 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/MIT The MIT License
 */

namespace Genesis\Api\Request\Financial;

use Genesis\Api\Traits\Request\Financial\BeneficiaryAttributes;
use Genesis\Api\Traits\Request\Financial\Installments\InstallmentAttributes;
use Genesis\Api\Traits\Request\Financial\Refund\BankAttributes;

/**
 * Class Refund
 *
 * Refund Request
 *
 * @package Genesis\Api\Request\Financial
 */
class Refund extends \Genesis\Api\Request\Base\Financial\Reference
{
    use BankAttributes;
    use BeneficiaryAttributes;
    use InstallmentAttributes;

    const CREDIT_REASON_INDICATOR_TRANSPORT_CANCELLATION   = 'A';
    const CREDIT_REASON_INDICATOR_TRAVEL_TRANSPORT         = 'B';
    const CREDIT_REASON_INDICATOR_PARTIAL_REFUND_OR_TICKET = 'P';
    const CREDIT_REASON_INDICATOR_OTHER                    = 'O';
    const TICKET_CHANGE_INDICATOR_TO_EXISTING              = 'C';
    const TICKET_CHANGE_INDICATOR_TO_NEW                   = 'N';

    /**
     * This field indicates the reason for a credit to the cardholder.
     * Allowed values: A, B, P, O
     *
     * @var string
     */
    protected $credit_reason_indicator_1;

    /**
     * This field indicates the reason for a credit to the cardholder.
     * Allowed values: A, B, P, O
     *
     * @var string
     */
    protected $credit_reason_indicator_2;

    /**
     * This field will contain either a space or a code to indicate why a ticket was changed.
     * Allowed values: C, N
     *
     * @var string
     */
    protected $ticket_change_indicator;

    /**
     * @param $value
     *
     * @return Refund
     * @throws \Genesis\Exceptions\InvalidArgument
     */
    public function setCreditReasonIndicator1($value)
    {
        return $this->setCreditReasonIndicator('credit_reason_indicator_1', $value);
    }

    /**
     * @param $value
     *
     * @return Refund
     * @throws \Genesis\Exceptions\InvalidArgument
     */
    public function setCreditReasonIndicator2($value)
    {
        return $this->setCreditReasonIndicator('credit_reason_indicator_2', $value);
    }

    /**
     * @param $value
     *
     * @return Refund
     * @throws \Genesis\Exceptions\InvalidArgument
     */
    public function setTicketChangeIndicator($value)
    {
        return $this->allowedOptionsSetter(
            'ticket_change_indicator',
            [
                self::TICKET_CHANGE_INDICATOR_TO_EXISTING,
                self::TICKET_CHANGE_INDICATOR_TO_NEW
            ],
            $value,
            'Invalid ticket change indicator.'
        );
    }

    /**
     * Returns the Request transaction type
     * @return string
     */
    protected function getTransactionType()
    {
        return \Genesis\Api\Constants\Transaction\Types::REFUND;
    }

    /**
     * @param $field
     * @param $value
     *
     * @return Refund
     * @throws \Genesis\Exceptions\InvalidArgument
     */
    protected function setCreditReasonIndicator($field, $value)
    {
        return $this->allowedOptionsSetter(
            $field,
            [
                self::CREDIT_REASON_INDICATOR_TRANSPORT_CANCELLATION,
                self::CREDIT_REASON_INDICATOR_TRAVEL_TRANSPORT,
                self::CREDIT_REASON_INDICATOR_PARTIAL_REFUND_OR_TICKET,
                self::CREDIT_REASON_INDICATOR_OTHER
            ],
            $value,
            'Invalid credit reason indicator.'
        );
    }

    /**
     * Return the payment transaction structure
     *
     * @return array
     */
    protected function getPaymentTransactionStructure()
    {
        return array_merge(
            parent::getPaymentTransactionStructure(),
            [
                'travel' => [
                    'ticket' => [
                        'credit_reason_indicator_1' => $this->credit_reason_indicator_1,
                        'credit_reason_indicator_2' => $this->credit_reason_indicator_2,
                        'ticket_change_indicator'   => $this->ticket_change_indicator,
                    ]
                ]
            ],
            $this->getBeneficiaryAttributesStructure(),
            $this->getBankAttributesStructure(),
            $this->getInstallmentAttributesStructure()
        );
    }
}
