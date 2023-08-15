<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Test\Unit\Model\Indexer\Plugin;

use Magento\CatalogPermissions\App\Config;
use Magento\CatalogPermissions\Model\Indexer\Category;
use Magento\CatalogPermissions\Model\Indexer\Plugin\IndexerConfigData;
use Magento\CatalogPermissions\Model\Indexer\Product;
use Magento\Indexer\Model\Config\Data;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class IndexerConfigDataTest extends TestCase
{
    /**
     * @var IndexerConfigData
     */
    protected $model;

    /**
     * @var Config|MockObject
     */
    protected $configMock;

    /**
     * @var MockObject
     */
    protected $subjectMock;

    protected function setUp(): void
    {
        $this->configMock = $this->createPartialMock(Config::class, ['isEnabled']);
        $this->subjectMock = $this->createMock(Data::class);

        $this->model = new IndexerConfigData($this->configMock);
    }

    /**
     * @param bool $isEnabled
     * @param string $path
     * @param mixed $default
     * @param array $inputData
     * @param array $outputData
     * @dataProvider afterGetDataProvider
     */
    public function testAfterGet($isEnabled, $path, $default, $inputData, $outputData)
    {
        $this->configMock->expects($this->any())->method('isEnabled')->willReturn($isEnabled);

        $this->assertEquals($outputData, $this->model->afterGet($this->subjectMock, $inputData, $path, $default));
    }

    public function afterGetDataProvider()
    {
        $categoryIndexerData = [
            'indexer_id' => Category::INDEXER_ID,
            'action' => '\Action\Class',
            'title' => 'Title',
            'description' => 'Description',
        ];
        $productIndexerData = [
            'indexer_id' => Product::INDEXER_ID,
            'action' => '\Action\Class',
            'title' => 'Title',
            'description' => 'Description',
        ];

        return [
            [
                true,
                null,
                null,
                [
                    Category::INDEXER_ID => $categoryIndexerData,
                    Product::INDEXER_ID => $productIndexerData
                ],
                [
                    Category::INDEXER_ID => $categoryIndexerData,
                    Product::INDEXER_ID => $productIndexerData
                ],
            ],
            [
                false,
                null,
                null,
                [
                    Category::INDEXER_ID => $categoryIndexerData,
                    Product::INDEXER_ID => $productIndexerData
                ],
                []
            ],
            [
                false,
                Category::INDEXER_ID,
                null,
                $categoryIndexerData,
                null
            ],
            [
                false,
                Product::INDEXER_ID,
                null,
                $productIndexerData,
                null
            ]
        ];
    }
}
