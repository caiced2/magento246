<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesArchive\Test\Unit\Model;

use Magento\Framework\DataObject;
use Magento\Framework\ObjectManager\ObjectManager;
use Magento\Sales\Model\ResourceModel\Order;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo;
use Magento\Sales\Model\ResourceModel\Order\Invoice;
use Magento\Sales\Model\ResourceModel\Order\Shipment;
use Magento\SalesArchive\Model\ArchivalList;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ArchivalListTest extends TestCase
{
    /**
     * @var ArchivalList $_model
     */
    protected $_model;

    /**
     * @var MockObject
     */
    protected $_objectManagerMock;

    protected function setUp(): void
    {
        $this->_objectManagerMock = $this->createPartialMock(
            ObjectManager::class,
            ['get', 'create']
        );

        $this->_model = new ArchivalList($this->_objectManagerMock);
    }

    /**
     * @dataProvider dataProviderGetResourcePositive
     * @param string $entity
     * @param string $className
     */
    public function testGetResourcePositive($entity, $className)
    {
        $this->_objectManagerMock->expects($this->once())->method('get')->willReturnArgument(0);
        $this->assertEquals($className, $this->_model->getResource($entity));
    }

    public function dataProviderGetResourcePositive()
    {
        return [
            ['order', Order::class],
            ['invoice', Invoice::class],
            ['shipment', Shipment::class],
            ['creditmemo', Creditmemo::class]
        ];
    }

    public function testGetResourceNegative()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('FAKE!ENTITY entity isn\'t allowed');
        $this->_model->getResource('FAKE!ENTITY');
    }

    /**
     * @dataProvider dataGetEntityByObject
     * @param string|bool $entity
     * @param string $className
     */
    public function testGetEntityByObject($entity, $className)
    {
        $object = $this->createMock($className);
        $this->assertEquals($entity, $this->_model->getEntityByObject($object));
    }

    public function dataGetEntityByObject()
    {
        return [
            ['order', Order::class],
            ['invoice', Invoice::class],
            ['shipment', Shipment::class],
            ['creditmemo', Creditmemo::class],
            [false, DataObject::class]
        ];
    }
}
