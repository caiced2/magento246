<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Frontend helper block to add links
 *
 */
namespace Magento\AdvancedCheckout\Block\Customer;

use Magento\Customer\Block\Account\SortLinkInterface;

/**
 * "Order by SKU" link.
 *
 * @api
 * @since 100.0.2
 */
class Link extends \Magento\Framework\View\Element\Html\Link\Current implements SortLinkInterface
{
    /**
     * @var \Magento\AdvancedCheckout\Helper\Data
     */
    protected $_customerHelper;

    /**
     * @codeCoverageIgnore
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\App\DefaultPathInterface $defaultPath
     * @param \Magento\AdvancedCheckout\Helper\Data $customerHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\DefaultPathInterface $defaultPath,
        \Magento\AdvancedCheckout\Helper\Data $customerHelper,
        array $data = []
    ) {
        parent::__construct($context, $defaultPath, $data);
        $this->_customerHelper = $customerHelper;
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->_customerHelper->isSkuApplied()) {
            return parent::_toHtml();
        } else {
            return '';
        }
    }

    /**
     * {@inheritdoc}
     * @since 100.2.0
     */
    public function getSortOrder()
    {
        return $this->getData(self::SORT_ORDER);
    }
}
