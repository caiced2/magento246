<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node;

use Magento\Cms\Api\Data\PageInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Helper;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Psr\Log\LoggerInterface;

/**
 * Cms Page Hierarchy Tree Nodes Collection
 *
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var Helper
     */
    protected $_resourceHelper;

    /**
     * @var MetadataPool
     * @since 100.1.0
     */
    protected $metadataPool;

    /**
     * @param EntityFactory $entityFactory
     * @param LoggerInterface $logger
     * @param FetchStrategyInterface $fetchStrategy
     * @param ManagerInterface $eventManager
     * @param Helper $resourceHelper
     * @param MetadataPool $metadataPool
     * @param AdapterInterface $connection
     * @param AbstractDb $resource
     */
    public function __construct(
        EntityFactory $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        Helper $resourceHelper,
        MetadataPool $metadataPool,
        AdapterInterface $connection = null,
        AbstractDb $resource = null
    ) {
        $this->_resourceHelper = $resourceHelper;
        $this->metadataPool = $metadataPool;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
    }

    /**
     * Define resource model for collection
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Magento\VersionsCms\Model\Hierarchy\Node::class,
            \Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node::class
        );
    }

    /**
     * Join Cms Page data to collection
     *
     * @return $this
     */
    public function joinCmsPage()
    {
        $entityMetadata = $this->metadataPool->getMetadata(PageInterface::class);

        if (!$this->getFlag('cms_page_data_joined')) {
            $this->getSelect()->joinLeft(
                ['page_table' => $entityMetadata->getEntityTable()],
                'main_table.page_id = page_table.' . $entityMetadata->getIdentifierField(),
                ['page_title' => 'title', 'page_identifier' => 'identifier']
            );
            $this->setFlag('cms_page_data_joined', true);
        }
        return $this;
    }

    /**
     * Add Store Filter to assigned CMS pages
     *
     * @param int|\Magento\Store\Model\Store $store
     * @param bool $withAdmin Include admin store or not
     * @return $this
     */
    public function addStoreFilter($store, $withAdmin = true)
    {
        if ($store instanceof \Magento\Store\Model\Store) {
            $store = $store->getId();
        }

        if ($withAdmin) {
            $storeIds = [\Magento\Store\Model\Store::DEFAULT_STORE_ID, $store];
        } else {
            $storeIds = [$store];
        }

        $this->addCmsPageInStoresColumn();
        $entityMetadata = $this->metadataPool->getMetadata(PageInterface::class);
        $linkField = $entityMetadata->getLinkField();
        $this->getSelect()->joinLeft(
            ['cmsps' => $this->getTable('cms_page_store')],
            $this->getConnection()->quoteInto(
                'cmsps.' . $linkField . ' = page_table.' . $linkField . ' AND cmsps.store_id IN (?)',
                $storeIds,
                \Zend_Db::INT_TYPE
            ),
            []
        )->where(
            'main_table.page_id IS NULL OR cmsps.store_id IS NOT NULL'
        );
        $this->distinct(true);
        return $this;
    }

    /**
     * Adding sub query for custom column to determine on which stores page active.
     *
     * @return $this
     */
    public function addCmsPageInStoresColumn()
    {
        $entityMetadata = $this->metadataPool->getMetadata(PageInterface::class);
        $linkField = $entityMetadata->getLinkField();

        if (!$this->getFlag('cms_page_in_stores_data_joined')) {
            $this->joinCmsPage();
            $subSelect = $this->getConnection()->select();
            $subSelect->from(
                ['cps' => $this->getTable('cms_page_store')],
                []
            )->where(
                'cps.' . $linkField . ' = page_table.' . $linkField
            );
            $subSelect = $this->_resourceHelper->addGroupConcatColumn($subSelect, 'store_id', 'store_id');
            $this->getSelect()->columns(['page_in_stores' => new \Zend_Db_Expr('(' . $subSelect . ')')]);

            // save subSelect to use later
            $this->setFlag('page_in_stores_select', $subSelect);

            $this->setFlag('cms_page_in_stores_data_joined', true);
        }
        return $this;
    }

    /**
     * Order nodes as tree
     *
     * @return $this
     */
    public function setTreeOrder()
    {
        if (!$this->getFlag('tree_order_added')) {
            $this->getSelect()->order(['parent_node_id', 'level', 'main_table.sort_order']);
            $this->setFlag('tree_order_added', true);
        }
        return $this;
    }

    /**
     * Order tree by level and position
     *
     * @return $this
     */
    public function setOrderByLevel()
    {
        $this->getSelect()->order(['main_table.level', 'main_table.sort_order']);
        return $this;
    }

    /**
     * Join meta data for tree root nodes from extra table.
     *
     * @return $this
     */
    public function joinMetaData()
    {
        if (!$this->getFlag('meta_data_joined')) {
            $this->getSelect()->joinLeft(
                ['metadata_table' => $this->getTable('magento_versionscms_hierarchy_metadata')],
                'main_table.node_id = metadata_table.node_id',
                [
                    'meta_first_last',
                    'meta_next_previous',
                    'meta_chapter',
                    'meta_section',
                    'meta_cs_enabled',
                    'pager_visibility',
                    'pager_frame',
                    'pager_jump',
                    'menu_visibility',
                    'menu_layout',
                    'menu_brief',
                    'menu_excluded',
                    'menu_levels_down',
                    'menu_ordered',
                    'menu_list_type',
                    'top_menu_visibility',
                    'top_menu_excluded'
                ]
            );
        }
        $this->setFlag('meta_data_joined', true);
        return $this;
    }

    /**
     * Join main table on self.
     * Join main table on self to discover which nodes
     * have defined page as direct child node.
     *
     * @param int|\Magento\Cms\Model\Page $page
     * @return $this
     */
    public function joinPageExistsNodeInfo($page)
    {
        if (!$this->getFlag('page_exists_joined')) {
            if ($page instanceof \Magento\Cms\Model\Page) {
                $page = $page->getId();
            }

            $connection = $this->getConnection();

            $onClause = 'main_table.node_id = clone.parent_node_id AND clone.page_id = ?';
            $ifPageExistExpr = $connection->getCheckSql('clone.node_id IS NULL', '0', '1');
            $ifCurrentPageExpr = $connection->quoteInto(
                $connection->getCheckSql('main_table.page_id = ?', '1', '0'),
                $page
            );
            $this->getSelect()->joinLeft(
                ['clone' => $this->getResource()->getMainTable()],
                $connection->quoteInto($onClause, $page),
                ['page_exists' => $ifPageExistExpr, 'current_page' => $ifCurrentPageExpr]
            );
            $this->getSelect()->group('main_table.node_id');

            $this->setFlag('page_exists_joined', true);
        }
        return $this;
    }

    /**
     * Apply filter to retrieve nodes with ids which
     * were defined as parameter or nodes which contain
     * defined page in their direct children.
     *
     * @param int|int[] $nodeIds
     * @param int|\Magento\Cms\Model\Page|null $page
     * @return $this
     */
    public function applyPageExistsOrNodeIdFilter($nodeIds, $page = null)
    {
        if (!$this->getFlag('page_exists_or_node_id_filter_applied')) {
            if (!$this->getFlag('page_exists_joined')) {
                $this->joinPageExistsNodeInfo($page);
            }
            if (is_array($nodeIds) && count($nodeIds) == 0) {
                $nodeIds = 0;
            }

            $this->getSelect()->where('clone.node_id IS NOT NULL OR main_table.node_id IN (?)', $nodeIds);
            $this->setFlag('page_exists_or_node_id_filter_applied', true);
        }

        return $this;
    }

    /**
     * Adds sort order.
     * Adds dynamic column with maximum value (which means that it
     * is sort_order of last direct child) of sort_order column in scope of one node.
     *
     * @return $this
     */
    public function addLastChildSortOrderColumn()
    {
        if (!$this->getFlag('last_child_sort_order_column_added')) {
            $subSelect = $this->getConnection()->select();
            $subSelect->from(
                $this->getResource()->getMainTable(),
                new \Zend_Db_Expr('MAX(sort_order)')
            )->where(
                'parent_node_id = main_table.node_id'
            );
            $this->getSelect()->columns(['last_child_sort_order' => $subSelect]);
            $this->setFlag('last_child_sort_order_column_added', true);
        }

        return $this;
    }

    /**
     * Apply filter to retrieve only root nodes.
     *
     * @return $this
     */
    public function applyRootNodeFilter()
    {
        $this->addFieldToFilter('parent_node_id', ['null' => true]);
        return $this;
    }

    /**
     * Apply filter to retrieve only proper scope nodes.
     *
     * @param string $scope Scope name: default|store|website
     * @return $this
     */
    public function applyScope($scope)
    {
        $this->getSelect()->where('main_table.scope = ?', $scope);
        return $this;
    }

    /**
     * Apply filter to retrieve only proper scope ID nodes.
     *
     * @param int $codeId
     * @return $this
     */
    public function applyScopeId($codeId)
    {
        $this->getSelect()->where('main_table.scope_id = ?', $codeId);
        return $this;
    }
}
