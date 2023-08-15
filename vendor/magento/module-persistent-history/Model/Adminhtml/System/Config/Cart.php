<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Enterprise Persistent System Config Shopping Cart option backend model
 *
 */
namespace Magento\PersistentHistory\Model\Adminhtml\System\Config;

class Cart extends \Magento\Framework\App\Config\Value
{
    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'magento_persistenthistory_options_shopping_cart';
}
