<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Observer;

use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Reward\Helper\Data;
use Magento\Reward\Model\Reward;
use Magento\Reward\Model\Reward\Balance\Validator;
use Magento\Reward\Model\RewardFactory;
use Magento\Reward\Observer\PlaceOrder\RestrictionInterface;
use Magento\Reward\Observer\RedeemForOrder;
use Magento\Sales\Model\Order;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RedeemForOrderTest extends TestCase
{
    /**
     * @var RedeemForOrder
     */
    protected $_model;

    /**
     * @var MockObject
     */
    protected $_restrictionMock;

    /**
     * @var MockObject
     */
    protected $_modelFactoryMock;

    /**
     * @var MockObject
     */
    protected $_resourceFactoryMock;

    /**
     * @var MockObject
     */
    protected $_storeManagerMock;

    /**
     * @var MockObject
     */
    protected $_validatorMock;

    /**
     * @var MockObject
     */
    protected $_observerMock;

    /**
     * @var MockObject
     */
    protected $rewardHelperMock;

    protected function setUp(): void
    {
        $this->_restrictionMock = $this->getMockForAbstractClass(RestrictionInterface::class);
        $this->_storeManagerMock = $this->createMock(StoreManager::class);
        $this->rewardHelperMock = $this->createMock(Data::class);
        $this->_modelFactoryMock = $this->createPartialMock(RewardFactory::class, ['create']);
        $this->_resourceFactoryMock = $this->createPartialMock(
            \Magento\Reward\Model\ResourceModel\RewardFactory::class,
            ['create']
        );
        $this->_validatorMock = $this->createMock(Validator::class);

        $this->_observerMock = $this->createMock(Observer::class);

        $this->_model = new RedeemForOrder(
            $this->_restrictionMock,
            $this->_storeManagerMock,
            $this->_modelFactoryMock,
            $this->_validatorMock
        );
    }

    public function testRedeemForOrderIfRestrictionNotAllowed()
    {
        $this->_restrictionMock->expects($this->once())->method('isAllowed')->willReturn(false);
        $this->_observerMock->expects($this->never())->method('getEvent');
        $this->_model->execute($this->_observerMock);
    }

    public function testRedeemForOrderIfRewardCurrencyAmountAboveNull()
    {
        $baseRewardCurrencyAmount = 1;
        $rewardPointsBalance = 100;
        $this->_restrictionMock->expects($this->once())->method('isAllowed')->willReturn(true);
        $order = $this->getMockBuilder(Order::class)
            ->addMethods(['setBaseRewardCurrencyAmount', 'setRewardPointsBalance'])
            ->disableOriginalConstructor()
            ->getMock();
        $quote = $this->getMockBuilder(Quote::class)
            ->addMethods(['getBaseRewardCurrencyAmount', 'getRewardPointsBalance'])
            ->disableOriginalConstructor()
            ->getMock();
        $event = $this->getMockBuilder(Event::class)
            ->addMethods(['getOrder', 'getQuote'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->_observerMock->expects($this->once())->method('getEvent')->willReturn($event);
        $event->expects($this->once())->method('getOrder')->willReturn($order);
        $event->expects($this->once())->method('getQuote')->willReturn($quote);
        $quote->expects($this->atLeastOnce())->method('getBaseRewardCurrencyAmount')
            ->willReturn($baseRewardCurrencyAmount);
        $model = $this->createMock(Reward::class);
        $this->_modelFactoryMock->expects($this->once())->method('create')->willReturn($model);
        $store = $this->createMock(Store::class);
        $this->_storeManagerMock->expects($this->once())->method('getStore')->willReturn($store);
        $store->expects($this->once())->method('getWebsiteId');
        $quote->expects($this->atLeastOnce())->method('getRewardPointsBalance')->willReturn($rewardPointsBalance);
        $order->expects($this->once())->method('setBaseRewardCurrencyAmount')->with($baseRewardCurrencyAmount);
        $order->expects($this->once())->method('setRewardPointsBalance')->with($rewardPointsBalance);
        $this->_model->execute($this->_observerMock);
    }

    public function testRedeemForOrderIfRewardCurrencyAmountBelowNull()
    {
        $baseRewardCurrencyAmount = -1;
        $this->_restrictionMock->expects($this->once())->method('isAllowed')->willReturn(true);
        $order = $this->createPartialMock(Order::class, ['__wakeup']);
        $quote = $this->getMockBuilder(Quote::class)
            ->addMethods(['getBaseRewardCurrencyAmount'])
            ->disableOriginalConstructor()
            ->getMock();
        $event = $this->getMockBuilder(Event::class)
            ->addMethods(['getOrder', 'getQuote'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->_observerMock->expects($this->once())->method('getEvent')->willReturn($event);
        $event->expects($this->once())->method('getOrder')->willReturn($order);
        $event->expects($this->once())->method('getQuote')->willReturn($quote);
        $quote->expects($this->once())->method('getBaseRewardCurrencyAmount')->willReturn($baseRewardCurrencyAmount);
        $this->_model->execute($this->_observerMock);
    }

    public function testRedeemForOrderPlacedViaMultyshipping()
    {
        $baseRewardCurrencyAmount = 1;
        $rewardPointsBalance = 100;
        $this->_restrictionMock->expects($this->once())->method('isAllowed')->willReturn(true);
        $order = $this->getMockBuilder(Order::class)
            ->addMethods(['setBaseRewardCurrencyAmount', 'setRewardPointsBalance'])
            ->disableOriginalConstructor()
            ->getMock();
        $quote = $this->getMockBuilder(Quote::class)
            ->addMethods(['getBaseRewardCurrencyAmount', 'getRewardPointsBalance', 'getIsMultiShipping'])
            ->disableOriginalConstructor()
            ->getMock();
        $addressMock = $this->getMockBuilder(Address::class)
            ->addMethods(['getBaseRewardCurrencyAmount', 'getRewardPointsBalance'])
            ->disableOriginalConstructor()
            ->getMock();
        $event = $this->getMockBuilder(Event::class)
            ->addMethods(['getOrder', 'getQuote', 'getAddress'])
            ->disableOriginalConstructor()
            ->getMock();
        $quote->expects($this->once())->method('getIsMultiShipping')->willReturn(true);
        $this->_observerMock->expects($this->once())->method('getEvent')->willReturn($event);
        $event->expects($this->once())->method('getOrder')->willReturn($order);
        $event->expects($this->once())->method('getQuote')->willReturn($quote);
        $event->expects($this->once())->method('getAddress')->willReturn($addressMock);
        $quote->expects($this->atLeastOnce())->method('getBaseRewardCurrencyAmount')
            ->willReturn($baseRewardCurrencyAmount);
        $addressMock->expects($this->atLeastOnce())->method('getBaseRewardCurrencyAmount')
            ->willReturn($baseRewardCurrencyAmount);
        $model = $this->createMock(Reward::class);
        $this->_modelFactoryMock->expects($this->once())->method('create')->willReturn($model);
        $store = $this->createMock(Store::class);
        $this->_storeManagerMock->expects($this->once())->method('getStore')->willReturn($store);
        $store->expects($this->once())->method('getWebsiteId');
        $addressMock->expects($this->atLeastOnce())->method('getRewardPointsBalance')->willReturn($rewardPointsBalance);
        $order->expects($this->once())->method('setBaseRewardCurrencyAmount')->with($baseRewardCurrencyAmount);
        $order->expects($this->once())->method('setRewardPointsBalance')->with($rewardPointsBalance);
        $this->_model->execute($this->_observerMock);
    }
}
