<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Model\Plugin\ResourceModel\Product\Fulltext;

use Magento\CatalogStaging\Model\Plugin\ResourceModel\Product\Fulltext\Collection;
use Magento\Framework\DB\Select;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Staging\Model\VersionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    /**
     * @var Collection
     */
    protected $model;

    /**
     * @var \Magento\Framework\EntityManager\EntityMetadata|MockObject
     */
    protected $metadataMock;

    /**
     * @var MockObject
     */
    protected $versionManagerMock;

    protected function setUp(): void
    {
        $this->markTestSkipped("MC-18948: Mysql Search Engine is deprecated");
        $objectManager = new ObjectManager($this);

        $this->versionManagerMock = $this->getMockBuilder(VersionManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->versionManagerMock;

        $this->model = $objectManager->getObject(
            Collection::class,
            [
                'versionManager' => $this->versionManagerMock
            ]
        );
    }

    public function testBeforeLoad()
    {
        $selectFromData = [
            'main_table' => [],
            'search_result' => ['joinType' => Select::INNER_JOIN],
            'tmp'
        ];
        $expectedSelectFromData = $selectFromData;
        $expectedSelectFromData['search_result']['joinType'] = Select::LEFT_JOIN;

        $collectionMock = $this->getMockBuilder(\Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $selectMock->expects($this->any())
            ->method('getPart')
            ->with(Select::FROM)
            ->willReturn($selectFromData);

        $collectionMock->expects($this->any())
            ->method('getSelect')
            ->willReturn($selectMock);

        $this->model->beforeLoad($collectionMock);
    }
}
