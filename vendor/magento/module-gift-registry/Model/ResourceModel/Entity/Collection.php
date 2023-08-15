<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Model\ResourceModel\Entity;

/**
 * Gift registry entity collection
 *
 * @api
 * @since 100.0.2
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var \Magento\GiftRegistry\Model\Attribute\Config
     */
    protected $attributeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\GiftRegistry\Model\ResourceModel\Helper
     */
    protected $resourceHelper;

    /**
     * @param \Magento\Framework\Data\Collection\EntityFactory $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\GiftRegistry\Model\Attribute\Config $attributeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\GiftRegistry\Model\ResourceModel\Helper $resourceHelper
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\GiftRegistry\Model\Attribute\Config $attributeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\GiftRegistry\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
        $this->attributeConfig = $attributeConfig;
        $this->storeManager = $storeManager;
        $this->resourceHelper = $resourceHelper;
    }

    /**
     * Collection initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Magento\GiftRegistry\Model\Entity::class,
            \Magento\GiftRegistry\Model\ResourceModel\Entity::class
        );
    }

    /**
     * Load collection by customer id
     *
     * @param int $id
     * @return $this
     */
    public function filterByCustomerId($id)
    {
        $this->getSelect()->where('main_table.customer_id = ?', (int)$id);
        return $this;
    }

    /**
     * Load collection by customer id
     *
     * @return $this
     */
    public function filterByActive()
    {
        $this->getSelect()->where('main_table.is_active = ?', 1);
        return $this;
    }

    /**
     * Add registry info
     *
     * @return $this
     */
    public function addRegistryInfo()
    {
        $this->_addQtyItemsData();
        $this->_addEventData();
        $this->_addRegistrantData();

        return $this;
    }

    /**
     * Add registry quantity info
     *
     * @return $this
     */
    protected function _addQtyItemsData()
    {
        $select = $this->getConnection()->select()->from(
            ['item' => $this->getTable('magento_giftregistry_item')],
            [
                'entity_id',
                'qty' => new \Zend_Db_Expr('SUM(item.qty)'),
                'qty_fulfilled' => new \Zend_Db_Expr('SUM(item.qty_fulfilled)'),
                'qty_remaining' => new \Zend_Db_Expr('SUM(item.qty - item.qty_fulfilled)')
            ]
        )->group(
            'entity_id'
        );

        $this->getSelect()->joinLeft(
            ['items' => new \Zend_Db_Expr(sprintf('(%s)', $select))],
            'main_table.entity_id = items.entity_id',
            ['qty', 'qty_fulfilled', 'qty_remaining']
        );

        return $this;
    }

    /**
     * Add event info to collection
     *
     * @return $this
     */
    protected function _addEventData()
    {
        $this->getSelect()->joinLeft(
            ['data' => $this->getTable('magento_giftregistry_data')],
            'main_table.entity_id = data.entity_id',
            ['data.event_date']
        );
        return $this;
    }

    /**
     * Add registrant info to collection
     *
     * @return $this
     */
    protected function _addRegistrantData()
    {
        $select = $this->getConnection()->select()->from(
            $this->getTable('magento_giftregistry_person'),
            ['entity_id']
        )->group(
            'entity_id'
        );

        $this->resourceHelper->addGroupConcatColumn($select, 'registrants', ['firstname', 'lastname'], ', ', ' ');

        $this->getSelect()->joinLeft(
            ['person' => new \Zend_Db_Expr(sprintf('(%s)', $select))],
            'main_table.entity_id = person.entity_id',
            ['registrants']
        );

        return $this;
    }

    /**
     * Apply search filters
     *
     * @param array $params
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function applySearchFilters($params)
    {
        $connection = $this->getConnection();
        $select = $connection->select();
        $select->from(
            ['m' => $this->getMainTable()],
            ['*']
        )->where(
            'm.is_public = ?',
            1
        )->where(
            'm.is_active = ?',
            1
        )->where(
            'm.website_id = ?',
            (int)$this->storeManager->getStore()->getWebsiteId()
        );

        /*
         * Join registry type store label
         */
        $select->joinLeft(
            ['i1' => $this->getTable('magento_giftregistry_type_info')],
            'i1.type_id = m.type_id AND i1.store_id = 0',
            []
        );
        $typeExpr = $connection->getCheckSql('i2.label IS NULL', 'i1.label', 'i2.label');
        $select->joinLeft(
            ['i2' => $this->getTable('magento_giftregistry_type_info')],
            $connection->quoteInto(
                'i2.type_id = m.type_id AND i2.store_id = ?',
                (int)$this->storeManager->getStore()->getId()
            ),
            ['type' => $typeExpr]
        );

        /*
         * Join registrant data
         */
        $registrantExpr = $connection->getConcatSql(['firstname', 'lastname'], ' ');
        $select->joinInner(
            ['p' => $this->getTable('magento_giftregistry_person')],
            'm.entity_id = p.entity_id',
            ['registrant' => $registrantExpr]
        );

        /*
         * Join entity event data
         */
        $select->joinLeft(
            ['d' => $this->getTable('magento_giftregistry_data')],
            'm.entity_id = d.entity_id',
            ['event_date', 'event_location']
        );

        /*
         * Apply search filters
         */
        if (!empty($params['type_id'])) {
            $select->where('m.type_id = ?', (int)$params['type_id']);
        }
        if (!empty($params['id'])) {
            $select->where('m.url_key = ?', $params['id']);
        }
        if (!empty($params['firstname'])) {
            $select->where($connection->quoteInto('p.firstname LIKE ?', $params['firstname'] . '%'));
        }
        if (!empty($params['lastname'])) {
            $select->where($connection->quoteInto('p.lastname LIKE ?', $params['lastname'] . '%'));
        }
        if (!empty($params['email'])) {
            $select->where('p.email = ?', $params['email']);
        }

        /*
         * Apply search filters by static attributes
         */
        $config = $this->attributeConfig;
        $staticCodes = $config->getStaticTypesCodes();
        foreach ($staticCodes as $code) {
            if (!empty($params[$code])) {
                $select->where($connection->quoteInto($code . ' =?', $params[$code]));
            }
        }
        $dateType = $config->getStaticDateType();
        if (!empty($params[$dateType . '_from'])) {
            $select->where($connection->quoteInto($dateType . ' >= ?', $params[$dateType . '_from']));
        }
        if (!empty($params[$dateType . '_to'])) {
            $select->where($connection->quoteInto($dateType . ' <= ?', $params[$dateType . '_to']));
        }

        $select->group('m.entity_id');
        $this->getSelect()->reset()->from(
            ['main_table' => new \Zend_Db_Expr(sprintf('(%s)', $select))],
            ['*']
        );

        return $this;
    }

    /**
     * Filter collection by specified websites
     *
     * @param array|int $websiteIds
     * @return $this
     */
    public function addWebsiteFilter($websiteIds)
    {
        $this->getSelect()->where('main_table.website_id IN (?)', $websiteIds);
        return $this;
    }

    /**
     * Filter collection by specified status
     *
     * @param int $status
     * @return $this
     */
    public function filterByIsActive($status)
    {
        $this->getSelect()->where('main_table.is_active = ?', $status);
        return $this;
    }
}
