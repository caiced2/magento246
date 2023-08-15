<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Plugin\Catalog\Block\Adminhtml\Product\Edit\Action;

use Magento\AdminGws\Model\Role;
use Magento\Catalog\Block\Adminhtml\Product\Edit\Action\Attribute as AttributeBlock;
use Magento\Framework\App\RequestInterface;
use Magento\Catalog\Helper\Product\Edit\Action\Attribute as AttributeHelper;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Attribute plugin of Magento\Catalog\Block\Adminhtml\Product\Edit\Action\Attribute
 */
class Attribute
{
    /**
     * @var Role
     */
    private $role;

    /**
     * @var AttributeHelper
     */
    private $attributeHelper;

    /**
     * Request object
     *
     * @var RequestInterface
     */
    private $request;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Role $role
     * @param RequestInterface $request
     * @param AttributeHelper $attributeHelper
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Role $role,
        RequestInterface $request,
        AttributeHelper $attributeHelper,
        StoreManagerInterface $storeManager
    ) {
        $this->role = $role;
        $this->request = $request;
        $this->attributeHelper = $attributeHelper;
        $this->storeManager = $storeManager;
    }

    /**
     * Adding first allowed store ID of user in save url if not allowed 'All Store Views'.
     *
     * @param AttributeBlock $subject
     * @param string $result
     * @return string
     */
    public function afterGetSaveUrl(
        AttributeBlock $subject,
        $result
    ) {
        if ($this->role->getIsAll()) {
            return $result;
        }

        if (!$this->isAllowedAllStoreView()) {
            $storeId = $this->request->getParam('store', $this->getFirstAllowedStoreId());
            $result = $subject->getUrl('*/*/save', ['store' => $storeId]);
        }

        return $result;
    }

    /**
     * Allowed 'All Store Views' if all websites in each product according to user's permissions on products.
     *
     * @return bool
     */
    private function isAllowedAllStoreView(): bool
    {
        $productCollection = $this->attributeHelper->getProducts()->addWebsiteNamesToResult();

        $isAllowedAllStoreView = true;
        foreach ($productCollection as $product) {
            $productWebsiteIds = $product->getWebsiteIds();
            if (!$this->role->hasExclusiveAccess($productWebsiteIds)) {
                $isAllowedAllStoreView = false;
                break;
            }
        }

        return $isAllowedAllStoreView;
    }

    /**
     * Returns first allowed store id according to current user role.
     *
     * @return int
     */
    private function getFirstAllowedStoreId(): int
    {
        $stores = $this->storeManager->getStores(true);
        $store = array_shift($stores);

        return (int)$store->getId();
    }
}
