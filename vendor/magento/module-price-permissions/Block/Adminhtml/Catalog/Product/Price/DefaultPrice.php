<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\PricePermissions\Block\Adminhtml\Catalog\Product\Price;

class DefaultPrice extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Price permissions data
     *
     * @var \Magento\PricePermissions\Helper\Data
     */
    protected $_pricePermissionsData = null;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\PricePermissions\Helper\Data $pricePermissionsData
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\PricePermissions\Helper\Data $pricePermissionsData,
        array $data = []
    ) {
        $this->_pricePermissionsData = $pricePermissionsData;
        parent::__construct($context, $data);
    }

    /**
     * Render Default Product Price field as disabled if user does not have enough permissions
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        if (!$this->_pricePermissionsData->getCanAdminEditProductPrice()) {
            $element->setReadonly(true, true);
        }
        return parent::_getElementHtml($element);
    }
}
