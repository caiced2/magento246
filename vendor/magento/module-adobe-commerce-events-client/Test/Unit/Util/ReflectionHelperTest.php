<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Test\Unit\Util;

use Magento\AdobeCommerceEventsClient\Util\ReflectionHelper;
use Magento\Framework\Reflection\FieldNamer;
use Magento\Framework\Reflection\TypeProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the @see ReflectionHelper Class
 */
class ReflectionHelperTest extends TestCase
{
    /**
     * @var TypeProcessor|MockObject
     */
    private $typeProcessorMock;

    /**
     * @var ReflectionHelper
     */
    private ReflectionHelper $reflectionHelper;

    public function setUp(): void
    {
        $this->typeProcessorMock = $this->createMock(TypeProcessor::class);
        $this->reflectionHelper = new ReflectionHelper(new FieldNamer(), $this->typeProcessorMock);
    }

    /**
     * Tests collection of properties from 'Magento\AdobeCommerceEventsClient\Test\Unit\Util\SampleClass'.
     *
     * @return void
     */
    public function testGetDataRelatedObjectPropertiesFromClass(): void
    {
        $this->typeProcessorMock->expects(self::exactly(3))
            ->method('getGetterReturnType')
            ->withConsecutive(
                [
                    $this->callback(
                        fn($method) => $method->getName() == 'isAvailable'
                    )
                ],
                [
                    $this->callback(
                        fn($method) => $method->getName() == 'getItemName'
                    )
                ],
                [
                    $this->callback(
                        fn($method) => $method->getName() == 'getPrice'
                    )
                ]
            )
            ->willReturnOnConsecutiveCalls(
                ['type' => 'bool'],
                ['type' => 'string'],
                ['type' => 'float']
            );

        $objectProperties = $this->reflectionHelper->getObjectProperties(
            'Magento\AdobeCommerceEventsClient\Test\Unit\Util\_files\SampleClass'
        );

        $this->assertEquals(
            [
                [
                    'type' => 'bool',
                    'name' => 'available'
                ],
                [
                    'type' => 'string',
                    'name' => 'item_name'
                ],
                [
                    'type' => 'float',
                    'name' => 'price'
                ]
            ],
            $objectProperties
        );
    }

    /**
     * Tests collection of properties from 'Magento\AdobeCommerceEventsClient\Test\Unit\Util\_files\SampleInterface'.
     *
     * @return void
     */
    public function testGetDataRelatedObjectPropertiesFromInterface(): void
    {
        $this->typeProcessorMock->expects(self::exactly(3))
            ->method('getGetterReturnType')
            ->withConsecutive(
                [
                    $this->callback(
                        fn($method) => $method->getName() == 'getId'
                    )
                ],
                [
                    $this->callback(
                        fn($method) => $method->getName() == 'getAttributeSetId'
                    )
                ],
                [
                    $this->callback(
                        fn($method) => $method->getName() == 'isAvailable'
                    )
                ]
            )
            ->willReturnOnConsecutiveCalls(
                ['type' => 'int'],
                ['type' => 'int'],
                ['type' => 'bool']
            );

        $objectProperties = $this->reflectionHelper->getObjectProperties(
            'Magento\AdobeCommerceEventsClient\Test\Unit\Util\_files\SampleInterface'
        );

        $this->assertEquals(
            [
                [
                    'type' => 'int',
                    'name' => 'id'
                ],
                [
                    'type' => 'int',
                    'name' => 'attribute_set_id'
                ],
                [
                    'type' => 'bool',
                    'name' => 'available'
                ]
            ],
            $objectProperties
        );
    }
}
