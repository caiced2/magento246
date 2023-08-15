<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckout\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Observer to save additional payment info
 */
class SaveAdditionalData extends AbstractDataAssignObserver
{
    /**
     * @var string[]
     */
    private $additionalInformationList = [
        'logged_in_with_bolt',
        'card',
        'is_card_new',
        'register_with_bolt',
        'add_new_card',
        'billing_address_id',
        'add_new_address',
        'shipping_address_id'
    ];

    /**
     * Save additional payment info
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $data = $this->readDataArgument($observer);
        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        $paymentInfo = $this->readPaymentModelArgument($observer);
        if (!is_array($additionalData)) {
            return;
        }
        foreach ($this->additionalInformationList as $additionalInformationKey) {
            if (isset($additionalData[$additionalInformationKey])) {
                $paymentInfo->setAdditionalInformation(
                    $additionalInformationKey,
                    $additionalData[$additionalInformationKey]
                );
            }
        }

        $method = $paymentInfo->getDataByKey('method');
        if ($method === 'quick_checkout') {
            unset($observer->getDataByKey('data')->getDataByKey('additional_data')['card']);
        }
    }
}
