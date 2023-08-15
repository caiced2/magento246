<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CmsStaging\Test\Unit\Model\Block\Identifier;

use Magento\Cms\Model\Block;
use Magento\Cms\Model\ResourceModel\Block\Collection;
use Magento\CmsStaging\Model\Page\Identifier\DataProvider as PageIdentifierDataProvider;
use Magento\Framework\App\Request\DataPersistorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataProviderTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $collection;

    /**
     * @var PageIdentifierDataProvider
     */
    protected $model;

    protected function setUp(): void
    {
        $this->collection = $this->createMock(Collection::class);
        $collectionFactory = $this->createPartialMock(
            \Magento\Cms\Model\ResourceModel\Block\CollectionFactory::class,
            ['create']
        );
        $collectionFactory->expects($this->once())->method('create')->willReturn($this->collection);

        $this->model = new \Magento\Cms\Model\Block\DataProvider(
            'name',
            'primaryFieldName',
            'requestFieldName',
            $collectionFactory,
            $this->getMockForAbstractClass(DataPersistorInterface::class)
        );
    }

    public function testGetData()
    {
        $blockId = 100;
        $blockData = ['key' => 'value'];
        $blockMock = $this->createMock(Block::class);
        $blockMock->expects($this->once())->method('getId')->willReturn($blockId);
        $blockMock->expects($this->once())->method('getData')->willReturn($blockData);

        $expectedResult = [$blockId => $blockData];
        $this->collection->expects($this->once())->method('getItems')->willReturn([$blockMock]);

        $this->assertEquals($expectedResult, $this->model->getData());
    }
}
