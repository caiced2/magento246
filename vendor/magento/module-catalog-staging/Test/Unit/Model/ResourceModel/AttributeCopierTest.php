<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Test\Unit\Model\ResourceModel;

use Magento\Catalog\Api\Data\CategorySearchResultsInterface;
use Magento\Catalog\Api\Data\CategoryAttributeInterface;
use Magento\CatalogStaging\Model\ResourceModel\AttributeCopier;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\EntityMetadata;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Model\Entity\ScopeResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class for test attribute copy
 */
class AttributeCopierTest extends TestCase
{
    /**
     * @var MetadataPool|MockObject
     */
    private $metadataPool;

    /**
     * @var ScopeResolver|MockObject
     */
    private $scopeResolver;

    /**
     * @var ResourceConnection|MockObject
     */
    private $resourceConnection;

    /**
     * @var AttributeRepositoryInterface|MockObject
     */
    private $attributeRepositoryMock;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilderMock;

    /**
     * @var EntityMetadata|MockObject
     */
    private $metadataMock;

    /**
     * @var AttributeCopier
     */
    private $attributeCopier;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->attributeRepositoryMock = $this->getMockBuilder(AttributeRepositoryInterface::class)
            ->getMockForAbstractClass();
        $this->searchCriteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataPool = $this->getMockBuilder(MetadataPool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->scopeResolver = $this->getMockBuilder(ScopeResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceConnection = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConnectionByName'])
            ->getMockForAbstractClass();
        $this->metadataMock = $this->createMock(EntityMetadata::class);

        $this->attributeCopier = new AttributeCopier(
            $this->metadataPool,
            $this->scopeResolver,
            $this->resourceConnection,
            $this->searchCriteriaBuilderMock,
            $this->attributeRepositoryMock,
        );
    }

    /**
     * Test copy Category attribute
     *
     * @return void
     */
    public function testCopyCategoryAttribute(): void
    {
        $entityData = [
            'entity_id' => 1,
        ];
        $searchCriteriaMock = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilderMock
            ->method('create')
            ->willReturn($searchCriteriaMock);
        $listMock = $this->getMockBuilder(CategorySearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->attributeRepositoryMock
            ->method('getList')
            ->willReturn($listMock);
        $listMock->method('getItems')
            ->willReturn([]);
        $this->metadataMock->method('getLinkField')
            ->willReturn('row_id');
        $this->metadataMock->method('getIdentifierField')
            ->willReturn('entity_id');
        $this->metadataPool->method('getMetadata')
            ->willReturn($this->metadataMock);
        $adapter = $this->getMockForAbstractClass(AdapterInterface::class);
        $this->resourceConnection->method('getConnectionByName')
            ->willReturn($adapter);
        $selectStub = $this->createMock(Select::class);
        $selectStub->method('from')
            ->willReturnSelf();
        $selectStub->method('where')
            ->willReturnSelf();
        $selectStub->method('order')
            ->willReturnSelf();
        $selectStub->method('limit')
            ->willReturnSelf();
        $selectStub->method('setPart')
            ->willReturnSelf();
        $adapter->method('select')
            ->willReturn($selectStub);
        $adapter->method('fetchOne')
            ->willReturn('2');

        $this->assertEquals(
            true,
            $this->attributeCopier->copy(
                CategoryAttributeInterface::ENTITY_TYPE_CODE,
                $entityData,
                17,
                12
            ),
        );
    }
}
