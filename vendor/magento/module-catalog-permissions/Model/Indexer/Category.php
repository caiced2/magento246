<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Model\Indexer;

use Magento\Framework\Indexer\CacheContext;

/**
 * @api
 * @since 100.0.2
 */
class Category implements
    \Magento\Framework\Indexer\ActionInterface,
    \Magento\Framework\Mview\ActionInterface
{
    /**
     * Indexer ID in configuration
     */
    const INDEXER_ID = 'catalogpermissions_category';

    /**
     * @var Category\Action\FullFactory
     */
    protected $fullActionFactory;

    /**
     * @var Category\Action\RowsFactory
     */
    protected $rowsActionFactory;

    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry
     */
    protected $indexerRegistry;

    /**
     * @var \Magento\Framework\Indexer\CacheContext
     * @since 100.0.6
     */
    protected $cacheContext;

    /**
     * @param Category\Action\FullFactory $fullActionFactory
     * @param Category\Action\RowsFactory $rowsActionFactory
     * @param \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry
     */
    public function __construct(
        Category\Action\FullFactory $fullActionFactory,
        Category\Action\RowsFactory $rowsActionFactory,
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry
    ) {
        $this->fullActionFactory = $fullActionFactory;
        $this->rowsActionFactory = $rowsActionFactory;
        $this->indexerRegistry = $indexerRegistry;
    }

    /**
     * Execute full indexation
     *
     * @return void
     */
    public function executeFull()
    {
        $this->fullActionFactory->create()->execute();
        $this->registerTags();
    }

    /**
     * Add tags to cache context
     *
     * @return void
     * @since 100.0.6
     */
    protected function registerTags()
    {
        $this->getCacheContext()->registerTags([\Magento\Catalog\Model\Category::CACHE_TAG]);
    }

    /**
     * Execute partial indexation by ID list
     *
     * @param int[] $ids
     * @return void
     */
    public function executeList(array $ids)
    {
        $this->executeAction($ids);
    }

    /**
     * Execute partial indexation by ID
     *
     * @param int $id
     * @return void
     */
    public function executeRow($id)
    {
        $this->executeAction([$id]);
    }

    /**
     * Execute materialization on ids entities
     *
     * @param int[] $ids
     * @return void
     */
    public function execute($ids)
    {
        $this->executeAction($ids);
        $this->registerEntities($ids);
    }

    /**
     * Add entities to cache context
     *
     * @param int[] $ids
     * @return void
     * @since 100.0.6
     */
    protected function registerEntities($ids)
    {
        $this->getCacheContext()->registerEntities(\Magento\Catalog\Model\Category::CACHE_TAG, $ids);
    }

    /**
     * Execute action for single entity or list of entities
     *
     * @param int[] $ids
     * @return void
     */
    protected function executeAction($ids)
    {
        $ids = array_unique($ids);

        /** @var Category\Action\Rows $action */
        $action = $this->rowsActionFactory->create();
        if ($this->indexerRegistry->get(static::INDEXER_ID)->isWorking()) {
            $action->execute($ids, true);
        }
        $action->execute($ids);
    }

    /**
     * Get cache context
     *
     * @return \Magento\Framework\Indexer\CacheContext
     * @deprecated 100.0.6
     * @since 100.0.6
     */
    protected function getCacheContext()
    {
        if (!($this->cacheContext instanceof CacheContext)) {
            return \Magento\Framework\App\ObjectManager::getInstance()->get(CacheContext::class);
        } else {
            return $this->cacheContext;
        }
    }
}
