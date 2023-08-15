<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AdvancedCheckout\Block\Customer;

/**
 * Customer Order By SKU block
 *
 * @codeCoverageIgnore
 * @api
 * @since 100.0.2
 */
class Sku extends \Magento\AdvancedCheckout\Block\Sku\AbstractSku
{
    /**
     * Retrieve form action URL
     *
     * @return string
     */
    public function getFormAction()
    {
        return $this->getUrl('magento_advancedcheckout/sku/uploadFile');
    }

    /**
     * Check whether form should be multipart
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsMultipart()
    {
        return true;
    }
}
