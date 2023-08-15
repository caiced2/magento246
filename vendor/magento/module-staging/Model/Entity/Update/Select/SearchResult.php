<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Model\Entity\Update\Select;

use Magento\Framework\Api;
use Magento\Framework\App\RequestInterface;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult as AbstractSearchResult;
use Magento\Framework\DB\Select;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
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
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param RequestInterface $request
     * @param VersionManager $versionManager
     * @param string $entityRequestName
     * @param string $entityTable
     * @param string $entityColumn
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
        $entityColumn
    ) {
        $this->request = $request;
        $this->versionManager = $versionManager;
        $this->entityRequestName = $entityRequestName;
        $this->entityTable = $entityTable;
        $this->entityColumn = $entityColumn;
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            'staging_update',
            \Magento\Staging\Model\ResourceModel\Update::class
        );
    }

    /**
     * Init select
     *
     * @return void
     */
    protected function _initSelect()
    {
        $this->getSelect()->setPart('disable_staging_preview', true);
        $entitySelect = clone $this->getSelect();
        $entitySelect->from(['entity' => $this->getTable($this->entityTable)], new \Zend_Db_Expr('COUNT(*)'));
        $entitySelect->join(
            ['entity_update' => $this->getMainTable()],
            sprintf(
                '%s.%s = %s.%s AND %s.%s IS NULL',
                'entity_update',
                'id',
                'entity',
                'created_in',
                'entity_update',
                'is_rollback'
            ),
            ''
        );
        $entitySelect->joinLeft(
            ['entity_rollback' => $this->getMainTable()],
            sprintf(
                '%s.%s = %s.%s',
                'entity_rollback',
                'id',
                'entity_update',
                'rollback_id'
            ),
            ''
        );
        $entitySelect->where(
            sprintf('entity.%s = ?', $this->entityColumn),
            $this->request->getParam($this->entityRequestName)
        );
        $updateId = $this->request->getParam('update_id');
        if ($updateId) {
            $entitySelect->where('entity.created_in != ?', $updateId);
        }
        $entitySelect->where(
            'entity.created_in = main_table.id OR entity.updated_in = main_table.id'
            . ' OR entity.created_in = rollbacks.id OR entity.updated_in = rollbacks.id'
            . ' OR entity_update.rollback_id IS NOT NULL AND entity.created_in <= main_table.id'
            . ' AND entity.updated_in >= main_table.id'
            . ' OR entity_update.rollback_id IS NULL AND rollbacks.id IS NOT NULL'
            . ' AND main_table.id <= entity.created_in AND rollbacks.id >= entity.created_in'
            . ' OR entity_update.rollback_id IS NOT NULL AND rollbacks.id IS NOT NULL'
            . ' AND !(main_table.id < entity.created_in AND rollbacks.id < entity.created_in'
            . ' OR main_table.id > entity.updated_in AND rollbacks.id > entity.updated_in)'
        );
        $conditions = ['(IF((' . $entitySelect . ') = 0, 0, 1) = 1)'];

        if ($updateId) {
            $conditions[] = $this->getConnection()->quoteInto('main_table.id = ?', $updateId);
        }
        $caseExpression = 'CASE WHEN '
            . implode(' OR ', $conditions)
            . ' THEN 1 ELSE 0 END';
        $this->getSelect()->from(
            ['main_table' => $this->getMainTable()],
            [
                '*',
                'item-disabled' => new \Zend_Db_Expr($caseExpression),
            ]
        );
        $this->getSelect()->where(sprintf('main_table.%s IS NULL', UpdateInterface::IS_ROLLBACK));
        $this->getSelect()->where('main_table.id > ' . $this->versionManager->getCurrentVersion()->getId());

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
