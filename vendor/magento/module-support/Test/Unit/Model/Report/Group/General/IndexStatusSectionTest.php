<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report\Group\General;

use Magento\Framework\Mview\View;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Indexer\Model\Indexer;
use Magento\Indexer\Model\Indexer\Collection;
use Magento\Support\Model\Report\Group\General\IndexStatusSection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class IndexStatusSectionTest extends TestCase
{
    /**
     * @var IndexStatusSection
     */
    protected $indexStatus;

    /**
     * @var \Magento\Indexer\Model\Indexer\CollectionFactory|MockObject
     */
    protected $indexerFactoryMock;

    /**
     * @var Collection|MockObject
     */
    protected $collectionFactory;

    /**
     * @var View|MockObject
     */
    protected $viewMock;

    /**
     * @var Indexer|MockObject
     */
    protected $categoryProductsIndexerMock;

    /**
     * @var Indexer|MockObject
     */
    protected $productCategoriesIndexerMock;

    /**
     * @var Indexer|MockObject
     */
    protected $catalogSearchIndexerMock;

    /**
     * @var TimezoneInterface|MockObject
     */
    protected $timeZoneMock;

    /**
     * @var ObjectManager
     */
    protected $objectManagerHelper;

    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManager($this);

        $this->indexerFactoryMock = $this->getMockBuilder(\Magento\Indexer\Model\Indexer\CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->collectionFactory = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['getItems'])
            ->getMock();

        $this->viewMock = $this->createMock(View::class);
        $this->timeZoneMock = $this->getMockForAbstractClass(TimezoneInterface::class);

        $this->categoryProductsIndexerMock = $this->getMockBuilder(Indexer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getView', 'getTitle', 'getStatus', 'isValid', 'getLatestUpdated', 'getDescription'])
            ->getMock();
        $this->productCategoriesIndexerMock = $this->getMockBuilder(Indexer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getView', 'getTitle', 'getStatus', 'isValid', 'getLatestUpdated', 'getDescription'])
            ->getMock();
        $this->catalogSearchIndexerMock = $this->getMockBuilder(Indexer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getView', 'getTitle', 'getStatus', 'isValid', 'getLatestUpdated', 'getDescription'])
            ->getMock();

        $this->indexStatus = $this->objectManagerHelper->getObject(
            IndexStatusSection::class,
            [
                'indexerFactory' => $this->indexerFactoryMock,
                'timeZone' => $this->timeZoneMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testExecute()
    {
        $categoryProductsTitle = 'Category Products';
        $categoryProductsDescription = 'Indexed category/products association';
        $productCategoriesTitle = 'Product Categories';
        $productCategoriesDescription = 'Indexed product/categories association';
        $catalogSearchTitle = 'Catalog Search';
        $catalogSearchDescription = 'Rebuild Catalog product fulltext search index';
        $invalidStatus = 'invalid';
        $validStatus = 'valid';
        $latestUpdatedDate = '2015-07-24 12:38:14';

        $expectedData = [
            IndexStatusSection::REPORT_TITLE => [
                'headers' => ['Index', 'Status', 'Update Required', 'Updated At', 'Mode', 'Is Visible', 'Description'],
                'data' => [
                    [
                        'Category Products',
                        'invalid',
                        'Yes',
                        '2015-07-24 12:38:14',
                        'Update On Save',
                        'n/a',
                        'Indexed category/products association'
                    ],
                    [
                        'Product Categories',
                        'invalid',
                        'Yes',
                        '2015-07-24 12:38:14',
                        'Update On Save',
                        'n/a',
                        'Indexed product/categories association'
                    ],
                    [
                        'Catalog Search',
                        'valid',
                        'No',
                        '2015-07-24 12:38:14',
                        'Update On Save',
                        'n/a',
                        'Rebuild Catalog product fulltext search index'
                    ],
                ]
            ]
        ];

        $indexers = [
            $this->categoryProductsIndexerMock,
            $this->productCategoriesIndexerMock,
            $this->catalogSearchIndexerMock
        ];

        $this->indexerFactoryMock->expects($this->once())->method('create')->willReturn($this->collectionFactory);
        $this->collectionFactory->expects($this->once())->method('getItems')->willReturn($indexers);

        $this->categoryProductsIndexerMock->expects($this->once())->method('getView')->willReturn($this->viewMock);
        $this->viewMock->expects($this->atLeastOnce())->method('isEnabled')->willReturn(false);
        $this->categoryProductsIndexerMock->expects($this->once())->method('getTitle')->willReturn(
            $categoryProductsTitle
        );
        $this->categoryProductsIndexerMock->expects($this->once())->method('getStatus')->willReturn($invalidStatus);
        $this->categoryProductsIndexerMock->expects($this->once())->method('isValid')->willReturn(false);
        $this->categoryProductsIndexerMock->expects($this->atLeastOnce())->method('getLatestUpdated')->willReturn(
            $latestUpdatedDate
        );
        $this->categoryProductsIndexerMock->expects($this->once())->method('getDescription')->willReturn(
            $categoryProductsDescription
        );
        $this->timeZoneMock->expects($this->any())->method('formatDateTime')->willReturn($latestUpdatedDate);
        $this->productCategoriesIndexerMock->expects($this->once())->method('getView')->willReturn($this->viewMock);
        $this->productCategoriesIndexerMock->expects($this->once())->method('getTitle')->willReturn(
            $productCategoriesTitle
        );
        $this->productCategoriesIndexerMock->expects($this->once())->method('getStatus')->willReturn($invalidStatus);
        $this->productCategoriesIndexerMock->expects($this->once())->method('isValid')->willReturn(false);
        $this->productCategoriesIndexerMock->expects($this->atLeastOnce())->method('getLatestUpdated')->willReturn(
            $latestUpdatedDate
        );
        $this->productCategoriesIndexerMock->expects($this->once())->method('getDescription')->willReturn(
            $productCategoriesDescription
        );

        $this->catalogSearchIndexerMock->expects($this->once())->method('getView')->willReturn($this->viewMock);
        $this->catalogSearchIndexerMock->expects($this->once())->method('getTitle')->willReturn($catalogSearchTitle);
        $this->catalogSearchIndexerMock->expects($this->once())->method('getStatus')->willReturn($validStatus);
        $this->catalogSearchIndexerMock->expects($this->once())->method('isValid')->willReturn(true);
        $this->catalogSearchIndexerMock->expects($this->atLeastOnce())->method('getLatestUpdated')->willReturn(
            $latestUpdatedDate
        );
        $this->catalogSearchIndexerMock->expects($this->once())->method('getDescription')->willReturn(
            $catalogSearchDescription
        );

        $this->assertSame($expectedData, $this->indexStatus->generate());
    }
}
