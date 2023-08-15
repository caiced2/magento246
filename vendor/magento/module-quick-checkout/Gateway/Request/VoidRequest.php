<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Gateway\Request;

use Magento\Framework\App\Request\Http;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Void request payload
 */
class VoidRequest implements BuilderInterface
{

    /**
     * Build request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = SubjectReader::readPayment($buildSubject);

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $paymentDO->getPayment();

        $uri = '/v1/merchant/transactions/void';

        return [
            'uri' => $uri,
            'method' => Http::METHOD_POST,
            'body' => [
                'transaction_id' => $payment->getAdditionalInformation('transaction_id'),
                'transaction_reference' => $payment->getAdditionalInformation('reference'),
                'skip_hook_notification' => true
            ],
            'headers' => [
                'Content-Type' => 'application/json',
            ]
        ];
    }
}
