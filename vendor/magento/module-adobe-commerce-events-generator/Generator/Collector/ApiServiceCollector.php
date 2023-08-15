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
 * Collects Api interface plugin information by event code.
 */
class ApiServiceCollector implements CollectorInterface
{
    /**
     * @var EventCodeConverter
     */
    private EventCodeConverter $eventCodeConvertor;

    /**
     * @var ModuleCollector
     */
    private ModuleCollector $moduleCollector;

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
        $this->eventCodeConvertor = $eventCodeConverter;
        $this->moduleCollector = $moduleCollector;
        $this->parametersCollector = $parametersCollector;
    }

    /**
     * Collects Api interface plugin information by event code.
     *
     * @param string $eventCode
     * @return array
     * @throws CollectorException
     */
    public function collect(string $eventCode): array
    {
        $interfaceName = $this->eventCodeConvertor->convertToFqcn($eventCode) . 'Interface';
        $methodName = $this->eventCodeConvertor->extractMethodName($eventCode);

        try {
            $interfaceReflection = new ReflectionClass($interfaceName);
        } catch (ReflectionException $exception) {
            throw new CollectorException(sprintf(
                'Interface "%s" for event code "%s" was not found',
                $interfaceName,
                $eventCode
            ));
        }

        try {
            $methodReflection = $interfaceReflection->getMethod($methodName);
        } catch (ReflectionException $exception) {
            throw new CollectorException(sprintf(
                'Could not find a method: "%s" in the api interface "%s" for event code "%s"',
                $methodName,
                $interfaceName,
                $eventCode
            ));
        }

        $this->moduleCollector->collect($interfaceReflection);

        return [
            $interfaceName => [
                [
                    'name' => $methodName,
                    'params' => $this->parametersCollector->collect($methodReflection)
                ]
            ]
        ];
    }
}
