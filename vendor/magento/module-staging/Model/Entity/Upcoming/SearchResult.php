<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Model\Entity\Upcoming;

use Magento\Framework\Api;
use Magento\Framework\App\RequestInterface;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult as AbstractSearchResult;
use Magento\Framework\DB\Select;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Staging\Model\ResourceModel\Update;
use Magento\Staging\Model\VersionManager;
use Psr\Log\LoggerInterface as Logger;

/**
 * SearchResult for updates
 */
class SearchResult extends AbstractSearchResult
{
    /**
     * @var string[]
     */
    protected $fieldsMap = [
        'end_time' => 'rollbacks.start_time',
    ];

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var VersionManager
     */
    protected $versionManager;

    /**
     * @var string
     */
    protected $entityRequestName;

    /**
     * @var string
     */
    protected $entityTable;

    /**
     * @var string
     */
    protected $entityColumn;

    /**
     * @var array
     */
    protected $entityFieldsToSelect;

    /**
     * @var string
     */
    private $entityStoreTable;

    /**
     * @var string
     */
    private $entityStoreColumn;

    /**
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param RequestInterface $request
     * @param VersionManager $versionManager
     * @param string $entityRequestName
     * @param string $entityTable
     * @param string $entityColumn
     * @param array $entityFieldsToSelect
     * @param string|null $entityStoreTable
     * @param string|null $entityStoreColumn
     * @param string|null $eventPrefix
     * @param string|null $eventObject
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        RequestInterface $request,
        VersionManager $versionManager,
        $entityRequestName,
        $entityTable,
        $entityColumn,
        array $entityFieldsToSelect = [],
        ?string $entityStoreTable = null,
        ?string $entityStoreColumn = null,
        ?string $eventPrefix = null,
        ?string $eventObject = null
    ) {
        $this->request = $request;
        $this->versionManager = $versionManager;
        $this->entityRequestName = $entityRequestName;
        $this->entityTable = $entityTable;
        $this->entityColumn = $entityColumn;
        $this->entityFieldsToSelect = array_merge([$this->entityColumn], $entityFieldsToSelect);
        $this->entityStoreTable = $entityStoreTable;
        $this->entityStoreColumn = $entityStoreColumn;
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            'staging_update',
            Update::class
        );
        $this->_eventPrefix = $eventPrefix ?? $this->_eventPrefix;
        $this->_eventObject = $eventObject ?? $this->_eventObject;
    }

    /**
     * Init select
     *
     * @return void
     */
    protected function _initSelect()
    {
        $this->getSelect()->from(['main_table' => $this->getMainTable()], ['*', 'start_time']);

        $this->getSelect()->where(sprintf('main_table.%s IS NULL', UpdateInterface::IS_ROLLBACK));
        $this->getSelect()->joinInner(
            ['entity_table' => $this->getTable($this->entityTable)],
            'main_table.id = entity_table.created_in',
            $this->entityFieldsToSelect
        );
        $this->getSelect()->where(
            'entity_table.created_in > ' . $this->versionManager->getCurrentVersion()->getId() .
            ' OR (entity_table.updated_in > ' . $this->versionManager->getCurrentVersion()->getId() .
            ' AND entity_table.updated_in < '. VersionManager::MAX_VERSION . ' )'
        );
        $this->getSelect()->where(
            'entity_table.' . $this->entityColumn . ' = ?',
            $this->request->getParam($this->entityRequestName)
        );

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
                'end_time' => 'start_time',
            ]
        );

        if ($this->entityStoreTable !== null && $this->entityStoreColumn !== null) {
            $storeTableHasRowIdField = $this->getResource()->getConnection()
                ->tableColumnExists($this->getTable($this->entityStoreTable), 'row_id');
            $entityTableHasRowIdField = $this->getResource()->getConnection()
                ->tableColumnExists($this->getTable($this->entityTable), 'row_id');

            if ($storeTableHasRowIdField && $entityTableHasRowIdField) {
                $this->getSelect()->joinLeft(
                    ['store_table' => $this->getTable($this->entityStoreTable)],
                    'store_table.row_id = entity_table.row_id',
                    ['store_id' => $this->entityStoreColumn]
                );
                $this->getSelect()->group('main_table.id');
            }
        }

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
