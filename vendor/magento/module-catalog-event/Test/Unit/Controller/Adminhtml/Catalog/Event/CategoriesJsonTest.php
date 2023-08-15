<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogEvent\Test\Unit\Controller\Adminhtml\Catalog\Event;

use Magento\CatalogEvent\Block\Adminhtml\Event\Edit\Category;
use Magento\CatalogEvent\Controller\Adminhtml\Catalog\Event\CategoriesJson;
use Magento\CatalogEvent\Test\Unit\Controller\Adminhtml\Catalog\AbstractEventTest;

class CategoriesJsonTest extends AbstractEventTest
{
    /**
     * @var CategoriesJson
     */
    protected $categoriesJson;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->categoriesJson = new CategoriesJson(
            $this->contextMock,
            $this->registryMock,
            $this->eventFactoryMock,
            $this->dateTimeMock,
            $this->storeManagerMock
        );
    }

    /**
     * @return void
     */
    public function testExecute()
    {
        $categoryBlockMock = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->getMock();
        $categoryBlockMock
            ->expects($this->once())
            ->method('getTreeArray')
            ->with(null, true, 1)
            ->willReturn('some result');

        $this->responseMock
            ->expects($this->once())
            ->method('representJson')
            ->with('some result');

        $this->layoutMock
            ->expects($this->once())
            ->method('createBlock')
            ->with(Category::class)
            ->willReturn($categoryBlockMock);

        $this->categoriesJson->execute();
    }
}
