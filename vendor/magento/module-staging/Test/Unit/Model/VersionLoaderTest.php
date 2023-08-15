<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Model\Entity\Builder;
use Magento\Staging\Model\Entity\VersionLoader;
use Magento\Staging\Model\VersionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Magento\Staging\Model\Entity\VersionLoader class.
 */
class VersionLoaderTest extends TestCase
{
    /**
     * @var EntityManager|MockObject
     */
    private $entityManagerMock;

    /**
     * @var VersionManager|MockObject
     */
    private $versionManagerMock;

    /**
     * @var Builder|MockObject
     */
    private $builderMock;

    /**
     * @var VersionLoader
     */
    private $model;

    protected function setUp(): void
    {
        $this->builderMock = $this->createMock(Builder::class);
        $this->versionManagerMock = $this->createMock(VersionManager::class);
        $this->entityManagerMock = $this->createMock(EntityManager::class);

        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            VersionLoader::class,
            [
                'builder' => $this->builderMock,
                'versionManager' => $this->versionManagerMock,
                'entityManager' => $this->entityManagerMock,
            ]
        );
    }

    /**
     * @return void
     */
    public function testLoad()
    {
        $prototypeMock = $this->getMockForAbstractClass(ProductInterface::class);
        $id = 1;
        $versionId = 2;
        $currentVersion = 3;
        $versionMock = $this->getMockForAbstractClass(UpdateInterface::class);

        $versionMock->expects($this->any())
            ->method('getId')
            ->willReturn($currentVersion);
        $this->versionManagerMock->expects($this->once())
            ->method('getCurrentVersion')
            ->willReturn($versionMock);
        $this->versionManagerMock->expects($this->exactly(2))
            ->method('setCurrentVersionId')
            ->withConsecutive([$versionId], [$currentVersion]);
        $this->entityManagerMock->expects($this->once())
            ->method('load')
            ->with($prototypeMock, $id)
            ->willReturn($prototypeMock);
        $this->builderMock->expects($this->once())
            ->method('build')
            ->with($prototypeMock)
            ->willReturn($prototypeMock);

        $this->model->load($prototypeMock, (string)$id, $versionId);
    }
}
