<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\LoginAsCustomerLogging\Observer;

use Magento\Customer\Model\Data\Customer;
use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Logging\Model\Event\ChangesFactory;
use Magento\Logging\Model\Processor;
use Magento\Logging\Model\ResourceModel\Event;
use Magento\Logging\Model\ResourceModel\Event\Changes;
use Magento\LoginAsCustomerApi\Api\GetLoggedAsCustomerAdminIdInterface;
use Magento\LoginAsCustomerLogging\Model\LogValidation;
use Magento\LoginAsCustomerLogging\Model\GetEventForLogging;

/**
 * Login as customer log customer changes.
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class LogSaveCustomerObserver implements ObserverInterface
{
    private const ACTION = 'save';

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
     * @var ChangesFactory
     */
    private $eventChangesFactory;

    /**
     * @var Processor
     */
    private $processor;

    /**
     * @param GetEventForLogging $getEventForLogging
     * @param Session $session
     * @param Event $eventResource
     * @param Changes $changesResource
     * @param LogValidation $logValidation
     * @param GetLoggedAsCustomerAdminIdInterface $getLoggedAsCustomerAdminId
     * @param ChangesFactory $eventChangesFactory
     * @param Processor $processor
     */
    public function __construct(
        GetEventForLogging $getEventForLogging,
        Session $session,
        Event $eventResource,
        Changes $changesResource,
        LogValidation $logValidation,
        GetLoggedAsCustomerAdminIdInterface $getLoggedAsCustomerAdminId,
        ChangesFactory $eventChangesFactory,
        Processor $processor
    ) {
        $this->getEventForLogging = $getEventForLogging;
        $this->session = $session;
        $this->eventResource = $eventResource;
        $this->changesResource = $changesResource;
        $this->logValidation = $logValidation;
        $this->getLoggedAsCustomerAdminId = $getLoggedAsCustomerAdminId;
        $this->eventChangesFactory = $eventChangesFactory;
        $this->processor = $processor;
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
            'Save customer id = %1, email = %2, ',
            $this->session->getCustomerId(),
            $this->session->getCustomer()->getEmail()
        );
        $info .= $event->getInfo();
        $event->setInfo($info);
        $this->eventResource->save($event);

        $customerData = $this->getFilteredData($observer->getEvent()->getCustomerDataObject()->__toArray());
        $customerOrigData = $this->getFilteredData($observer->getEvent()->getOrigCustomerDataObject()->__toArray());

        $this->processChanges($customerData, $customerOrigData, (int)$event->getId());
    }

    /**
     * Log address changes.
     *
     * @param array $customerData
     * @param array $customerOrigData
     * @param int $eventId
     * @return void
     * @throws AlreadyExistsException
     */
    private function processChanges(array $customerData, array $customerOrigData, int $eventId): void
    {
        /** @var Changes $changes */
        $changes = $this->eventChangesFactory->create();
        $changes->setOriginalData($customerOrigData)->setResultData($customerData);
        if (!$changes) {
            return;
        }
        $changes->setEventId($eventId);
        $changes->setSourceName(Customer::class);
        $changes->setSourceId($customerData['id']);
        $this->changesResource->save($changes);
    }

    /**
     * Returns filtered data.
     *
     * @param array $data
     * @return array
     */
    private function getFilteredData(array $data): array
    {
        $skipKeys = ['addresses', 'updated_at'];
        foreach ($skipKeys as $key) {
            unset($data[$key]);
        }

        return $data;
    }
}
