<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Model\Reward\Balance;

use Magento\Checkout\Model\Session;
use Magento\Reward\Model\Reward;
use Magento\Reward\Model\Reward\Balance\Validator;
use Magento\Reward\Model\RewardFactory;
use Magento\Sales\Model\Order;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    /**
     * @var Validator
     */
    protected $_model;

    /**
     * @var MockObject
     */
    protected $_modelFactoryMock;

    /**
     * @var MockObject
     */
    protected $_storeManagerMock;

    /**
     * @var MockObject
     */
    protected $_sessionMock;

    /**
     * @var MockObject
     */
    protected $_orderMock;

    protected function setUp(): void
    {
        $this->_storeManagerMock = $this->createMock(StoreManager::class);
        $this->_modelFactoryMock = $this->createPartialMock(RewardFactory::class, ['create']);
        $this->_sessionMock = $this->getMockBuilder(Session::class)
            ->addMethods(['setUpdateSection', 'setGotoSection'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->_orderMock = $this->getMockBuilder(Order::class)
            ->addMethods(['getRewardPointsBalance'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->_model = new Validator(
            $this->_storeManagerMock,
            $this->_modelFactoryMock,
            $this->_sessionMock
        );
    }

    public function testValidateWhenBalanceAboveNull()
    {
        $this->_orderMock->expects($this->any())->method('getRewardPointsBalance')->willReturn(1);
        $store = $this->createMock(Store::class);
        $this->_storeManagerMock->expects($this->once())->method('getStore')->willReturn($store);
        $store->expects($this->once())->method('getWebsiteId');
        $reward = $this->getMockBuilder(Reward::class)
            ->addMethods(['getPointsBalance'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->_modelFactoryMock->expects($this->once())->method('create')->willReturn($reward);
        $reward->expects($this->once())->method('getPointsBalance')->willReturn(1);
        $this->_model->validate($this->_orderMock);
    }

    public function testValidateWhenBalanceNotEnoughToPlaceOrder()
    {
        $this->expectException('Magento\Framework\Exception\LocalizedException');
        $this->expectExceptionMessage('You don\'t have enough reward points to pay for this purchase.');
        $this->_orderMock->expects($this->any())->method('getRewardPointsBalance')->willReturn(1);
        $store = $this->createMock(Store::class);
        $this->_storeManagerMock->expects($this->once())->method('getStore')->willReturn($store);
        $store->expects($this->once())->method('getWebsiteId');
        $reward = $this->getMockBuilder(Reward::class)
            ->addMethods(['getPointsBalance'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->_modelFactoryMock->expects($this->once())->method('create')->willReturn($reward);
        $reward->expects($this->once())->method('getPointsBalance')->willReturn(0.5);
        $this->_sessionMock->expects($this->once())->method('setUpdateSection')->with('payment-method');
        $this->_sessionMock->expects($this->once())->method('setGotoSection')->with('payment');

        $this->_model->validate($this->_orderMock);
    }
}
