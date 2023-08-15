<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesRuleStaging\Test\Unit\Observer;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\SalesRuleStaging\Model\ResourceModel\Rule\AddWebsiteIdsToCollection;
use Magento\SalesRuleStaging\Model\Staging\PreviewStoreIdResolver;
use Magento\SalesRuleStaging\Observer\AddStoreIdToSalesRuleUpcomingSearchResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test add store id to sales rules upcoming search result observer
 */
class AddStoreIdToSalesRuleUpcomingSearchResultTest extends TestCase
{
    /**
     * @var PreviewStoreIdResolver|MockObject
     */
    private $previewStoreIdResolver;

    /**
     * @var AddStoreIdToSalesRuleUpcomingSearchResult
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $addWebsiteIdsToCollection = $this->createMock(AddWebsiteIdsToCollection::class);
        $this->previewStoreIdResolver = $this->createMock(PreviewStoreIdResolver::class);
        $this->model = new AddStoreIdToSalesRuleUpcomingSearchResult(
            $addWebsiteIdsToCollection,
            $this->previewStoreIdResolver
        );
    }

    /**
     * Test that store id is set for each item in the collection
     */
    public function testExecute(): void
    {
        $items = [
            new DataObject(['website_ids' => [1, 2]]),
            new DataObject(['website_ids' => [2]]),
            new DataObject(['website_ids' => []]),
        ];
        $collection = $this->getMockBuilder(AbstractDb::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getItems'])
            ->getMockForAbstractClass();
        $collection->method('getItems')
            ->willReturn($items);
        $observer = new Observer(
            [
                'collection' => $collection
            ]
        );

        $this->previewStoreIdResolver
            ->method('execute')
            ->willReturnMap(
                [
                    [[1, 2], 11],
                    [[2], 22],
                    [[], null],
                ]
            );
        $this->model->execute($observer);
        $this->assertEquals(11, $items[0]->getStoreId());
        $this->assertEquals(22, $items[1]->getStoreId());
        $this->assertNull($items[2]->getStoreId());
    }
}
