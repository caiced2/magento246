<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Block\Adminhtml\Sales\Order\Payment;

use Magento\Backend\Block\Template\Context;
use Magento\Customer\Model\Data\Customer;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Quote\Model\Quote;
use Magento\Reward\Block\Adminhtml\Sales\Order\Create\Payment;
use Magento\Reward\Helper\Data;
use Magento\Reward\Model\Reward;
use Magento\Reward\Model\RewardFactory;
use Magento\Sales\Model\AdminOrder\Create;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PaymentTest extends TestCase
{
    /**
     * @var Payment
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $rewardFactoryMock;

    /**
     * @var MockObject
     */
    protected $orderCreateMock;

    protected function setUp(): void
    {
        $this->rewardFactoryMock = $this->getMockBuilder(RewardFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $contextMock = $this->createMock(Context::class);
        $this->orderCreateMock = $this->createMock(Create::class);
        $rewardDataMock = $this->createMock(Data::class);
        $converterMock = $this->createMock(ExtensibleDataObjectConverter::class);

        $this->model = new Payment(
            $contextMock,
            $rewardDataMock,
            $this->orderCreateMock,
            $this->rewardFactoryMock,
            $converterMock
        );
    }

    public function testGetReward()
    {
        $rewardMock = $this->getMockBuilder(Reward::class)
            ->addMethods(['setStore'])
            ->onlyMethods(['setCustomer', 'loadByCustomer'])
            ->disableOriginalConstructor()
            ->getMock();
        $quoteMock = $this->createMock(Quote::class);
        $customerMock = $this->createMock(Customer::class);
        $storeMock = $this->createMock(Store::class);
        $this->orderCreateMock->expects($this->any())->method('getQuote')->willReturn($quoteMock);

        $this->model->setData('reward', false);

        $quoteMock->expects($this->once())->method('getCustomer')->willReturn($customerMock);
        $this->rewardFactoryMock->expects($this->once())->method('create')->willReturn($rewardMock);
        $rewardMock->expects($this->once())->method('setCustomer')->with($customerMock)->willReturnSelf();
        $quoteMock->expects($this->once())->method('getStore')->willReturn($storeMock);
        $rewardMock->expects($this->once())->method('setStore')->with($storeMock);
        $rewardMock->expects($this->once())->method('loadByCustomer');

        $this->assertEquals($rewardMock, $this->model->getReward());
    }

    public function testGetRewardWithExistingReward()
    {
        $rewardMock = $this->createMock(Reward::class);
        $this->model->setData('reward', $rewardMock);
        $this->rewardFactoryMock->expects($this->never())->method('create');

        $this->assertEquals($rewardMock, $this->model->getReward());
    }
}
