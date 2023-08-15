<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Logging\Model\ResourceModel\Grid;

class Actions implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Magento\Logging\Helper\Data
     */
    protected $_helper;

    /**
     * @var \Magento\Logging\Model\ResourceModel\Event
     */
    protected $_resource;

    /**
     * @param \Magento\Logging\Helper\Data $loggingHelper
     * @param \Magento\Logging\Model\ResourceModel\Event $resource
     */
    public function __construct(
        \Magento\Logging\Helper\Data $loggingHelper,
        \Magento\Logging\Model\ResourceModel\Event $resource
    ) {
        $this->_helper = $loggingHelper;
        $this->_resource = $resource;
    }

    /**
     * Get options as array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $actions = [];
        $fieldValues = $this->_resource->getAllFieldValues('action');

        foreach ($fieldValues as $action) {
            $actions[$action] = $this->_helper->getLoggingActionTranslatedLabel($action);
        }
        return $actions;
    }
}
