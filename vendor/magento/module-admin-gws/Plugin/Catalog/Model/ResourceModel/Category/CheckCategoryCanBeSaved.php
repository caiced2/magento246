<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Plugin\Catalog\Model\ResourceModel\Category;

use Magento\AdminGws\Model\Role as GwsRole;
use Magento\Catalog\Model\ResourceModel\Category as CategoryResourceModel;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;

/**
 * Plugin checks a product's category can be saved.
 */
class CheckCategoryCanBeSaved
{
    /**
     * Admin role
     *
     * @var GwsRole
     */
    private $_role;

    /**
     * @param GwsRole $role
     */
    public function __construct(GwsRole $role)
    {
        $this->_role = $role;
    }

    /**
     * Check if category can be saved.
     *
     * @param CategoryResourceModel $subject
     * @param AbstractModel $object
     *
     * @return void
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSave(CategoryResourceModel $subject, AbstractModel $object): void
    {
        if (!$this->_role->getIsAll()) {
            $this->checkRolePermissions($object);
        }
    }

    /**
     * Check user role permissions to operations with category.
     *
     * @param AbstractModel $category
     * @return void
     * @throws LocalizedException
     */
    private function checkRolePermissions(AbstractModel $category): void
    {
        if (!$this->_role->hasExclusiveCategoryAccess($category->getData('path'))) {
            throw new LocalizedException(
                __('More permissions are needed to save this item.')
            );
        }
    }
}
