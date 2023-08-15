<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogEvent\Model\ResourceModel\Event;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\EntityManager\MetadataPool;

/**
 * Catalog Event resource collection
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @api
 * @since 100.0.2
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Whether category data was added to collection
     *
     * @var bool
     */
    protected $_categoryDataAdded = false;

    /**
     * Whether collection should dispose of the closed events
     *
     * @var bool
     */
    protected $_skipClosed = false;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $localeDate;

    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $eavConfig;

    /**
     * @var MetadataPool
     * @since 100.1.0
     */
    protected $metadataPool;

    /**
     * @param \Magento\Framework\Data\Collection\EntityFactory $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param MetadataPool $metadataPool
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb|null $resource
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Eav\Model\Config $eavConfig,
        MetadataPool $metadataPool,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        $this->localeDate = $localeDate;
        $this->_storeManager = $storeManager;
        $this->eavConfig = $eavConfig;
        $this->metadataPool = $metadataPool;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
    }

    /**
     * Initialize collection
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\CatalogEvent\Model\Event::class, \Magento\CatalogEvent\Model\ResourceModel\Event::class);
    }

    /**
     * Redefining of standard field to filter adding, for availability of bit operations for display state
     *
     * @param string $field
     * @param null|string|array $condition
     * @return \Magento\CatalogEvent\Model\ResourceModel\Event\Collection
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if ($field == 'display_state') {
            if (is_array($condition) && isset($condition['eq'])) {
                $condition = $condition['eq'];
            }
            if ((int)$condition > 0) {
                $this->getSelect()->where('display_state = 3 OR display_state = ?', (int)$condition);
            }
            return $this;
        }
        parent::addFieldToFilter($field, $condition);
        return $this;
    }

    /**
     * Add filter for visible events on frontend
     *
     * @return \Magento\CatalogEvent\Model\ResourceModel\Event\Collection
     */
    public function addVisibilityFilter()
    {
        $statusMapper = \Magento\CatalogEvent\Model\Event::$statusMapper;
        $this->_skipClosed = true;
        $this->addFieldToFilter('status', ['nin' => $statusMapper[\Magento\CatalogEvent\Model\Event::STATUS_CLOSED]]);
        return $this;
    }

    /**
     * Set sort order
     *
     * @param string $field
     * @param string $direction
     * @param boolean $unshift
     * @return \Magento\CatalogEvent\Model\ResourceModel\Event\Collection
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _setOrder($field, $direction, $unshift = false)
    {
        if ($field == 'category_name' && $this->_categoryDataAdded) {
            $field = 'category_position';
        }
        return parent::setOrder($field, $direction);
    }

    /**
     * Add category data to collection select (name, position)
     *
     * @return \Magento\CatalogEvent\Model\ResourceModel\Event\Collection
     */
    public function addCategoryData()
    {
        if (!$this->_categoryDataAdded) {
            $entityTypeId = $this->eavConfig
                ->getEntityType(\Magento\Catalog\Api\Data\CategoryAttributeInterface::ENTITY_TYPE_CODE)
                ->getId();

            $meta = $this->metadataPool->getMetadata(CategoryInterface::class);
            $categoryLinkField = $meta->getLinkField();

            $this->getSelect()->joinLeft(
                ['category' => $this->getTable('catalog_category_entity')],
                'category.entity_id = main_table.category_id',
                ['category_position' => 'position']
            )->joinLeft(
                ['category_name_attribute' => $this->getTable('eav_attribute')],
                'category_name_attribute.entity_type_id = ' . $entityTypeId . '
                    AND category_name_attribute.attribute_code = \'name\'',
                []
            )->joinLeft(
                ['category_varchar' => $this->getTable('catalog_category_entity_varchar')],
                'category_varchar.' . $categoryLinkField . ' = category.' . $categoryLinkField . '
                    AND category_varchar.attribute_id = category_name_attribute.attribute_id
                    AND category_varchar.store_id = 0',
                ['category_name' => 'value']
            );
            $this->_map['fields']['category_name'] = 'category_varchar.value';
            $this->_map['fields']['category_position'] = 'category.position';
            $this->_categoryDataAdded = true;
        }
        return $this;
    }

    /**
     * Add sorting by status.
     *
     * First will be open, then upcoming
     *
     * @return \Magento\CatalogEvent\Model\ResourceModel\Event\Collection
     */
    public function addSortByStatus()
    {
        $this->getSelect()->order('status ASC');

        return $this;
    }

    /**
     * Add image data
     *
     * @return \Magento\CatalogEvent\Model\ResourceModel\Event\Collection
     */
    public function addImageData()
    {
        $connection = $this->getConnection();
        $this->getSelect()->joinLeft(
            ['event_image' => $this->getTable('magento_catalogevent_event_image')],
            implode(
                ' AND ',
                [
                    'event_image.event_id = main_table.event_id',
                    $connection->quoteInto('event_image.store_id = ?', $this->_storeManager->getStore()->getId())
                ]
            ),
            [
                'image' => $connection->getCheckSql(
                    'event_image.image IS NULL',
                    'event_image_default.image',
                    'event_image.image'
                )
            ]
        )->joinLeft(
            ['event_image_default' => $this->getTable('magento_catalogevent_event_image')],
            'event_image_default.event_id = main_table.event_id AND event_image_default.store_id = 0',
            []
        );

        return $this;
    }

    /**
     * Limit collection by specified category paths
     *
     * @param array $allowedPaths
     * @return \Magento\CatalogEvent\Model\ResourceModel\Event\Collection
     */
    public function capByCategoryPaths($allowedPaths)
    {
        $this->addCategoryData();
        $paths = [];
        foreach ($allowedPaths as $path) {
            $paths[] = $this->getConnection()->quoteInto('category.path = ?', $path);
            $paths[] = $this->getConnection()->quoteInto('category.path LIKE ?', $path . '/%');
        }
        if ($paths) {
            $this->getSelect()->where(implode(' OR ', $paths));
        }
        return $this;
    }

    /**
     * Override _afterLoad() implementation
     *
     * @return \Magento\CatalogEvent\Model\ResourceModel\Event\Collection
     */
    protected function _afterLoad()
    {
        $events = parent::_afterLoad();
        foreach ($events->_items as $event) {
            if ($this->_skipClosed && $event->getStatus() == \Magento\CatalogEvent\Model\Event::STATUS_CLOSED) {
                $this->removeItemByKey($event->getId());
            }
        }
        return $this;
    }

    /**
     * Reset collection
     *
     * @return \Magento\CatalogEvent\Model\ResourceModel\Event\Collection
     */
    protected function _reset()
    {
        $this->_skipClosed = false;
        return parent::_reset();
    }

    /**
     * Retrieve DB Expression for status column
     *
     * @return \Zend_Db_Expr
     *
     * @deprecated 100.1.0
     */
    protected function _getStatusColumnExpr()
    {
        $connection = $this->getConnection();
        $timeNow = $this->localeDate->date(null, null, false);
        $dateStart1 = $connection->quoteInto('date_start <= ?', $timeNow);
        $dateEnd1 = $connection->quoteInto('date_end >= ?', $timeNow);
        $dateStart2 = $connection->quoteInto('date_start > ?', $timeNow);
        $dateEnd2 = $connection->quoteInto('date_end > ?', $timeNow);

        return $connection->getCaseSql(
            '',
            [
                "({$dateStart1} AND {$dateEnd1})" => $connection->quote(\Magento\CatalogEvent\Model\Event::STATUS_OPEN),
                "({$dateStart2} AND {$dateEnd2})" => $connection->quote(
                    \Magento\CatalogEvent\Model\Event::STATUS_UPCOMING
                )
            ],
            $connection->quote(\Magento\CatalogEvent\Model\Event::STATUS_CLOSED)
        );
    }

    /**
     * @inheritDoc
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        return $this;
    }
}
