<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsGenerator\Generator\Collector;

use Magento\AdobeCommerceEventsClient\Util\EventCodeConverter;
use Magento\AdobeCommerceEventsGenerator\Generator\Collector\Reflection\MethodParametersCollector;
use Magento\AdobeCommerceEventsGenerator\Generator\CollectorInterface;
use ReflectionClass;
use ReflectionException;

/**
 * Collects resource model plugin information by event code.
 */
class ResourceModelCollector implements CollectorInterface
{
    /**
     * @var ModuleCollector
     */
    private ModuleCollector $moduleCollector;

    /**
     * @var EventCodeConverter
     */
    private EventCodeConverter $eventCodeConverter;

    /**
     * @var MethodParametersCollector
     */
    private MethodParametersCollector $parametersCollector;

    /**
     * @param EventCodeConverter $eventCodeConverter
     * @param ModuleCollector $moduleCollector
     * @param MethodParametersCollector $parametersCollector
     */
    public function __construct(
        EventCodeConverter $eventCodeConverter,
        ModuleCollector $moduleCollector,
        MethodParametersCollector $parametersCollector
    ) {
        $this->eventCodeConverter = $eventCodeConverter;
        $this->moduleCollector = $moduleCollector;
        $this->parametersCollector = $parametersCollector;
    }

    /**
     * Collects resource model plugin information by event code.
     *
     * @param string $eventCode
     * @return array
     * @throws CollectorException
     */
    public function collect(string $eventCode): array
    {
        $className = $this->eventCodeConverter->convertToFqcn($eventCode);
        $methodName = $this->eventCodeConverter->extractMethodName($eventCode);

        try {
            $resourceModelReflection = new ReflectionClass($className);
        } catch (ReflectionException $exception) {
            throw new CollectorException(sprintf(
                'Resource model class "%s" for event code "%s" was not found',
                $className,
                $eventCode
            ));
        }

        try {
            $methodReflection = $resourceModelReflection->getMethod($methodName);
        } catch (ReflectionException $exception) {
            throw new CollectorException(sprintf(
                'Could not find a method: "%s" in the resource model class "%s" for event code "%s"',
                $methodName,
                $className,
                $eventCode
            ));
        }

        $this->moduleCollector->collect($resourceModelReflection);

        return [
            $className => [
                [
                    'name' => $methodReflection->getName(),
                    'params' => $this->parametersCollector->collect($methodReflection)
                ]
            ]
        ];
    }
}
