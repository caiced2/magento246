<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Model\Plugin;

use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Reward\Helper\Data as RewardData;
use Magento\Reward\Model\Plugin\RewardPointsRefund;
use Magento\Reward\Model\ResourceModel\Reward\History as HistoryResourceModel;
use Magento\Reward\Model\ResourceModel\Reward\History\Collection as HistoryCollection;
use Magento\Reward\Model\ResourceModel\Reward\History\CollectionFactory as HistoryCollectionFactory;
use Magento\Reward\Model\Reward as RewardModel;
use Magento\Reward\Model\Reward\History as RewardHistory;
use Magento\Reward\Model\Reward\Refund\SalesRuleRefund;
use Magento\Reward\Model\RewardFactory;
use Magento\Sales\Model\Order as OrderModel;
use Magento\Sales\Model\Order\Creditmemo as CreditMemoModel;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo as CreditMemoResourceModel;
use Magento\Store\Model\Store as StoreModel;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RewardPointsRefundTest extends TestCase
{
    /**
     * @var RewardData|MockObject
     */
    private $rewardData;

    /**
     * @var RewardFactory|MockObject
     */
    private $rewardFactory;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManager;

    /**
     * @var HistoryCollectionFactory|MockObject
     */
    private $historyCollectionFactory;

    /**
     * @var ManagerInterface|MockObject
     */
    private $eventManager;

    /**
     * @var SalesRuleRefund|MockObject
     */
    private $salesRuleRefund;

    /**
     * @var CreditMemoModel|MockObject
     */
    private $creditMemo;

    /**
     * @var OrderModel|MockObject
     */
    private $order;

    /**
     * @var CreditMemoResourceModel|MockObject
     */
    private $creditMemoResourceModel;

    /**
     * @var RewardHistory|MockObject
     */
    private $rewardHistory;

    /**
     * @var StoreModel|MockObject
     */
    private $store;

    /**
     * @var HistoryCollection|MockObject
     */
    private $historyCollection;

    /**
     * @var RewardModel|MockObject
     */
    private $reward;

    /**
     * @var HistoryResourceModel|MockObject
     */
    private $history;

    /**
     * @var RewardPointsRefund
     */
    private $plugin;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp(): void
    {
        /** @var ObjectManager  */
        $objectManager = new ObjectManager($this);

        $this->rewardData = $this->getMockBuilder(RewardData::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->rewardFactory = $this->getMockBuilder(RewardFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->getMockForAbstractClass();
        $this->historyCollectionFactory = $this->getMockBuilder(HistoryCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->eventManager = $this->getMockBuilder(ManagerInterface::class)
            ->getMockForAbstractClass();
        $this->salesRuleRefund = $this->getMockBuilder(SalesRuleRefund::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->creditMemo = $this->getMockBuilder(CreditMemoModel::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getOrder',
                    'getAutomaticallyCreated',
                    'getBaseRewardCurrencyAmount',
                    'getRewardedAmountAfterRefund',
                    'setRewardedAmountAfterRefund',
                    'getRewardPointsBalanceRefund',
                ]
            )
            ->getMock();
        $this->order = $this->getMockBuilder(OrderModel::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getCustomerId',
                    'getStore',
                    'getIncrementId',
                    'getStoreId',
                    'getBaseGrandTotal',
                    'getBaseTaxAmount',
                    'getBaseShippingAmount',
                    'getBaseTotalRefunded',
                    'getBaseTaxRefunded',
                    'getBaseShippingRefunded',
                ]
            )
            ->getMock();
        $this->creditMemoResourceModel = $this->getMockBuilder(CreditMemoResourceModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->rewardHistory = $this->getMockBuilder(RewardHistory::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'addCustomerFilter',
                    'addWebsiteFilter',
                    'addFilter',
                    'getAdditionalData',
                    'getPointsVoided',
                    'getPointsDelta',
                    'getResource'
                ]
            )
            ->getMock();
        $this->store = $this->getMockBuilder(StoreModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getWebsiteId'])
            ->getMock();
        $this->historyCollection = $this->getMockBuilder(HistoryCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->reward = $this->getMockBuilder(RewardModel::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'setWebsiteId',
                    'setCustomerId',
                    'loadByCustomer',
                    'getPointsBalance',
                    'setPointsDelta',
                    'save'
                ]
            )
            ->getMock();
        $this->history = $this->getMockBuilder(HistoryResourceModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->plugin = $objectManager->getObject(
            RewardPointsRefund::class,
            [
                'historyCollectionFactory' => $this->historyCollectionFactory,
                'storeManager' => $this->storeManager,
                'eventManager' => $this->eventManager,
                'rewardFactory' => $this->rewardFactory,
                'rewardData' => $this->rewardData,
                'salesRuleRefund' => $this->salesRuleRefund
            ]
        );
    }

    /**
     * Data provider for beforeSave and afterSave methods
     *
     * @return array
     */
    public function getCustomerIdDataProvider()
    {
        return
            [
                ['customer_id' => 123],
                ['no_customer_id' => null]
            ];
    }

    /**
     * Test for beforeSave method
     *
     * @return void
     * @dataProvider getCustomerIdDataProvider
     */
    public function testBeforeSave(?int $customerId)
    {

        $this->creditMemo->method('getOrder')->willReturn($this->order);
        $this->order->method('getCustomerId')->willReturn($customerId);

        if ($customerId) {
            $this->rewardFactory->method('create')->willReturn($this->reward);
            $this->reward->method('setCustomerId')->willReturnSelf();
            $this->reward->expects($this->once())
                ->method('setPointsDelta')
                ->with(10)
                ->willReturnSelf();

            $this->creditMemo->method('getAutomaticallyCreated')->willReturn(null);
            $this->creditMemo->method('getBaseRewardCurrencyAmount')->willReturn(10);
            $this->creditMemo->method('getRewardPointsBalanceRefund')->willReturn(10);
        }

        $this->plugin->beforeSave($this->creditMemoResourceModel, $this->creditMemo);
    }

    /**
     * Test for afterSave method
     *
     * @dataProvider getCustomerIdDataProvider
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testAfterSave(?int $customerId)
    {
        $grandTotal = 655;
        $taxAmount = 0;
        $shippingCost = 5;
        $pointsDelta = 120;
        $pointsBalance = 240;
        $incrementId = '000000002';
        $additionalData = [
            'increment_id' => $incrementId,
            'rate' => [
                'points' => '20',
                'currency_amount' => '100.0000',
                'direction' => '2',
                'currency_code' => 'USD'
            ]
        ];
        $this->creditMemo->method('getOrder')
            ->willReturn($this->order);
        $this->order->method('getCustomerId')
            ->willReturn($customerId);

        if ($customerId) {
            $this->historyCollectionFactory->method('create')
                ->willReturn($this->historyCollection);
            $this->historyCollection->method('addCustomerFilter')
                ->willReturnSelf();
            $this->historyCollection->method('addWebsiteFilter')
                ->willReturnSelf();
            $this->historyCollection->method('addFilter')
                ->willReturnSelf();

            $this->order->method('getCustomerId')
                ->willReturn(1);
            $this->order->method('getStore')
                ->willReturn($this->store);
            $this->order->method('getIncrementId')
                ->willReturn($incrementId);
            $this->store->method('getWebsiteId')
                ->willReturn(0);

            $this->historyCollection->method('getIterator')
                ->willReturn(new \ArrayIterator([$this->rewardHistory]));

            $this->rewardHistory->method('getAdditionalData')
                ->willReturn($additionalData);
            $this->salesRuleRefund->method('refund')
                ->willReturnSelf();

            $this->order->method('getBaseGrandTotal')
                ->willReturn($grandTotal);
            $this->order->method('getBaseTaxAmount')
                ->willReturn($taxAmount);
            $this->order->method('getBaseShippingAmount')
                ->willReturn($shippingCost);
            $this->order->method('getBaseTotalRefunded')
                ->willReturn($grandTotal);
            $this->order->method('getBaseTaxRefunded')
                ->willReturn($taxAmount);
            $this->order->method('getBaseShippingRefunded')
                ->willReturn($shippingCost);

            $this->creditMemo->method('setRewardedAmountAfterRefund')
                ->willReturnSelf();
            $this->eventManager->method('dispatch')
                ->willReturnSelf();
            $this->creditMemo->method('getRewardedAmountAfterRefund')
                ->willReturn(0);
            $this->rewardHistory->method('getPointsVoided')
                ->willReturn('0');
            $this->rewardHistory->method('getPointsDelta')
                ->willReturn($pointsDelta);

            $this->rewardFactory->method('create')
                ->willReturn($this->reward);
            $this->storeManager->method('getStore')
                ->willReturn($this->store);
            $this->order->method('getStoreId')
                ->willReturn(1);
            $this->reward->method('setWebsiteId')
                ->willReturnSelf();
            $this->reward->method('setCustomerId')
                ->willReturnSelf();
            $this->reward->method('loadByCustomer')
                ->willReturnSelf();
            $this->reward->method('getPointsBalance')
                ->willReturn($pointsBalance);

            $this->rewardData->method('getGeneralConfig')
                ->willReturn(null);

            $this->rewardHistory->method('getResource')
                ->willReturn($this->history);
            $this->history->method('updateHistoryRow')
                ->willReturnSelf();
        }

        $this->assertSame(
            $this->creditMemoResourceModel,
            $this->plugin->afterSave($this->creditMemoResourceModel, $this->creditMemoResourceModel, $this->creditMemo)
        );
    }
}
