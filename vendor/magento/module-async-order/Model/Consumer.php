<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AsyncOrder\Model;

use Magento\AsyncOrder\Api\Data\AsyncOrderMessageInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Notification\NotifierInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Consumer for async order placement.
 */
class Consumer
{
    /**
     * @var NotifierInterface
     */
    private $notifier;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var GuestOrderProcessor
     */
    private $guestOrderProcessor;

    /**
     * @var CustomerOrderProcessor
     */
    private $registeredCustomerOrderProcessor;

    /**
     * @var OrderRejecter
     */
    private $orderRejecter;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     * @param NotifierInterface $notifier
     * @param GuestOrderProcessor $guestOrderProcessor
     * @param CustomerOrderProcessor $registeredCustomerOrderProcessor
     * @param OrderRejecter $orderRejecter
     */
    public function __construct(
        LoggerInterface $logger,
        NotifierInterface $notifier,
        GuestOrderProcessor $guestOrderProcessor,
        CustomerOrderProcessor $registeredCustomerOrderProcessor,
        OrderRejecter $orderRejecter
    ) {
        $this->logger = $logger;
        $this->notifier = $notifier;
        $this->guestOrderProcessor = $guestOrderProcessor;
        $this->registeredCustomerOrderProcessor = $registeredCustomerOrderProcessor;
        $this->orderRejecter = $orderRejecter;
    }

    /**
     * Process Order Message
     *
     * @param AsyncOrderMessageInterface $asyncOrderMessage
     * @throws NoSuchEntityException
     * @return void
     */
    public function process(AsyncOrderMessageInterface $asyncOrderMessage): void
    {
        try {
            if ($asyncOrderMessage->getIsGuest()) {
                $this->guestOrderProcessor->process($asyncOrderMessage);
            } else {
                $this->registeredCustomerOrderProcessor->process($asyncOrderMessage);
            }
        } catch (LocalizedException $exception) {
            $rejectComment = $exception->getMessage() . ' order ID - ' . $asyncOrderMessage->getIncrementId();
            $this->notifier->addCritical(
                __('Error during placing order occurred'),
                __('Error during placing order occurred. ' . $rejectComment)
            );
            $this->logger->critical(
                'Something went wrong while order placing process. ' . $rejectComment
            );
            $this->orderRejecter->reject($asyncOrderMessage, $rejectComment);
        }
    }
}
