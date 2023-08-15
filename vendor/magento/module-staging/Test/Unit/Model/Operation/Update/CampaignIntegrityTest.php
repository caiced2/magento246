<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model\Operation\Update;

use Magento\Framework\EntityManager\TypeResolver;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Model\Operation\Update\CampaignIntegrity;
use Magento\Staging\Model\Operation\Update\PermanentUpdateProcessorPool;
use Magento\Staging\Model\Operation\Update\TemporaryUpdateProcessorPool;
use Magento\Staging\Model\Operation\Update\UpdateProcessorInterface;
use Magento\Staging\Model\VersionInfo;
use Magento\Staging\Model\VersionInfoProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Magento\Staging\Model\Operation\Update\CampaignIntegrity class.
 */
class CampaignIntegrityTest extends TestCase
{
    /**
     * @var TypeResolver|MockObject
     */
    private $typeResolverMock;

    /**
     * @var PermanentUpdateProcessorPool|MockObject
     */
    private $permanentUpdateProcessorPoolMock;

    /**
     * @var TemporaryUpdateProcessorPool|MockObject
     */
    private $temporaryUpdateProcessorPoolMock;

    /**
     * @var VersionInfoProvider|MockObject
     */
    private $versionInfoProviderMock;

    /**
     * @var CampaignIntegrity
     */
    private $model;

    protected function setUp(): void
    {
        $this->permanentUpdateProcessorPoolMock = $this->createMock(PermanentUpdateProcessorPool::class);
        $this->temporaryUpdateProcessorPoolMock = $this->createMock(TemporaryUpdateProcessorPool::class);
        $this->versionInfoProviderMock = $this->createMock(VersionInfoProvider::class);
        $this->typeResolverMock = $this->createMock(TypeResolver::class);

        $this->model = new CampaignIntegrity(
            $this->permanentUpdateProcessorPoolMock,
            $this->temporaryUpdateProcessorPoolMock,
            $this->typeResolverMock,
            $this->versionInfoProviderMock
        );
    }

    /**
     * @dataProvider synchronizeAffectedCampaignsDataProvider
     * @param string|null $rollbackId
     * @param string|null $updatedIn
     * @param string $expectation
     * @return void
     */
    public function testSynchronizeAffectedCampaigns($rollbackId, $updatedIn, $expectation)
    {
        $entity = new \stdClass();
        $entityType = 'EntityType';
        $versionInfoMock = $this->createMock(VersionInfo::class);
        $updateMock = $this->getMockForAbstractClass(UpdateInterface::class);
        $processorMock = $this->getMockForAbstractClass(UpdateProcessorInterface::class);
        $updateId = '1';

        $updateMock->expects($this->any())
            ->method('getId')
            ->willReturn($updateId);
        $this->versionInfoProviderMock->expects($this->once())
            ->method('getVersionInfo')
            ->with($entity, $updateId)
            ->willReturn($versionInfoMock);
        $this->typeResolverMock->expects($this->once())
            ->method('resolve')
            ->willReturn($entityType);
        $updateMock->expects($this->any())
            ->method('getRollbackId')
            ->willReturn($rollbackId);
        $versionInfoMock->expects($this->any())
            ->method('getUpdatedIn')
            ->willReturn($updatedIn);
        $this->permanentUpdateProcessorPoolMock->expects($this->$expectation())
            ->method('getProcessor')
            ->with($entityType)
            ->willReturn($processorMock);
        $processorMock->expects($this->$expectation())
            ->method('process')
            ->with($entity, $updateId, $rollbackId);

        $this->model->synchronizeAffectedCampaigns($updateMock, $entity);
    }

    /**
     * @return array
     */
    public function synchronizeAffectedCampaignsDataProvider()
    {
        return [
            [
                'rollbackId' => '1',
                'updatedIn' => '2',
                'expectation' => 'once',
            ],
            [
                'rollbackId' => '1',
                'updatedIn' => '1',
                'expectation' => 'never',
            ],
            [
                'rollbackId' => null,
                'updatedIn' => null,
                'expectation' => 'once',
            ],
        ];
    }
}
