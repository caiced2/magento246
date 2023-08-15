<?php
declare(strict_types=1);

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogPermissions\Plugin\Customer\Model\ResourceModel;

use Magento\CatalogPermissions\Model\Indexer\AbstractAction;
use Magento\CatalogPermissions\Model\Indexer\Category\ModeSwitcher;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as GroupCollectionFactory;
use Magento\Customer\Model\ResourceModel\GroupRepository;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;

class GroupRepositoryPlugin
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var AdapterInterface
     */
    private $connection;

    /**
     * @var GroupCollectionFactory
     */
    private $groupCollectionFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ResourceConnection $resource
     * @param GroupCollectionFactory $groupCollectionFactory
     * @param ScopeConfigInterface $scopeConfig
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        ResourceConnection $resource,
        GroupCollectionFactory $groupCollectionFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->groupCollectionFactory = $groupCollectionFactory;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Plugin that creates index tables for each customer group
     *
     * @param GroupRepository $subject
     * @param GroupInterface $result
     * @return GroupInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(GroupRepository $subject, GroupInterface $result):GroupInterface
    {
        if ($this->scopeConfig->getValue(ModeSwitcher::XML_PATH_CATEGORY_PERMISSION_DIMENSIONS_MODE) ===
            ModeSwitcher::DIMENSION_CUSTOMER_GROUP
        ) {
            $customerGroup = $result->getId();
            $existingIds = $this->groupCollectionFactory->create()->getAllIds();
            $id = reset($existingIds);
            $this->connection->createTable(
                $this->connection->createTableByDdl(
                    $this->getTable(AbstractAction::INDEX_TABLE . AbstractAction::TMP_SUFFIX),
                    $this->resource->getTablePrefix() .
                    AbstractAction::INDEX_TABLE . '_' . $customerGroup
                )
            );
            $this->connection->createTable(
                $this->connection->createTableByDdl(
                    $this->getTable(
                        AbstractAction::INDEX_TABLE . AbstractAction::PRODUCT_SUFFIX . '_' . $id
                    ),
                    $this->resource->getTablePrefix() .
                    AbstractAction::INDEX_TABLE . AbstractAction::PRODUCT_SUFFIX . '_' . $customerGroup
                )
            );
            $this->connection->createTable(
                $this->connection->createTableByDdl(
                    $this->getTable(AbstractAction::INDEX_TABLE . AbstractAction::TMP_SUFFIX),
                    $this->resource->getTablePrefix() .
                    AbstractAction::INDEX_TABLE . '_' . $customerGroup . AbstractAction::REPLICA_SUFFIX
                )
            );
            $this->connection->createTable(
                $this->connection->createTableByDdl(
                    $this->getTable(
                        AbstractAction::INDEX_TABLE . AbstractAction::PRODUCT_SUFFIX . '_' . $id
                    ),
                    $this->resource->getTablePrefix() .
                    AbstractAction::INDEX_TABLE . AbstractAction::PRODUCT_SUFFIX
                    . '_' . $customerGroup . AbstractAction::REPLICA_SUFFIX
                )
            );
        }
        return $result;
    }

    /**
     * Plugin that deletes index tables for each customer group
     *
     * @param GroupRepository $subject
     * @param bool $result
     * @param string $id
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterDeleteById(GroupRepository $subject, bool $result, string $id)
    {
        if ($this->scopeConfig->getValue(ModeSwitcher::XML_PATH_CATEGORY_PERMISSION_DIMENSIONS_MODE) ===
            ModeSwitcher::DIMENSION_CUSTOMER_GROUP
        ) {
            $this->connection->dropTable($this->getTable(AbstractAction::INDEX_TABLE . '_' . $id));
            $this->connection->dropTable(
                $this->getTable(
                    AbstractAction::INDEX_TABLE . '_' . $id . AbstractAction::REPLICA_SUFFIX
                )
            );
            $this->connection->dropTable(
                $this->getTable(AbstractAction::INDEX_TABLE) . AbstractAction::PRODUCT_SUFFIX . '_' . $id
            );
            $this->connection->dropTable(
                $this->getTable(AbstractAction::INDEX_TABLE)
                . AbstractAction::PRODUCT_SUFFIX . '_' . $id . AbstractAction::REPLICA_SUFFIX
            );
        }
        return $result;
    }

    /**
     * Return validated table name
     *
     * @param string|string[] $table
     * @return string
     */
    private function getTable($table)
    {
        return $this->resource->getTableName($table);
    }
}
