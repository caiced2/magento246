<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\LoginAsCustomerLogging\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Logging\Model\ResourceModel\Event;
use Magento\LoginAsCustomerApi\Api\GetLoggedAsCustomerAdminIdInterface;
use Magento\LoginAsCustomerLogging\Model\GetEventForLogging;
use Magento\LoginAsCustomerLogging\Model\LogValidation;

/**
 * Login as customer log add to cart observer.
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class LogApplyCoupon implements ObserverInterface
{
    private const ACTION = 'apply_coupon_code';

    /**
     * @var GetEventForLogging
     */
    private $getEventForLogging;

    /**
     * @var Event
     */
    private $eventResource;

    /**
     * @var LogValidation
     */
    private $logValidation;

    /**
     * @var GetLoggedAsCustomerAdminIdInterface
     */
    private $getLoggedAsCustomerAdminId;

    /**
     * @param GetEventForLogging $getEventForLogging
     * @param Event $eventResource
     * @param LogValidation $logValidation
     * @param GetLoggedAsCustomerAdminIdInterface $getLoggedAsCustomerAdminId
     */
    public function __construct(
        GetEventForLogging $getEventForLogging,
        Event $eventResource,
        LogValidation $logValidation,
        GetLoggedAsCustomerAdminIdInterface $getLoggedAsCustomerAdminId
    ) {
        $this->getEventForLogging = $getEventForLogging;
        $this->eventResource = $eventResource;
        $this->logValidation = $logValidation;
        $this->getLoggedAsCustomerAdminId = $getLoggedAsCustomerAdminId;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        if (!$this->logValidation->shouldBeLogged()) {
            return;
        }
        $quote = $observer->getEvent()->getQuote();

        // Do not need log "remove coupon" action
        if (!empty($quote->getOrigData('coupon_code'))
            || $quote->getOrigData('coupon_code') === $quote->getData('coupon_code')) {
            return;
        }

        $event = $this->getEventForLogging->execute($this->getLoggedAsCustomerAdminId->execute());
        $event->setAction(self::ACTION);

        $info = __(
            'Quote id = %1, Coupon = %2 has been applied for customer id %3, email %4, ',
            $quote->getId(),
            $quote->getData('coupon_code'),
            $quote->getCustomer()->getId(),
            $quote->getCustomer()->getEmail()
        ) . $event->getInfo();

        $event->setInfo($info);
        $this->eventResource->save($event);
    }
}
