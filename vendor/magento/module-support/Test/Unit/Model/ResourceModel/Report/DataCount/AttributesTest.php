<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\ResourceModel\Report\DataCount;

use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Type;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Support\Model\ResourceModel\Report\DataCount\Attributes;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AttributesTest extends TestCase
{
    /**
     * @var \Magento\Support\Model\ResourceModel\Report\DataCount\Attributes
     */
    protected $attributes;

    /**
     * @var \Magento\Eav\Model\ConfigFactory|MockObject
     */
    protected $eavConfigFactoryMock;

    /**
     * @var Config|MockObject
     */
    protected $eavConfigMock;

    /**
     * @var Type|MockObject
     */
    protected $entityTypeMock;

    /**
     * @var AdapterInterface|MockObject
     */
    protected $connectionMock;

    /**
     * @var AbstractDb|MockObject
     */
    protected $resourceMock;

    /**
     * @var ObjectManager
     */
    protected $objectManagerHelper;

    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManager($this);

        $this->eavConfigFactoryMock = $this->createPartialMock(\Magento\Eav\Model\ConfigFactory::class, ['create']);
        $this->eavConfigMock = $this->createMock(Config::class);

        $this->entityTypeMock = $this->createMock(Type::class);
        $this->entityTypeMock = $this->getMockBuilder(Type::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->connectionMock = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->resourceMock = $this->getMockBuilder(AbstractDb::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connectionMock);

        $this->attributes = $this->objectManagerHelper->getObject(
            Attributes::class,
            ['eavConfigFactory' => $this->eavConfigFactoryMock]
        );
    }

    /**
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExecute()
    {
        $entityTypeId = 1;
        $type = 'customer';
        $info = [
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '1',
                'is_used_for_customer_segment' => '0',
                'is_visible' => '0'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '0',
                'is_used_for_customer_segment' => '1',
                'is_visible' => '0'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '1',
                'is_used_for_customer_segment' => '0',
                'is_visible' => '1'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '1',
                'is_used_for_customer_segment' => '1',
                'is_visible' => '0'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '1',
                'is_used_for_customer_segment' => '1',
                'is_visible' => '0'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '1',
                'is_used_for_customer_segment' => '0',
                'is_visible' => '1'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '0',
                'is_used_for_customer_segment' => '1',
                'is_visible' => '0'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '1',
                'is_used_for_customer_segment' => '1',
                'is_visible' => '1'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '1',
                'is_used_for_customer_segment' => '1',
                'is_visible' => '1'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '0',
                'is_used_for_customer_segment' => '1',
                'is_visible' => '0'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '1',
                'is_used_for_customer_segment' => '1',
                'is_visible' => '1'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '1',
                'is_used_for_customer_segment' => '1',
                'is_visible' => '1'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '0',
                'is_used_for_customer_segment' => '0',
                'is_visible' => '0'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '1',
                'is_used_for_customer_segment' => '0',
                'is_visible' => '0'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '0',
                'is_used_for_customer_segment' => '0',
                'is_visible' => '0'
            ],
            [
                'backend_type' => 'int',
                'is_user_defined' => '0',
                'is_system' => '1',
                'is_used_for_customer_segment' => '0',
                'is_visible' => '0'
            ],
            [
                'backend_type' => 'int',
                'is_user_defined' => '0',
                'is_system' => '1',
                'is_used_for_customer_segment' => '0',
                'is_visible' => '0'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '1',
                'is_used_for_customer_segment' => '0',
                'is_visible' => '0'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '1',
                'is_used_for_customer_segment' => '0',
                'is_visible' => '0'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '1',
                'is_used_for_customer_segment' => '0',
                'is_visible' => '0'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '0',
                'is_used_for_customer_segment' => '0',
                'is_visible' => '0'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '0',
                'is_used_for_customer_segment' => '0',
                'is_visible' => '0'
            ],
            [
                'backend_type' => 'static',
                'is_user_defined' => '0',
                'is_system' => '1',
                'is_used_for_customer_segment' => '0',
                'is_visible' => '1'
            ]
        ];

        $expectedData = [
            [
                'Customer Attributes',
                23,
                'Attributes Flags: is_user_defined: 0; is_system: 16; is_used_for_customer_segment: 9; is_visible: 7; '
            ],
            [
                '', '', 'Attributes Types: static: 21; int: 2; '
            ]
        ];

        $this->eavConfigFactoryMock->expects($this->once())->method('create')->willReturn($this->eavConfigMock);
        $this->eavConfigMock->expects($this->once())->method('getEntityType')->with($type)->willReturn(
            $this->entityTypeMock
        );
        $this->entityTypeMock->expects($this->once())->method('getId')->willReturn($entityTypeId);

        $this->resourceMock->expects($this->atLeastOnce())->method('getTable')->willReturnMap(
            [
                ['customer_eav_attribute', 'customer_eav_attribute'],
                ['eav_attribute', 'eav_attribute']
            ]
        );
        $this->connectionMock->expects($this->once())->method('fetchAll')->willReturn($info);

        $this->assertSame($expectedData, $this->attributes->getAttributesCount($type, $this->resourceMock));
    }
}
