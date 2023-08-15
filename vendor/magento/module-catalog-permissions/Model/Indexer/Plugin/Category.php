<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Model\Indexer\Plugin;

use Magento\CatalogPermissions\Block\Adminhtml\Catalog\Category\Tab\Permissions\Row as PermissionsRow;
use Magento\CatalogPermissions\Model\Permission;

class Category
{
    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry
     */
    protected $indexerRegistry;

    /**
     * @var \Magento\CatalogPermissions\App\ConfigInterface
     */
    protected $appConfig;

    /**
     * @var \Magento\Framework\AuthorizationInterface
     */
    protected $authorization;

    /**
     * @var \Magento\CatalogPermissions\Model\PermissionFactory
     */
    protected $permissionFactory;

    /**
     * @param \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry
     * @param \Magento\CatalogPermissions\App\ConfigInterface $appConfig
     * @param \Magento\Framework\AuthorizationInterface $authorization
     * @param \Magento\CatalogPermissions\Model\PermissionFactory $permissionFactory
     */
    public function __construct(
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        \Magento\CatalogPermissions\App\ConfigInterface $appConfig,
        \Magento\Framework\AuthorizationInterface $authorization,
        \Magento\CatalogPermissions\Model\PermissionFactory $permissionFactory
    ) {
        $this->indexerRegistry = $indexerRegistry;
        $this->appConfig = $appConfig;
        $this->authorization = $authorization;
        $this->permissionFactory = $permissionFactory;
    }

    /**
     * Save category permissions on category after save
     *
     * @param \Magento\Catalog\Model\Category $subject
     * @return \Magento\Catalog\Model\Category
     */
    public function afterSave(\Magento\Catalog\Model\Category $subject)
    {
        if ($this->appConfig->isEnabled()) {
            if ($this->authorization->isAllowed('Magento_CatalogPermissions::catalog_magento_catalogpermissions')) {
                $this->savePermission($subject);
            }
            $indexer = $this->indexerRegistry->get(\Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID);
            if (!$indexer->isScheduled()) {
                $indexer->reindexRow($subject->getId());
            }
        }

        return $subject;
    }

    /**
     * Reindex category permissions on category move event
     *
     * @param \Magento\Catalog\Model\Category $subject
     * @param \Closure $closure
     * @param int $parentId
     * @param int $afterCategoryId
     * @return \Magento\Catalog\Model\Category
     */
    public function aroundMove(
        \Magento\Catalog\Model\Category $subject,
        \Closure $closure,
        $parentId,
        $afterCategoryId
    ) {
        $oldParentId = $subject->getParentId();
        $closure($parentId, $afterCategoryId);
        if ($this->appConfig->isEnabled()) {
            $indexer = $this->indexerRegistry->get(\Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID);
            if (!$indexer->isScheduled()) {
                $indexer->reindexList([$subject->getId(), $oldParentId]);
            }
        }

        return $subject;
    }

    /**
     * Save permissions before reindex category
     *
     * @param \Magento\Catalog\Model\Category $category
     * @return void
     */
    protected function savePermission(\Magento\Catalog\Model\Category $category)
    {
        if (!$category->hasData('permissions') || !is_array($category->getData('permissions'))) {
            return;
        }
        foreach ($category->getData('permissions') as $data) {
            /** @var Permission $permission */
            $permission = $this->permissionFactory->create();
            if (!empty($data['id'])) {
                $permission->load($data['id']);
            }

            if (!empty($data['_deleted'])) {
                if ($permission->getId()) {
                    $permission->delete();
                }
                continue;
            }

            if ($data['website_id'] == PermissionsRow::FORM_SELECT_ALL_VALUES) {
                $data['website_id'] = null;
            }

            if ($data['customer_group_id'] == PermissionsRow::FORM_SELECT_ALL_VALUES) {
                $data['customer_group_id'] = null;
            }

            $permission->addData($data)->preparePermission()->setCategoryId($category->getId())->save();
        }
    }
}
