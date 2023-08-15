<?php
/**
 * Grid row url generator
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Model\Segment\Report\Detail\Grid;

class UrlGenerator extends \Magento\Backend\Model\Widget\Grid\Row\UrlGenerator
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registryManager;

    /**
     * @param \Magento\Backend\Model\UrlInterface $backendUrl
     * @param \Magento\Framework\Registry $registry
     * @param array $args
     */
    public function __construct(
        \Magento\Backend\Model\UrlInterface $backendUrl,
        \Magento\Framework\Registry $registry,
        array $args = []
    ) {
        $this->_registryManager = $registry;
        parent::__construct($backendUrl, $args);
    }

    /**
     * Convert template params array and merge with preselected params
     *
     * @param \Magento\Framework\DataObject $item
     * @return array|mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _prepareParameters($item)
    {
        $params = [];
        foreach ($this->_extraParamsTemplate as $paramKey => $paramValueMethod) {
            $params[$paramKey] = $this->_registryManager->registry('current_customer_segment')->{$paramValueMethod}();
        }
        return array_merge($this->_params, $params);
    }
}
