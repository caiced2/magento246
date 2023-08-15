<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\LoginAsCustomerLogging\Observer;

use Magento\Customer\Model\Address;
use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Logging\Model\Handler\Models;
use Magento\Logging\Model\Processor;
use Magento\Logging\Model\ResourceModel\Event;
use Magento\Logging\Model\ResourceModel\Event\Changes;
use Magento\LoginAsCustomerApi\Api\GetLoggedAsCustomerAdminIdInterface;
use Magento\LoginAsCustomerLogging\Model\LogValidation;
use Magento\LoginAsCustomerLogging\Model\GetEventForLogging;

/**
 * Login as customer log customer address changes.
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class LogSaveCustomerAddressObserver implements ObserverInterface
{
    private const ACTION = 'save_address';

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
     * @var Processor
     */
    private $processor;

    /**
     * @var Models
     */
    private $models;

    /**
     * @var Changes
     */
    private $changesResource;

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
     * @param Processor $processor
     * @param Models $models
     * @param Changes $changesResource
     * @param LogValidation $logValidation
     * @param GetLoggedAsCustomerAdminIdInterface $getLoggedAsCustomerAdminId
     */
    public function __construct(
        GetEventForLogging $getEventForLogging,
        Session $session,
        Event $eventResource,
        Processor $processor,
        Models $models,
        Changes $changesResource,
        LogValidation $logValidation,
        GetLoggedAsCustomerAdminIdInterface $getLoggedAsCustomerAdminId
    ) {
        $this->getEventForLogging = $getEventForLogging;
        $this->session = $session;
        $this->eventResource = $eventResource;
        $this->processor = $processor;
        $this->models = $models;
        $this->changesResource = $changesResource;
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
        $info = __(
            'Save address for customer id = %1, email = %2, ',
            $this->session->getCustomerId(),
            $this->session->getCustomer()->getEmail()
        );
        $info .= $event->getInfo();
        $event->setInfo($info);
        $this->eventResource->save($event);
        $address = $observer->getEvent()->getCustomerAddress();
        $this->processChanges($address, (int)$event->getId());
    }

    /**
     * Log address changes.
     *
     * @param Address $address
     * @param int $eventId
     * @return void
     * @throws AlreadyExistsException
     */
    private function processChanges(Address $address, int $eventId): void
    {
        $changes = $this->models->modelSaveAfter($address, $this->processor);
        if (!$changes) {
            return;
        }
        $changes->setEventId($eventId);
        $changes->setSourceName(Address::class);
        $changes->setSourceId($address->getId());
        $this->changesResource->save($changes);
    }
}
