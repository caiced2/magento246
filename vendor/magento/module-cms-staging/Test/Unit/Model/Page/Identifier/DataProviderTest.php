<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CmsStaging\Test\Unit\Model\Page\Identifier;

use Magento\Cms\Model\Page;
use Magento\Cms\Model\ResourceModel\Page\Collection;
use Magento\CmsStaging\Model\Page\Identifier\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataProviderTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $collection;

    /**
     * @var DataProvider
     */
    protected $model;

    protected function setUp(): void
    {
        $this->collection = $this->createMock(Collection::class);
        $collectionFactory = $this->createPartialMock(
            \Magento\Cms\Model\ResourceModel\Page\CollectionFactory::class,
            ['create']
        );
        $collectionFactory->expects($this->once())->method('create')->willReturn($this->collection);

        $this->model = new DataProvider(
            'name',
            'primaryFieldName',
            'requestFieldName',
            $collectionFactory
        );
    }

    public function testGetData()
    {
        $pageId = 100;
        $pageTitle = 'title';
        $pageMock = $this->createMock(Page::class);
        $pageMock->expects($this->exactly(2))->method('getId')->willReturn($pageId);
        $pageMock->expects($this->once())->method('getTitle')->willReturn($pageTitle);

        $expectedResult = [
            $pageId => [
                'page_id' => $pageId,
                'title' => $pageTitle
            ]
        ];
        $this->collection->expects($this->once())->method('getItems')->willReturn([$pageMock]);

        $this->assertEquals($expectedResult, $this->model->getData());
    }
}
