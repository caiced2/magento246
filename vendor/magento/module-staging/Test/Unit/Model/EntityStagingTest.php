<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model;

use Magento\Framework\EntityManager\TypeResolver;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Staging\Model\EntityStaging;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EntityStagingTest extends TestCase
{
    /**
     * @var EntityStaging|MockObject
     */
    private $entityStagingMock;

    /**
     * @var ObjectManagerInterface|MockObject
     */
    private $objectManagerMock;

    /**
     * @var TypeResolver|MockObject
     */
    private $typeResolverMock;

    /**
     * @var array
     */
    private $types = [
        'Test/TestType' => 'Result/StagingInterface'
    ];

    /**
     * @var EntityStaging
     */
    private $model;

    protected function setUp(): void
    {
        $this->objectManagerMock = $this->getMockBuilder(
            ObjectManagerInterface::class
        )->getMockForAbstractClass();
        $this->typeResolverMock = $this->getMockBuilder(TypeResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityStagingMock = $this->getMockBuilder(EntityStaging::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            EntityStaging::class,
            [
                'objectManager' => $this->objectManagerMock,
                'typeResolver' => $this->typeResolverMock,
                'stagingServices' => $this->types
            ]
        );
    }

    public function testSchedule()
    {
        $entity = new \stdClass();
        $this->expectTypeResolving($entity, key($this->types));
        $this->objectManagerMock->expects($this->once())->method('get')->with(current($this->types))->willReturn(
            $this->entityStagingMock
        );
        $this->entityStagingMock->expects($this->once())->method('schedule')->willReturn(true);
        $this->assertTrue($this->model->schedule($entity, 1));
    }

    public function testScheduleConfigurationMismatch()
    {
        $this->expectException('Magento\Framework\Exception\ConfigurationMismatchException');
        $entity = new \stdClass();
        $this->expectTypeResolving($entity, 'unknowntype');
        $this->objectManagerMock->expects($this->never())->method('get');
        $this->entityStagingMock->expects($this->never())->method('schedule');
        $this->assertTrue($this->model->schedule($entity, 1));
    }

    public function testUnSchedule()
    {
        $entity = new \stdClass();
        $this->expectTypeResolving($entity, key($this->types));
        $this->objectManagerMock->expects($this->once())->method('get')->with(current($this->types))->willReturn(
            $this->entityStagingMock
        );
        $this->entityStagingMock->expects($this->once())->method('unschedule')->willReturn(true);
        $this->assertTrue($this->model->unschedule($entity, 1));
    }

    public function testUnScheduleConfigurationMismatch()
    {
        $this->expectException('Magento\Framework\Exception\ConfigurationMismatchException');
        $entity = new \stdClass();
        $this->expectTypeResolving($entity, 'unknowntype');
        $this->objectManagerMock->expects($this->never())->method('get');
        $this->entityStagingMock->expects($this->never())->method('unschedule');
        $this->assertTrue($this->model->schedule($entity, 1));
    }

    /**
     * @param object $entity
     * @param string $resultingType
     * @return void
     */
    private function expectTypeResolving($entity, $resultingType)
    {
        $this->typeResolverMock->expects($this->once())->method('resolve')->with($entity)->willReturn($resultingType);
    }
}
