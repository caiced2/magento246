<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Plugin\Catalog\Controller\Adminhtml\Product\Action\Attribute;

use Magento\AdminGws\Model\Role;
use Magento\Backend\Block\Store\Switcher;
use Magento\Backend\Model\View\Result\Page;
use Magento\Catalog\Controller\Adminhtml\Product\Action\Attribute\Edit;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Catalog\Helper\Product\Edit\Action\Attribute;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Updates store switcher on product edit form.
 */
class StoreSwitcherUpdater
{
    /**
     * @var Role
     */
    private $role;

    /**
     * @var Attribute
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
     * @param Attribute $attributeHelper
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Role $role,
        RequestInterface $request,
        Attribute $attributeHelper,
        StoreManagerInterface $storeManager
    ) {
        $this->role = $role;
        $this->request = $request;
        $this->attributeHelper = $attributeHelper;
        $this->storeManager = $storeManager;
    }

    /**
     * Removes 'All Store Views' from store view switcher according to user's permissions on products.
     *
     * @param Edit $subject
     * @param ResultInterface|ResponseInterface $result
     * @return ResultInterface|ResponseInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(
        Edit $subject,
        $result
    ) {
        if ($this->role->getIsAll() || !($result instanceof Page)) {
            return $result;
        }

        /** @var Switcher $switcherBlock */
        $switcherBlock = $result->getLayout()->getBlock('store_switcher');
        if ($switcherBlock === false) {
            return $result;
        }

        if (!$this->isAllowedAllStoreView()) {
            $switcherBlock->hasDefaultOption(false);
            $switcherBlock->setStoreId(
                $this->request->getParam('store', $this->getFirstAllowedStoreId())
            );
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
