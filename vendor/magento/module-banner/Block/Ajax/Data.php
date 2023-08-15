<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Block\Ajax;

use Magento\Framework\View\Element\Template;

/**
 * Data block
 *
 * @api
 * @since 100.0.2
 */
class Data extends Template
{
    /***
     * Default Time To Live for banner cache in milliseconds
     */
    const CACHE_DEFAULT_TTL = 30000;

    /**
     * Get url for customer data ajax requests. Returns url with protocol matching used to request page.
     *
     * @param string $route
     * @return string Customer data url.
     */
    public function getCustomerDataUrl($route)
    {
        return $this->getUrl($route, ['_secure' => $this->getRequest()->isSecure()]);
    }

    /**
     * Returns Time To Live value for banner cache on Store Front
     *
     * @return int
     * @since 100.1.5
     */
    public function getCacheTtl()
    {
        return isset($this->_data['cacheTtl']) && (int)$this->_data['cacheTtl'] > 0
            ? (int)$this->_data['cacheTtl']
            : self::CACHE_DEFAULT_TTL;
    }
}
