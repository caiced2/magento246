<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Logging\App\Action\Plugin;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Logging\Model\Processor;

class Log
{
    /**
     * @var Processor
     */
    protected Processor $_processor;

    /**
     * @var array
     */
    private array $extraParamMapping;

    /**
     * @param Processor $processor
     * @param array|null $extraParamMapping
     */
    public function __construct(Processor $processor, array $extraParamMapping = [])
    {
        $this->_processor = $processor;
        $this->extraParamMapping = $extraParamMapping;
    }

    /**
     * Mark actions for logging, if required
     *
     * @param ActionInterface $subject
     * @param RequestInterface $request
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeDispatch(ActionInterface $subject, RequestInterface $request)
    {
        $beforeForwardInfo = $request->getBeforeForwardInfo();

        // Always use current action name bc basing on
        // it we make decision about access granted or denied
        $actionName = $request->getActionName();

        if (empty($beforeForwardInfo)) {
            $fullActionName = $request->getFullActionName();
        } else {
            $fullActionName = [$request->getRouteName()];

            if (isset($beforeForwardInfo['controller_name'])) {
                $fullActionName[] = $beforeForwardInfo['controller_name'];
            } else {
                $fullActionName[] = $request->getControllerName();
            }

            if (isset($beforeForwardInfo['action_name'])) {
                $fullActionName[] = $beforeForwardInfo['action_name'];
            } else {
                $fullActionName[] = $actionName;
            }

            $fullActionName = \implode('_', $fullActionName);
        }

        $fullActionName = $this->mapExtraParam($request, $fullActionName);
        $this->_processor->initAction($fullActionName, $actionName);
    }

    /**
     * Map Extra Parameters for grid component tags
     *
     * @param RequestInterface $request
     * @param string $fullActionName
     * @return string
     */
    private function mapExtraParam(RequestInterface $request, string $fullActionName) :string
    {
        if (!empty($this->extraParamMapping) && isset($this->extraParamMapping[$fullActionName])) {
            $extraParam = $this->extraParamMapping[$fullActionName];
            if ($request->getParam($extraParam) != "") {
                $fullActionName = $fullActionName . '_' . $request->getParam($extraParam);
            }
        }

        return $fullActionName;
    }
}
