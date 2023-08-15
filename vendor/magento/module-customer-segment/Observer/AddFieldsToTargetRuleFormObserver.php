<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Observer;

use Magento\Framework\Event\ObserverInterface;

class AddFieldsToTargetRuleFormObserver implements ObserverInterface
{
    /**
     * @var \Magento\CustomerSegment\Helper\Data
     */
    private $_segmentHelper;

    /**
     * @param \Magento\CustomerSegment\Helper\Data $segmentHelper
     */
    public function __construct(
        \Magento\CustomerSegment\Helper\Data $segmentHelper
    ) {
        $this->_segmentHelper = $segmentHelper;
    }

    /**
     * Add Customer Segment form fields to Target Rule form
     *
     * Observe  targetrule_edit_tab_main_after_prepare_form event
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->_segmentHelper->isEnabled()) {
            return;
        }
        /* @var $form \Magento\Framework\Data\Form */
        $form = $observer->getEvent()->getForm();
        /** @var \Magento\Framework\DataObject $model */
        $model = $observer->getEvent()->getModel();
        /** @var \Magento\Framework\View\Element\AbstractBlock $block */
        $block = $observer->getEvent()->getBlock();

        /** @var \Magento\Backend\Block\Widget\Form\Element\Dependence $fieldDependencies */
        $fieldDependencies = $block->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Form\Element\Dependence::class
        );
        $block->setChild('form_after', $fieldDependencies);

        $this->_segmentHelper->addSegmentFieldsToForm($form, $model, $fieldDependencies);
    }
}
