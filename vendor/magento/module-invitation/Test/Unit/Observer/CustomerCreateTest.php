<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Invitation\Test\Unit\Observer;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Invitation\Controller\Customer\Account\CreatePost;
use Magento\Invitation\Model\Invitation;
use Magento\Invitation\Model\InvitationProvider;
use Magento\Invitation\Observer\CustomerCreate;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomerCreateTest extends TestCase
{
    private const WEBSITE_ID = 'website_id';
    private const CUSTOMER_ID = 'customer_id';

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var CustomerCreate
     */
    private $customerCreate;

    /**
     * @var InvitationProvider|MockObject
     */
    private $invitationProviderMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    protected function setUp(): void
    {
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $websiteMock = $this->getMockBuilder(Website::class)
            ->disableOriginalConstructor()
            ->getMock();
        $websiteMock->method('getId')
            ->willReturn(self::WEBSITE_ID);
        $this->storeManagerMock->method('getWebsite')
            ->willReturn($websiteMock);

        $this->invitationProviderMock = $this->getMockBuilder(InvitationProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->customerCreate = $this->objectManager->getObject(
            CustomerCreate::class,
            [
                'invitationProvider' => $this->invitationProviderMock,
                'storeManager' => $this->storeManagerMock
            ]
        );
    }

    /**
     * @param string $controllerClassName
     * @param string $customerId
     * @param int $callNum
     * @dataProvider executeDataProvider
     */
    public function testExecute(string $controllerClassName, $customerId = self::CUSTOMER_ID, int $callNum = 1)
    {
        $requestMock = $this->getMockBuilder(RequestInterface::class)
            ->getMock();
        $accountController = $this->objectManager->getObject(
            $controllerClassName,
            ['request' => $requestMock]
        );
        $customerMock = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customerMock->method('getId')
            ->willReturn($customerId);
        $observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event = $this->objectManager->getObject(
            Event::class,
            [
                'data' => [
                    'account_controller' => $accountController,
                    'customer' => $customerMock
                ]
            ]
        );
        $observerMock->method('getEvent')
            ->willReturn($event);
        $invitationMock = $this->createMock(Invitation::class);
        $invitationMock->expects($this->exactly($callNum))
            ->method('accept')
            ->with(self::WEBSITE_ID, self::CUSTOMER_ID);
        $this->invitationProviderMock->expects($this->exactly($callNum))
            ->method('get')
            ->willReturn($invitationMock);
        $this->customerCreate->execute($observerMock);
    }

    public function executeDataProvider() : array
    {
        return [
            'invitation controller' => [CreatePost::class],
            'no customer id' => [CreatePost::class, null, 0],
            'customer controller' => [
                \Magento\Customer\Controller\Account\CreatePost::class,
                self::CUSTOMER_ID,
                0
            ],
        ];
    }

    public function testExecuteException()
    {
        $requestMock = $this->getMockBuilder(RequestInterface::class)
            ->getMock();
        $accountController = $this->objectManager->getObject(
            CreatePost::class,
            ['request' => $requestMock]
        );
        $customerMock = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $customerMock->method('getId')
            ->willReturn(self::CUSTOMER_ID);
        $observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event = $this->objectManager->getObject(
            Event::class,
            [
                'data' => [
                    'account_controller' => $accountController,
                    'customer' => $customerMock
                ]
            ]
        );
        $observerMock->method('getEvent')
            ->willReturn($event);
        $invitationMock = $this->createMock(Invitation::class);
        $invitationMock->expects($this->exactly(0))
            ->method('accept')
            ->with(self::WEBSITE_ID, self::CUSTOMER_ID);
        $this->invitationProviderMock->expects($this->exactly(1))
            ->method('get')
            ->willThrowException(new \Exception());
        $this->customerCreate->execute($observerMock);
    }
}
