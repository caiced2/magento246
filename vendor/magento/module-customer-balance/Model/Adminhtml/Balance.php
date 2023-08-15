<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Model\Adminhtml;

/**
 * Customer balance model for backend
 */
class Balance extends \Magento\CustomerBalance\Model\Balance
{
    /**
     * Get website id
     *
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getWebsiteId()
    {
        if ($this->hasWebsiteId()) {
            return $this->_getData('website_id');
        }
        throw new \Magento\Framework\Exception\LocalizedException(__('Please set a website ID.'));
    }
}
