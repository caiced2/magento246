<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdminGws\Model\Plugin;

/**
 * Plugin for \Magento\Store\Api\WebsiteRepositoryInterface
 */
class WebsiteRepository
{
    /**
     * Returns the admin as the default store if a default is not available.
     *
     * Due to \Magento\AdminGws\Model\Collections::limitWebsites, only websites which a user's role has access to are
     * returned from the collection. In a situation where the default website is not available (e.g. the user's role
     * does not include the default website), the admin website is returned instead.
     *
     * @param \Magento\Store\Api\WebsiteRepositoryInterface $websiteRepository
     * @param callable $proceed
     * @return \Magento\Store\Api\Data\WebsiteInterface
     */
    public function aroundGetDefault(
        \Magento\Store\Api\WebsiteRepositoryInterface $websiteRepository,
        \Closure $proceed
    ) {
        try {
            return $proceed();
        } catch (\DomainException $e) {
            return $websiteRepository->getById(0);
        }
    }
}
