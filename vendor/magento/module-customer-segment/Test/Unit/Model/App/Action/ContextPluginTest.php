<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Test\Unit\Model\App\Action;

use Magento\Customer\Model\Session;
use Magento\CustomerSegment\Helper\Data;
use Magento\CustomerSegment\Model\App\Action\ContextPlugin;
use Magento\CustomerSegment\Model\Customer;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Http\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ContextPluginTest extends TestCase
{
    /**
     * @var ContextPlugin
     */
    protected $plugin;

    /**
     * @var Session|MockObject
     */
    protected $customerSessionMock;

    /**
     * @var Context|\PHPUnit\Framework\MockObject\MockObject $httpContext
     */
    protected $httpContextMock;

    /**
     * @var Customer|MockObject
     */
    protected $customerSegmentMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    protected $storeManagerMock;

    /**
     * @var Website|MockObject
     */
    protected $websiteMock;

    /**
     * @var MockObject
     */
    protected $subjectMock;

    /**
     * @var MockObject
     */
    protected $requestMock;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->customerSessionMock = $this->createPartialMock(
            Session::class,
            ['getCustomerId']
        );
        $this->httpContextMock = $this->createMock(Context::class);
        $this->customerSegmentMock = $this->getMockBuilder(Customer::class)
            ->addMethods(['getCustomerId'])
            ->onlyMethods(['getCustomerSegmentIdsForWebsite'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManagerMock = $this->getMockForAbstractClass(
            StoreManagerInterface::class,
            [],
            '',
            false
        );
        $this->subjectMock = $this->createMock(Action::class);
        $this->requestMock = $this->getMockForAbstractClass(RequestInterface::class);
        $this->websiteMock = $this->createPartialMock(Website::class, ['getId']);

        $this->plugin = new ContextPlugin(
            $this->customerSessionMock,
            $this->httpContextMock,
            $this->customerSegmentMock,
            $this->storeManagerMock
        );
    }

    /**
     * Test aroundDispatch
     */
    public function testBeforeDispatch()
    {
        $customerId = 1;
        $customerSegmentIds = [1, 2, 3];
        $websiteId  = 1;

        $this->customerSessionMock->expects($this->exactly(2))
            ->method('getCustomerId')
            ->willReturn($customerId);

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsite')
            ->willReturn($this->websiteMock);

        $this->websiteMock->expects($this->once())
            ->method('getId')
            ->willReturn($websiteId);

        $this->customerSegmentMock->expects($this->once())
            ->method('getCustomerSegmentIdsForWebsite')
            ->with($customerId, $websiteId)
            ->willReturn($customerSegmentIds);

        $this->httpContextMock->expects($this->once())
            ->method('setValue')
            ->with(
                $this->equalTo(Data::CONTEXT_SEGMENT),
                $this->equalTo($customerSegmentIds)
            );

        $this->plugin->beforeDispatch($this->subjectMock, $this->requestMock);
    }
}
