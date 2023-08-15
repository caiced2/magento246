<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Model\ResourceModel\Hierarchy;

use Magento\Cms\Api\Data\PageInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\VersionsCms\Model\Hierarchy\NodeFactory;

/**
 * Cms Hierarchy Pages Node Resource Model
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Node extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Secondary table for storing meta data
     *
     * @var string
     */
    protected $_metadataTable;

    /**
     * Flag to indicate whether append active pages only or not
     *
     * @var bool
     */
    protected $_appendActivePagesOnly = false;

    /**
     * Flag to indicate whether append included in menu pages only or not
     *
     * @var bool
     */
    protected $_appendIncludedPagesOnly = false;

    /**
     * Maximum tree depth for tree slice, if equals zero - no limitations
     *
     * @var int
     */
    protected $_treeMaxDepth = 0;

    /**
     * Tree Detalization, i.e. brief or detailed
     *
     * @var bool
     */
    protected $_treeIsBrief = false;

    /**
     * @var NodeFactory
     */
    protected $_nodeFactory;

    /**
     * @var MetadataPool
     */
    protected $metadataPool;

    /**
     * @param Context $context
     * @param NodeFactory $nodeFactory
     * @param MetadataPool $metadataPool
     * @param string $connectionName
     */
    public function __construct(
        Context $context,
        NodeFactory $nodeFactory,
        MetadataPool $metadataPool,
        $connectionName = null
    ) {
        $this->_nodeFactory = $nodeFactory;
        $this->metadataPool = $metadataPool;
        parent::__construct($context, $connectionName);
    }

    /**
     * Initialize connection and define main table and field
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magento_versionscms_hierarchy_node', 'node_id');
        $this->_metadataTable = $this->getTable('magento_versionscms_hierarchy_metadata');
    }

    /**
     * Retrieve select object for load object data.
     * Join page information if page assigned.
     * Join secondary table with meta data for root nodes.
     *
     * @param string $field
     * @param mixed $value
     * @param \Magento\VersionsCms\Model\Hierarchy\Node $object
     * @return \Magento\Framework\DB\Select
     */
    protected function _getLoadSelect($field, $value, $object)
    {
        $entityMetadata = $this->metadataPool->getMetadata(PageInterface::class);

        $select = parent::_getLoadSelect($field, $value, $object);
        $select->joinLeft(
            ['page_table' => $entityMetadata->getEntityTable()],
            $this->getMainTable() . '.page_id = page_table.' . $entityMetadata->getIdentifierField(),
            ['page_title' => 'title', 'page_identifier' => 'identifier', 'page_is_active' => 'is_active']
        )->joinLeft(
            ['metadata_table' => $this->_metadataTable],
            sprintf('%s.%s = metadata_table.node_id', $this->getMainTable(), $this->getIdFieldName()),
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

        $this->_applyParamFilters($select);

        return $select;
    }

    /**
     * Add attributes filter to select object based on flags
     *
     * @param \Magento\Framework\DB\Select $select Select object instance
     * @return $this
     */
    protected function _applyParamFilters($select)
    {
        if ($this->_appendActivePagesOnly) {
            $condition = sprintf('page_table.is_active=1 OR %s.page_id IS NULL', $this->getMainTable());
            $select->where($condition);
        }
        if ($this->_appendIncludedPagesOnly) {
            $select->where('metadata_table.menu_excluded = ?', 0);
        }
        return $this;
    }

    /**
     * Flag to indicate whether append active pages only or not
     *
     * @param bool $flag
     * @return $this
     */
    public function setAppendActivePagesOnly($flag)
    {
        $this->_appendActivePagesOnly = (bool)$flag;
        return $this;
    }

    /**
     * Flag to indicate whether append included pages (menu_excluded=0) only or not
     *
     * @param bool $flag
     * @return $this
     */
    public function setAppendIncludedPagesOnly($flag)
    {
        $this->_appendIncludedPagesOnly = (bool)$flag;
        return $this;
    }

    /**
     * Load node by Request Path
     *
     * @param \Magento\VersionsCms\Model\Hierarchy\Node $object
     * @param string $url
     * @return $this
     */
    public function loadByRequestUrl($object, $url)
    {
        $connection = $this->getConnection();
        if ($url !== null) {
            $select = $this->_getLoadSelect('request_url', $url, $object);
            if ($object) {
                $select->where('scope = ?', $object->getScope())->where('scope_id = ?', $object->getScopeId());
            }
            $data = $connection->fetchRow($select);

            if ($data) {
                $object->setData($data);
            }
        }

        $this->_afterLoad($object);
        return $this;
    }

    /**
     * Load First node by parent node id
     *
     * @param \Magento\VersionsCms\Model\Hierarchy\Node $object
     * @param int $parentNodeId
     * @return $this
     */
    public function loadFirstChildByParent($object, $parentNodeId)
    {
        $connection = $this->getConnection();
        if ($parentNodeId !== null) {
            $select = $this->_getLoadSelect(
                'parent_node_id',
                $parentNodeId,
                $object
            )->order(
                [$this->getMainTable() . '.sort_order']
            )->limit(
                1
            );
            $data = $connection->fetchRow($select);

            if ($data) {
                $object->setData($data);
            }
        }

        $this->_afterLoad($object);
        return $this;
    }

    /**
     * Remove children by root node.
     *
     * @param \Magento\VersionsCms\Model\Hierarchy\Node $object
     * @return $this
     */
    public function removeTreeChilds($object)
    {
        $where = ['parent_node_id = ?' => (int)$object->getId()];
        $this->getConnection()->delete($this->getMainTable(), $where);
        return $this;
    }

    /**
     * Retrieve xpaths array which contains defined page
     *
     * @param int $pageId
     * @return array
     */
    public function getTreeXpathsByPage($pageId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getMainTable(),
            'xpath'
        )->where(
            'page_id = ?',
            $pageId
        );

        $rowset = $this->getConnection()->fetchAll($select);
        $treeXpaths = [];
        foreach ($rowset as $row) {
            $treeXpaths[] = $row['xpath'];
        }
        return $treeXpaths;
    }

    /**
     * Rebuild URL rewrites for a tree with specified path.
     *
     * @param string $xpath
     * @return $this
     */
    public function updateRequestUrlsForTreeByXpath($xpath)
    {
        $entityMetadata = $this->metadataPool->getMetadata(PageInterface::class);

        $select = $this->getConnection()->select()->from(
            ['node_table' => $this->getMainTable()],
            [
                $this->getIdFieldName(),
                'parent_node_id',
                'page_id',
                'identifier',
                'request_url',
                'level',
                'sort_order'
            ]
        )->joinLeft(
            ['page_table' => $entityMetadata->getEntityTable()],
            'node_table.page_id=page_table.' . $entityMetadata->getIdentifierField(),
            ['page_identifier' => 'identifier']
        )->where(
            'xpath LIKE ? OR xpath = ?',
            $xpath . '/%'
        )->group(
            'node_table.node_id'
        )->order(
            ['level', 'sort_order']
        );

        $nodes = [];
        $rowSet = $this->getConnection()->fetchAll($select);
        foreach ($rowSet as $row) {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $nodes[intval($row['parent_node_id'])][$row[$this->getIdFieldName()]] = $row;
        }

        if (!$nodes) {
            return $this;
        }

        $keys = array_keys($nodes);
        $parentNodeId = array_shift($keys);
        $this->_updateNodeRequestUrls($nodes, $parentNodeId, null);

        return $this;
    }

    /**
     * Recursive update Request URL for node and all it's children
     *
     * @param array $nodes
     * @param int $parentNodeId
     * @param string $path
     * @return $this
     */
    protected function _updateNodeRequestUrls(array $nodes, $parentNodeId = 0, $path = null)
    {
        foreach ($nodes[$parentNodeId] as $nodeRow) {
            $identifier = $nodeRow['page_id'] ? $nodeRow['page_identifier'] : $nodeRow['identifier'];

            if ($path) {
                $requestUrl = $path . '/' . $identifier;
            } else {
                $route = explode('/', $nodeRow['request_url']);
                array_pop($route);
                $route[] = $identifier;
                $requestUrl = implode('/', $route);
            }

            if ($nodeRow['request_url'] != $requestUrl) {
                $this->getConnection()->update(
                    $this->getMainTable(),
                    ['request_url' => $requestUrl],
                    $this->getConnection()->quoteInto(
                        $this->getIdFieldName() . '=?',
                        $nodeRow[$this->getIdFieldName()]
                    )
                );
            }

            if (isset($nodes[$nodeRow[$this->getIdFieldName()]])) {
                $this->_updateNodeRequestUrls($nodes, $nodeRow[$this->getIdFieldName()], $requestUrl);
            }
        }

        return $this;
    }

    /**
     * Check identifier
     * If a CMS Page belongs to a tree (binded to a tree node), it should not be accessed standalone
     * only by URL that identifies it in a hierarchy.
     *
     * @param string $identifier
     * @param int $storeId
     * @return bool
     */
    public function checkIdentifier($identifier, $storeId)
    {
        $entityMetadata = $this->metadataPool->getMetadata(PageInterface::class);
        $linkField = $entityMetadata->getLinkField();

        $connection = $this->getConnection();
        $select = $connection->select()->from(
            ['main_table' => $entityMetadata->getEntityTable()],
            [$entityMetadata->getIdentifierField(), 'website_root']
        )->join(
            ['cps' => $this->getTable('cms_page_store')],
            'main_table.' . $entityMetadata->getIdentifierField() . ' = cps.' . $linkField,
            []
        )->where(
            'main_table.identifier = ?',
            $identifier
        )->where(
            'main_table.is_active = 1 AND cps.store_id IN (0, ?) ',
            $storeId
        )->order(
            'store_id ' . \Magento\Framework\DB\Select::SQL_DESC
        )->limit(
            1
        );

        $page = $connection->fetchRow($select);

        if (!$page || $page['website_root'] == 1) {
            return false;
        }

        return true;
    }

    /**
     * Prepare xpath after object save
     *
     * @param \Magento\Framework\Model\AbstractModel|\Magento\VersionsCms\Model\Hierarchy\Node $object
     * @return $this
     */
    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object)
    {
        if ($object->dataHasChangedFor($this->getIdFieldName())) {
            // update xpath
            $xpath = $object->getXpath() . $object->getId();
            $bind = ['xpath' => $xpath];
            $where = $this->getConnection()->quoteInto($this->getIdFieldName() . '=?', $object->getId());
            $this->getConnection()->update($this->getMainTable(), $bind, $where);
            $object->setXpath($xpath);
        }

        return $this;
    }

    /**
     * Saving meta if such available for node (in case node is root node of three)
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    public function saveMetaData(\Magento\Framework\Model\AbstractModel $object)
    {
        // we save to metadata table not only metadata :(
        //if ($object->getParentNodeId()) {
        //    return $this;
        //}
        $preparedData = $this->_prepareDataForTable($object, $this->_metadataTable);
        $this->getConnection()->insertOnDuplicate($this->_metadataTable, $preparedData, array_keys($preparedData));
        return $this;
    }

    /**
     * Load meta node's data by Parent node and Type
     * Allowed types:
     *  - chapter       parent node chapter
     *  - section       parent node section
     *  - first         first node in current parent node level
     *  - next          next node (only in current parent node level)
     *  - previous      previous node (only in current parent node level)
     *
     * @param \Magento\VersionsCms\Model\Hierarchy\Node $node The parent node
     * @param string $type
     * @return array|bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getMetaNodeDataByType($node, $type)
    {
        $connection = $this->getConnection();
        if ($connection) {
            $select = $this->_getLoadSelectWithoutWhere();
            $found = false;
            // Whether add parent node limitation to select or not
            $addParentNodeCondition = false;

            switch ($type) {
                case \Magento\VersionsCms\Model\Hierarchy\Node::META_NODE_TYPE_CHAPTER:
                case \Magento\VersionsCms\Model\Hierarchy\Node::META_NODE_TYPE_SECTION:
                    $fieldName = 'meta_chapter';
                    if ($type == \Magento\VersionsCms\Model\Hierarchy\Node::META_NODE_TYPE_SECTION) {
                        $fieldName = 'meta_section';
                    }
                    if ($node->getData($fieldName)) {
                        $found = $node->getData();
                        break;
                    }
                    $xpath = explode('/', $node->getXpath());
                    array_pop($xpath);
                    // exclude self node
                    if (count($xpath) > 0) {
                        $found = true;
                        $select->where(
                            $this->getMainTable() . '.node_id IN (?)',
                            $xpath
                        )->where(
                            'metadata_table.' . $fieldName . '=1'
                        )->order(
                            [$this->getMainTable() . '.level ' . \Magento\Framework\DB\Select::SQL_DESC]
                        )->limit(
                            1
                        );
                    }
                    break;

                case \Magento\VersionsCms\Model\Hierarchy\Node::META_NODE_TYPE_FIRST:
                    $found = true;
                    $addParentNodeCondition = true;
                    $select->order($this->getMainTable() . '.sort_order ' . \Magento\Framework\DB\Select::SQL_ASC);
                    $select->limit(1);
                    break;

                case \Magento\VersionsCms\Model\Hierarchy\Node::META_NODE_TYPE_PREVIOUS:
                    if ($node->getSortOrder() > 0) {
                        $found = true;
                        $addParentNodeCondition = true;
                        $select->where($this->getMainTable() . '.sort_order<?', $node->getSortOrder());
                        $select->order($this->getMainTable() . '.sort_order ' . \Magento\Framework\DB\Select::SQL_DESC);
                        $select->limit(1);
                    }
                    break;

                case \Magento\VersionsCms\Model\Hierarchy\Node::META_NODE_TYPE_NEXT:
                    $found = true;
                    $addParentNodeCondition = true;
                    $select->where($this->getMainTable() . '.sort_order>?', $node->getSortOrder());
                    $select->order($this->getMainTable() . '.sort_order ' . \Magento\Framework\DB\Select::SQL_ASC);
                    $select->limit(1);
                    break;

                default:
                    break;
            }

            if (is_array($found)) {
                return $found;
            }

            if (!$found) {
                return false;
            }

            // Add parent node search to select
            if ($addParentNodeCondition) {
                if ($node->getParentNodeId()) {
                    $select->where($this->getMainTable() . '.parent_node_id=?', $node->getParentNodeId());
                } else {
                    $select->where($this->getMainTable() . '.parent_node_id IS NULL');
                }
            }

            return $connection->fetchRow($select);
        }

        return false;
    }

    /**
     * Setter for $_treeMaxDepth
     *
     * @param int $depth
     * @return $this
     */
    public function setTreeMaxDepth($depth)
    {
        $this->_treeMaxDepth = (int)$depth;
        return $this;
    }

    /**
     * Setter for $_treeIsBrief
     *
     * @param bool $brief
     * @return $this
     */
    public function setTreeIsBrief($brief)
    {
        $this->_treeIsBrief = (bool)$brief;
        return $this;
    }

    /**
     * Retrieve brief/detailed Tree Slice for object
     * 2 level array
     *
     * @param \Magento\VersionsCms\Model\Hierarchy\Node $object
     * @param int $up ,if equals zero - no limitation
     * @param int $down ,if equals zero - no limitation
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getTreeSlice($object, $up = 0, $down = 0)
    {
        $tree = [];
        $parentId = $object->getParentNodeId();

        if ($this->_treeMaxDepth > 0 && $object->getLevel() > $this->_treeMaxDepth) {
            return $tree;
        }

        $xpath = explode('/', $object->getXpath());
        if (!$this->_treeIsBrief) {
            array_pop($xpath); //remove self node
        }
        $parentIds = [];
        $useUp = $up > 0;
        while (count($xpath) > 0) {
            if ($useUp && $up == 0) {
                break;
            }
            $parentIds[] = array_pop($xpath);
            if ($useUp) {
                $up--;
            }
        }

        /**
         * Collect childs
         */
        $children = [];
        if ($this->_treeMaxDepth > 0 && $this->_treeMaxDepth > $object->getLevel() || $this->_treeMaxDepth == 0) {
            $children = $this->_getSliceChildren($object, $down);
        }

        /**
         * Collect parent and neighbours
         */
        $connection = $this->getConnection();
        if ($parentIds) {
            $parentId = $parentIds[count($parentIds) - 1];
            if ($this->_treeIsBrief) {
                $where = $connection->quoteInto($this->getMainTable() . '.node_id IN (?)', $parentIds);
                // Collect neighbours if there are no children
                if (count($children) == 0) {
                    $where .= $connection->quoteInto(' OR parent_node_id=?', $object->getParentNodeId());
                }
            } else {
                $where = $connection->quoteInto('parent_node_id IN (?) OR parent_node_id IS NULL', $parentIds);
            }
        } else {
            $where = 'parent_node_id IS NULL';
        }

        $select = $this->_getLoadSelectWithoutWhere()->where($where);

        if ($object) {
            $select->where('scope = ?', $object->getScope())->where('scope_id = ?', $object->getScopeId());
        }

        $select->order(['level', $this->getMainTable() . '.sort_order']);
        $nodes = $select->query()->fetchAll();
        $tree = $this->_prepareRelatedStructure($nodes, 0, $tree);

        // add children to tree
        if (count($children) > 0) {
            $tree = $this->_prepareRelatedStructure($children, 0, $tree);
        }

        return $tree;
    }

    /**
     * Return object nested childs and its neighbours in Tree Slice
     *
     * @param \Magento\VersionsCms\Model\Hierarchy\Node $object
     * @param int $down Number of Child Node Levels to Include, if equals zero - no limitation
     * @return array
     */
    protected function _getSliceChildren($object, $down = 0)
    {
        $select = $this->_getLoadSelectWithoutWhere();

        $xpath = $object->getXpath() . '/%';
        $select->where('xpath LIKE ?', $xpath);

        if (max($down, $this->_treeMaxDepth) > 0) {
            $maxLevel = $this->_treeMaxDepth > 0 ? min(
                $this->_treeMaxDepth,
                $object->getLevel() + $down
            ) : $object->getLevel() + $down;
            $select->where($this->getConnection()->quoteIdentifier('level') . ' <= ?', $maxLevel);
        }
        $select->order(['level', $this->getMainTable() . '.sort_order']);
        return $select->query()->fetchAll();
    }

    /**
     * Preparing array where all nodes grouped in sub arrays by parent id.
     *
     * @param array $nodes source node's data
     * @param int $startNodeId
     * @param array $tree Initial array which will modified and returned with new data
     * @return array
     */
    protected function _prepareRelatedStructure($nodes, $startNodeId, $tree)
    {
        foreach ($nodes as $row) {
            $parentNodeId = (int)$row['parent_node_id'] == $startNodeId ? 0 : $row['parent_node_id'];
            $tree[$parentNodeId][$row[$this->getIdFieldName()]] = $row;
        }

        return $tree;
    }

    /**
     * Retrieve Parent node children
     *
     * @param \Magento\VersionsCms\Model\Hierarchy\Node $object
     * @return array
     */
    public function getParentNodeChildren($object)
    {
        if ($object->getParentNodeId() === null) {
            $where = 'parent_node_id IS NULL';
        } else {
            $where = $this->getConnection()->quoteInto('parent_node_id=?', $object->getParentNodeId());
        }
        $select = $this->_getLoadSelectWithoutWhere()->where($where)->order($this->getMainTable() . '.sort_order');
        $nodes = $select->query()->fetchAll();

        return $nodes;
    }

    /**
     * Return nearest parent params for pagination/menu
     *
     * @param \Magento\VersionsCms\Model\Hierarchy\Node $object
     * @param string $fieldName Parent metadata field to use in filter
     * @param string $values Values for filter
     * @return array|null
     */
    public function getParentMetadataParams($object, $fieldName, $values)
    {
        $values = is_array($values) ? $values : [$values];

        $parentIds = preg_split('/\/{1}/', $object->getXpath(), 0, PREG_SPLIT_NO_EMPTY);
        array_pop($parentIds); //remove self node
        $select = $this->_getLoadSelectWithoutWhere()->where(
            $this->getMainTable() . '.node_id IN (?)',
            $parentIds
        )->where(
            'metadata_table.' . $fieldName . ' IN (?)',
            $values
        )->order(
            [$this->getMainTable() . '.level ' . \Magento\Framework\DB\Select::SQL_DESC]
        )->limit(
            1
        );
        $params = $this->getConnection()->fetchRow($select);

        if (is_array($params) && count($params) > 0) {
            return $params;
        }
        return null;
    }

    /**
     * Load page data for model if defined page id
     *
     * @param \Magento\VersionsCms\Model\Hierarchy\Node $object
     * @return $this
     */
    public function loadPageData($object)
    {
        $entityMetadata = $this->metadataPool->getMetadata(PageInterface::class);

        $pageId = $object->getPageId();
        if (!empty($pageId)) {
            $columns = [
                'page_title' => 'title',
                'page_identifier' => 'identifier',
                'page_is_active' => 'is_active',
            ];
            $select = $this->getConnection()->select()
                ->from($entityMetadata->getEntityTable(), $columns)
                ->where($entityMetadata->getIdentifierField() . '=?', $pageId)
                ->limit(1);
            $row = $this->getConnection()->fetchRow($select);
            if ($row) {
                $object->addData($row);
            }
        }
        return $this;
    }

    /**
     * Remove node which are representing specified page from defined nodes.
     *
     * Which will also remove child nodes by foreign key.
     *
     * @param int $pageId
     * @param int|array $nodes
     * @param array $scopeIds
     * @return $this
     */
    public function removePageFromNodes($pageId, $nodes, $scopeIds = [])
    {
        $whereClause = ['page_id = ?' => $pageId];
        if (!empty($nodes)) {
            $whereClause += ['parent_node_id IN (?)' => $nodes];
        } else {
            $whereClause += ['parent_node_id IS NULL'];
        }
        if (!empty($scopeIds)) {
            $whereClause += ['scope_id IN (?)' => $scopeIds];
        }
        $this->getConnection()->delete($this->getMainTable(), $whereClause);

        return $this;
    }

    /**
     * Remove nodes defined by id. Which will also remove their child nodes by foreign key.
     *
     * @param int|int[] $nodeIds
     * @return $this
     */
    public function dropNodes($nodeIds)
    {
        $this->getConnection()->delete($this->getMainTable(), ['node_id IN (?)' => $nodeIds]);
        return $this;
    }

    /**
     * Retrieve tree meta data flags from secondary table. Filtering by root node of passed node.
     *
     * @param \Magento\VersionsCms\Model\Hierarchy\Node $object
     * @return array
     */
    public function getTreeMetaData(\Magento\VersionsCms\Model\Hierarchy\Node $object)
    {
        $connection = $this->getConnection();
        $select = $connection->select();
        $xpath = explode('/', $object->getXpath());
        $select->from($this->_metadataTable)->where('node_id = ?', $xpath[0]);

        return $connection->fetchRow($select);
    }

    /**
     * Prepare load select but without where part. So all extra joins to secondary tables will be present.
     *
     * @return \Magento\Framework\DB\Select
     */
    public function _getLoadSelectWithoutWhere()
    {
        $select = $this->_getLoadSelect(null, null, null)->reset(\Magento\Framework\DB\Select::WHERE);
        $this->_applyParamFilters($select);
        return $select;
    }

    /**
     * Updating nodes sort_order with new value.
     *
     * @param int $nodeId
     * @param int $sortOrder
     * @return $this
     */
    public function updateSortOrder($nodeId, $sortOrder)
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            ['sort_order' => $sortOrder],
            [$this->getIdFieldName() . ' = ? ' => $nodeId]
        );

        return $this;
    }

    /**
     * Copy Cms Hierarchy to another scope
     *
     * @param string $scope
     * @param int $scopeId
     * @param \Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\Collection $collection
     * @return $this
     * @throws \Exception
     */
    public function copyTo($scope, $scopeId, $collection)
    {
        // Copy hierarchy
        /** @var $nodesModel \Magento\VersionsCms\Model\Hierarchy\Node */
        $nodesModel = $this->_nodeFactory->create(['data' => ['scope' => $scope, 'scope_id' => $scopeId]]);

        $nodes = [];
        foreach ($collection as $node) {
            if ($node->getLevel() == \Magento\VersionsCms\Model\Hierarchy\Node::NODE_LEVEL_FAKE) {
                continue;
            }

            $nodeData = $node->toArray();
            $nodeData['node_id'] = '_' . $nodeData['node_id'];
            $nodeData['parent_node_id'] = empty($nodeData['parent_node_id']) ? '' : '_' . $nodeData['parent_node_id'];
            if (empty($nodeData['identifier'])) {
                $nodeData['identifier'] = $nodeData['page_identifier'];
            }
            $nodes[] = $nodeData;
        }
        $this->beginTransaction();
        try {
            $nodesModel->collectTree($nodes, []);
            $this->addEmptyNode($scope, $scopeId);
            $this->commit();
        } catch (\Exception $e) {
            $this->rollBack();
            throw $e;
        }

        return $this;
    }

    /**
     * Delete Cms Hierarchy of the scope
     *
     * @param string $scope
     * @param int $scopeId
     * @return $this
     */
    public function deleteByScope($scope, $scopeId)
    {
        $this->beginTransaction();
        try {
            $connection = $this->getConnection();
            // Delete metadata
            $connection->delete(
                $this->getTable('magento_versionscms_hierarchy_metadata'),
                [
                    'node_id IN (?)' => $connection->select()->from(
                        $this->getMainTable(),
                        ['node_id']
                    )->where(
                        'scope = ?',
                        $scope
                    )->where(
                        'scope_id = ?',
                        $scopeId
                    )
                ]
            );
            // Delete nodes
            $connection->delete($this->getMainTable(), ['scope = ?' => $scope, 'scope_id = ?' => $scopeId]);
            $this->commit();
        } catch (\Exception $e) {
            $this->rollBack();
        }
        return $this;
    }

    /**
     * Whether the hierarchy is inherited from parent scope
     *
     * @param string $scope
     * @param int $scopeId
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsInherited($scope, $scopeId)
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from(
            $this->getMainTable()
        )->where(
            'scope = ?',
            $scope
        )->where(
            'scope_id = ?',
            $scopeId
        )->where(
            $connection->quoteIdentifier('level') . ' = ?',
            \Magento\VersionsCms\Model\Hierarchy\Node::NODE_LEVEL_FAKE
        )->limit(
            1
        );
        return $connection->fetchRow($select) ? false : true;
    }

    /**
     * Adding an empty node, for ability to obtain empty tree hierarchy for specific scope
     *
     * @param string $scope
     * @param int $scopeId
     * @return void
     */
    public function addEmptyNode($scope, $scopeId)
    {
        if ($scope != \Magento\VersionsCms\Model\Hierarchy\Node::NODE_SCOPE_DEFAULT && $this->getIsInherited(
            $scope,
            $scopeId
        )
        ) {
            $this->getConnection()->insert(
                $this->getMainTable(),
                ['sort_order' => 0, 'scope' => $scope, 'scope_id' => $scopeId]
            );
        }
    }
}
