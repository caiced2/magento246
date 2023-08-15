<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Model\Plugin\Store\StoreSwitcher;

use Closure;
use Magento\AdminGws\Model\Role;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreCookieManagerInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\StoreSwitcher\ManageStoreCookie as ManageStoreCookieClass;

/**
 * Plugin for ManagerStoreCookie Class for restricted admin user
 */
class ManageStoreCookie
{
    /**
     * Admin role
     *
     * @var Role
     */
    private $role;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var StoreCookieManagerInterface
     */
    private $storeCookieManager;

    /**
     * @var HttpContext
     */
    private $httpContext;

    /**
     * @param Role $role
     * @param StoreManagerInterface $storeManager
     * @param StoreCookieManagerInterface $storeCookieManager
     * @param HttpContext $httpContext
     */
    public function __construct(
        Role $role,
        StoreManagerInterface $storeManager,
        StoreCookieManagerInterface $storeCookieManager,
        HttpContext $httpContext
    ) {
        $this->role = $role;
        $this->storeManager = $storeManager;
        $this->storeCookieManager = $storeCookieManager;
        $this->httpContext = $httpContext;
    }

    /**
     * Store switch logic for restricted admin user
     *
     * @param ManageStoreCookieClass $subject
     * @param Closure $proceed
     * @param StoreInterface $fromStore
     * @param StoreInterface $targetStore
     * @param string $redirectUrl
     *
     * @return array|mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws LocalizedException
     */
    public function aroundSwitch(
        ManageStoreCookieClass $subject,
        Closure                $proceed,
        StoreInterface $fromStore,
        StoreInterface $targetStore,
        string $redirectUrl
    ) {
        if (!$this->role->getIsAll()) {
            $website = $this->storeManager->getWebsite($targetStore->getWebsiteId());
            if ($website->getIsDefault() && $targetStore->isDefault()) {
                $this->storeCookieManager->deleteStoreCookie($targetStore);
            } else {
                $this->httpContext->setValue(Store::ENTITY, $targetStore->getCode(), $fromStore->getCode());
                $this->storeCookieManager->setStoreCookie($targetStore);
            }
            return $redirectUrl;
        } else {
            return $proceed($fromStore, $targetStore, $redirectUrl);
        }
    }
}
