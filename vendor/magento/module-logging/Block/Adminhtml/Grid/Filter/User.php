<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * User column filter for Event Log grid
 */
namespace Magento\Logging\Block\Adminhtml\Grid\Filter;

class User extends \Magento\Backend\Block\Widget\Grid\Column\Filter\Select
{
    /**
     * @var \Magento\Logging\Model\ResourceModel\EventFactory
     */
    protected $eventFactory;

    /**
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Framework\DB\Helper $resourceHelper
     * @param \Magento\Logging\Model\ResourceModel\EventFactory $eventFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Framework\DB\Helper $resourceHelper,
        \Magento\Logging\Model\ResourceModel\EventFactory $eventFactory,
        array $data = []
    ) {
        $this->eventFactory = $eventFactory;
        parent::__construct($context, $resourceHelper, $data);
    }

    /**
     * Build filter options list
     *
     * @return array
     */
    public function _getOptions()
    {
        $options = [['value' => '', 'label' => __('All Users')]];
        foreach ($this->eventFactory->create()->getUserNames() as $username) {
            $options[] = ['value' => $username, 'label' => $username];
        }
        return $options;
    }

    /**
     * Filter condition getter
     *
     * @return string
     */
    public function getCondition()
    {
        return $this->getValue();
    }
}
