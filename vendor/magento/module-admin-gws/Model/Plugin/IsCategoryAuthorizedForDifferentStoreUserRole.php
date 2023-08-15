<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Model\Plugin;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\Category\Authorization;
use Magento\Framework\Exception\AuthorizationException;
use Magento\AdminGws\Model\Role as AdminRole;

/**
 * Plugin for authorization of category changes for different store user role
 */
class IsCategoryAuthorizedForDifferentStoreUserRole
{
    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @var AdminRole $adminRole
     */
    private $adminRole;

    /**
     * Initialize dependencies
     *
     * @param UserContextInterface $userContext
     * @param AdminRole $adminRole
     */
    public function __construct(
        UserContextInterface $userContext,
        AdminRole $adminRole
    ) {
        $this->userContext = $userContext;
        $this->adminRole = $adminRole;
    }

    /**
     * Check if the current admin user have access to the category current store
     *
     * @param Authorization $subject
     * @param CategoryInterface $category
     * @throws AuthorizationException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeAuthorizeSavingOf(
        Authorization $subject,
        CategoryInterface $category
    ): void {
        if ($this->userContext->getUserId()
            && $this->userContext->getUserType() === UserContextInterface::USER_TYPE_ADMIN
        ) {
            if (!$this->adminRole->getIsAll()) {
                $parentIds = $category->getParentIds();
                if (empty($parentIds)) {
                    $parentIds = [$category->getParentId()];
                }
                $allowedCategoriesIds = array_keys($this->adminRole->getAllowedRootCategories());
                if (empty(array_intersect($parentIds, $allowedCategoriesIds))) {
                    throw new AuthorizationException(__('Not allowed to edit the category\'s design attributes'));
                }
            }
        }
    }
}
