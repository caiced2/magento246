<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Helper;

use Magento\CatalogStaging\Helper\ReindexPool;
use Magento\Framework\Indexer\AbstractProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReindexPoolTest extends TestCase
{
    /**
     * @var array
     */
    private const REINDEX_POOL = [
        'FlatIndexProcessor',
        'CatalogInventoryIndexProcessor',
        'PriceIndexProcessor',
        'EavIndexProcessor',
        'ProductCategoryIndexProcessor',
        'FulltextIndexProcessor'
    ];

    /**
     * @var string
     */
    private const REINDEX_POOL_CLASS_NAME = 'Magento\CatalogStaging\Helper\ReindexPool';

    /**
     * @var string
     */
    private const REINDEX_POOL_NAME = 'reindexPool';

    /**
     * @var AbstractProcessor|MockObject
     */
    private $indexerProcessor;

    /**
     * @var ReindexPool
     */
    private $helper;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->indexerProcessor = $this->getMockBuilder(AbstractProcessor::class)
            ->disableOriginalConstructor()
            ->setMethods(['reindexList'])
            ->getMockForAbstractClass();

        $reindexPool = [
            $this->indexerProcessor
        ];

        $this->helper = new ReindexPool($reindexPool);
    }

    /**
     * Tests that reindexPool has all the necessary processors in the list
     */
    public function testReindexPoolList()
    {
        $diXml = simplexml_load_file(__DIR__ . '/../../../etc/di.xml');
        $actualReindexPool = [];
        foreach ($diXml->type as $type) {
            if ($type->attributes()['name'] == self::REINDEX_POOL_CLASS_NAME) {
                foreach ($type->arguments->argument as $argument) {
                    if ((string)$argument->attributes()['name'] == self::REINDEX_POOL_NAME) {
                        foreach ($argument->item as $item) {
                            array_push($actualReindexPool, (string)$item->attributes()['name']);
                        }
                    }
                }
            }
        }
        $this->assertEquals(self::REINDEX_POOL, $actualReindexPool);
    }

    /**
     * Tests that reindexList was executed
     */
    public function testReindexList()
    {
        $ids = [1];

        $this->indexerProcessor->expects($this->once())
            ->method('reindexList')
            ->with($ids, true)
            ->willReturnSelf();

        $this->helper->reindexList($ids);
    }
}
