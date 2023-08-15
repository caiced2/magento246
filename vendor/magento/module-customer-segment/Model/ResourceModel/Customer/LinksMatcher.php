<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Model\ResourceModel\Customer;

use Magento\Customer\Model\Config\Share;
use Magento\CustomerSegment\Model\Segment as SegmentModel;
use Magento\CustomerSegment\Model\Segment\Condition\Combine\Root;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Select;

/**
 * Class to match and store customer segment links
 */
class LinksMatcher
{
    private const TEMPORARY_TABLE_PREFIX = 'tmp_customer_segment_';

    private const BATCH_SIZE = 1000;

    /**
     * @var ResourceConnection
     */
    private $resourceModel;

    /**
     * @var Share
     */
    private $configShare;

    /**
     * @var string
     */
    private $tempTableName;

    /**
     * @param ResourceConnection $resourceModel
     * @param Share $configShare
     */
    public function __construct(ResourceConnection $resourceModel, Share $configShare)
    {
        $this->resourceModel = $resourceModel;
        $this->configShare = $configShare;
    }

    /**
     * Math and store customer segment links
     *
     * @param SegmentModel $segment
     * @throws \Exception
     */
    public function matchCustomerLinks(SegmentModel $segment): void
    {
        $this->createTemporaryTable();
        /* Match and store to temporary storage new customers links */
        $this->matchAndStoreLinks($segment);

        $segmentId = (int)$segment->getId();
        $connection = $this->resourceModel->getConnection();
        $connection->beginTransaction();
        try {
            /* Sync exists customer links with matched */
            $this->deleteCustomerSegments($segmentId);
            $this->copyCustomerSegments($segmentId);
        } catch (\Exception $e) {
            $connection->rollback();
            throw $e;
        } finally {
            $this->dropTemporaryTable();
        }
        $connection->commit();
    }

    /**
     * Math and store customer segment links by condition to temporary storage
     *
     * @param SegmentModel $segment
     */
    private function matchAndStoreLinks(SegmentModel $segment): void
    {
        if (empty($segment->getWebsiteIds())) {
            return;
        }

        $segmentId = (int)$segment->getId();
        $condition = $segment->getConditions();
        if (empty($condition->getConditions()) && $condition instanceof Root) {
            /* If condition is empty then need create links for all customers */
            $this->storeCustomerSegments($segmentId);
            return;
        }

        $customerIds = [];
        $count = 0;
        foreach ($this->matchCustomersByCondition($segment) as $customerId) {
            $count++;
            $customerIds[] = $customerId;
            if ($count % self::BATCH_SIZE === 0) {
                $this->storeCustomerSegments($segmentId, $customerIds);
                $customerIds = [];
            }
        }
        if (!empty($customerIds)) {
            $this->storeCustomerSegments($segmentId, $customerIds);
        }
    }

    /**
     * Match customer ids by conditions
     *
     * @param SegmentModel $segment
     * @return \Generator
     */
    private function matchCustomersByCondition(SegmentModel $segment): \Generator
    {
        $websiteIds = $segment->getWebsiteIds();
        foreach ($websiteIds as $websiteId) {
            $customerIds = $segment->getConditions()->getSatisfiedIds($websiteId);
            //get customers ids that satisfy conditions
            foreach ($customerIds as $customerId) {
                yield $customerId;
            }
            if ($this->configShare->isGlobalScope()) {
                break;
            }
        }
    }

    /**
     * Store customers segments to temporary table
     *
     * @param int $segmentId
     * @param array $customerIds
     * @return void
     */
    private function storeCustomerSegments(int $segmentId, array $customerIds = []): void
    {
        $select = $this->getSelectToMatchCustomerSegments($segmentId, $customerIds);
        $connection = $this->resourceModel->getConnection();
        $connection->query(
            $connection->insertFromSelect(
                $select,
                $this->resourceModel->getTableName($this->tempTableName),
                ['customer_id', 'website_id'],
                AdapterInterface::INSERT_IGNORE
            )
        );
    }

