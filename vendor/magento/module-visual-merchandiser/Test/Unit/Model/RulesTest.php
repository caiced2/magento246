<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VisualMerchandiser\Test\Unit\Model;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\VisualMerchandiser\Model\Config\Source\InsertMode;
use Magento\VisualMerchandiser\Model\Rules;
use PHPUnit\Framework\TestCase;

class RulesTest extends TestCase
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Attribute
     */
    protected $attribute;

    /**
     * @var Category
     */
    protected $category;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @var Rules
     */
    protected $model;

    /**
     * Set up instances and mock objects
     */
    protected function setUp(): void
    {
        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->setMethods(['getValue'])
            ->getMockForAbstractClass();

        $scopeValueMap = [
            [Rules::XML_PATH_AVAILABLE_ATTRIBUTES, null, 'xxx'],
            [InsertMode::XML_PATH_INSERT_MODE, null, 'xxx']
        ];
        $this->scopeConfig
            ->expects($this->any())
            ->method('getValue')
            ->willReturnMap($scopeValueMap);

        $this->attribute = $this->getMockBuilder(Attribute::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->attribute
            ->expects($this->any())
            ->method('loadByCode')
            ->willReturn($this->attribute);

        $this->category = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProductsPosition'])
            ->getMock();

        $this->category
            ->expects($this->any())
            ->method('getProductsPosition')
            ->willReturn([]);

        $this->collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collection
            ->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([]));

        $resource = $this->getMockBuilder(AbstractResource::class)
            ->setMethods(['getIdFieldName', 'load'])
            ->getMockForAbstractClass();

        $resource->expects($this->any())
            ->method('getIdFieldName')
            ->willReturn('id');

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->model = (new ObjectManager($this))->getObject(
            Rules::class,
            [
                'scopeConfig' => $this->scopeConfig,
                'attribute' => $this->attribute,
                'resource' => $resource,
                'storeManager' => $this->storeManager
            ]
        );
    }

    /**
     * Tests the method getAvailableAttributes
     */
    public function testGetAvailableAttributes()
    {
        $this->assertIsArray($this->model->getAvailableAttributes());
    }

    /**
     * Tests the method getConditions
     */
    public function testGetConditions()
    {
        $this->assertIsArray($this->model->getAvailableAttributes());
    }

    /**
     * Tests the method loadByCategory
     */
    public function testLoadByCategory()
    {
        $this->assertEquals(
            $this->model,
            $this->model->loadByCategory($this->category)
        );
    }

    /**
     * Tests the method applyAllRules
     */
    public function testApplyAllRules()
    {
        $this->assertNull($this->model->applyAllRules(
            $this->category,
            $this->collection
        ));
    }

    /**
     * Tests the method applyConditions
     */
    public function testApplyConditions()
    {
        $this->scopeConfig
            ->expects($this->any())
            ->method('getValue')
            ->willReturn('visualmerchandiser/options/insert_mode');

        $this->assertNull($this->model->applyConditions(
            $this->category,
            $this->collection,
            []
        ));
    }
}
