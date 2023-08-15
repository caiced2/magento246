<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Banner\Model\ResourceModel;

/**
 * Banner resource module
 *
 * @api
 * @since 100.0.2
 */
class Banner extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Sales rule table name
     *
     * @var string
     */
    protected $_salesRuleTable;

    /**
     * Catalog rule table name
     *
     * @var string
     */
    protected $_catalogRuleTable;

    /**
     * Contents table name
     *
     * @var string
     */
    protected $_contentsTable;

    /**
     * Define if joining related sales rule to banner is already preformed
     *
     * @var bool
     */
    protected $_isSalesRuleJoined = false;

    /**
     * Define if joining related catalog rule to banner is already preformed
     *
     * @var bool
     */
    protected $_isCatalogRuleJoined = false;

    /**
     * Whether to filter banners by specified types
     *
     * @var array
     */
    protected $_bannerTypesFilter = [];

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $_eventManager;

    /**
     * @var \Magento\Banner\Model\Config
     */
    private $_bannerConfig;

    /**
     * Salesrule collection factory
     *
     * @var \Magento\Banner\Model\ResourceModel\Salesrule\CollectionFactory
     */
    private $_salesruleColFactory = null;

    /**
     * Catalogrule collection factory
     *
     * @var \Magento\Banner\Model\ResourceModel\Catalogrule\CollectionFactory
     */
    private $_catRuleColFactory = null;

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Banner\Model\Config $bannerConfig
     * @param Salesrule\CollectionFactory $salesruleColFactory
     * @param Catalogrule\CollectionFactory $catRuleColFactory
     * @param string $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Banner\Model\Config $bannerConfig,
        \Magento\Banner\Model\ResourceModel\Salesrule\CollectionFactory $salesruleColFactory,
        \Magento\Banner\Model\ResourceModel\Catalogrule\CollectionFactory $catRuleColFactory,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->_eventManager = $eventManager;
        $this->_bannerConfig = $bannerConfig;
        $this->_salesruleColFactory = $salesruleColFactory;
        $this->_catRuleColFactory = $catRuleColFactory;
    }

    /**
     * Initialize banner resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magento_banner', 'banner_id');
        $this->_salesRuleTable = $this->getTable('magento_banner_salesrule');
        $this->_catalogRuleTable = $this->getTable('magento_banner_catalogrule');
        $this->_contentsTable = $this->getTable('magento_banner_content');
    }

    /**
     * Set filter by specified types
     *
     * @param string|array $types
     * @return $this
     */
    public function filterByTypes($types = [])
    {
        $this->_bannerTypesFilter = $this->_bannerConfig->explodeTypes($types);
        return $this;
    }

    /**
     * Save banner contents for different store views
     *
     * @param int $bannerId
     * @param array $contents
     * @param array $notuse
     * @return $this
     */
    public function saveStoreContents($bannerId, $contents, $notuse = [])
    {
        $deleteByStores = [];
        if (!is_array($notuse)) {
            $notuse = [];
        }
        $connection = $this->getConnection();

        foreach ($contents as $storeId => $content) {
            if (!empty($content)) {
                $connection->insertOnDuplicate(
                    $this->_contentsTable,
                    ['banner_id' => $bannerId, 'store_id' => $storeId, 'banner_content' => $content],
                    ['banner_content']
                );
            } else {
                $deleteByStores[] = $storeId;
            }
        }
        if (!empty($deleteByStores) || !empty($notuse)) {
            $condition = [
                'banner_id = ?' => $bannerId,
                'store_id IN (?)' => array_merge($deleteByStores, array_keys($notuse)),
            ];
            $connection->delete($this->_contentsTable, $condition);
        }
        return $this;
    }

    /**
     * Delete unchecked catalog rules
     *
     * @param int $bannerId
     * @param array $rules
     * @return $this
     */
    public function saveCatalogRules($bannerId, $rules)
    {
        $connection = $this->getConnection();
        if (empty($rules)) {
            $rules = [0];
        } else {
            foreach ($rules as $ruleId) {
                $connection->insertOnDuplicate(
                    $this->_catalogRuleTable,
                    ['banner_id' => $bannerId, 'rule_id' => $ruleId],
                    ['rule_id']
                );
            }
        }
        $condition = ['banner_id=?' => $bannerId, 'rule_id NOT IN (?)' => $rules];
        $connection->delete($this->_catalogRuleTable, $condition);
        return $this;
    }

    /**
     * Delete unchecked sale rules
     *
     * @param int $bannerId
     * @param array $rules
     * @return $this
     */
    public function saveSalesRules($bannerId, $rules)
    {
        $connection = $this->getConnection();
        if (empty($rules)) {
            $rules = [0];
        } else {
            foreach ($rules as $ruleId) {
                $connection->insertOnDuplicate(
                    $this->_salesRuleTable,
                    ['banner_id' => $bannerId, 'rule_id' => $ruleId],
                    ['rule_id']
                );
            }
        }
        $connection->delete($this->_salesRuleTable, ['banner_id=?' => $bannerId, 'rule_id NOT IN (?)' => $rules]);
        return $this;
    }

    /**
     * Get all existing banner contents
     *
     * @param int $bannerId
     * @return array
     */
    public function getStoreContents($bannerId)
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from(
            $this->_contentsTable,
            ['store_id', 'banner_content']
        )->where(
            'banner_id=?',
            $bannerId
        );
        return $connection->fetchPairs($select);
    }

    /**
     * Get banner content by specific store id
     *
     * @param int $bannerId
     * @param int $storeId
     * @return string
     */
    public function getStoreContent($bannerId, $storeId)
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from(
            ['main_table' => $this->_contentsTable],
            'banner_content'
        )->where(
            'main_table.banner_id = ?',
            $bannerId
        )->where(
            'main_table.store_id IN (?)',
            [$storeId, 0]
        )->order(
            'main_table.store_id DESC'
        );

        if ($this->_bannerTypesFilter) {
            $select->joinInner(
                ['banner' => $this->getTable('magento_banner')],
                'main_table.banner_id = banner.banner_id'
            );
            $filter = [];
            foreach ($this->_bannerTypesFilter as $type) {
                $filter[] = $connection->prepareSqlCondition('banner.types', ['finset' => $type]);
            }
            $select->where(implode(' OR ', $filter));
        }

        $this->_eventManager->dispatch(
            'magento_banner_resource_banner_content_select_init',
            ['select' => $select, 'banner_id' => $bannerId]
        );

        return $connection->fetchOne($select);
    }

    /**
     * Get sales rule that associated to banner
     *
     * @param int $bannerId
     * @return array
     */
    public function getRelatedSalesRule($bannerId)
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from($this->_salesRuleTable, [])->where('banner_id = ?', $bannerId);
        if (!$this->_isSalesRuleJoined) {
            $select->join(
                ['rules' => $this->getTable('salesrule')],
                $this->_salesRuleTable . '.rule_id = rules.rule_id',
                ['rule_id']
            );
            $this->_isSalesRuleJoined = true;
        }
        $rules = $connection->fetchCol($select);
        return $rules;
    }

    /**
     * Get catalog rule that associated to banner
     *
     * @param int $bannerId
     * @return array
     */
    public function getRelatedCatalogRule($bannerId)
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from($this->_catalogRuleTable, [])->where('banner_id = ?', $bannerId);
        if (!$this->_isCatalogRuleJoined) {
            $select->join(
                ['rules' => $this->getTable('catalogrule')],
                $this->_catalogRuleTable . '.rule_id = rules.rule_id',
                ['rule_id']
            );
            $this->_isCatalogRuleJoined = true;
        }

        $rules = $connection->fetchCol($select);
        return $rules;
    }

    /**
     * Get banners that associated to catalog rule
     *
     * @param int $ruleId
     * @return array
     */
    public function getRelatedBannersByCatalogRuleId($ruleId)
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from(
            $this->_catalogRuleTable,
            ['banner_id']
        )->where(
            'rule_id = ?',
            $ruleId
        );
        return $connection->fetchCol($select);
    }

    /**
     * Get banners that associated to sales rule
     *
     * @param int $ruleId
     * @return array
     */
    public function getRelatedBannersBySalesRuleId($ruleId)
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from($this->_salesRuleTable, ['banner_id'])->where('rule_id = ?', $ruleId);
        return $connection->fetchCol($select);
    }

    /**
     * Bind specified banners to catalog rule by rule id
     *
     * @param int $ruleId
     * @param array $banners
     * @return $this
     */
    public function bindBannersToCatalogRule($ruleId, $banners)
    {
        $connection = $this->getConnection();
        foreach ($banners as $bannerId) {
            $connection->insertOnDuplicate(
                $this->_catalogRuleTable,
                ['banner_id' => $bannerId, 'rule_id' => $ruleId],
                ['rule_id']
            );
        }

        if (empty($banners)) {
            $banners = [0];
        }

        $connection->delete(
            $this->_catalogRuleTable,
            ['rule_id = ?' => $ruleId, 'banner_id NOT IN (?)' => $banners]
        );
        return $this;
    }

    /**
     * Bind specified banners to sales rule by rule id
     *
     * @param int $ruleId
     * @param array $banners
     * @return $this
     */
    public function bindBannersToSalesRule($ruleId, $banners)
    {
        $connection = $this->getConnection();
        foreach ($banners as $bannerId) {
            $connection->insertOnDuplicate(
                $this->_salesRuleTable,
                ['banner_id' => $bannerId, 'rule_id' => $ruleId],
                ['rule_id']
            );
        }

        if (empty($banners)) {
            $banners = [0];
        }

        $connection->delete($this->_salesRuleTable, ['rule_id = ?' => $ruleId, 'banner_id NOT IN (?)' => $banners]);
        return $this;
    }

    /**
     * Get real existing active banner ids
     *
     * @return array
     */
    public function getActiveBannerIds()
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from(
            $this->getMainTable(),
            ['banner_id']
        )->where(
            'is_enabled  = ?',
            1
        );
        return $connection->fetchCol($select);
    }

    /**
     * Get banners content per store view
     *
     * @param array $bannerIds
     * @param int $storeId
     * @return array
     */
    public function getBannersContent(array $bannerIds, $storeId)
    {
        $result = [];
        foreach ($bannerIds as $bannerId) {
            $bannerContent = $this->getStoreContent($bannerId, $storeId);
            if (!empty($bannerContent)) {
                $result[$bannerId] = $bannerContent;
            }
        }
        return $result;
    }

    /**
     * Get banners IDs that related to sales rule and satisfy conditions
     *
     * @param array $appliedRules
     * @return array
     */
    public function getSalesRuleRelatedBannerIds(array $appliedRules)
    {
        return $this->_salesruleColFactory->create()
            ->addIsActiveSalesRuleFilter()
            ->addRuleIdsFilter($appliedRules)
            ->getColumnValues('banner_id');
    }

    /**
     * Get banners IDs that related to sales rule and satisfy conditions
     *
     * @param int $websiteId
     * @param int $customerGroupId
     * @return array
     */
    public function getCatalogRuleRelatedBannerIds($websiteId, $customerGroupId)
    {
        return $this->_catRuleColFactory->create()
            ->addFieldToFilter('catalogrule.is_active', 1)
            ->addWebsiteCustomerGroupFilter(
                $websiteId,
                $customerGroupId
            )
            ->getColumnValues(
                'banner_id'
            );
    }

    /**
     * Prepare banner types for saving
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    protected function _beforeSave(\Magento\Framework\Model\AbstractModel $object)
    {
        $types = $object->getTypes();
        if (empty($types)) {
            $types = null;
        } elseif (is_array($types)) {
            $types = implode(',', $types);
        }
        if (empty($types)) {
            $types = null;
        }
        $object->setTypes($types);
        return parent::_beforeSave($object);
    }
}
