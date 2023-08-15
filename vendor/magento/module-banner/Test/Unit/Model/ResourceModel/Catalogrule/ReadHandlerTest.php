<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Banner\Test\Unit\Model\ResourceModel\Catalogrule;

use Magento\Banner\Model\Banner;
use Magento\Banner\Model\ResourceModel\Catalogrule\ReadHandler;
use Magento\CatalogRule\Api\Data\RuleInterface;
use Magento\Framework\EntityManager\EntityMetadata;
use Magento\Framework\EntityManager\MetadataPool;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReadHandlerTest extends TestCase
{
    /**
     * @var ReadHandler
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $metadataMock;

    /**
     * @var MockObject
     */
    protected $bannerModelFactoryMock;

    protected function setUp(): void
    {
        $this->bannerModelFactoryMock = $this->createPartialMock(
            \Magento\Banner\Model\BannerFactory::class,
            ['create']
        );
        $this->metadataMock = $this->createMock(MetadataPool::class);

        $this->model = new ReadHandler(
            $this->bannerModelFactoryMock,
            $this->metadataMock
        );
    }

    public function testExecute()
    {
        $entityType = RuleInterface::class;
        $entityData = [
            'entity_id' => 100
        ];
        $relatedBanners = [1, 2, 3];

        $entityMetadataMock = $this->createPartialMock(
            EntityMetadata::class,
            ['getIdentifierField']
        );
        $entityMetadataMock->expects($this->once())->method('getIdentifierField')->willReturn('entity_id');

        $this->metadataMock->expects($this->once())
            ->method('getMetadata')
            ->with($entityType)
            ->willReturn($entityMetadataMock);

        $bannerModelMock = $this->createMock(Banner::class);
        $this->bannerModelFactoryMock->expects($this->once())->method('create')->willReturn($bannerModelMock);

        $bannerModelMock->expects($this->once())
            ->method('getRelatedBannersByCatalogRuleId')
            ->with(100)
            ->willReturn($relatedBanners);

        $expectedResult = array_merge($entityData, ['related_banners' => $relatedBanners]);

        $this->assertEquals(
            $expectedResult,
            $this->model->execute($entityType, $entityData)
        );
    }
}
