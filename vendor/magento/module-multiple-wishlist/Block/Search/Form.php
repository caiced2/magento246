<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Multiple Wishlist search form
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\MultipleWishlist\Block\Search;

/**
 * @api
 * @since 100.0.2
 */
class Form extends \Magento\Framework\View\Element\Template
{
    /**
     * Posted form data
     *
     * @var array|null
     */
    protected $_formData = null;

    /**
     * Retrieve form header
     *
     * @return \Magento\Framework\Phrase
     */
    public function getFormHeader()
    {
        return __('Wish List Search');
    }

    /**
     * Retrieve submitted param by key
     *
     * @param string $key
     * @return string|null
     */
    public function getFormData($key)
    {
        if ($this->_formData === null) {
            $this->_formData = $this->getRequest()->getParam('params');
        }
        if (!$this->_formData || !isset($this->_formData[$key])) {
            return null;
        }
        return $this->escapeHtml($this->_formData[$key]);
    }

    /**
     * Return search form action url
     *
     * @return string
     */
    public function getActionUrl()
    {
        return $this->getUrl('wishlist/search/results');
    }
}
