<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedCheckout\Test\Unit\Controller\Adminhtml;

use Magento\AdvancedCheckout\Model\Cart;
use Magento\AdvancedCheckout\Test\Unit\Controller\Adminhtml\Stub\Child;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Session;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\Customer;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\ObjectManager\ObjectManager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for AdvancedCheckout Index
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class IndexTest extends TestCase
{
    /**
     * @var Child
     */
    protected $controller;

    /**
     * @var ObjectManagerInterface|MockObject
     */
    protected $objectManager;

    /**
     * @var Http|MockObject
     */
    protected $request;

    /**
     * @var CustomerInterfaceFactory|MockObject
     */
    protected $customerFactory;

    /**
     * Set Up
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = $this->createMock(ObjectManager::class);
        $this->customerFactory = $this->createPartialMock(
            CustomerInterfaceFactory::class,
            ['create']
        );

        $this->request = $this->createPartialMock(Http::class, ['getPost', 'getParam']);
        $response = $this->getMockForAbstractClass(ResponseInterface::class);

        $context = $this->createMock(Context::class);
        $context->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);
        $context->expects($this->once())
            ->method('getResponse')
            ->willReturn($response);
        $context->expects($this->once())
            ->method('getObjectManager')
            ->willReturn($this->objectManager);
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->controller = $objectManager->getObject(
            Child::class,
            ['context' => $context, 'customerFactory' => $this->customerFactory]
        );
    }

    /**
     * Test AdvancedCheckoutIndex InitData with Quote id false
     *
     * @return void
     */
    public function testInitData(): void
    {
        $this->request->expects($this->any())
            ->method('getParam')
            ->willReturn(true);

        $customerModel = $this->getMockBuilder(Customer::class)
            ->addMethods(['getWebsiteId'])
            ->onlyMethods(['load', 'getId', 'getData'])
            ->disableOriginalConstructor()
            ->getMock();
        $customerModel->expects($this->once())
            ->method('load')
            ->willReturnSelf();
        $customerId = 1;
        $customerModel->expects($this->exactly(2))
            ->method('getId')
            ->willReturn($customerId);
        $customerModel->expects($this->once())
            ->method('getWebsiteId')
            ->willReturn(true);

        $store = $this->createMock(Store::class);

        $storeManager = $this->getMockBuilder(StoreManager::class)
            ->addMethods(['getWebsiteId'])
            ->onlyMethods(['getStore'])
            ->disableOriginalConstructor()
            ->getMock();
        $storeManager->expects($this->any())
            ->method('getStore')
            ->willReturn($store);

        $quote = $this->createMock(Quote::class);
        $quote->expects($this->once())
            ->method('getId')
            ->willReturn(false);

        $cart = $this->createMock(Cart::class);
        $cart->expects($this->once())
            ->method('setSession')
            ->willReturnSelf();
        $cart->expects($this->once())
            ->method('setContext')
            ->willReturnSelf();
        $cart->expects($this->once())
            ->method('setCurrentStore')
            ->willReturnSelf();
        $cart->expects($this->once())
            ->method('getQuote')
            ->willReturn($quote);

        $session = $this->createMock(Session::class);
        $quoteRepository = $this->getMockForAbstractClass(CartRepositoryInterface::class);

        $this->objectManager
            ->method('create')
            ->with(Customer::class)
            ->willReturn($customerModel);
        $this->objectManager
            ->method('get')
            ->withConsecutive(
                [StoreManager::class],
                [Cart::class],
                [Session::class],
                [CartRepositoryInterface::class]
            )
            ->willReturnOnConsecutiveCalls(
                $storeManager,
                $cart,
                $session,
                $quoteRepository
            );
        $customerData = $this->expectCustomerModelConvertToCustomerData($customerModel, $customerId);
        $quote->expects($this->once())
            ->method('setCustomer')
            ->with($customerData);
        $quote->expects($this->once())
            ->method('setStore')
            ->willReturnSelf();

        $this->controller->execute();
    }

    /**
     * Expecting for converting Customer Model.
     *
     * @param $customerModel
     * @param $customerId
     *
     * @return MockObject
     */
    protected function expectCustomerModelConvertToCustomerData($customerModel, $customerId): MockObject
    {
        $customerData = $this->getMockForAbstractClass(
            CustomerInterface::class,
            [],
            '',
            false
        );
        $customerData->expects($this->once())
            ->method('setId')
            ->with($customerId)
            ->willReturnSelf();

        $this->customerFactory->expects($this->once())
            ->method('create')
            ->willReturn($customerData);

        $customerDataArray = ['entity_id' => 1];
        $customerModel->expects($this->once())
            ->method('getData')
            ->willReturn($customerDataArray);

        return $customerData;
    }
}
