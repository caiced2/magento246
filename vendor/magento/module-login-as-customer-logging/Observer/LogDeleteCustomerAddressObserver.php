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
 * Login as customer log customer address delete.
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class LogDeleteCustomerAddressObserver implements ObserverInterface
{
    private const ACTION = 'delete_address';

    /**
     * @var Event
     */
    private $eventResource;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var Processor
     */
    private $processor;

    /**
     * @var GetEventForLogging
     */
    private $getEventForLogging;

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
     * @param Event $eventResource
     * @param Session $session
     * @param Processor $processor
     * @param GetEventForLogging $getEventForLogging
     * @param Models $models
     * @param Changes $changesResource
     * @param LogValidation $logValidation
     * @param GetLoggedAsCustomerAdminIdInterface $getLoggedAsCustomerAdminId
     */
    public function __construct(
        Event $eventResource,
        Session $session,
        Processor $processor,
        GetEventForLogging $getEventForLogging,
        Models $models,
        Changes $changesResource,
        LogValidation $logValidation,
        GetLoggedAsCustomerAdminIdInterface $getLoggedAsCustomerAdminId
    ) {
        $this->eventResource = $eventResource;
        $this->session = $session;
        $this->processor = $processor;
        $this->getEventForLogging = $getEventForLogging;
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
        $address = $observer->getEvent()->getCustomerAddress();
        $info = __(
            'Delete address with id %1 for customer id = %2, email = %3, ',
            $address->getId(),
            $this->session->getCustomerId(),
            $this->session->getCustomer()->getEmail()
        );
        $info .= $event->getInfo();
        $event->setInfo($info);
        $this->eventResource->save($event);
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
        $changes = $this->models->modelDeleteAfter($address, $this->processor);
        if (!$changes) {
            return;
        }
        $changes->setEventId($eventId);
        $changes->setSourceName(Address::class);
        $changes->setSourceId($address->getId());
        $this->changesResource->save($changes);
    }
}
