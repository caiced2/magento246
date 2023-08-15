<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsGenerator\Generator;

use Magento\Framework\View\Element\BlockInterface;

/**
 * The helper class used for template rendering
 */
class ModuleBlock implements BlockInterface
{
    /**
     * @var Module
     */
    private Module $module;

    /**
     * @param Module $module
     */
    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    /**
     * Returns module name
     *
     * @return string
     */
    public function getModuleName(): string
    {
        return $this->module->getVendor() . '_' . $this->module->getName();
    }

    /**
     * @inheritDoc
     */
    public function toHtml(): string
    {
        return '';
    }

    /**
     * Returns list of module dependencies to add them into module.xml file
     *
     * @return array
     */
    public function getDependencies(): array
    {
        return array_unique(array_filter(array_column($this->module->getDependencies(), 'name')));
    }

    /**
     * Returns list of plugins that must be registered in di.xml
     *
     * @return array
     */
    public function getDiPlugins(): array
    {
        return array_merge($this->module->getPlugins(), [$this->module->getObserverEventPlugin()]);
    }

    /**
     * Returns namespace based on module vendor and name
     *
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->module->getVendor() . '\\' . $this->module->getName();
    }

    /**
     * Returns Module object.
     *
     * @return Module
     */
    public function getModule(): Module
    {
        return $this->module;
    }

    /**
     * Renders list of parameters for the given method.
     *
     * @param array $method
     * @return string
     */
    public function renderParametersForMethod(array $method): string
    {
        $result = '';

        if (isset($method['params'])) {
            foreach ($method['params'] as $param) {
                $result .= ', $' . $param['name'];

                if (isset($param['isDefaultValueAvailable']) && $param['isDefaultValueAvailable']) {
                    $result .= ' = ' . $param['defaultValue'];
                }
            }
        }

        return $result;
    }
}
