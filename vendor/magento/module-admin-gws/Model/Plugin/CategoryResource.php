<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Model\Plugin;

use Magento\AdminGws\Model\Role as GwsRole;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category as CategoryResourceModel;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;

/**
 * Category resource plugin.
 */
class CategoryResource
{
    /**
     * Admin role
     *
     * @var GwsRole
     */
    protected $_role;

    /**
     * @param GwsRole $role
     */
    public function __construct(GwsRole $role)
    {
        $this->_role = $role;
    }

    /**
     * Check if category can be moved
     *
     * @param CategoryResourceModel $subject
     * @param Category $category
     * @param Category $newParent
     * @param null|int $afterCategoryId
     *
     * @return void
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeChangeParent(
        CategoryResourceModel $subject,
        Category $category,
        Category $newParent,
        $afterCategoryId = null
    ): void {
        if (!$this->_role->getIsAll()) {
            /** @var $categoryItem Category */
            foreach ([$newParent, $category] as $categoryItem) {
                $this->checkRolePermissions($categoryItem);
            }
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
