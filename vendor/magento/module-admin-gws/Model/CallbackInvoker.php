<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AdminGws\Model;

/**
 * @api
 * @since 100.0.2
 */
class CallbackInvoker
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Invoke specified callback depending on whether it is a string or array
     *
     * @param string|array $callback
     * @param string $defaultFactoryClassName
     * @param object $passThroughObject
     * @return mixed
     */
    public function invoke($callback, $defaultFactoryClassName, $passThroughObject)
    {
        $class = $defaultFactoryClassName;
        $method = $callback;
        if (is_array($callback)) {
            list($class, $method) = $callback;
        }

        $object = $this->objectManager->get($class);
        if (method_exists($object, $method)) {
            return call_user_func_array([$object, $method], [$passThroughObject]);
        }
        return null;
    }
}
