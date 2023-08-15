<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogRuleStaging\Test\Unit\Pricing\Price;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\CatalogRule\Model\ResourceModel\Rule as RuleResourceModel;
use Magento\CatalogRule\Model\ResourceModel\Rule\Collection as RuleCollection;
use Magento\CatalogRule\Model\ResourceModel\Rule\CollectionFactory;
use Magento\CatalogRule\Model\Rule as RuleModel;
use Magento\CatalogRuleStaging\Pricing\Price\CatalogRulePrice;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Pricing\Adjustment\Calculator;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Pricing\PriceInfo\Base;
use Magento\Framework\Pricing\PriceInfoInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Staging\Model\VersionManager;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Magento\CatalogRuleStaging\Pricing\Price\CatalogRulePrice
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CatalogRulePriceTest extends TestCase
{
    /**
     * @var CatalogRulePrice
     */
    private $price;

    /**
     * @var Product|MockObject
     */
    private $saleableItemMock;

    /**
     * @var TimezoneInterface|MockObject
     */
    private $dataTimeMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var Session|MockObject
     */
    private $customerSessionMock;

    /**
     * @var Base|MockObject
     */
    private $priceInfoMock;

    /**
     * @var RuleResourceModel|MockObject
     */
    private $catalogRuleResourceMock;

    /**
     * @var StoreInterface|MockObject
     */
    private $coreStoreMock;

    /**
     * @var Calculator|MockObject
     */
    private $calculator;

    /**
     * @var PriceCurrencyInterface|MockObject
     */
    private $priceCurrencyMock;

    /**
     * @var CollectionFactory|MockObject
     */
    private $collectionFactory;

    /**
     * @var VersionManager|MockObject
     */
    private $versionManagerMock;

    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $catalogRepositoryMock;

    /**
     * @var MetadataPool|MockObject
     */
    private $metadataPoolMock;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilderMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->saleableItemMock = $this->getMockBuilder(Product::class)
            ->addMethods(['getParentId'])
            ->onlyMethods(['getPrice', 'getId', 'getPriceInfo'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataTimeMock = $this->getMockForAbstractClass(TimezoneInterface::class);
        $this->coreStoreMock = $this->getMockForAbstractClass(StoreInterface::class);
        $this->storeManagerMock = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $this->storeManagerMock->method('getStore')
            ->willReturn($this->coreStoreMock);
        $this->customerSessionMock = $this->createMock(Session::class);
        $this->priceInfoMock = $this->getMockForAbstractClass(PriceInfoInterface::class);
        $this->catalogRuleResourceMock = $this->createMock(RuleResourceModel::class);
        $this->priceInfoMock->expects($this->any())
            ->method('getAdjustments')
            ->willReturn([]);
        $this->saleableItemMock->expects($this->any())
            ->method('getPriceInfo')
            ->willReturn($this->priceInfoMock);
        $this->calculator = $this->createMock(Calculator::class);
        $qty = 1;
        $this->priceCurrencyMock = $this->getMockForAbstractClass(PriceCurrencyInterface::class);
        $this->collectionFactory = $this->createPartialMock(
            CollectionFactory::class,
            ['create']
        );
        $this->versionManagerMock = $this->createMock(VersionManager::class);
        $this->catalogRepositoryMock = $this->getMockForAbstractClass(ProductRepositoryInterface::class);
        $this->searchCriteriaBuilderMock = $this->createMock(SearchCriteriaBuilder::class);
        $this->metadataPoolMock = $this->createMock(MetadataPool::class);

        $this->price = new CatalogRulePrice(
            $this->saleableItemMock,
            $qty,
            $this->calculator,
            $this->priceCurrencyMock,
            $this->dataTimeMock,
            $this->storeManagerMock,
            $this->customerSessionMock,
            $this->catalogRuleResourceMock,
            $this->collectionFactory,
            $this->versionManagerMock,
            $this->catalogRepositoryMock,
            $this->searchCriteriaBuilderMock,
            $this->metadataPoolMock
        );
    }

    /**
     * @dataProvider getValueDataProvider
     * @param string $simpleAction
     * @param float $expectedPrice
     * @return void
     */
    public function testGetValue(string $simpleAction, float $expectedPrice)
    {
        $price = 10;
        $websiteId = 1;
        $customerGroupId = 2;
        $ruleCollection =
            $this->createMock(RuleCollection::class);
        $ruleMock = $this->createMock(RuleModel::class);
        $this->versionManagerMock->expects($this->once())->method('isPreviewVersion')->willReturn(true);
        $this->saleableItemMock->expects($this->once())->method('getPrice')->willReturn($price);
        $this->coreStoreMock->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);
        $this->customerSessionMock->expects($this->once())->method('getCustomerGroupId')->willReturn($customerGroupId);
        $this->collectionFactory->expects($this->once())->method('create')->willReturn($ruleCollection);
        $ruleCollection->expects($this->once())->method('addWebsiteFilter')->with($websiteId)->willReturnSelf();
        $this->saleableItemMock->expects($this->once())->method('getParentId')->willReturn(false);
        $this->catalogRepositoryMock->expects($this->never())->method('getById');
        $ruleCollection
            ->expects($this->once())
            ->method('addCustomerGroupFilter')
            ->with($customerGroupId)
            ->willReturnSelf();
        $ruleCollection->expects($this->once())->method('addFieldToFilter')->with('is_active', 1)->willReturnSelf();
        $ruleCollection
            ->expects($this->once())
            ->method('setOrder')
            ->with('sort_order', \Magento\Framework\Data\Collection::SORT_ORDER_ASC)
            ->willReturn([$ruleMock]);
        $ruleMock->expects($this->once())->method('validate')->with($this->saleableItemMock)->willReturn(true);
        $ruleMock->expects($this->once())->method('getSimpleAction')->willReturn($simpleAction);
        $ruleMock->expects($this->once())->method('getDiscountAmount')->willReturn(5);
        $this->priceCurrencyMock->expects($this->once())->method('round')->willReturn($expectedPrice);
        $ruleMock->expects($this->once())->method('getStopRulesProcessing')->willReturn(true);
        $this->assertEquals($expectedPrice, $this->price->getValue());
    }

    /**
     * @return array
     */
    public function getValueDataProvider(): array
    {
        return [
            ['to_fixed', 5],
            ['to_percent', 0.5],
            ['by_fixed', 5],
            ['by_percent', 9.5]
        ];
    }

    /**
     * @return void
     */
    public function testGetValueForDefaultPrice()
    {
        $price = 10;
        $websiteId = 1;
        $customerGroupId = 2;
        $expectedPrice = 0;
        $ruleCollection =
            $this->createMock(RuleCollection::class);
        $ruleMock = $this->createMock(RuleModel::class);
        $this->versionManagerMock->expects($this->once())->method('isPreviewVersion')->willReturn(true);
        $this->saleableItemMock->expects($this->once())->method('getPrice')->willReturn($price);
        $this->coreStoreMock->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);
        $this->customerSessionMock->expects($this->once())->method('getCustomerGroupId')->willReturn($customerGroupId);
        $this->collectionFactory->expects($this->once())->method('create')->willReturn($ruleCollection);
        $ruleCollection->expects($this->once())->method('addWebsiteFilter')->with($websiteId)->willReturnSelf();
        $ruleCollection
            ->expects($this->once())
            ->method('addCustomerGroupFilter')
            ->with($customerGroupId)
            ->willReturnSelf();
        $ruleCollection->expects($this->once())->method('addFieldToFilter')->with('is_active', 1)->willReturnSelf();
        $ruleCollection
            ->expects($this->once())
            ->method('setOrder')
            ->with('sort_order', \Magento\Framework\Data\Collection::SORT_ORDER_ASC)
            ->willReturn([$ruleMock]);
        $ruleMock->expects($this->once())->method('validate')->with($this->saleableItemMock)->willReturn(true);
        $ruleMock->expects($this->once())->method('getSimpleAction')->willReturn('default');
        $ruleMock->expects($this->never())->method('getDiscountAmount');
        $this->priceCurrencyMock->expects($this->once())->method('round')->willReturn($expectedPrice);
        $ruleMock->expects($this->once())->method('getStopRulesProcessing')->willReturn(false);
        $this->assertEquals($expectedPrice, $this->price->getValue());
    }

    /**
     * @return void
     */
    public function testGetValueForInvalidProduct()
    {
        $price = 10;
        $websiteId = 1;
        $customerGroupId = 2;
        $ruleCollection =
            $this->createMock(RuleCollection::class);
        $ruleMock = $this->createMock(RuleModel::class);
        $this->versionManagerMock->expects($this->once())->method('isPreviewVersion')->willReturn(true);
        $this->saleableItemMock->expects($this->once())->method('getPrice')->willReturn($price);
        $this->coreStoreMock->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);
        $this->customerSessionMock->expects($this->once())->method('getCustomerGroupId')->willReturn($customerGroupId);
        $this->collectionFactory->expects($this->once())->method('create')->willReturn($ruleCollection);
        $ruleCollection->expects($this->once())->method('addWebsiteFilter')->with($websiteId)->willReturnSelf();
        $ruleCollection
            ->expects($this->once())
            ->method('addCustomerGroupFilter')
            ->with($customerGroupId)
            ->willReturnSelf();
        $ruleCollection->expects($this->once())->method('addFieldToFilter')->with('is_active', 1)->willReturnSelf();
        $ruleCollection
            ->expects($this->once())
            ->method('setOrder')
            ->with('sort_order', \Magento\Framework\Data\Collection::SORT_ORDER_ASC)
            ->willReturn([$ruleMock]);
        $ruleMock->expects($this->once())->method('validate')->with($this->saleableItemMock)->willReturn(false);
        $ruleMock->expects($this->never())->method('getSimpleAction')->willReturn('default');
        $ruleMock->expects($this->never())->method('getDiscountAmount');
        $this->priceCurrencyMock->expects($this->never())->method('round');
        $ruleMock->expects($this->never())->method('getStopRulesProcessing');
        $this->assertEquals($price, $this->price->getValue());
    }

    /**
     * @return void
     */
    public function testGetValueForConfigurableProduct()
    {
        $price = 10;
        $websiteId = 1;
        $customerGroupId = 2;
        $parentId = 2;
        $ruleCollection =
            $this->createMock(RuleCollection::class);
        $saleableItem = $this->getMockBuilder(Product::class)
            ->addMethods(['getParentId'])
            ->onlyMethods(['getPrice', 'getId', 'getPriceInfo'])
            ->disableOriginalConstructor()
            ->getMock();
        $ruleMock = $this->createMock(RuleModel::class);
        $this->versionManagerMock->expects($this->once())->method('isPreviewVersion')->willReturn(true);
        $this->saleableItemMock->expects($this->once())->method('getPrice')->willReturn($price);
        $this->coreStoreMock->expects($this->once())->method('getWebsiteId')->willReturn($websiteId);
        $this->customerSessionMock->expects($this->once())->method('getCustomerGroupId')->willReturn($customerGroupId);
        $this->collectionFactory->expects($this->once())->method('create')->willReturn($ruleCollection);
        $ruleCollection->expects($this->once())->method('addWebsiteFilter')->with($websiteId)->willReturnSelf();
        $this->saleableItemMock->expects($this->exactly(2))->method('getParentId')->willReturn($parentId);
        $metadataMock = $this->getMockForAbstractClass(EntityMetadataInterface::class);
        $metadataMock->expects($this->once())
            ->method('getLinkField')
            ->willReturn('row_id');
        $this->metadataPoolMock->expects($this->once())
            ->method('getMetadata')
            ->with(ProductInterface::class)
            ->willReturn($metadataMock);
        $searchCriteriaMock = $this->createMock(SearchCriteria::class);
        $productResultMock = $this->getMockForAbstractClass(ProductSearchResultsInterface::class);
        $this->searchCriteriaBuilderMock
            ->expects($this->once())
            ->method('addFilter')
            ->with('row_id', $parentId)
            ->willReturn($this->searchCriteriaBuilderMock);
        $this->searchCriteriaBuilderMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteriaMock);
        $this->catalogRepositoryMock
            ->expects($this->once())
            ->method('getList')
            ->with($searchCriteriaMock)
            ->willReturn($productResultMock);
        $productResultMock
            ->expects($this->once())
            ->method('getItems')
            ->willReturn([$saleableItem]);

        $ruleCollection
            ->expects($this->once())
            ->method('addCustomerGroupFilter')
            ->with($customerGroupId)
            ->willReturnSelf();
        $ruleCollection->expects($this->once())->method('addFieldToFilter')->with('is_active', 1)->willReturnSelf();
        $ruleCollection
            ->expects($this->once())
            ->method('setOrder')
            ->with('sort_order', \Magento\Framework\Data\Collection::SORT_ORDER_ASC)
            ->willReturn([$ruleMock]);
        $ruleMock->expects($this->once())->method('validate')->with($saleableItem)->willReturn(true);
        $ruleMock->expects($this->once())->method('getSimpleAction')->willReturn('to_fixed');
        $ruleMock->expects($this->once())->method('getDiscountAmount')->willReturn(5);
        $this->priceCurrencyMock->expects($this->once())->method('round')->willReturn(5);
        $ruleMock->expects($this->once())->method('getStopRulesProcessing')->willReturn(true);
        $this->assertEquals(5, $this->price->getValue());
    }
}
