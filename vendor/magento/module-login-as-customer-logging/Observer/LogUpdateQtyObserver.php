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
use Magento\LoginAsCustomerLogging\Model\GetEventForLogging;
use Magento\LoginAsCustomerLogging\Model\LogValidation;
use Magento\LoginAsCustomerApi\Api\GetLoggedAsCustomerAdminIdInterface;

/**
 * Login as customer log add to cart observer.
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class LogUpdateQtyObserver implements ObserverInterface
{
    private const ACTION = 'update_qty';

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
    private LogValidation $logValidation;

    /**
     * @var GetLoggedAsCustomerAdminIdInterface
     */
    private $getLoggedAsCustomerAdminId;

    /**
     * @param GetEventForLogging $getEventForLogging
     * @param Event $eventResource
     * @param GetLoggedAsCustomerAdminIdInterface $getLoggedAsCustomerAdminId
     * @param LogValidation $logValidation
     */
    public function __construct(
        GetEventForLogging $getEventForLogging,
        Event $eventResource,
        GetLoggedAsCustomerAdminIdInterface $getLoggedAsCustomerAdminId,
        LogValidation $logValidation
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
        $item = $observer->getEvent()->getItem();

        // Do not need log "change qty" action while adding product to a cart
        if ((int)$item->getOrigData('qty') === 0 || (int)$item->getOrigData('qty') === (int)$item->getData('qty')) {
            return;
        }

        $event = $this->getEventForLogging->execute($this->getLoggedAsCustomerAdminId->execute());
        $event->setAction(self::ACTION);

        $info = __(
            'Quote id = %1, Item sku = %2, Old qty = %3 has been set to %4 for customer id %5, email %6, ',
            $item->getData('quote_id'),
            $item->getData('sku'),
            (int)$item->getOrigData('qty'),
            (int)$item->getData('qty'),
            $item->getQuote()->getCustomer()->getId(),
            $item->getQuote()->getCustomer()->getEmail()
        ) . $event->getInfo();

        $event->setInfo($info);
        $this->eventResource->save($event);
    }
}
