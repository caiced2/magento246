<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PersistentHistory\Test\Unit\Observer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Persistent\Helper\Session;
use Magento\PersistentHistory\Helper\Data;
use Magento\PersistentHistory\Observer\QuotePersistentPreventFlag;
use Magento\PersistentHistory\Observer\SetQuotePersistentDataObserver;
use Magento\Quote\Api\Data\CartInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SetQuotePersistentDataObserverTest extends TestCase
{
    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $customerRepository;

    /**
     * @var Session|MockObject
     */
    private $persistentSession;

    /**
     * @var Data|MockObject
     */
    private $persistentHistoryDataHelper;

    /**
     * @var \Magento\Persistent\Helper\Data|MockObject
     */
    private $persistentDataHelper;

    /**
     * @var \Magento\Customer\Model\Session|MockObject
     */
    private $customerSession;

    /**
     * @var QuotePersistentPreventFlag|MockObject
     */
    private $quotePersistent;

    /**
     * @var SetQuotePersistentDataObserver
     */
    private $observer;

    /**
     * @var Observer|MockObject
     */
    private $eventObserver;

    /**
     * @var Event|MockObject
     */
    private $event;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var CartInterface|MockObject
     */
    private $quote;

    protected function setUp(): void
    {
        $this->quote = $this->getMockBuilder(CartInterface::class)
            ->setMethods(['setCustomer'])
            ->getMockForAbstractClass();
        $this->event = $this->getMockBuilder(Event::class)
            ->setMethods(['getQuote'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventObserver = $this->getMockBuilder(Observer::class)
            ->setMethods(['getEvent'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->persistentSession = $this->getMockBuilder(Session::class)
            ->setMethods(['isPersistent'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->persistentHistoryDataHelper = $this->getMockBuilder(Data::class)
            ->setMethods(['isCustomerAndSegmentsPersist'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->persistentDataHelper = $this->getMockBuilder(
            \Magento\Persistent\Helper\Data::class
        )
            ->setMethods(['canProcess'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerSession = $this->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->setMethods(['getCustomerId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quotePersistent = $this->getMockBuilder(
            QuotePersistentPreventFlag::class
        )
            ->setMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerRepository = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->setMethods(['getById'])
            ->getMockForAbstractClass();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->observer = (new ObjectManager($this))->getObject(
            SetQuotePersistentDataObserver::class,
            [
                'persistentSession' => $this->persistentSession,
                'persistentHistoryDataHelper' => $this->persistentHistoryDataHelper,
                'persistentDataHelper' => $this->persistentDataHelper,
                'customerSession' => $this->customerSession,
                'quotePersistent' => $this->quotePersistent,
                'customerRepository' => $this->customerRepository,
                'logger' => $this->logger,
            ]
        );
    }

    public function testUnprocessableEvent()
    {
        $this->persistentDataHelper->expects($this->once())->method('canProcess')->willReturn(false);
        $this->persistentSession->expects($this->never())->method('isPersistent');
        $this->eventObserver->expects($this->never())->method('getEvent');
        $this->event->expects($this->never())->method('getQuote');
        $this->quote->expects($this->never())->method('setCustomer');
        $this->persistentHistoryDataHelper->expects($this->never())->method('isCustomerAndSegmentsPersist');
        $this->quotePersistent->expects($this->never())->method('getValue');
        $this->customerSession->expects($this->never())->method('getCustomerId');
        $this->customerRepository->expects($this->never())->method('getById');
        $this->observer->execute($this->eventObserver);
    }

    public function testNotPersistableSession()
    {
        $this->persistentDataHelper->expects($this->once())->method('canProcess')->willReturn(true);
        $this->persistentSession->expects($this->once())->method('isPersistent')->willReturn(false);
        $this->eventObserver->expects($this->never())->method('getEvent');
        $this->event->expects($this->never())->method('getQuote');
        $this->quote->expects($this->never())->method('setCustomer');
        $this->persistentHistoryDataHelper->expects($this->never())->method('isCustomerAndSegmentsPersist');
        $this->quotePersistent->expects($this->never())->method('getValue');
        $this->customerSession->expects($this->never())->method('getCustomerId');
        $this->customerRepository->expects($this->never())->method('getById');
        $this->observer->execute($this->eventObserver);
    }

    public function testMissingQuote()
    {
        $this->persistentDataHelper->expects($this->once())->method('canProcess')->willReturn(true);
        $this->persistentSession->expects($this->once())->method('isPersistent')->willReturn(true);
        $this->eventObserver->expects($this->once())->method('getEvent')->willReturn($this->event);
        $this->event->expects($this->once())->method('getQuote')->willReturn(null);
        $this->quote->expects($this->never())->method('setCustomer');
        $this->persistentHistoryDataHelper->expects($this->never())->method('isCustomerAndSegmentsPersist');
        $this->quotePersistent->expects($this->never())->method('getValue');
        $this->customerSession->expects($this->never())->method('getCustomerId');
        $this->customerRepository->expects($this->never())->method('getById');
        $this->observer->execute($this->eventObserver);
    }

    public function testCustomerPersistanceDisabled()
    {
        $this->persistentDataHelper->expects($this->once())->method('canProcess')->willReturn(true);
        $this->persistentSession->expects($this->once())->method('isPersistent')->willReturn(true);
        $this->eventObserver->expects($this->once())->method('getEvent')->willReturn($this->event);
        $this->event->expects($this->once())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->never())->method('setCustomer');
        $this->persistentHistoryDataHelper->expects($this->once())
            ->method('isCustomerAndSegmentsPersist')
            ->with(null)
            ->willReturn(false);
        $this->quotePersistent->expects($this->never())->method('getValue');
        $this->customerSession->expects($this->never())->method('getCustomerId');
        $this->customerRepository->expects($this->never())->method('getById');
        $this->observer->execute($this->eventObserver);
    }

    public function testQuotePersistanceDisabled()
    {
        $this->persistentDataHelper->expects($this->once())->method('canProcess')->willReturn(true);
        $this->persistentSession->expects($this->once())->method('isPersistent')->willReturn(true);
        $this->eventObserver->expects($this->once())->method('getEvent')->willReturn($this->event);
        $this->event->expects($this->once())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->never())->method('setCustomer');
        $this->persistentHistoryDataHelper->expects($this->once())
            ->method('isCustomerAndSegmentsPersist')
            ->with(null)
            ->willReturn(true);
        $this->quotePersistent->expects($this->once())->method('getValue')->willReturn(false);
        $this->customerSession->expects($this->never())->method('getCustomerId');
        $this->customerRepository->expects($this->never())->method('getById');
        $this->observer->execute($this->eventObserver);
    }

    public function testAnonymousCustomer()
    {
        $this->persistentDataHelper->expects($this->once())->method('canProcess')->willReturn(true);
        $this->persistentSession->expects($this->once())->method('isPersistent')->willReturn(true);
        $this->eventObserver->expects($this->once())->method('getEvent')->willReturn($this->event);
        $this->event->expects($this->once())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->never())->method('setCustomer');
        $this->persistentHistoryDataHelper->expects($this->once())
            ->method('isCustomerAndSegmentsPersist')
            ->with(null)
            ->willReturn(true);
        $this->quotePersistent->expects($this->once())->method('getValue')->willReturn(true);
        $this->customerSession->expects($this->once())->method('getCustomerId')->willReturn(null);
        $this->customerRepository->expects($this->never())->method('getById');
        $this->observer->execute($this->eventObserver);
    }

    public function testUnknownCustomer()
    {
        $this->persistentDataHelper->expects($this->once())->method('canProcess')->willReturn(true);
        $this->persistentSession->expects($this->once())->method('isPersistent')->willReturn(true);
        $this->eventObserver->expects($this->once())->method('getEvent')->willReturn($this->event);
        $this->event->expects($this->once())->method('getQuote')->willReturn($this->quote);
        $this->persistentHistoryDataHelper->expects($this->once())
            ->method('isCustomerAndSegmentsPersist')
            ->with(null)
            ->willReturn(true);
        $this->quotePersistent->expects($this->once())->method('getValue')->willReturn(true);
        $this->customerSession->expects($this->once())->method('getCustomerId')->willReturn(123);
        $this->customerRepository->expects($this->once())->method('getById')->willThrowException(
            NoSuchEntityException::singleField('customer_id', 123)
        );
        $this->logger->expects($this->once())->method('notice');
        $this->quote->expects($this->never())->method('setCustomer');
        $this->observer->execute($this->eventObserver);
    }

    public function testCustomerHasBeenSetToQuote()
    {
        $customer = $this->getMockBuilder(CustomerInterface::class)
            ->setMethods([])
            ->getMockForAbstractClass();

        $this->persistentDataHelper->expects($this->once())->method('canProcess')->willReturn(true);
        $this->persistentSession->expects($this->once())->method('isPersistent')->willReturn(true);
        $this->eventObserver->expects($this->once())->method('getEvent')->willReturn($this->event);
        $this->event->expects($this->once())->method('getQuote')->willReturn($this->quote);
        $this->persistentHistoryDataHelper->expects($this->once())
            ->method('isCustomerAndSegmentsPersist')
            ->with(null)
            ->willReturn(true);
        $this->quotePersistent->expects($this->once())->method('getValue')->willReturn(true);
        $this->customerSession->expects($this->once())->method('getCustomerId')->willReturn(12);
        $this->customerRepository->expects($this->once())->method('getById')->with(12)->willReturn($customer);
        $this->logger->expects($this->never())->method('notice');
        $this->quote->expects($this->once())->method('setCustomer')->with($customer);
        $this->observer->execute($this->eventObserver);
    }
}
