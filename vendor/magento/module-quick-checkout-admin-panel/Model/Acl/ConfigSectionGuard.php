<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuickCheckoutAdminPanel\Model\Acl;

use Magento\Authorization\Model\Acl\AclRetriever;
use Magento\User\Model\User;

/**
 * Checks if the given user is allowed to access some config section according to the required resources
 */
class ConfigSectionGuard
{
    /**
     * Resource with the highest access level
     */
    public const ALL_ACCESS_RESOURCE = 'Magento_Backend::all';

    /**
     * @var AclRetriever
     */
    private AclRetriever $aclRetriever;

    /**
     * @var array
     */
    private array $requiredResources;

    /**
     * @param AclRetriever $aclRetriever
     * @param array $requiredResources
     */
    public function __construct(
        AclRetriever $aclRetriever,
        array $requiredResources = []
    ) {
        $this->aclRetriever = $aclRetriever;
        $this->requiredResources = $requiredResources;
    }

    /**
     * Checks if current user has all the required permissions
     *
     * @param User $user
     * @return bool
     */
    public function isAllowed(User $user): bool
    {
        $resources = $this->aclRetriever->getAllowedResourcesByRole($user->getRole()->getId());
        if (count($resources) === 0) {
            return false;
        }

        if (in_array(self::ALL_ACCESS_RESOURCE, $resources, true)) {
            return true;
        }

        foreach ($this->requiredResources as $requiredResource) {
            if (!in_array($requiredResource, $resources, true)) {
                return false;
            }
        }
        return true;
    }
}
