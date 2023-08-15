<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report\Group\Data;

use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Type;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Module\ModuleResource;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Support\Model\Report\Group\Data\AbstractDataGroup;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

abstract class AbstractDataGroupTest extends TestCase
{
    /**
     * @var AdapterInterface|MockObject
     */
    protected $connectionMock;

    /**
     * @var ModuleResource|MockObject
     */
    protected $resourceMock;

    /**
     * @var Config|MockObject
     */
    protected $eavConfigMock;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $loggerMock;

    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var string
     */
    protected $reportNamespace = '';

    /**
     * @var AbstractDataGroup
     */
    protected $report;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->connectionMock = $this->getMockForAbstractClass(AdapterInterface::class);
        $this->eavConfigMock = $this->createMock(Config::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);

        /** @var ModuleResource|MockObject $resourceMock */
        $this->resourceMock = $this->createMock(ModuleResource::class);
        $this->resourceMock->expects($this->once())
            ->method('getConnection')
            ->willReturn($this->connectionMock);

        /** @var \Magento\Eav\Model\ConfigFactory|MockObject $eavConfigFactoryMock */
        $eavConfigFactoryMock = $this->createPartialMock(\Magento\Eav\Model\ConfigFactory::class, ['create']);
        $eavConfigFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->eavConfigMock);

        $this->report = $this->objectManagerHelper->getObject(
            $this->reportNamespace,
            [
                'logger' => $this->loggerMock,
                'resource' => $this->resourceMock,
                'eavConfigFactory' => $eavConfigFactoryMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testGenerateWithException()
    {
        $expectedResult = $this->getExpectedResult();
        $e = new \Exception();
        $this->resourceMock->expects($this->once())
            ->method('getTable')
            ->willThrowException($e);
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($e);

        $this->assertEquals($expectedResult, $this->report->generate());
    }

    /**
     * @param string $entityType
     * @param int $entityTypeId
     */
    protected function entityTypeTest($entityType, $entityTypeId)
    {
        /** @var Type|MockObject $typeMock */
        $typeMock = $this->createMock(Type::class);
        $typeMock->expects($this->once())
            ->method('getId')
            ->willReturn($entityTypeId);
        $this->eavConfigMock->expects($this->once())
            ->method('getEntityType')
            ->with($entityType)
            ->willReturn($typeMock);
    }

    /**
     * @param string $attributeCode
     * @param string $eavTable
     * @param int $entityTypeId
     * @return string
     */
    protected function getSqlAttributeId($attributeCode, $eavTable, $entityTypeId)
    {
        return 'SELECT `attribute_id`'
        . ' FROM `' . $eavTable . '`'
        . ' WHERE `attribute_code` = "' . $attributeCode . '" AND `entity_type_id` = ' . $entityTypeId;
    }
}
