<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Model\Reward\Refund;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Reward\Helper\Data;
use Magento\Reward\Model\Reward;
use Magento\Reward\Model\Reward\Refund\SalesRuleRefund;
use Magento\Reward\Model\RewardFactory;
use Magento\Reward\Model\SalesRule\RewardPointCounter;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Creditmemo\Item;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection;
use Magento\SalesRule\Api\Data\RuleExtensionInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SalesRuleRefundTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $rewardFactoryMock;

    /**
     * @var MockObject
     */
    protected $storeManagerMock;

    /**
     * @var MockObject
     */
    protected $rewardHelperMock;

    /**
     * @var SalesRuleRefund
     */
    protected $subject;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var RewardPointCounter|MockObject
     */
    private $rewardPointCounterMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->rewardFactoryMock = $this->getMockBuilder(RewardFactory::class)
            ->disableOriginalConstructor()
            ->addMethods(['__wakeup'])
            ->onlyMethods(['create'])
            ->getMock();
        $this->storeManagerMock = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $this->rewardHelperMock = $this->createMock(Data::class);
        $this->rewardPointCounterMock = $this->getMockBuilder(RewardPointCounter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->subject = $this->objectManager->getObject(
            SalesRuleRefund::class,
            [
                'rewardFactory' => $this->rewardFactoryMock,
                'storeManager' => $this->storeManagerMock,
                'rewardHelper' => $this->rewardHelperMock,
                'rewardPointCounter' => $this->rewardPointCounterMock
            ]
        );
    }

    /**
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testRefundSuccess(): void
    {
        $websiteId = 2;
        $customerId = 10;
        $appliedRuleIds = '1,2,1,1,3,4,3';

        $orderMock = $this->createPartialMock(Order::class, [
            '__wakeup',
            'getCreditmemosCollection',
            'getTotalQtyOrdered',
            'getStoreId',
            'getCustomerId',
            'getAppliedRuleIds'
        ]);
        $creditmemoMock = $this->getMockBuilder(Creditmemo::class)
            ->addMethods(['getAutomaticallyCreated', 'setRewardPointsBalanceRefund', 'getRewardPointsBalance'])
            ->onlyMethods(['getOrder'])
            ->disableOriginalConstructor()
            ->getMock();

        $creditmemoMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $creditmemo = $this->createPartialMock(
            Creditmemo::class,
            ['getData', 'getAllItems']
        );
        $creditmemoCollectionMock = $this->objectManager->getCollectionMock(
            Collection::class,
            [$creditmemo]
        );
        $orderMock->expects($this->atLeastOnce())
            ->method('getCreditmemosCollection')
            ->willReturn($creditmemoCollectionMock);
        $itemMock = $this->createPartialMock(Item::class, ['getQty']);
        $creditmemo->expects($this->atLeastOnce())->method('getAllItems')->willReturn([$itemMock]);

        $itemMock->expects($this->atLeastOnce())->method('getQty')->willReturn(5);
        $creditmemoMock->expects($this->exactly(2))->method('getAutomaticallyCreated')->willReturn(true);
        $this->rewardHelperMock->expects($this->once())->method('isAutoRefundEnabled')->willReturn(true);

        $creditmemoMock->method('getRewardPointsBalance')->willReturn(100);
        $creditmemoMock->expects($this->once())
            ->method('setRewardPointsBalanceRefund')
            ->with(100)->willReturnSelf();

        $orderMock->expects($this->once())->method('getTotalQtyOrdered')->willReturn(5);
        $rewardMock = $this->getRewardMockForRefund();

        $orderMock->expects($this->exactly(2))->method('getAppliedRuleIds')->willReturn($appliedRuleIds);

        $this->rewardPointCounterMock->expects(self::any())
            ->method('getPointsForRules')
            ->with(
                [
                    0 => '1',
                    1 => '2',
                    4 => '3',
                    5 => '4'
                ]
            )
            ->willReturn(100);

        $this->rewardFactoryMock->expects($this->exactly(2))->method('create')->willReturn($rewardMock);
        $orderMock->expects($this->exactly(2))->method('getStoreId')->willReturn(1);

        $storeMock = $this->createMock(Store::class);
        $this->storeManagerMock->expects($this->exactly(2))->method('getStore')->willReturn($storeMock);
        $storeMock->expects($this->exactly(2))->method('getWebsiteId')->willReturn($websiteId);
        $orderMock->expects($this->exactly(2))->method('getCustomerId')->willReturn($customerId);

        $rewardMock->expects($this->once())->method('loadByCustomer')->willReturnSelf();
        $rewardMock->expects($this->once())->method('getPointsBalance')->willReturn(500);
        $rewardMock->expects($this->once())->method('setActionEntity')->with($orderMock)->willReturnSelf();
        $rewardMock->expects($this->once())->method('save')->willReturnSelf();
        $this->subject->refund($creditmemoMock);
    }

    /**
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testRefundWhenAutoRefundDisabled(): void
    {
        $appliedRuleIds = '1,2,1,1,3,4,3';
        $websiteId = 2;
        $customerId = 10;
        $orderMock = $this->createMock(Order::class);
        $creditmemoMock = $this->getCreditMemoMockForRefund();
        $rewardMock = $this->getRewardMockForRefund();

        $orderMock->expects($this->exactly(2))->method('getAppliedRuleIds')->willReturn($appliedRuleIds);

        $this->rewardPointCounterMock->expects(self::any())
            ->method('getPointsForRules')
            ->with(
                [
                    0 => '1',
                    1 => '2',
                    4 => '3',
                    5 => '4',
                ]
            )
            ->willReturn(100);

        $this->rewardFactoryMock->expects($this->once())->method('create')->willReturn($rewardMock);
        $orderMock->expects($this->once())->method('getStoreId')->willReturn(1);

        $storeMock = $this->createMock(Store::class);
        $this->storeManagerMock->expects($this->once())->method('getStore')->willReturn($storeMock);
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);
        $orderMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);

        $rewardMock->expects($this->once())->method('loadByCustomer')->willReturnSelf();
        $rewardMock->expects($this->once())->method('getPointsBalance')->willReturn(500);
        $creditmemoMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $creditmemoMock->expects($this->exactly(2))->method('getAutomaticallyCreated')->willReturn(true);
        $creditmemoMock->method('getRewardPointsBalance')->willReturn(100);
        $creditmemoMock->expects($this->once())
            ->method('setRewardPointsBalanceRefund')
            ->with(100)->willReturnSelf();

        $creditmemo = $this->createPartialMock(
            Creditmemo::class,
            ['getData', 'getAllItems']
        );

        $creditmemoCollectionMock = $this->objectManager->getCollectionMock(
            Collection::class,
            [$creditmemo]
        );
        $orderMock->expects($this->atLeastOnce())
            ->method('getCreditmemosCollection')
            ->willReturn($creditmemoCollectionMock);

        $itemMock =
            $this->createPartialMock(Item::class, ['getQty']);
        $itemMock->expects($this->atLeastOnce())->method('getQty')->willReturn(3);
        $creditmemo->expects($this->atLeastOnce())->method('getAllItems')->willReturn([$itemMock]);

        $this->rewardHelperMock->expects($this->once())->method('isAutoRefundEnabled')->willReturn(false);
        $this->subject->refund($creditmemoMock);
    }

    /**
     * @return void
     */
    public function testPartialRefund(): void
    {
        $orderMock = $this->createPartialMock(
            Order::class,
            ['getTotalQtyOrdered', 'getCreditmemosCollection', 'getAppliedRuleIds']
        );

        $appliedRuleIds = '1,1';
        $creditmemoMock = $this->getCreditMemoMockForRefund();
        $rewardMock = $this->getRewardMockForRefund();
        $orderMock->expects($this->exactly(2))->method('getAppliedRuleIds')->willReturn($appliedRuleIds);

        /** @var RuleExtensionInterface|MockObject $attributesOneMock */
        $attributesOneMock = $this->getMockBuilder(RuleExtensionInterface::class)
            ->setMethods(['getRewardPointsDelta'])
            ->getMockForAbstractClass();
        $attributesOneMock->expects(self::any())
            ->method('getRewardPointsDelta')
            ->willReturn(100);

        $this->rewardPointCounterMock->expects(self::any())
            ->method('getPointsForRules')
            ->with(
                [
                    0 => '1'
                ]
            )
            ->willReturn(100);

        $this->rewardFactoryMock->expects($this->once())->method('create')->willReturn($rewardMock);

        $storeMock = $this->createMock(Store::class);
        $this->storeManagerMock->expects($this->once())->method('getStore')->willReturn($storeMock);

        $rewardMock->expects($this->once())->method('loadByCustomer')->willReturnSelf();
        $rewardMock->expects($this->once())->method('getPointsBalance')->willReturn(500);
        $creditmemoMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $creditmemoMock->expects($this->exactly(2))->method('getAutomaticallyCreated')->willReturn(true);
        $creditmemoMock->method('getRewardPointsBalance')->willReturn(100);
        $creditmemoMock->expects($this->once())
            ->method('setRewardPointsBalanceRefund')
            ->with(100)->willReturnSelf();

        $this->rewardHelperMock->expects($this->once())->method('isAutoRefundEnabled')->willReturn(true);

        $orderMock->expects($this->once())->method('getTotalQtyOrdered')->willReturn(5);

        $creditmemo = $this->createPartialMock(
            Creditmemo::class,
            ['getData', 'getAllItems']
        );
        $creditmemoCollectionMock = $this->objectManager->getCollectionMock(
            Collection::class,
            [$creditmemo]
        );
        $orderMock->expects($this->atLeastOnce())
            ->method('getCreditmemosCollection')
            ->willReturn($creditmemoCollectionMock);

        $itemMock = $this->createPartialMock(Item::class, ['getQty']);
        $itemMock->expects($this->atLeastOnce())->method('getQty')->willReturn(3);
        $creditmemo->expects($this->atLeastOnce())->method('getAllItems')->willReturn([$itemMock]);

        $this->subject->refund($creditmemoMock);
    }

    /**
     * Mock for using in test cases
     *
     * @return MockObject
     */
    private function getCreditMemoMockForRefund(): MockObject
    {
        return $this->getMockBuilder(Creditmemo::class)
            ->addMethods(['getAutomaticallyCreated', 'getRewardPointsBalance', 'setRewardPointsBalanceRefund'])
            ->onlyMethods(['getOrder'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Mock for using in test cases
     *
     * @return MockObject
     */
    private function getRewardMockForRefund(): MockObject
    {
        return $this->getMockBuilder(Reward::class)
            ->addMethods(['getPointsBalance'])
            ->onlyMethods(['setActionEntity', 'loadByCustomer', 'save', 'getResource'])
            ->disableOriginalConstructor()
            ->getMock();
    }
}
