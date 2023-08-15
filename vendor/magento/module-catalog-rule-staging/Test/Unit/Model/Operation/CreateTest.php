<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogRuleStaging\Test\Unit\Model\Operation;

use Magento\CatalogRule\Api\Data\RuleInterface;
use Magento\CatalogRuleStaging\Model\Operation\Create;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Api\Data\UpdateInterfaceFactory;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\Operation\Update;
use Magento\Staging\Model\VersionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for Create model
 */
class CreateTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $versionManagerMock;

    /**
     * @var MockObject
     */
    private $updateRepositoryMock;

    /**
     * @var MockObject
     */
    private $operationUpdateMock;

    /**
     * @var MockObject
     */
    private $updateFactoryMock;

    /**
     * @var MockObject
     */
    private $operationCreateMock;

    /**
     * @var MockObject
     */
    private $updateMock;

    /**
     * @var Create
     */
    private $operation;

    /**
     * @var MockObject
     */
    private $entityMock;

    protected function setUp(): void
    {
        $this->versionManagerMock = $this->createMock(VersionManager::class);
        $this->updateRepositoryMock = $this->getMockForAbstractClass(UpdateRepositoryInterface::class);
        $this->operationUpdateMock = $this->createMock(Update::class);
        $this->updateFactoryMock = $this->createPartialMock(
            UpdateInterfaceFactory::class,
            ['create']
        );
        $this->operationCreateMock = $this->createMock(\Magento\Staging\Model\Operation\Create::class);

        $this->updateMock = $this->getMockForAbstractClass(UpdateInterface::class);
        $this->entityMock = $this->getMockForAbstractClass(RuleInterface::class);
        $this->operation = new Create(
            $this->versionManagerMock,
            $this->updateRepositoryMock,
            $this->operationUpdateMock,
            $this->updateFactoryMock,
            $this->operationCreateMock
        );
    }

    public function testExecute()
    {
        //execute create operation
        $this->operationCreateMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->entityMock)
            ->willReturn($this->entityMock);

        $this->assertEquals($this->entityMock, $this->operation->execute($this->entityMock));
    }
}
