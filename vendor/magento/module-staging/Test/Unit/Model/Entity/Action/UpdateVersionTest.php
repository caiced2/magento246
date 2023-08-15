<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model\Entity\Action;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Model\ResourceModel\Db\UpdateEntityRow;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Model\Entity\Action\UpdateVersion;
use Magento\Staging\Model\ResourceModel\Db\ReadEntityVersion;
use Magento\Staging\Model\VersionManager\Proxy as VersionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 */
class UpdateVersionTest extends TestCase
{
    /**
     * @var UpdateVersion
     */
    private $updateVersion;

    /**
     * @var UpdateEntityRow|MockObject
     */
    private $updateEntityRowMock;

    /**
     * @var MetadataPool|MockObject
     */
    private $metadataPoolMock;

    /**
     * @var ReadEntityVersion|MockObject
     */
    private $entityVersionMock;

    /**
     * @var VersionManager|MockObject
     */
    private $versionManagerMock;

    /**
     * @var UpdateInterface|MockObject
     */
    private $versionMock;

    /**
     * @var EntityMetadataInterface|MockObject
     */
    private $metadataMock;

    protected function setUp(): void
    {
        $this->updateEntityRowMock = $this->getMockBuilder(UpdateEntityRow::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataPoolMock = $this->getMockBuilder(MetadataPool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityVersionMock = $this->getMockBuilder(ReadEntityVersion::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->versionManagerMock = $this->getMockBuilder(VersionManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getVersion'])
            ->getMock();
        $this->versionMock = $this->getMockBuilder(UpdateInterface::class)
            ->getMockForAbstractClass();
        $this->metadataMock = $this->getMockBuilder(EntityMetadataInterface::class)
            ->getMockForAbstractClass();
        $this->updateVersion = new UpdateVersion(
            $this->updateEntityRowMock,
            $this->metadataPoolMock,
            $this->entityVersionMock,
            $this->versionManagerMock
        );
    }

    public function testExecute()
    {
        $entityType = ProductInterface::class;
        $identifier = 1;
        $previousRowId = 4;
        $currentVersionId = 232232332;
        $linkField = 'row_id';
        $this->metadataPoolMock->expects($this->once())->method('getMetadata')->with($entityType)->willReturn(
            $this->metadataMock
        );
        $this->metadataMock->expects($this->once())->method('getLinkField')->willReturn($linkField);
        $this->entityVersionMock->expects($this->once())
            ->method('getPreviousVersionRowId')
            ->with($entityType, $identifier)
            ->willReturn($previousRowId);
        $this->versionManagerMock->expects($this->once())->method('getVersion')->willReturn(
            $this->versionMock
        );
        $this->versionMock->expects($this->once())->method('getId')->willReturn($currentVersionId);
        $this->updateEntityRowMock->expects($this->once())->method('execute')->with(
            $entityType,
            [
                $linkField => $previousRowId,
                'updated_in' => $currentVersionId
            ]
        );

        $this->updateVersion->execute($entityType, $identifier);
    }
}
