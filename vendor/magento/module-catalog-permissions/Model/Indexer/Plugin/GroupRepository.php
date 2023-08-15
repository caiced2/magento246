<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Model\Indexer\Plugin;

use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\CatalogPermissions\Model\Indexer\UpdateIndexInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Indexer\IndexerRegistry;

/**
 * Plugin of Customer Group Repository
 */
class GroupRepository
{
    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @var ConfigInterface
     */
    private $appConfig;

    /**
     * @var UpdateIndexInterface
     */
    private $updateIndex;

    /**
     * @param IndexerRegistry $indexerRegistry
     * @param ConfigInterface $appConfig
     * @param UpdateIndexInterface $updateIndex
     */
    public function __construct(
        IndexerRegistry $indexerRegistry,
        ConfigInterface $appConfig,
        UpdateIndexInterface $updateIndex
    ) {
        $this->indexerRegistry = $indexerRegistry;
        $this->appConfig = $appConfig;
        $this->updateIndex = $updateIndex;
    }

    /**
     * Invalidate indexer on customer group save
     *
     * @param GroupRepositoryInterface $subject
     * @param \Closure $proceed
     * @param GroupInterface $customerGroup
     * @return int
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundSave(
        GroupRepositoryInterface $subject,
        \Closure $proceed,
        GroupInterface $customerGroup
    ) {
        $needInvalidating = $customerGroup->getId() === null;

        $customerGroup = $proceed($customerGroup);

        if ($needInvalidating && $this->appConfig->isEnabled()) {
            $this->updateIndex->update($customerGroup, $needInvalidating);
        }

        return $customerGroup;
    }

    /**
     * Invalidate indexer on customer group delete
     *
     * @param GroupRepositoryInterface $subject
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterDelete(GroupRepositoryInterface $subject)
    {
        return $this->invalidateIndexer();
    }

    /**
     * Invalidate indexer on customer group delete
     *
     * @param GroupRepositoryInterface $subject
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterDeleteById(GroupRepositoryInterface $subject)
    {
        return $this->invalidateIndexer();
    }

    /**
     * Invalidate indexer
     *
     * @return bool
     */
    protected function invalidateIndexer()
    {
        if ($this->appConfig->isEnabled()) {
            $this->indexerRegistry->get(\Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID)->invalidate();
            $this->indexerRegistry->get(\Magento\CatalogPermissions\Model\Indexer\Product::INDEXER_ID)->invalidate();
        }
        return true;
    }
}
