<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Block\Adminhtml\Manage;

/**
 * Accordion for different product sources for adding to shopping cart
 *
 * @api
 * @author     Magento Core Team <core@magentocommerce.com>
 * @since 100.0.2
 */
class Accordion extends \Magento\Backend\Block\Widget\Accordion
{
    /**
     * Add accordion items based on layout updates
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->_authorization->isAllowed('Magento_AdvancedCheckout::update')) {
            return parent::_toHtml();
        }
        $layout = $this->getLayout();
        /** @var $child \Magento\Framework\View\Element\AbstractBlock  */
        foreach ($layout->getChildBlocks($this->getNameInLayout()) as $child) {
            $name = $child->getNameInLayout();
            $data = ['title' => $child->getHeaderText(), 'open' => false];
            if ($child->hasData('open')) {
                $data['open'] = $child->getData('open');
            }
            if ($child->hasData('content_url')) {
                $data['content_url'] = $child->getData('content_url');
            } else {
                $data['content'] = $layout->renderElement($name);
            }
            $this->addItem($name, $data);
        }

        return parent::_toHtml();
    }
}
