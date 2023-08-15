<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedSalesRule\Test\Unit\Model\Rule\Condition;

use Magento\AdvancedRule\Model\Condition\FilterableConditionInterface;
use Magento\AdvancedRule\Model\Condition\FilterGroupInterface;
use Magento\AdvancedSalesRule\Model\Rule\Condition\ConcreteCondition\Product\Factory;
use Magento\AdvancedSalesRule\Model\Rule\Condition\Product;
use Magento\Backend\Helper\Data;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\AbstractEntity;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rule\Model\Condition\Context;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProductTest extends TestCase
{
    /**
     * @var Product
     */
    protected $model;

    /**
     * @var Context|MockObject
     */
    protected $context;

    /**
     * @var Data|MockObject
     */
    protected $backendData;

    /**
     * @var Config|MockObject
     */
    protected $config;

    /**
     * @var ProductFactory|MockObject
     */
    protected $productFactory;

    /**
     * @var ProductRepositoryInterface|MockObject
     */
    protected $productRepository;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product|MockObject
     */
    protected $productResource;

    /**
     * @var Collection|MockObject
     */
    protected $attrSetCollection;

    /**
     * @var FormatInterface|MockObject
     */
    protected $localeFormat;

    /**
     * @var Factory|MockObject
     */
    protected $concreteConditionFactory;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * Setup the test
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $className = Context::class;
        $this->context = $this->createMock($className);

        $className = Data::class;
        $this->backendData = $this->createMock($className);

        $className = Config::class;
        $this->config = $this->createMock($className);

        $className = ProductFactory::class;
        $this->productFactory = $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $className = ProductRepositoryInterface::class;
        $this->productRepository = $this->createMock($className);

        $className = \Magento\Catalog\Model\ResourceModel\Product::class;
        $this->productResource = $this->createMock($className);

        $className = AbstractEntity::class;
        $abstractEntity = $this->createMock($className);

        $this->productResource->expects($this->any())
            ->method('loadAllAttributes')
            ->willReturn($abstractEntity);

        $abstractEntity->expects($this->any())
            ->method('getAttributesByCode')
            ->willReturn([]);

        $className = Collection::class;
        $this->attrSetCollection = $this->createMock($className);

        $className = FormatInterface::class;
        $this->localeFormat = $this->createMock($className);

        $className = Factory::class;
        $this->concreteConditionFactory = $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->model = $this->objectManager->getObject(
            Product::class,
            [
                'context' => $this->context,
                'backendData' => $this->backendData,
                'config' => $this->config,
                'productFactory' => $this->productFactory,
                'productRepository' => $this->productRepository,
                'productResource' => $this->productResource,
                'attrSetCollection' => $this->attrSetCollection,
                'localeFormat' => $this->localeFormat,
                'concreteConditionFactory' => $this->concreteConditionFactory,
                'data' => [],
            ]
        );
    }

    /**
     * test IsFilterable
     */
    public function testIsFilterable()
    {
        $className = FilterableConditionInterface::class;
        $interface =$this->createMock($className);

        $interface->expects($this->any())
            ->method('isFilterable')
            ->willReturn(true);

        $this->concreteConditionFactory->expects($this->any())
            ->method('create')
            ->willReturn($interface);

        $this->assertTrue($this->model->isFilterable());
    }

    /**
     * test GetFilterGroups
     */
    public function testGetFilterGroups()
    {
        $className = FilterGroupInterface::class;
        $filterGroupInterface =$this->createMock($className);

        $className = FilterableConditionInterface::class;
        $interface =$this->createMock($className);

        $interface->expects($this->any())
            ->method('getFilterGroups')
            ->willReturn([$filterGroupInterface]);

        $this->concreteConditionFactory->expects($this->any())
            ->method('create')
            ->willReturn($interface);

        $this->assertEquals([$filterGroupInterface], $this->model->getFilterGroups());
    }
}