    /**
     * Create query to match customer segment links
     *
     * @param int $segmentId
     * @param array $customerIds
     * @return Select
     */
    private function getSelectToMatchCustomerSegments(int $segmentId, array $customerIds = []): Select
    {
        $select = $this->resourceModel->getConnection()->select();
        $select->from(
            ['customer_entity' => $this->resourceModel->getTableName('customer_entity')],
            ['entity_id']
        )->join(
            ['customer_segment_website' => $this->resourceModel->getTableName('magento_customersegment_website')],
            "customer_segment_website.segment_id = $segmentId"
            . (
            $this->configShare->isWebsiteScope()
                ? ' AND customer_segment_website.website_id = customer_entity.website_id'
                : ''
            ),
            ['website_id']
        );
        if (!empty($customerIds)) {
            $select->where('customer_entity.entity_id IN (?)', $customerIds);
        }

        return $select;
    }

    /**
     * Delete customer segment links which is absent in temporary storage
     *
     * @param int $segmentId
     */
    private function deleteCustomerSegments(int $segmentId): void
    {
        $innerSelect = $this->resourceModel->getConnection()->select();
        $mainTable = $this->resourceModel->getTableName('magento_customersegment_customer');
        $innerSelect->from(
            ['tmp' => $this->resourceModel->getTableName($this->tempTableName)],
            [new \Zend_Db_Expr(1)]
        )
            ->where("tmp.customer_id = $mainTable.customer_id")
            ->where("tmp.website_id = $mainTable.website_id");
        $this->resourceModel->getConnection()
            ->delete(
                $mainTable,
                "segment_id = {$segmentId} AND NOT EXISTS ({$innerSelect->assemble()})"
            );
    }

    /**
     * Copy customer segment links from temporary storage
     *
     * @param int $segmentId
     */
    private function copyCustomerSegments(int $segmentId): void
    {
        $connection = $this->resourceModel->getConnection();
        $existsSelect = $connection->select();
        $existsSelect->from(['main' => $this->resourceModel->getTableName('magento_customersegment_customer')], [])
            ->where('main.segment_id = ?', $segmentId);
        $joinCondition = "main.customer_id = tmp.customer_id AND main.website_id = tmp.website_id";
        $select = $connection->select();
        $select->from(
            ['tmp' => $this->resourceModel->getTableName($this->tempTableName)],
            ['segment_id' => new \Zend_Db_Expr($segmentId), 'customer_id', 'website_id']
        )
            ->exists($existsSelect, $joinCondition, false);

        $connection->query(
            $connection->insertFromSelect(
                $select,
                $this->resourceModel->getTableName('magento_customersegment_customer'),
                ['segment_id', 'customer_id', 'website_id'],
                AdapterInterface::INSERT_IGNORE
            )
        );
    }

    /**
     * Create temporary table
     *
     * @return void
     */
    private function createTemporaryTable(): void
    {
        if ($this->tempTableName !== null) {
            return;
        }

        $connection = $this->resourceModel->getConnection();
        $this->tempTableName = self::TEMPORARY_TABLE_PREFIX . date('YmdHis');
        $tableName = $this->resourceModel->getTableName($this->tempTableName);
        $table = $connection->newTable($tableName);
        $table->addColumn(
            'website_id',
            Table::TYPE_SMALLINT,
            null,
            ['unsigned' => true, 'nullable' => false],
            'Website ID'
        );
        $table->addColumn(
            'customer_id',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false],
            'Customer ID'
        );
        $connection->createTemporaryTable($table);

        $connection->addIndex(
            $tableName,
            $connection->getIndexName(
                $this->tempTableName,
                ['website_id', 'customer_id'],
                AdapterInterface::INDEX_TYPE_UNIQUE
            ),
            ['website_id', 'customer_id'],
            AdapterInterface::INDEX_TYPE_UNIQUE
        );
    }

    /**
     * Drop temporary table
     */
    private function dropTemporaryTable(): void
    {
        if ($this->tempTableName === null) {
            return;
        }
        $connection = $this->resourceModel->getConnection();
        $connection->dropTemporaryTable($connection->getTableName($this->tempTableName));
        $this->tempTableName = null;
    }
}
