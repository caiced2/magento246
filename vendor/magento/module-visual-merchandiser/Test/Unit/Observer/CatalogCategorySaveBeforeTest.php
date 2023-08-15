<?php
/***
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VisualMerchandiser\Test\Unit\Observer;

use Magento\Catalog\Model\Category;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\VisualMerchandiser\Model\Category\Builder;
use Magento\VisualMerchandiser\Model\Rules;
use Magento\VisualMerchandiser\Model\RulesFactory;
use Magento\VisualMerchandiser\Observer\CatalogCategorySaveBefore;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for \Magento\VisualMerchandiser\Observer\CatalogCategorySaveBefore
 */
class CatalogCategorySaveBeforeTest extends TestCase
{
    /**
     * @var CatalogCategorySaveBefore
     */
    private $model;

    /**
     * @var Builder|MockObject
     */
    private $categoryBuilder;

    /**
     * @var RulesFactory|MockObject
     */
    private $rulesFactory;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->categoryBuilder = $this->getMockBuilder(Builder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->rulesFactory = $this->getMockBuilder(RulesFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->model = new CatalogCategorySaveBefore(
            $this->categoryBuilder,
            $this->rulesFactory
        );
    }

    public function testExecute()
    {
        /** @var Observer|MockObject $observer */
        $observer = $this->getMockBuilder(Observer::class)->disableOriginalConstructor()->getMock();
        $event = $this->getMockBuilder(Event::class)
            ->setMethods(['getDataObject'])->disableOriginalConstructor()->getMock();
        $category = $this->getMockBuilder(Category::class)->disableOriginalConstructor()->getMock();
        $rules = $this->getMockBuilder(Rules::class)->disableOriginalConstructor()->getMock();
        $rule = $this->getMockBuilder(Rules::class)
            ->setMethods(['getIsActive', 'getId'])->disableOriginalConstructor()->getMock();

        $observer->expects($this->once())->method('getEvent')->willReturn($event);
        $event->expects($this->once())->method('getDataObject')->willReturn($category);
        $this->rulesFactory->expects($this->once())->method('create')->willReturn($rules);
        $rules->expects($this->once())->method('loadByCategory')->willReturn($rule);
        $rule->expects($this->once())->method('getId')->willReturn(1);
        $rule->expects($this->once())->method('getIsActive')->willReturn(false);

        $this->model->execute($observer);
    }
}
