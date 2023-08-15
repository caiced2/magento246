<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogPermissions\Block\Adminhtml\Catalog\Category;

class PermissionsContainer extends \Magento\Backend\Block\Template
{
    /**
     * @return string
     */
    public function toHtml()
    {
        return $this->getLayout()->createBlock(
            \Magento\CatalogPermissions\Block\Adminhtml\Catalog\Category\Tab\Permissions::class,
            'category.permissions.row'
        )->toHtml();
    }
}
