<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogStaging\Model\Update\Grid;

use Magento\Framework\Api;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult as AbstractSearchResult;
use Magento\Framework\DB\Select;
use Magento\Framework\Registry;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Staging\Model\VersionManager;
use Psr\Log\LoggerInterface as Logger;

/**
 * SearchResult for updates
 * @deprecated 100.2.0
 */
class SearchResult extends AbstractSearchResult
{
    /**
     * @var string[]
     */
    protected $fieldsMap = [
        'end_time' => 'rollbacks.start_time',
        'id' => 'products.entity_id'
    ];

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var VersionManager
     */
    protected $versionManager;

    /**
     * SearchResult constructor.
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param string $mainTable
     * @param string $resourceModel
     * @param Registry $registry
     * @param VersionManager $versionManager
     */
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        $mainTable,
        $resourceModel,
        Registry $registry,
        VersionManager $versionManager
    ) {
        $this->registry = $registry;
        $this->versionManager = $versionManager;
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $mainTable,
            $resourceModel
        );
    }

    /**
     * Init select
     *
     * @return void
     */
    protected function _initSelect()
    {
        $this->getSelect()->from(['main_table' => $this->getMainTable()]);

        $this->getSelect()->where(sprintf('main_table.%s IS NULL', UpdateInterface::IS_ROLLBACK));
        $this->getSelect()->joinInner(
            ['products' => $this->getTable('catalog_product_entity')],
            'main_table.id = products.created_in'
        );
        $this->getSelect()->where('products.created_in > ' . $this->versionManager->getCurrentVersion()->getId());

        $this->getSelect()->joinLeft(
            ['rollbacks' => $this->getMainTable()],
            sprintf(
                '%s.%s = %s.%s',
                'main_table',
                'rollback_id',
                'rollbacks',
                'id'
            ),
            [
                'end_time' => 'start_time'
            ]
        );
        $this->getSelect()->setPart('disable_staging_preview', true);
    }

    /**
     * Add field filter to collection
     *
     * @see self::_getConditionSql for $condition
     *
     * @param string|array $field
     * @param null|string|array $condition
     * @return $this
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if (is_array($field)) {
            foreach ($field as $key => $value) {
                $field[$key] = $this->addTableToField($value);
            }
        } else {
            $field = $this->addTableToField($field);
        }

        return parent::addFieldToFilter($field, $condition);
    }

    /**
     * Add corresponding table to requested field using fields map
     *
     * @param string $field
     * @return string
     */
    protected function addTableToField($field)
    {
        return isset($this->fieldsMap[$field]) ? $this->fieldsMap[$field] : sprintf('main_table.%s', $field);
    }
}
