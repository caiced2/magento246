<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Reminder adminhtml promo rules notice block
 */
namespace Magento\Reminder\Block\Adminhtml\Promo;

/**
 * @api
 * @since 100.0.2
 */
class Notice extends \Magento\Backend\Block\Template
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * Rule Resource Model
     *
     * @var \Magento\Reminder\Model\ResourceModel\Rule
     */
    protected $_resourceModel;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Reminder\Model\ResourceModel\Rule $resourceModel
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Reminder\Model\ResourceModel\Rule $resourceModel,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_coreRegistry = $registry;
        $this->_resourceModel = $resourceModel;
    }

    /**
     * Preparing block layout
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        if ($salesRule = $this->_coreRegistry->registry('current_promo_quote_rule')) {
            if ($count = $this->_resourceModel->getAssignedRulesCount($salesRule->getId())) {
                $confirm = __(
                    'This rule is assigned to %1 automated reminder rule(s). '
                    . 'Deleting this rule will automatically unassign it.',
                    $count
                );
                $block = $this->getLayout()->getBlock('promo_quote_edit');
                if ($block instanceof \Magento\SalesRule\Block\Adminhtml\Promo\Quote\Edit) {
                    $block->updateButton(
                        'delete',
                        'onclick',
                        'deleteConfirm(\'' . $confirm . '\', \'' . $block->getDeleteUrl() . '\')'
                    );
                }
            }
        }
        return $this;
    }
}
