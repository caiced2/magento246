<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Plugin;

use Magento\AdminGws\Model\Role;
use Magento\Backend\Block\Store\Switcher;
use Magento\Store\Model\ResourceModel\Store\CollectionFactory;
use Magento\Backend\Model\View\Result\Page;
use Magento\Catalog\Controller\Adminhtml\Category\Edit;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;

/**
 * Updates store switcher on category edit form.
 */
class CategoryStoreSwitcherUpdater
{
    /**
     * @var Role
     */
    private $role;

    /**
     * @var CollectionFactory
     */
    private $storeCollectionFactory;

    /**
     * @param Role $role
     * @param CollectionFactory $storeCollectionFactory
     */
    public function __construct(
        Role $role,
        CollectionFactory $storeCollectionFactory
    ) {
        $this->role = $role;
        $this->storeCollectionFactory = $storeCollectionFactory;
    }

    /**
     * Removes 'All Store Views' from the store view switcher for a user with a scope restricted access.
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
        $switcherBlock = $result->getLayout()->getBlock('category.store.switcher');
        if ($switcherBlock === false) {
            return $result;
        }

        $switcherBlock->hasDefaultOption('');

        return $result;
    }
}
