<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Enterprise Persistent System Config Shopping Customer option backend model
 *
 */
namespace Magento\PersistentHistory\Model\Adminhtml\System\Config;

class Customer extends \Magento\Framework\App\Config\Value
{
    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'magento_persistenthistory_options_customer';

    /**
     * Processing object before save data
     *
     * @return \Magento\Framework\Model\AbstractModel
     */
    public function beforeSave()
    {
        $groups = $this->getGroups();
        if (isset(
            $groups['options']['fields']['shopping_cart']['value']
        ) && $groups['options']['fields']['shopping_cart']['value']
        ) {
            $this->_dataSaveAllowed = false;
            return $this;
        }

        return parent::beforeSave();
    }
}
