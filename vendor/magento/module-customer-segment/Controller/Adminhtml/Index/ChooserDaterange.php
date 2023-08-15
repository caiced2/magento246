<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Controller\Adminhtml\Index;

class ChooserDaterange extends \Magento\CustomerSegment\Controller\Adminhtml\Index
{
    /**
     * Date range chooser action
     *
     * @return void
     */
    public function execute()
    {
        $block = $this->_view->getLayout()->createBlock(
            \Magento\CustomerSegment\Block\Adminhtml\Widget\Chooser\Daterange::class
        );
        if ($block) {
            // set block data from request
            $block->setTargetElementId($this->getRequest()->getParam('value_element_id'));
            $selectedValues = $this->getRequest()->getParam('selected');
            if (!empty($selectedValues) && is_array($selectedValues) && 1 === count($selectedValues)) {
                $block->setRangeValue(array_shift($selectedValues));
            }

            $this->getResponse()->setBody($block->toHtml());
        }
    }
}
