<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Permission resource model
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\CatalogPermissions\Model\ResourceModel;

/**
 * @api
 * @since 100.0.2
 */
class Permission extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magento_catalogpermissions', 'permission_id');
    }

    /**
     * Initialize unique scope for permission
     *
     * @return void
     */
    protected function _initUniqueFields()
    {
        parent::_initUniqueFields();
        $this->_uniqueFields[] = [
            'field' => ['category_id', 'website_id', 'customer_group_id'],
            'title' => __('Permission with the same scope'),
        ];
    }
}
