<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\AsyncOrder\Model;

use Magento\AsyncOrder\Api\Data\AsyncOrderMessageInterface;
use Magento\Checkout\Api\GuestPaymentInformationManagementInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Processor for a guest order async message.
 */
class GuestOrderProcessor
{
    /**
     * @var GuestPaymentInformationManagementInterface
     */
    private $guestPaymentInformationManagement;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @param GuestPaymentInformationManagementInterface $guestPaymentInformationManagement
     * @param Json $serializer
     */
    public function __construct(
        GuestPaymentInformationManagementInterface $guestPaymentInformationManagement,
        Json $serializer
    ) {
        $this->guestPaymentInformationManagement = $guestPaymentInformationManagement;
        $this->serializer = $serializer;
    }

    /**
     * Process Order Message
     *
     * @param AsyncOrderMessageInterface $asyncOrderMessage
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function process(AsyncOrderMessageInterface $asyncOrderMessage)
    {
        $data = $this->serializer->unserialize($asyncOrderMessage->getAdditionalData());
        $asyncOrderMessage->getPaymentMethod()->setAdditionalData($data);

        $this->guestPaymentInformationManagement->savePaymentInformationAndPlaceOrder(
            $asyncOrderMessage->getCartId(),
            $asyncOrderMessage->getEmail(),
            $asyncOrderMessage->getPaymentMethod(),
            $asyncOrderMessage->getAddress()
        );
    }
}
