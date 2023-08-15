<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedSalesRule\Test\Unit\Model\ResourceModel\Plugin\Rule;

use Magento\AdvancedRule\Model\Condition\Filter as FilterModel;
use Magento\AdvancedRule\Model\Condition\FilterTextGeneratorFactory;
use Magento\AdvancedRule\Model\Condition\FilterTextGeneratorInterface;
use Magento\AdvancedSalesRule\Model\ResourceModel\Plugin\Rule\Collection;
use Magento\AdvancedSalesRule\Model\ResourceModel\Rule\Condition\Filter as FilterResource;
use Magento\AdvancedSalesRule\Model\ResourceModel\Rule\Condition\FilterFactory;
use Magento\AdvancedSalesRule\Model\Rule\Condition\FilterTextGenerator\Product\Attribute;
use Magento\AdvancedSalesRule\Model\Rule\Condition\FilterTextGenerator\Product\Category;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Select\SelectRenderer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote\Address;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CollectionTest extends TestCase
{
    /**
     * @var Collection
     */
    protected $model;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var \Magento\SalesRule\Model\ResourceModel\Rule\Collection|MockObject
     */
    protected $ruleCollectionMock;

    /**
     * @var \Closure
     */
    protected $closureMock;

    /**
     * @var Address|MockObject
     */
    protected $addressMock;

    /**
     * @var FilterTextGeneratorFactory|MockObject
     */
    protected $filterTextGeneratorFactoryMock;

    /**
     * @var FilterResource|MockObject
     */
    protected $filterResourceMock;

    /**
     * Setup the test
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->closureMock = function () {
            return $this->ruleCollectionMock;
        };

        $this->ruleCollectionMock = $this->getMockBuilder(\Magento\SalesRule\Model\ResourceModel\Rule\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->addressMock = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->filterResourceMock = $this->getMockBuilder(
            \Magento\AdvancedSalesRule\Model\ResourceModel\Rule\Condition\Filter::class
        )->disableOriginalConstructor()
            ->getMock();

        $filterResourceFactoryMock = $this->getMockBuilder(FilterFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $filterResourceFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->filterResourceMock);

        $this->filterTextGeneratorFactoryMock = $this->getMockBuilder(
            FilterTextGeneratorFactory::class
        )->disableOriginalConstructor()
            ->getMock();

        $this->model = $this->objectManager->getObject(
            Collection::class,
            [
                'filterResourceFactory' => $filterResourceFactoryMock,
                'filterTextGeneratorFactory' => $this->filterTextGeneratorFactoryMock
            ]
        );
    }

    public function testAroundSetValidationFilter(): void
    {
        $websiteId = 1;
        $customerGroupId = 2;
        $filteredRuleIds = [1, 2];

        $connectionMock = $this->getMockBuilder(Mysql::class)
            ->disableOriginalConstructor()
            ->getMock();
        $selectRendererMock = $this->getMockBuilder(SelectRenderer::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var Select|MockObject $selectMock */
        $selectMock = $this->getMockBuilder(Select::class)
            ->setConstructorArgs([$connectionMock, $selectRendererMock])
            ->getMock();

        $this->ruleCollectionMock->expects($this->once())
            ->method('getConnection')
            ->willReturn($connectionMock);
        $this->ruleCollectionMock->expects($this->once())
            ->method('getSelect')
            ->willReturn($selectMock);

        $connectionMock
            ->expects($this->any())
            ->method('quoteInto')
            ->willReturnCallback(
                function ($value) {
                    return "'$value'";
                }
            );

        $this->setupFilterResourceMock($this->addressMock, $filteredRuleIds);

        $result = $this->model->aroundSetValidationFilter(
            $this->ruleCollectionMock,
            $this->closureMock,
            $websiteId,
            $customerGroupId,
            '',
            null,
            $this->addressMock
        );
        $this->assertNotNull($result);
    }

    /**
     * Setup method.
     *
     * @param $addressMock
     * @param $filteredRuleIds
     *
     * @return void
     */
    private function setupFilterResourceMock($addressMock, $filteredRuleIds): void
    {
        $filterTextGeneratorClass1 =
            Attribute::class;
        $filterTextGeneratorData1 = ['attribute' => 'sku'];
        $filterTextGeneratorClass2 =
            Category::class;
        $filterTextGeneratorData2 = [];

        $filterText1 = ['product:attribute:sku:123'];
        $filterText2 = ['product:attribute:sku:123', 'product:category:4'];

        $expectedFilterArray = ['true', 'product:attribute:sku:123', 'product:attribute:sku:123', 'product:category:4'];
        $expectedFilterArray = array_unique($expectedFilterArray);

        $filterTextGenerators = [
            [
                FilterModel::KEY_FILTER_TEXT_GENERATOR_CLASS => $filterTextGeneratorClass1,
                FilterModel::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => json_encode($filterTextGeneratorData1),
            ],
            [
                FilterModel::KEY_FILTER_TEXT_GENERATOR_CLASS => $filterTextGeneratorClass2,
                FilterModel::KEY_FILTER_TEXT_GENERATOR_ARGUMENTS => json_encode($filterTextGeneratorData2),
            ],
        ];

        $this->filterResourceMock->expects($this->once())
            ->method('getFilterTextGenerators')
            ->willReturn($filterTextGenerators);

        $filterTextGenerator1 = $this->createMock(
            FilterTextGeneratorInterface::class
        );
        $filterTextGenerator1->expects($this->once())
            ->method('generateFilterText')
            ->with($addressMock)
            ->willReturn($filterText1);

        $filterTextGenerator2 = $this->createMock(
            FilterTextGeneratorInterface::class
        );
        $filterTextGenerator2->expects($this->once())
            ->method('generateFilterText')
            ->with($addressMock)
            ->willReturn($filterText2);

        $this->filterTextGeneratorFactoryMock
            ->method('create')
            ->withConsecutive(
                [$filterTextGeneratorClass1, ['data' => $filterTextGeneratorData1]],
                [$filterTextGeneratorClass2, ['data' => $filterTextGeneratorData2]]
            )
            ->willReturnOnConsecutiveCalls(
                $filterTextGenerator1,
                $filterTextGenerator2
            );

        $this->filterResourceMock->expects($this->once())
            ->method('filterRules')
            ->with(
                $expectedFilterArray
            )->willReturn($filteredRuleIds);
    }

    public function testAroundSetValidationFilterWithCoupon(): void
    {
        $websiteId = 1;
        $customerGroupId = 2;

        $this->filterResourceMock->expects($this->never())
            ->method('getFilterTextGenerators');

        $result = $this->model->aroundSetValidationFilter(
            $this->ruleCollectionMock,
            $this->closureMock,
            $websiteId,
            $customerGroupId,
            'couponCode',
            null,
            $this->addressMock
        );
        $this->assertEquals($this->ruleCollectionMock, $result);
    }

    public function testAroundSetValidationFilterWithoutQuoteAddress(): void
    {
        $websiteId = 1;
        $customerGroupId = 2;

        $this->filterResourceMock->expects($this->never())
            ->method('getFilterTextGenerators');

        $result = $this->model->aroundSetValidationFilter(
            $this->ruleCollectionMock,
            $this->closureMock,
            $websiteId,
            $customerGroupId,
            '',
            null,
            null
        );
        $this->assertEquals($this->ruleCollectionMock, $result);
    }

    public function testAroundSetValidationFilterSkipValidation(): void
    {
        $websiteId = 1;
        $customerGroupId = 2;

        $this->addressMock->expects($this->once())
            ->method('getData')
            ->with('skip_validation_filter')
            ->willReturn(true);

        $this->filterResourceMock->expects($this->never())
            ->method('getFilterTextGenerators');

        $result = $this->model->aroundSetValidationFilter(
            $this->ruleCollectionMock,
            $this->closureMock,
            $websiteId,
            $customerGroupId,
            '',
            null,
            $this->addressMock
        );
        $this->assertEquals($this->ruleCollectionMock, $result);
    }
}
