<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Ui\DataProvider\Catalog\Product\Form\Modifier;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductAttributeGroupRepositoryInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Attribute\ScopeOverriddenValue;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory as EavAttributeFactory;
use Magento\Catalog\Ui\DataProvider\CatalogEavValidationRules;
use Magento\CatalogStaging\Ui\DataProvider\Catalog\Product\Form\Modifier\Eav;
use Magento\Eav\Api\Data\AttributeGroupSearchResultsInterface;
use Magento\Eav\Model\Attribute;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory as GroupCollectionFactory;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Filter\Translit;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\Mapper\FormElement as FormElementMapper;
use Magento\Ui\DataProvider\Mapper\MetaProperties as MetaPropertiesMapper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EavTest extends TestCase
{
    /**
     * @var Eav
     */
    protected $modifier;

    /**
     * @var MockObject
     */
    protected $locatorMock;

    /**
     * @var MockObject
     */
    protected $validationRulesMock;

    /**
     * @var MockObject
     */
    protected $eavConfigMock;

    /**
     * @var MockObject
     */
    protected $requestMock;

    /**
     * @var MockObject
     */
    protected $groupFactoryMock;

    /**
     * @var MockObject
     */
    protected $storeManagerMock;

    /**
     * @var MockObject
     */
    protected $formMapperMock;

    /**
     * @var MockObject
     */
    protected $metaPropertiesMapperMock;

    /**
     * @var MockObject
     */
    protected $attributeGroupRepositoryMock;

    /**
     * @var MockObject
     */
    protected $attributeRepositoryMock;

    /**
     * @var MockObject
     */
    protected $searchCriteriaBuilderMock;

    /**
     * @var MockObject
     */
    protected $sortOrderBuilderMock;

    /**
     * @var MockObject
     */
    protected $eavAttributeFactoryMock;

    /**
     * @var MockObject
     */
    protected $translitFilterMock;

    /**
     * @var MockObject
     */
    protected $arrayManagerMock;

    /**
     * @var MockObject
     */
    protected $scopeOverriddenValueMock;

    /**
     * @var MockObject
     */
    protected $dataPersistorMock;

    protected function setUp(): void
    {
        $this->locatorMock = $this->getMockBuilder(LocatorInterface::class)
            ->getMock();
        $this->validationRulesMock = $this->getMockBuilder(CatalogEavValidationRules::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->eavConfigMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->getMock();
        $this->groupFactoryMock = $this->getMockBuilder(GroupCollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->getMock();
        $this->formMapperMock = $this->getMockBuilder(FormElementMapper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->metaPropertiesMapperMock = $this->getMockBuilder(MetaPropertiesMapper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->attributeGroupRepositoryMock = $this->getMockBuilder(ProductAttributeGroupRepositoryInterface::class)
            ->getMock();
        $this->attributeRepositoryMock = $this->getMockBuilder(ProductAttributeRepositoryInterface::class)
            ->getMock();
        $this->searchCriteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sortOrderBuilderMock = $this->getMockBuilder(SortOrderBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->eavAttributeFactoryMock = $this->getMockBuilder(EavAttributeFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->translitFilterMock = $this->getMockBuilder(Translit::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->arrayManagerMock = $this->getMockBuilder(ArrayManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->scopeOverriddenValueMock = $this->getMockBuilder(ScopeOverriddenValue::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataPersistorMock = $this->getMockBuilder(DataPersistorInterface::class)
            ->getMock();

        $this->modifier = new Eav(
            $this->locatorMock,
            $this->validationRulesMock,
            $this->eavConfigMock,
            $this->requestMock,
            $this->groupFactoryMock,
            $this->storeManagerMock,
            $this->formMapperMock,
            $this->metaPropertiesMapperMock,
            $this->attributeGroupRepositoryMock,
            $this->attributeRepositoryMock,
            $this->searchCriteriaBuilderMock,
            $this->sortOrderBuilderMock,
            $this->eavAttributeFactoryMock,
            $this->translitFilterMock,
            $this->arrayManagerMock,
            $this->scopeOverriddenValueMock,
            $this->dataPersistorMock
        );
    }

    /**
     * Checks the configuration array returned by modifyMeta() method
     *
     * Checks that 'product-details' array is exists.
     * Checks that 'prefer' item with 'toggle' value is exists.
     */
    public function testModifyMeta()
    {
        $meta = [1, 2, 3];
        $this->getGroupsMock();
        $productMock = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->locatorMock->expects($this->atLeastOnce())->method('getProduct')->willReturn($productMock);
        $productMock->expects($this->once())->method('getId')->willReturn(1);

        $attributeMock = $this->getMockBuilder(Attribute::class)
            ->onlyMethods(['getId'])
            ->addMethods(['isScopeGlobal', 'isScopeWebsite', 'isScopeStore'])
            ->disableOriginalConstructor()
            ->getMock();
        $attributeMock->method('getId')->willReturn(1);
        $attributeMock->method('isScopeGlobal')->willReturn(false);
        $attributeMock->method('isScopeWebsite')->willReturn(true);
        $attributeMock->method('isScopeStore')->willReturn(false);
        $this->attributeRepositoryMock->method('get')
            ->with('news_from_date')->willReturn($attributeMock);

        $result = $this->modifier->modifyMeta($meta);
        $this->assertArrayHasKey('product-details', $result);

        $this->assertArrayHasKey(
            'prefer',
            $result['product-details']['children']['container_is_product_new']['children']['is_new']
            ['arguments']['data']['config']
        );
        $this->assertEquals(
            'toggle',
            $result['product-details']['children']['container_is_product_new']['children']['is_new']
            ['arguments']['data']['config']['prefer']
        );
    }

    public function testModifyData()
    {
        $productId = 100;
        $meta = [1, 2, $productId => ['product' => ['news_from_date' => 1]]];
        $productMock = $this->getMockBuilder(ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->locatorMock->expects($this->atLeastOnce())->method('getProduct')->willReturn($productMock);
        $this->getGroupsMock();
        $productMock->expects($this->atLeastOnce())->method('getId')->willReturn($productId);
        $result = $this->modifier->modifyData($meta);
        $this->assertEquals(1, $result[$productId]['product']['is_new']);
    }

    private function getGroupsMock()
    {
        $this->searchCriteriaBuilderMock->expects($this->atLeastOnce())->method('addFilter')->willReturnSelf();
        $searchCriteriaMock = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilderMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($searchCriteriaMock);
        $listMock = $this->getMockBuilder(AttributeGroupSearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->attributeGroupRepositoryMock->expects($this->once())
            ->method('getList')
            ->willReturn($listMock);
        $listMock->expects($this->atLeastOnce())->method('getItems')->willReturn([]);
    }
}
