<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogImportExportStaging\Test\Unit\Model\Catalog\Import;

use Magento\CatalogImportExport\Model\Import\Product;
use Magento\CatalogImportExportStaging\Model\Import\ProductPlugin;
use Magento\Framework\EntityManager\EntityMetadata;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Staging\Model\VersionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductPluginTest extends TestCase
{
    /** @var  ProductPlugin */
    private $model;

    /** @var  MockObject|MetadataPool */
    private $metadataPoolMock;

    /** @var  ObjectManager */
    private $objectManager;

    /** @var  Product */
    private $subject;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->metadataPoolMock = $this->getMockBuilder(MetadataPool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->subject = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = $this->objectManager->getObject(
            ProductPlugin::class,
            ['metadataPool' => $this->metadataPoolMock]
        );
    }

    public function testBeforeSaveProductEntity()
    {
        $idField = 'id';
        $updateRows = ['should', 'not', 'be', 'changed'];
        $metadataMock = $this->getMockBuilder(EntityMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataPoolMock->expects($this->any())
            ->method('getMetadata')
            ->willReturn($metadataMock);
        $metadataMock->expects($this->exactly(6))
            ->method('getIdentifierField')
            ->willReturn($idField);
        $metadataMock->expects($this->exactly(3))
            ->method('generateIdentifier')
            ->willReturnOnConsecutiveCalls(1, 2, 3);

        $insertRowsBefore = [
            ['color' => 'red', 'sku' => 'red shirt'],
            ['color' => 'blue', 'sku' => 'blue shirt'],
            ['color' => 'grey', 'sku' => 'grey shirt']
        ];
        $expectedInsertRows = [
            [
                'color' => 'red',
                'sku' => 'red shirt',
                'id' => 1,
                'created_in' => 1,
                'updated_in' => VersionManager::MAX_VERSION,
            ],
            [
                'color' => 'blue',
                'sku' => 'blue shirt',
                'id' => 2,
                'created_in' => 1,
                'updated_in' => VersionManager::MAX_VERSION,
            ],
            [
                'color' => 'grey',
                'sku' => 'grey shirt',
                'id' => 3,
                'created_in' => 1,
                'updated_in' => VersionManager::MAX_VERSION,
            ],
        ];
        $result = $this->model->beforeSaveProductEntity($this->subject, $insertRowsBefore, $updateRows);
        $this->assertEquals($expectedInsertRows, $result[0]);
        $this->assertEquals($updateRows, $result[1]);
    }
}
