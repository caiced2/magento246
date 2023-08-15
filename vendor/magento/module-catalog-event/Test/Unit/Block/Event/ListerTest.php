<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogEvent\Test\Unit\Block\Event;

use Magento\Catalog\Helper\Category;
use Magento\CatalogEvent\Block\Event\Lister;
use Magento\CatalogEvent\Helper\Data;
use Magento\CatalogEvent\Model\DateResolver;
use Magento\CatalogEvent\Model\Event;
use Magento\CatalogEvent\Model\ResourceModel\Event\CollectionFactory;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\View\Element\Template\Context;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for Magento\CatalogEvent\Block\Event\Lister
 */
class ListerTest extends TestCase
{
    /**
     * @var Lister
     */
    protected $lister;

    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var ResolverInterface|MockObject
     */
    protected $resolverMock;

    /**
     * @var Data|MockObject
     */
    protected $catalogEventHelperMock;

    /**
     * @var MockObject
     */
    protected $collectionFactoryMock;

    /**
     * @var Category|MockObject
     */
    protected $catalogCategoryHelperMock;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resolverMock = $this->getMockBuilder(DateResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->catalogEventHelperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionFactoryMock = $this->getMockBuilder(
            CollectionFactory::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->catalogCategoryHelperMock = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->lister = new Lister(
            $this->contextMock,
            $this->resolverMock,
            $this->catalogEventHelperMock,
            $this->collectionFactoryMock,
            $this->catalogCategoryHelperMock
        );
    }

    /**
     * @return void
     */
    public function testGetCategoryUrl()
    {
        $parameterMock = $this->createMock(\Magento\Catalog\Model\Category::class);

        $this->catalogCategoryHelperMock
            ->expects($this->once())
            ->method('getCategoryUrl')
            ->with($parameterMock)
            ->willReturn('Result');

        $this->assertEquals('Result', $this->lister->getCategoryUrl($parameterMock));
    }

    /**
     * @return void
     */
    public function testGetEventImageUrl()
    {
        $eventMock = $this->createMock(Event::class);
        $this->catalogEventHelperMock
            ->expects($this->once())
            ->method('getEventImageUrl')
            ->with($eventMock)
            ->willReturn('Result');

        $this->assertEquals('Result', $this->lister->getEventImageUrl($eventMock));
    }
}
