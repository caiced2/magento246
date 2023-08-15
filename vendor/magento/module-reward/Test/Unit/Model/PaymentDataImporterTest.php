<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Model;

use Magento\Customer\Model\Data\Customer;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\App\Config;
use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote;
use Magento\Reward\Model\PaymentDataImporter;
use Magento\Reward\Model\Reward;
use Magento\Reward\Model\RewardFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PaymentDataImporterTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $rewardFactoryMock;

    /**
     * @var MockObject
     */
    protected $scopeConfigMock;

    /**
     * @var MockObject
     */
    protected $objectConverterMock;

    /**
     * @var PaymentDataImporter
     */
    protected $model;

    protected function setUp(): void
    {
        $this->rewardFactoryMock = $this->getMockBuilder(RewardFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->scopeConfigMock = $this->createMock(Config::class);
        $this->objectConverterMock = $this->createMock(ExtensibleDataObjectConverter::class);
        $this->model = new PaymentDataImporter(
            $this->rewardFactoryMock,
            $this->scopeConfigMock,
            $this->objectConverterMock
        );
    }

    public function testImport()
    {
        $baseGrandTotal = 42;
        $rewardCurrencyAmount = 74;
        $useRewardPoints = true;
        $customerId = 24;
        $websiteId = 18;
        $minPointsBalance = 100;
        $storeId = 94;
        $rewardId = 88;
        $pointsBalance = 128;
        $customerMock = $this->createMock(Customer::class);
        $storeMock = $this->createMock(Store::class);
        $paymentMock = $this->getMockBuilder(DataObject::class)
            ->addMethods(['getMethod', 'setMethod'])
            ->disableOriginalConstructor()
            ->getMock();
        $rewardMock = $this->getMockBuilder(Reward::class)
            ->addMethods(['getPointsBalance', 'setWebsiteId'])
            ->onlyMethods(['getId', 'setCustomer', 'loadByCustomer'])
            ->disableOriginalConstructor()
            ->getMock();
        $quoteMock = $this->getMockBuilder(Quote::class)
            ->addMethods(
                [
                    'setUseRewardPoints',
                    'getBaseRewardCurrencyAmount',
                    'getUseRewardPoints',
                    'getCustomerId',
                    'getBaseGrandTotal',
                    'setRewardInstance'
                ]
            )
            ->onlyMethods(['getCustomer', 'getStore', 'getStoreId'])
            ->disableOriginalConstructor()
            ->getMock();

        $quoteMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $quoteMock->expects($this->once())->method('getBaseGrandTotal')->willReturn($baseGrandTotal);
        $quoteMock->expects($this->once())->method('getBaseRewardCurrencyAmount')->willReturn($rewardCurrencyAmount);
        $quoteMock->expects($this->once())->method('setUseRewardPoints')->with($useRewardPoints);
        $quoteMock->expects($this->once())->method('getUseRewardPoints')->willReturn($useRewardPoints);
        $quoteMock->expects($this->once())->method('getCustomer')->willReturn($customerMock);
        $this->rewardFactoryMock->expects($this->once())->method('create')->willReturn($rewardMock);
        $rewardMock->expects($this->once())->method('setCustomer')->with($customerMock)->willReturnSelf();
        $quoteMock->expects($this->once())->method('getStore')->willReturn($storeMock);
        $storeMock->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);
        $rewardMock->expects($this->once())->method('setWebsiteId')->with($websiteId);
        $rewardMock->expects($this->once())->method('loadByCustomer');
        $quoteMock->expects($this->once())->method('getStoreId')->willReturn($storeId);
        $this->scopeConfigMock->expects($this->once())->method('getValue')->with(
            Reward::XML_PATH_MIN_POINTS_BALANCE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        )->willReturn($minPointsBalance);
        $rewardMock->expects($this->once())->method('getId')->willReturn($rewardId);
        $rewardMock->expects($this->once())->method('getPointsBalance')->willReturn($pointsBalance);
        $quoteMock->expects($this->once())->method('setRewardInstance')->with($rewardMock);
        $paymentMock->expects($this->once())->method('getMethod')->willReturn(null);
        $paymentMock->expects($this->once())->method('setMethod')->with('free');

        $this->assertEquals($this->model, $this->model->import($quoteMock, $paymentMock, $useRewardPoints));
    }

    public function testImportNotUsingRewardPoints()
    {
        $baseGrandTotal = 42;
        $rewardCurrencyAmount = 74;
        $useRewardPoints = false;
        $customerId = 24;
        $paymentMock = $this->createMock(DataObject::class);
        $quoteMock = $this->getMockBuilder(Quote::class)
            ->addMethods(
                [
                    'setUseRewardPoints',
                    'getBaseRewardCurrencyAmount',
                    'getUseRewardPoints',
                    'getCustomerId',
                    'getBaseGrandTotal'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $quoteMock->expects($this->once())->method('getCustomerId')->willReturn($customerId);
        $quoteMock->expects($this->once())->method('getBaseGrandTotal')->willReturn($baseGrandTotal);
        $quoteMock->expects($this->once())->method('getBaseRewardCurrencyAmount')->willReturn($rewardCurrencyAmount);
        $quoteMock->expects($this->once())->method('setUseRewardPoints')->with($useRewardPoints);
        $quoteMock->expects($this->once())->method('getUseRewardPoints')->willReturn($useRewardPoints);
        $this->rewardFactoryMock->expects($this->never())->method('create');

        $this->assertEquals($this->model, $this->model->import($quoteMock, $paymentMock, $useRewardPoints));
    }

    public function testImportWithInvalidParameters()
    {
        $quoteMock = $this->getMockBuilder(Quote::class)
            ->addMethods(['setUseRewardPoints'])
            ->disableOriginalConstructor()
            ->getMock();
        $quoteMock->expects($this->never())->method('setUseRewardPoints');
        $this->assertEquals($this->model, $this->model->import(null, null, null));
    }
}
