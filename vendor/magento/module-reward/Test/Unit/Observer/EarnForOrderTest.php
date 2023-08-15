<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Observer;

use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Reward\Helper\Data;
use Magento\Reward\Model\Reward;
use Magento\Reward\Model\RewardFactory;
use Magento\Reward\Model\SalesRule\RewardPointCounter;
use Magento\Reward\Observer\EarnForOrder;
use Magento\Reward\Observer\PlaceOrder\RestrictionInterface;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Status\History;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EarnForOrderTest extends TestCase
{
    /**
     * @var EarnForOrder
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
    protected $_storeManagerMock;

    /**
     * @var MockObject
     */
    protected $_observerMock;

    /**
     * @var MockObject
     */
    protected $rewardHelperMock;

    /**
     * @var RewardPointCounter|MockObject
     */
    private $rewardPointCounterMock;

    /**
     * @var OrderStatusHistoryRepositoryInterface|MockObject
     */
    private $histiryRepositoryMock;

    protected function setUp(): void
    {
        $this->_restrictionMock = $this->getMockForAbstractClass(RestrictionInterface::class);
        $this->_storeManagerMock = $this->createMock(StoreManager::class);
        $this->rewardHelperMock = $this->createMock(Data::class);
        $this->_modelFactoryMock = $this->createPartialMock(RewardFactory::class, ['create']);

        $this->_observerMock = $this->createMock(Observer::class);

        $this->rewardPointCounterMock = $this->getMockBuilder(RewardPointCounter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->histiryRepositoryMock = $this->getMockForAbstractClass(OrderStatusHistoryRepositoryInterface::class);

        $this->_model = new EarnForOrder(
            $this->_restrictionMock,
            $this->_storeManagerMock,
            $this->_modelFactoryMock,
            $this->rewardHelperMock,
            $this->rewardPointCounterMock,
            $this->histiryRepositoryMock
        );
    }

    public function testEarnForOrderRestricted()
    {
        $this->_restrictionMock->expects($this->once())->method('isAllowed')->willReturn(false);
        $this->_observerMock->expects($this->never())->method('getEvent');

        $this->_model->execute($this->_observerMock);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testEarnForOrder()
    {
        $apliedRuleIds = '1,2,1,1,3,4,3';
        $pointsDelta = 30;
        $customerId = 42;
        $websiteId = 1;
        $historyEntry = __('Customer earned promotion extra %1.', $pointsDelta);

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getOrder'])
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock = $this->createMock(Order::class);
        $storeMock = $this->createMock(Store::class);
        $historyMock = $this->createPartialMock(History::class, ['save']);
        $rewardModelMock = $this->getMockBuilder(Reward::class)
            ->addMethods(['setCustomerId', 'setWebsiteId', 'setPointsDelta', 'setAction'])
            ->onlyMethods(['setActionEntity', 'updateRewardPoints'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->_observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);
        $eventMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getAppliedRuleIds')->willReturn($apliedRuleIds);

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
            ->willReturn($pointsDelta);

        $this->_modelFactoryMock->expects($this->once())->method('create')->willReturn($rewardModelMock);
        $orderMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $rewardModelMock->expects($this->once())->method('setCustomerId')->with($customerId)->willReturnSelf();
        $this->_storeManagerMock->expects($this->once())->method('getStore')->willReturn($storeMock);
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);
        $rewardModelMock->expects($this->once())->method('setWebsiteId')->with($websiteId)->willReturnSelf();
        $rewardModelMock->expects($this->once())->method('setPointsDelta')->with($pointsDelta)->willReturnSelf();
        $rewardModelMock->expects($this->once())->method('setAction')->with(Reward::REWARD_ACTION_SALESRULE)
            ->willReturnSelf();
        $rewardModelMock->expects($this->once())->method('setActionEntity')->with($orderMock)->willReturnSelf();
        $rewardModelMock->expects($this->once())->method('updateRewardPoints');

        $this->rewardHelperMock->expects($this->once())->method('formatReward')->with($pointsDelta)
            ->willReturn($pointsDelta);
        $orderMock->expects($this->once())->method('addCommentToStatusHistory')->with($historyEntry)
            ->willReturn($historyMock);

        $this->_model->execute($this->_observerMock);
    }

    /**
     * @param string|null $appliedRuleIds
     *
     * @dataProvider getAppliedRuleIdsDataProvider
     */
    public function testEarnForOrderWithNoSalesRule(?string $appliedRuleIds)
    {
        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getOrder'])
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock = $this->createMock(Order::class);

        $this->_observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);
        $eventMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getAppliedRuleIds')->willReturn($appliedRuleIds);

        $this->_modelFactoryMock->expects($this->never())->method('create');

        $this->_model->execute($this->_observerMock);
    }

    /**
     * @return array
     */
    public function getAppliedRuleIdsDataProvider(): array
    {
        return [
            [
                ''
            ],
            [
                null
            ]
        ];
    }
}
