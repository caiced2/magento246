<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VisualMerchandiser\Test\Unit\Model;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\VisualMerchandiser\Model\Sorting;
use Magento\VisualMerchandiser\Model\Sorting\Factory;
use Magento\VisualMerchandiser\Model\Sorting\UserDefined;
use PHPUnit\Framework\TestCase;

class SortingTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var Category
     */
    protected $category;

    /**
     * @var Factory
     */
    protected $sortingFactory;

    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @var Sorting
     */
    protected $model;

    /**
     * @var UserDefined
     */
    protected $sorting;

    /**
     * Set up instances and mock objects
     */
    protected function setUp(): void
    {
        $this->sortingFactory = $this->getMockBuilder(Factory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $userSort = $this->getMockBuilder(UserDefined::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sortingFactory
            ->expects($this->any())
            ->method('create')
            ->with(
                $this->logicalOr(
                    'UserDefined',
                    'LowStockTop',
                    'OutStockBottom',
                    'SpecialPriceTop',
                    'SpecialPriceBottom',
                    'NewestTop',
                    'SortColor',
                    'Name\Ascending',
                    'Name\Descending',
                    'Sku\Ascending',
                    'Sku\Descending',
                    'Price\HighToLow',
                    'Price\LowToHigh'
                )
            )
            ->willReturn($userSort);

        $this->category = $this->getMockBuilder(Category::class)
            ->setMethods(['getAutomaticSorting'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->category
            ->expects($this->any())
            ->method('getAutomaticSorting')
            ->willReturn(2);

        $this->collection = $this->getMockBuilder(Collection::class)
            ->setMethods(['getSelect', 'isLoaded'])
            ->disableOriginalConstructor()
            ->getMock();

        $userSort
            ->expects($this->any())
            ->method('sort')
            ->willReturn($this->collection);

        $this->collection
            ->expects($this->any())
            ->method('isLoaded')
            ->willReturn(false);

        $this->collection
            ->expects($this->any())
            ->method('getSelect')
            ->willReturn(true);

        $this->model = (new ObjectManager($this))->getObject(
            Sorting::class,
            [
                'factory' => $this->sortingFactory
            ]
        );
    }

    /**
     * Tests the method getSortingOptions
     */
    public function testGetSortingOptions()
    {
        $this->assertIsArray($this->model->getSortingOptions());
    }

    /**
     * Tests the method getSortingInstance
     */
    public function testGetSortingInstance()
    {
        $this->assertInstanceOf(
            UserDefined::class,
            $this->model->getSortingInstance(null)
        );
    }

    /**
     * Tests the method applySorting
     */
    public function testApplySorting()
    {
        $this->assertInstanceOf(
            Collection::class,
            $this->model->applySorting(
                $this->category,
                $this->collection
            )
        );
    }
}
