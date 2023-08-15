<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Model\Indexer\Plugin\Store;

class Group extends AbstractPlugin
{
    /**
     * Validate changes for invalidating indexer
     *
     * @param \Magento\Framework\Model\AbstractModel $group
     * @return bool
     */
    protected function validate(\Magento\Framework\Model\AbstractModel $group)
    {
        return ($group->dataHasChangedFor(
            'website_id'
        ) || $group->dataHasChangedFor(
            'root_category_id'
        )) && !$group->isObjectNew();
    }

    /**
     * Invalidate indexer on store group save
     *
     * @param \Magento\Store\Model\ResourceModel\Group $subject
     * @param \Magento\Store\Model\ResourceModel\Group $result
     * @param \Magento\Framework\Model\AbstractModel $store
     *
     * @return \Magento\Store\Model\ResourceModel\Group
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(
        \Magento\Store\Model\ResourceModel\Group $subject,
        \Magento\Store\Model\ResourceModel\Group $result,
        \Magento\Framework\Model\AbstractModel $store
    ) {
        $needInvalidating = $this->validate($store);
        if ($needInvalidating && $this->appConfig->isEnabled()) {
            $this->indexerRegistry->get(\Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID)->invalidate();
            $this->indexerRegistry->get(\Magento\CatalogPermissions\Model\Indexer\Product::INDEXER_ID)->invalidate();
        }

        return $result;
    }
}
