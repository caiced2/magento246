<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Block\Returns;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rma\Block\Returns\View;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ViewTest extends TestCase
{
    /**
     * @var View
     */
    private $view;

    /**
     * @var MockObject
     */
    private $currentCustomerMock;

    /**
     * @var MockObject
     */
    private $customerRepositoryMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->currentCustomerMock = $this->createMock(CurrentCustomer::class);
        $this->customerRepositoryMock = $this->getMockForAbstractClass(CustomerRepositoryInterface::class);
        $this->view = $objectManager->getObject(
            View::class,
            [
                'currentCustomer' => $this->currentCustomerMock,
                'customerRepository' => $this->customerRepositoryMock
            ]
        );
    }

    public function testGetCustomerData()
    {
        $customerId = 1;
        $customerMock = $this->getMockForAbstractClass(CustomerInterface::class);
        $this->currentCustomerMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $this->customerRepositoryMock->expects($this->once())->method('getById')->willReturn($customerMock);
        $this->assertEquals($customerMock, $this->view->getCustomerData());
    }
}
