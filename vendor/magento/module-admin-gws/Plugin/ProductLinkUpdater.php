<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Plugin;

use Magento\AdminGws\Model\Role;
use Magento\Catalog\Ui\Component\Listing\Columns\ProductActions;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;

/**
 * Provides appropriate store Id to a link to a product.
 */
class ProductLinkUpdater
{
    /**
     * @var Role
     */
    private $role;

    /**
     * @var ContextInterface
     */
    private $context;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Role $role
     * @param ContextInterface $context
     * @param UrlInterface $urlBuilder
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Role $role,
        ContextInterface $context,
        UrlInterface $urlBuilder,
        StoreManagerInterface $storeManager
    ) {
        $this->role = $role;
        $this->context = $context;
        $this->urlBuilder = $urlBuilder;
        $this->storeManager = $storeManager;
    }

    /**
     * Adds store param to 'edit' product link in product grid according to user role restrictions.
     *
     * @param ProductActions $subject
     * @param array $result
     *
     * @return array
     */
    public function afterPrepareDataSource(
        ProductActions $subject,
        array $result
    ) {
        if ($this->role->getIsAll() || !isset($result['data']['items'])) {
            return $result;
        }

        foreach ($result['data']['items'] as &$item) {
            $item[$subject->getData('name')]['edit']['href'] =
                $this->urlBuilder->getUrl(
                    'catalog/product/edit',
                    ['id' => $item['entity_id'], 'store' => $this->getStoreId($item['website_ids'])]
                );
        }

        return $result;
    }

    /**
     * Returns store id for 'edit' product link in grid.
     *
     * @param array $productWebsiteIds
     *
     * @return null|int
     */
    private function getStoreId(array $productWebsiteIds)
    {
        $filterStoreId = $this->context->getFilterParam('store_id');
        if ($filterStoreId !== null) {
            return $filterStoreId;
        }

        if ($this->role->hasExclusiveAccess($productWebsiteIds)) {
            return null;
        }

        foreach ($productWebsiteIds as $websiteId) {
            if ($this->role->hasWebsiteAccess($websiteId)) {
                return $this->getFirstAllowedStoreId((int)$websiteId);
            }
        }

        return null;
    }

    /**
     * Returns first allowed store id from given website according to current user role.
     *
     * @param int $websiteId
     * @return int|null
     * @throws LocalizedException
     */
    private function getFirstAllowedStoreId(int $websiteId)
    {
        /** @var Website $website */
        $website = $this->storeManager->getWebsite($websiteId);
        $storeIds = $website->getStoreIds();
        foreach ($storeIds as $storeId) {
            if ($this->role->hasStoreAccess($storeId)) {
                return (int)$storeId;
            }
        }

        return null;
    }
}
