<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Util;

use Laminas\Code\Reflection\ClassReflection;
use Laminas\Code\Reflection\MethodReflection;
use Magento\Framework\DataObject;
use Magento\Framework\Reflection\FieldNamer;
use Magento\Framework\Reflection\TypeProcessor;
use ReflectionException;
use Throwable;

class ReflectionHelper
{
    public const TYPE_VOID = 'void';

    /**
     * @var string[]
     */
    private array $simpleTypes = [
        'bool',
        'boolean',
        'int',
        'integer',
        'string',
        'object',
        'float',
        'array',
        'mixed',
        'null',
        'void',
    ];

    /**
     * @var FieldNamer
     */
    private FieldNamer $fieldNamer;

    /**
     * @var TypeProcessor
     */
    private TypeProcessor $typeProcessor;

    /**
     * @param FieldNamer $fieldNamer
     * @param TypeProcessor $typeProcessor
     */
    public function __construct(
        FieldNamer $fieldNamer,
        TypeProcessor $typeProcessor
    ) {
        $this->fieldNamer = $fieldNamer;
        $this->typeProcessor = $typeProcessor;
    }

    /**
     * Gets method return type.
     *
     * @param MethodReflection $methodReflection
     * @param ClassReflection $classReflection
     * @return string|null
     */
    public function getReturnType(MethodReflection $methodReflection, ClassReflection $classReflection): ?string
    {
        try {
            $returnType = $this->typeProcessor->getGetterReturnType($methodReflection)['type'];
        } catch (Throwable $e) {
            return 'mixed';
        }

        if ($returnType === null) {
            return 'null';
        }

        if ($this->isSimple($returnType)) {
            return $returnType;
        }

        if (in_array($returnType, ['$this', 'this', 'self'])) {
            $returnType = $classReflection->getName();
        }

        return $this->typeProcessor->resolveFullyQualifiedClassName($classReflection, $returnType);
    }

    /**
     * Converts array type to single type by removing `[]` part
     *
     * @param string $type
     * @return string
     */
    public function arrayTypeToSingle(string $type): string
    {
        return str_replace('[]', '', $type);
    }

    /**
     * Retrieves objects properties by a class name in [type, name] format based on get*, is*, and has* methods.
     *
     * For classes extending Magento\Framework\DataObject, returns object properties based only on the get*, is*, or
     * has* methods that access _data.
     *
     * @param string $fqcn
     * @return array
     * @throws ReflectionException
     */
    public function getObjectProperties(string $fqcn): array
    {
        $result = [];

        $refClass = new ClassReflection($fqcn);
        $methodList = $refClass->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($methodList as $method) {
            if ($method->class != $refClass->getName()) {
                continue;
            }

            if ($method->getNumberOfParameters() != 0) {
                continue;
            }

            $propName = $this->fieldNamer->getFieldNameForMethodName($method->getName());
            if ($propName === null) {
                continue;
            }

            if (is_a($fqcn, DataObject::class, true) && !$this->hasDataAccess($method)) {
                continue;
            }

            $result[] = [
                'type' => str_replace('|null', '', $this->getReturnType($method, $refClass)),
                'name' => $propName
            ];
        }

        return $result;
    }

    /**
     * Checks if input method's body accesses the _data variable directly or through the getData or _getData methods
     *
     * @param MethodReflection $method
     * @return bool
     */
    private function hasDataAccess(MethodReflection $method): bool
    {
        $body = $method->getBody();
        return strpos($body, '$this->_data') !== false ||
            strpos($body, '$this->getData(') !== false ||
            strpos($body, '$this->_getData(') !== false;
    }

    /**
     * Checks if a type is an array.
     *
     * @param string|null $input
     * @return bool
     */
    public function isArray(?string $input): bool
    {
        if ($input == null) {
            return false;
        }

        return $input === 'array' || strpos($input, '[]') !== false;
    }

    /**
     * Checks if input string is a simple type.
     *
     * @param string $input
     * @return bool
     */
    public function isSimple(string $input): bool
    {
        return in_array($this->arrayTypeToSingle($input), $this->simpleTypes);
    }

    /**
     * Returns list of method parameters.
     *
     * @param MethodReflection $methodReflection
     * @return array
     */
    public function getMethodParameters(MethodReflection $methodReflection): array
    {
        $params = [];

        foreach ($methodReflection->getParameters() as $parameterReflection) {
            $params[] = [
                'name' => $parameterReflection->getName(),
                'type' => $parameterReflection->detectType(),
            ];
        }

        return $params;
    }
}
