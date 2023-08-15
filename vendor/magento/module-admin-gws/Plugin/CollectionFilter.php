<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Plugin;

use Magento\AdminGws\Model\Role;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Store\Model\StoreManagerInterface;
use Zend_Db_Select_Exception;

/**
 * Class for filer collection and leave only allowed for current admin entities.
 */
class CollectionFilter
{
    private const FILTERED_FLAG_NAME = 'admin_gws_filtered';

    /**
     * @var Role
     */
    private $role;

    /**
     * @var array
     */
    private $tableColumns;

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
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Role $role,
        RequestInterface $request,
        StoreManagerInterface $storeManager
    ) {
        $this->role = $role;
        $this->tableColumns = [];
        $this->request = $request;
        $this->storeManager = $storeManager;
    }

    /**
     * Adds allowed websites or stores to query filter.
     *
     * @param AbstractCollection $collection
     * @param bool $printQuery
     * @param bool $logQuery
     * @return array
     * @throws Zend_Db_Select_Exception|LocalizedException
     */
    public function beforeLoadWithFilter(AbstractCollection $collection, $printQuery = false, $logQuery = false)
    {
        $this->filterCollection($collection);

        return [$printQuery, $logQuery];
    }

    /**
     * Adds allowed websites or stores to query filter.
     *
     * @param AbstractCollection $collection
     * @throws Zend_Db_Select_Exception
     */
    public function beforeGetSelectCountSql(AbstractCollection $collection)
    {
        $this->filterCollection($collection);
    }

    /**
     * Add filter to collection.
     *
     * @param AbstractCollection $collection
     * @throws Zend_Db_Select_Exception
     * @throws LocalizedException
     */
    private function filterCollection(AbstractCollection $collection)
    {
        if (!$this->role->getIsAll() && !$collection->getFlag(self::FILTERED_FLAG_NAME)) {
            if (method_exists($collection, 'addStoresFilter')) {
                $collection->addStoresFilter($this->getStoreIds());
                $collection->setFlag(self::FILTERED_FLAG_NAME, true);
            } elseif (isset($collection->getSelect()->getPart(Select::FROM)['main_table'])) {
                $this->tableBasedFilter($collection);
                $collection->setFlag(self::FILTERED_FLAG_NAME, true);
            }
        }
    }

    /**
     * Filter the collection by setting relevant website or store Ids by looking up collection's insides.
     *
     * @param AbstractCollection $collection
     * @return void
     * @throws LocalizedException
     */
    private function tableBasedFilter(AbstractCollection $collection)
    {
        $mainTable = $collection->getMainTable();
        if (!isset($this->tableColumns[$mainTable])) {
            $describe = $collection->getConnection()->describeTable($mainTable);
            $this->tableColumns[$mainTable] = array_column($describe, 'COLUMN_NAME');
        }
        if (in_array('website_id', $this->tableColumns[$mainTable], true)) {
            $whereCondition = 'main_table.website_id IN (?) OR main_table.website_id IS NULL';
            $collection->getSelect()->where($whereCondition, $this->role->getRelevantWebsiteIds());
        } elseif (in_array('store_website_id', $this->tableColumns[$mainTable], true)) {
            $whereCondition = 'main_table.store_website_id IN (?) OR main_table.store_website_id IS NULL';
            $collection->getSelect()->where(
                $whereCondition,
                $this->role->getRelevantWebsiteIds()
            );
        } elseif (in_array('store_id', $this->tableColumns[$mainTable], true)) {
            $whereCondition = 'main_table.store_id IN (?) OR main_table.store_id IS NULL';
            $collection->getSelect()->where($whereCondition, $this->getStoreIds());
        }
    }

    /**
     * Get Store Ids based on the filter area.
     *
     * @return array|null
     * @throws LocalizedException
     */
    private function getStoreIds()
    {
        $restrictedStoreIds = $this->role->getStoreIds();
        $storeIds = null;

        if ($this->request->getParam('store_ids')) {
            $storeIds =  $this->request->getParam('store_ids');
        } elseif ($this->request->getParam('group')) {
            $storeIds = array_values($this->storeManager->getGroup($this->request->getParam('group'))
                ->getStoreIds());
        } elseif ($this->request->getParam('website')) {
            $websiteId = $this->request->getParam('website');
            $storeIds =  array_values($this->storeManager->getWebsite($websiteId)->getStoreIds());
        }
        $storeIdsFilter = (array) $storeIds;
        if (!empty($storeIdsFilter) && $storeIdsFilter !== [\Magento\Store\Model\Store::DEFAULT_STORE_ID]) {
            $storeIdsFilter =  array_intersect($storeIdsFilter, $restrictedStoreIds);
        } else {
            $storeIdsFilter = $restrictedStoreIds;
        }

        return $storeIdsFilter;
    }
}
