<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGwsStaging\Plugin;

use Magento\AdminGws\Model\Role;
use Magento\Staging\Model\Preview\UrlBuilder;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Add default (first) store to the preview URL the current user has access to
 */
class PreviewUrlBuilder
{
    /**
     * @var Role
     */
    private $role;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Initialize dependencies.
     *
     * @param Role $role
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(Role $role, StoreManagerInterface $storeManager)
    {
        $this->role = $role;
        $this->storeManager = $storeManager;
    }

    /**
     * Add default (first) store to the preview URL the current user has access to
     *
     * @param UrlBuilder $subject
     * @param int| $versionId
     * @param string|null $url
     * @param string|null $store
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeGetPreviewUrl(UrlBuilder $subject, $versionId, $url = null, $store = null)
    {
        if ($store === null) {
            $storeGroupIds = $this->role->getStoreGroupIds();
            if (count($storeGroupIds) > 0) {
                $storeGroupId = reset($storeGroupIds);
                $storeId = $this->storeManager->getGroup($storeGroupId)->getDefaultStoreId();
                $store = $this->storeManager->getStore($storeId)->getCode();
            }
        }
        return [$versionId, $url, $store];
    }
}
