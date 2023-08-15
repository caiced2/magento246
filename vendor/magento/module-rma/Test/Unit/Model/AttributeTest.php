<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Model;

use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\Config;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rma\Model\Attribute;
use Magento\Store\Model\StoreManager;
use Magento\Store\Model\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AttributeTest extends TestCase
{
    /**
     * @var Attribute
     */
    protected $rmaAttribute;

    /**
     * @var StoreManager|\PHPUnit_Framework_MockObject
     */
    protected $storeManagerMock;

    /**
     * @var \Magento\Rma\Model\ResourceModel\Item\Attribute|\PHPUnit_Framework_MockObject
     */
    protected $getResourceMock;

    /**
     * @var Website|\PHPUnit_Framework_MockObject
     */
    protected $websiteMock;

    /**
     * @var ObjectManager
     */
    protected $objectManagerHelper;

    /**
     * @var Config|MockObject
     */
    protected $eavConfigMock;

    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManager($this);
        $this->storeManagerMock = $this->createPartialMock(StoreManager::class, ['getWebsite']);
        $this->getResourceMock = $this->createPartialMock(
            \Magento\Rma\Model\ResourceModel\Item\Attribute::class,
            ['getUsedInForms', 'getIdFieldName']
        );
        $this->eavConfigMock = $this->getMockBuilder(Config::class)
            ->setMethods(['clear'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->rmaAttribute = $this->objectManagerHelper->getObject(
            Attribute::class,
            [
                'storeManager' => $this->storeManagerMock,
                'resource' => $this->getResourceMock,
                'eavConfig' => $this->eavConfigMock
            ]
        );
    }

    public function testSetWebsite()
    {
        $this->storeManagerMock->expects($this->once())->method('getWebsite')->with(12);
        $this->assertEquals($this->rmaAttribute, $this->rmaAttribute->setWebsite(12));
    }

    public function testGetWebsite()
    {
        $this->storeManagerMock->expects($this->once())
            ->method('getWebsite')
            ->willReturn($this->websiteMock);
        $this->assertEquals($this->websiteMock, $this->rmaAttribute->getWebsite());
    }

    public function testGetUsedInForms()
    {
        $this->getResourceMock->expects($this->once())
            ->method('getUsedInForms')
            ->with($this->rmaAttribute)
            ->willReturn('test_value');
        $this->assertEquals('test_value', $this->rmaAttribute->getUsedInForms());
    }

    /**
     * @dataProvider getValidateRulesDataProvider
     * @param array $data
     */
    public function testGetValidateRules(array $data)
    {
        $modelClassName = Attribute::class;
        $rmaAttribute = $this->getMockForAbstractClass($modelClassName, [], '', false);

        $serializerMock = $this->getMockForAbstractClass(SerializerInterface::class);

        $reflection = new \ReflectionClass($modelClassName);
        $reflectionProperty = $reflection->getProperty('serializer');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($rmaAttribute, $serializerMock);

        $rmaAttribute->setData(AttributeInterface::VALIDATE_RULES, $data);

        if (empty($data)) {
            $this->assertEmpty($rmaAttribute->getValidateRules());
        } else {
            $this->assertNotEmpty($rmaAttribute->getValidateRules());
        }
    }

    /**
     * @dataProvider setValidateRulesDataProvider
     * @param array|string $rules
     */
    public function testSetValidateRules($rules)
    {
        $modelClassName = Attribute::class;
        $rmaAttribute = $this->getMockForAbstractClass($modelClassName, [], '', false);

        $serializerMock = $this->getMockForAbstractClass(SerializerInterface::class);

        $reflection = new \ReflectionClass($modelClassName);
        $reflectionProperty = $reflection->getProperty('serializer');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($rmaAttribute, $serializerMock);

        $this->assertEquals($rmaAttribute, $rmaAttribute->setValidateRules($rules));
    }

    /**
     * @dataProvider getIsRequiredDataProvider
     * @param array $data
     */
    public function testGetIsRequired($data)
    {
        $rmaAttribute = $this->objectManagerHelper->getObject(Attribute::class, ['data' => $data]);
        $this->assertEquals(1, $rmaAttribute->getIsRequired());
    }

    /**
     * @dataProvider getIsVisibleDataProvider
     * @param array $data
     */
    public function testGetIsVisible($data)
    {
        $rmaAttribute = $this->objectManagerHelper->getObject(Attribute::class, ['data' => $data]);
        $this->assertEquals(1, $rmaAttribute->getIsVisible());
    }

    /**
     * @dataProvider getMultilineCountDataProvider
     * @param array $data
     */
    public function testGetMultilineCount($data)
    {
        $rmaAttribute = $this->objectManagerHelper->getObject(Attribute::class, ['data' => $data]);
        $this->assertEquals(1, $rmaAttribute->getMultilineCount());
    }

    public function getValidateRulesDataProvider()
    {
        $serialize = json_encode(['test-key' => 'test-value']);
        return [
            [
                'data' => [
                    'validate_rules' => [
                        'key' => 'value',
                    ],
                ],
            ],
            [
                'data' => [
                    'validate_rules' => $serialize,
                ]
            ],
            [
                'data' => []
            ]
        ];
    }

    public function setValidateRulesDataProvider()
    {
        return [
            [
                'rules' => [
                    'validate_rules' => [
                        'key' => 'value',
                    ],
                ],
            ],
            [
                'rules' => ''
            ]
        ];
    }

    public function getIsRequiredDataProvider()
    {
        return [
            [
                'data' => [
                    'is_required' => 1,
                ],
            ],
            [
                'data' => [
                    'scope_is_required' => 1,
                ]
            ]
        ];
    }

    public function getIsVisibleDataProvider()
    {
        return [
            [
                'data' => [
                    'is_visible' => 1,
                ],
            ],
            [
                'data' => [
                    'scope_is_visible' => 1,
                ]
            ]
        ];
    }

    public function getDefaultValueDataProvider()
    {
        return [
            [
                'data' => [
                    'default_value' => 1,
                ],
            ],
            [
                'data' => [
                    'scope_default_value' => 1,
                ]
            ]
        ];
    }

    public function getMultilineCountDataProvider()
    {
        return [
            [
                'data' => [
                    'multiline_count' => 1,
                ],
            ],
            [
                'data' => [
                    'scope_multiline_count' => 1,
                ]
            ]
        ];
    }

    public function testAfterSaveEavCache()
    {
        $this->eavConfigMock
            ->expects($this->once())
            ->method('clear');
        $this->rmaAttribute->afterSave();
    }

    public function testAfterDeleteEavCache()
    {
        $this->eavConfigMock
            ->expects($this->once())
            ->method('clear');
        $this->rmaAttribute->afterDelete();
    }
}
