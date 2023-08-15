<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\AsyncOrder\Model;

use Magento\AsyncOrder\Api\Data\AsyncOrderMessageInterface;
use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Process registered customer order async messages.
 */
class CustomerOrderProcessor
{
    /**
     * @var PaymentInformationManagementInterface
     */
    private $customerPaymentInformationManagement;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @param PaymentInformationManagementInterface $customerPaymentInformationManagement
     * @param Json $serializer
     */
    public function __construct(
        PaymentInformationManagementInterface $customerPaymentInformationManagement,
        Json $serializer
    ) {
        $this->customerPaymentInformationManagement = $customerPaymentInformationManagement;
        $this->serializer = $serializer;
    }

    /**
     * Process async order placement message.
     *
     * @param AsyncOrderMessageInterface $asyncOrderMessage
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function process(AsyncOrderMessageInterface $asyncOrderMessage): void
    {
        $data = $this->serializer->unserialize($asyncOrderMessage->getAdditionalData());
        $asyncOrderMessage->getPaymentMethod()->setAdditionalData($data);

        $this->customerPaymentInformationManagement->savePaymentInformationAndPlaceOrder(
            $asyncOrderMessage->getCartId(),
            $asyncOrderMessage->getPaymentMethod(),
            $asyncOrderMessage->getAddress()
        );
    }
}
