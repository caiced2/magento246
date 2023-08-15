<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Plugin;

use Magento\AdminGws\Model\Role;
use Magento\Backend\Block\Store\Switcher;
use Magento\Backend\Model\View\Result\Page;
use Magento\Catalog\Controller\Adminhtml\Product\Edit;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;

/**
 * Updates store switcher on product edit form.
 */
class ProductStoreSwitcherUpdater
{
    /**
     * @var Role
     */
    private $role;

    /**
     * @param Role $role
     */
    public function __construct(
        Role $role
    ) {
        $this->role = $role;
    }

    /**
     * Removes 'All Store Views' from store view switcher according to user permissions on product.
     *
     * @param Edit $subject
     * @param ResultInterface|ResponseInterface $result
     *
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

        $productWebsiteIds = $switcherBlock->getData('website_ids');
        if (!$this->role->hasExclusiveAccess($productWebsiteIds)) {
            $switcherBlock->hasDefaultOption('');
        }

        return $result;
    }
}
