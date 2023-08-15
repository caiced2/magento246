<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerBalance\Helper;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Helper\AbstractHelper;

/**
 * Utility class for customer balance
 */
class Data extends AbstractHelper
{
    /**
     * XML configuration paths
     */
    const XML_PATH_ENABLED = 'customer/magento_customerbalance/is_enabled';

    const XML_PATH_AUTO_REFUND = 'customer/magento_customerbalance/refund_automatically';

    const XML_PATH_HISTORY_ENABLED = 'customer/magento_customerbalance/show_history';

    /**
     * Check whether customer balance functionality should be enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE) == 1;
    }

    /**
     * Check if automatically refund is enabled
     *
     * @return bool
     */
    public function isAutoRefundEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_AUTO_REFUND, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Check whether customer balance history functionality is be enabled
     *
     * @return bool
     */
    public function isHistoryEnabled()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_HISTORY_ENABLED, ScopeInterface::SCOPE_STORE) == 1;
    }
}
