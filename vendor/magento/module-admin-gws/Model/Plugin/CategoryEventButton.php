<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdminGws\Model\Plugin;

class CategoryEventButton
{
    /**
     * Admin role
     *
     * @var \Magento\AdminGws\Model\Role
     */
    protected $_role;

    /**
     * @param \Magento\AdminGws\Model\Role $role
     */
    public function __construct(\Magento\AdminGws\Model\Role $role)
    {
        $this->_role = $role;
    }

    /**
     * Remove buttons if no category exclusive rights ane not all roles
     *
     * @param \Magento\CatalogEvent\Block\Adminhtml\Catalog\Category\Edit\AddEventButton $subject
     * @param \Closure $proceed
     * @return array
     */
    public function aroundGetButtonData(
        \Magento\CatalogEvent\Block\Adminhtml\Catalog\Category\Edit\AddEventButton $subject,
        \Closure $proceed
    ) {
        if (!$this->_role->getIsAll()
            && !$this->_role->hasExclusiveCategoryAccess($subject->getCategory()->getData('path'))
        ) {
            return [];
        }
        return $proceed();
    }
}
