<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsGenerator\Test\Unit\Generator\Collector\Reflection;

use Magento\AdobeCommerceEventsGenerator\Generator\Collector\Reflection\MethodParametersCollector;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionNamedType;

/**
 * Tests for MethodParametersCollector class.
 */
class MethodParametersCollectorTest extends TestCase
{
    /**
     * @var MethodParametersCollector
     */
    private MethodParametersCollector $parametersCollector;

    public function setUp(): void
    {
        $this->parametersCollector = new MethodParametersCollector();
    }

    /**
     * Tests collection of a method parameter without a default value.
     *
     * @return void
     */
    public function testCollectParamNoDefault(): void
    {
        $this->testParameterCollection(null, false);
    }

    /**
     * Tests collection of a method parameter with a default null value.
     *
     * @return void
     */
    public function testCollectDefaultNullParam(): void
    {
        $this->testParameterCollection('nullParam', true, null, 'null');
    }

    /**
     * Tests collection of a method parameter with a default string value.
     *
     * @return void
     */
    public function testCollectDefaultStringParam(): void
    {
        $this->testParameterCollection('stringParam', true, 'default', '\'default\'');
    }

    /**
     * Tests collection of a method parameter with a default array value.
     *
     * @return void
     */
    public function testCollectDefaultArrayParam(): void
    {
        $this->testParameterCollection('arrayParam', true, [1, 2, 3], '[1, 2, 3]');
    }

    /**
     * Tests collection of a method parameter with a default boolean value.
     *
     * @return void
     */
    public function testCollectDefaultBoolParam(): void
    {
        $this->testParameterCollection('boolParam', true, true, 'true');
    }

    /**
     * Tests collection of a method parameter with a default int value.
     *
     * @return void
     */
    public function testCollectDefaultIntParam(): void
    {
        $this->testParameterCollection('intParam', true, 1, '1');
    }

    /**
     * @param string|null $typeName
     * @param bool $defaultAvailable
     * @param $defaultParamValue
     * @param $collectedDefaultValue
     * @return void
     */
    private function testParameterCollection(
        ?string $typeName,
        bool $defaultAvailable,
        $defaultParamValue = null,
        $collectedDefaultValue = null
    ): void {
        $parameterName = 'paramName';

        $parameter = $this->createMock(ReflectionParameter::class);

        if ($typeName != null) {
            $parameterType = $this->createMock(ReflectionNamedType::class);
            $parameterType->expects(self::once())
                ->method('getName')
                ->willReturn($typeName);
        } else {
            $parameterType = $typeName;
        }
        $parameter->expects(self::exactly($typeName? 2 : 1))
            ->method('getType')
            ->willReturn($parameterType);

        $parameter->expects(self::once())
            ->method('getName')
            ->willReturn($parameterName);
        $parameter->expects(self::once())
            ->method('isDefaultValueAvailable')
            ->willReturn($defaultAvailable);

        if ($defaultAvailable) {
            $parameter->expects(self::once())
                ->method('getDefaultValue')
                ->willReturn($defaultParamValue);
        } else {
            $parameter->expects(self::never())
                ->method('getDefaultValue');
        }

        $reflectionMethod = $this->createMock(ReflectionMethod::class);
        $reflectionMethod->expects(self::once())
            ->method('getParameters')
            ->willReturn([$parameter]);

        $expectedResult = [
            [
                'type' => $typeName,
                'name' => $parameterName,
                'isDefaultValueAvailable' => $defaultAvailable,
            ]
        ];
        if ($defaultAvailable) {
            $expectedResult[0]['defaultValue'] = $collectedDefaultValue;
        }

        $this->assertEquals(
            $expectedResult,
            $this->parametersCollector->collect($reflectionMethod)
        );
    }
}
