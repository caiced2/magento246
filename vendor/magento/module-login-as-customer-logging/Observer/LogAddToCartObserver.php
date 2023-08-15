<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\LoginAsCustomerLogging\Observer;

use Magento\Customer\Model\Session;
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
class LogAddToCartObserver implements ObserverInterface
{
    private const ACTION = 'add_to_cart';

    /**
     * @var GetEventForLogging
     */
    private $getEventForLogging;

    /**
     * @var Session
     */
    private $session;

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
     * @param Session $session
     * @param Event $eventResource
     * @param LogValidation $logValidation
     * @param GetLoggedAsCustomerAdminIdInterface $getLoggedAsCustomerAdminId
     */
    public function __construct(
        GetEventForLogging $getEventForLogging,
        Session $session,
        Event $eventResource,
        LogValidation $logValidation,
        GetLoggedAsCustomerAdminIdInterface $getLoggedAsCustomerAdminId
    ) {
        $this->getEventForLogging = $getEventForLogging;
        $this->session = $session;
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
        $event = $this->getEventForLogging->execute($this->getLoggedAsCustomerAdminId->execute());
        $event->setAction(self::ACTION);
        $product = $observer->getEvent()->getProduct();
        $info = __(
            'Product sku = %1, qty = %2 has been added to cart for customer id %3, email %4, ',
            $product->getSku(),
            $product->getQty(),
            $this->session->getCustomerId(),
            $this->session->getCustomer()->getEmail()
        ) . $event->getInfo();

        $event->setInfo($info);
        $this->eventResource->save($event);
    }
}
