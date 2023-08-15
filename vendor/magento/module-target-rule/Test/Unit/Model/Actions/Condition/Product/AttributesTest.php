<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Model\Actions\Condition\Product;

use Magento\Backend\Helper\Data;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection;
use Magento\Framework\DB\Select;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Rule\Block\Editable;
use Magento\Rule\Model\Condition\Context;
use Magento\TargetRule\Model\Actions\Condition\Product\Attributes;
use Magento\TargetRule\Model\Actions\Condition\Product\Attributes\SqlBuilder;
use Magento\TargetRule\Model\Index;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class AttributesTest extends TestCase
{
    /**
     * Tested model
     *
     * @var Attributes
     */
    protected $attributes;

    /**
     * Object manager helper
     *
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * Context mock
     *
     * @var \Magento\Rule\Model\Condition\Context|MockObject
     */
    protected $contextMock;

    /**
     * Backend helper mock
     *
     * @var Data|MockObject
     */
    protected $backendHelperMock;

    /**
     * Config mock
     *
     * @var \Magento\Eav\Model\Config|MockObject
     */
    protected $configMock;

    /**
     * Product mock
     *
     * @var \Magento\Catalog\Model\Product|MockObject
     */
    protected $productMock;

    /**
     * Product resource model mock
     *
     * @var \Magento\Catalog\Model\ResourceModel\Product|MockObject
     */
    protected $resourceProductMock;

    /**
     * Attribute set collection mock
     *
     * @var Collection|MockObject
     */
    protected $collectionMock;

    /**
     * Locale format mock
     *
     * @var FormatInterface|MockObject
     */
    protected $formatInterfaceMock;

    /**
     * Editable block mock
     *
     * @var \Magento\Rule\Block\Editable|MockObject
     */
    protected $editableMock;

    /**
     * Product Type mock
     *
     * @var \Magento\Catalog\Model\Product\Type|MockObject
     */
    protected $typeMock;

    /**
     * Index mock
     *
     * @var Index|MockObject
     */
    private $indexMock;

    /**
     * EAV Attribute mock
     *
     * @var Select|MockObject
     */
    private $selectMock;

    /**
     * @var SqlBuilder|MockObject
     */
    private $sqlBuilderMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->backendHelperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionMock =
            $this->getMockBuilder(Collection::class)
                ->disableOriginalConstructor()
                ->getMock();
        $this->formatInterfaceMock = $this->getMockBuilder(FormatInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->editableMock = $this->getMockBuilder(Editable::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->typeMock = $this->getMockBuilder(Type::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceProductMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceProductMock->expects($this->any())->method('loadAllAttributes')->willReturnSelf();
        $this->resourceProductMock->expects($this->any())->method('getAttributesByCode')->willReturnSelf();
        $this->indexMock = $this->getMockBuilder(Index::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getTable',
                'bindArrayOfIds',
                'getOperatorCondition',
                'getOperatorBindCondition',
                'getResource',
                'select',
                'getConnection',
                'getStoreId'
            ])
            ->getMock();
        $this->selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->setMethods(['from', 'assemble', 'where', 'joinLeft'])
            ->getMock();
        $this->sqlBuilderMock = $this->getMockBuilder(SqlBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['generateWhereClause'])
            ->getMock();
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->attributes = $this->objectManagerHelper->getObject(
            Attributes::class,
            [
                'context' => $this->contextMock,
                'backendData' => $this->backendHelperMock,
                'config' => $this->configMock,
                'product' => $this->productMock,
                'productResource' => $this->resourceProductMock,
                'attrSetCollection' => $this->collectionMock,
                'localeFormat' => $this->formatInterfaceMock,
                'editable' => $this->editableMock,
                'type' => $this->typeMock,
                [],
                'sqlBuilder' => $this->sqlBuilderMock
            ]
        );
    }

    /**
     * Test get conditions for collection
     *
     * @return void
     */
    public function testGetConditionForCollection()
    {
        $collection = null;
        $bind = [];
        $expectedWhereClause = 'generated where clause';
        $storeId = 1;

        $this->indexMock->expects($this->any())->method('select')->willReturn($this->selectMock);
        $this->indexMock->expects($this->any())->method('getStoreId')->willReturn($storeId);
        $this->sqlBuilderMock->expects($this->once())
            ->method('generateWhereClause')
            ->with($this->attributes, $bind, $storeId, $this->selectMock)
            ->willReturn($expectedWhereClause);

        $result = $this->attributes->getConditionForCollection($collection, $this->indexMock, $bind);
        $this->assertEquals($expectedWhereClause, $result);
        $this->assertEquals($bind, []);
    }
}
