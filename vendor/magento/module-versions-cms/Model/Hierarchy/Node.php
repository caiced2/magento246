<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Model\Hierarchy;

use Magento\Cms\Model\Page;
use Magento\Framework\App\Cache\Type\Block as BlockCache;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\App\ObjectManager;
use Magento\Backend\Model\Validator\UrlKey\CompositeUrlKey;
use Magento\Framework\Exception\LocalizedException;

/**
 * Cms Hierarchy Pages Node Model
 *
 * @api
 * @method int getParentNodeId()
 * @method \Magento\VersionsCms\Model\Hierarchy\Node setParentNodeId(int $value)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @since 100.0.2
 */
class Node extends AbstractModel implements \Magento\VersionsCms\Api\Data\HierarchyNodeInterface
{
    /**
     * Meta node's types
     */
    const META_NODE_TYPE_CHAPTER = 'chapter';

    const META_NODE_TYPE_SECTION = 'section';

    const META_NODE_TYPE_FIRST = 'start';

    const META_NODE_TYPE_NEXT = 'next';

    const META_NODE_TYPE_PREVIOUS = 'prev';

    /**
     * Node's scope constants
     */
    const NODE_SCOPE_DEFAULT = 'default';

    const NODE_SCOPE_WEBSITE = 'website';

    const NODE_SCOPE_STORE = 'store';

    const NODE_SCOPE_DEFAULT_ID = 0;

    /**
     * Whether the hierarchy is inherited from parent scope
     *
     * @var null|bool
     */
    protected $_isInherited = null;

    /**
     * Copy collection cache
     *
     * @var array
     */
    protected $_copyCollection = null;

    /**
     * @var array
     */
    protected $_metaNodes = [];

    /**
     * The level of root node for appropriate scope
     */
    const NODE_LEVEL_FAKE = 0;

    /**
     * Node's scope
     * @var string
     */
    protected $_scope = self::NODE_SCOPE_DEFAULT;

    /**
     * Node's scope ID
     *
     * @var int
     */
    protected $_scopeId = self::NODE_SCOPE_DEFAULT_ID;

    /**
     * Tree metadata
     *
     * @var array
     */
    protected $_treeMetaData;

    /**
     * @var \Magento\VersionsCms\Model\Hierarchy\ConfigInterface
     */
    protected $_hierarchyConfig;

    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * Cms hierarchy
     *
     * @var \Magento\VersionsCms\Helper\Hierarchy
     */
    protected $_cmsHierarchy;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     * @var \Magento\VersionsCms\Model\Hierarchy\NodeFactory
     */
    protected $_nodeFactory;

    /**
     * @var CompositeUrlKey
     */
    private $compositeUrlValidator;

    /**
     * @var TypeListInterface
     */
    private $appCache;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\VersionsCms\Helper\Hierarchy $cmsHierarchy
     * @param ConfigInterface $hierarchyConfig
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node $resource
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param NodeFactory $nodeFactory
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     * @param CompositeUrlKey|null $compositeUrlValidator
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\VersionsCms\Helper\Hierarchy $cmsHierarchy,
        \Magento\VersionsCms\Model\Hierarchy\ConfigInterface $hierarchyConfig,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node $resource,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Store\Model\System\Store $systemStore,
        \Magento\VersionsCms\Model\Hierarchy\NodeFactory $nodeFactory,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        CompositeUrlKey $compositeUrlValidator = null,
        TypeListInterface $appCache = null
    ) {
        $this->_cmsHierarchy = $cmsHierarchy;
        $this->_hierarchyConfig = $hierarchyConfig;
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->_systemStore = $systemStore;
        $this->_nodeFactory = $nodeFactory;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $scope = $scopeId = null;
        if (array_key_exists('scope', $data)) {
            $scope = $data['scope'];
        }

        if (array_key_exists('scope_id', $data)) {
            $scopeId = $data['scope_id'];
        }

        $this->setScope($scope);

        $this->setScopeId($scopeId);
        $this->compositeUrlValidator = $compositeUrlValidator
            ?? ObjectManager::getInstance()->get(CompositeUrlKey::class);
        $this->appCache = $appCache
            ?? ObjectManager::getInstance()->get(TypeListInterface::class);
    }

    /**
     * Set nodes scope
     *
     * @param string $scope
     * @return $this
     */
    public function setScope($scope)
    {
        if ($scope == self::NODE_SCOPE_STORE || $scope == self::NODE_SCOPE_WEBSITE) {
            $this->_scope = $scope;
        } else {
            $this->_scope = self::NODE_SCOPE_DEFAULT;
        }
        return $this->setData(self::SCOPE, $this->_scope);
    }

    /**
     * Set nodes scope id
     *
     * @param int|string $scopeId
     * @return $this
     */
    public function setScopeId($scopeId)
    {
        $collection = [];
        if ($this->_scope == self::NODE_SCOPE_STORE) {
            $collection = $this->_systemStore->getStoreCollection();
        } elseif ($this->_scope == self::NODE_SCOPE_WEBSITE) {
            $collection = $this->_systemStore->getWebsiteCollection();
        }

        $isSet = false;
        foreach ($collection as $scope) {
            if ($scope->getCode() == $scopeId || $scope->getId() == $scopeId) {
                $isSet = true;
                $this->_scopeId = $scope->getId();
            }
        }

        if (!$isSet) {
            $this->_scope = self::NODE_SCOPE_DEFAULT;
            $this->_scopeId = self::NODE_SCOPE_DEFAULT_ID;
        }

        return $this->setData(self::SCOPE, $this->_scope)
            ->setData(self::SCOPE_ID, $this->_scopeId);
    }

    /**
     * Retrieving nodes for appropriate scope and scope ID.
     *
     * @return array
     */
    public function getNodesData()
    {
        $nodes = [];
        $collection = $this->getCollection()->joinCmsPage()->addCmsPageInStoresColumn()->joinMetaData()->applyScope(
            $this->_scope
        )->applyScopeId(
            $this->_scopeId
        )->setOrderByLevel();

        $this->_isInherited = $this->getIsInherited(true);
        foreach ($collection as $item) {
            $this->_isInherited = false;
            if ($item->getLevel() == self::NODE_LEVEL_FAKE) {
                continue;
            }
            /* @var $item \Magento\VersionsCms\Model\Hierarchy\Node */
            $node = [
                'node_id' => $item->getId(),
                'parent_node_id' => $item->getParentNodeId(),
                'label' => $item->getLabel(),
                'identifier' => $item->getIdentifier(),
                'page_id' => $item->getPageId(),
            ];
            $nodes[] = $this->_cmsHierarchy->copyMetaData($item->getData(), $node);
        }

        return $nodes;
    }

    /**
     * Retrieving nodes collection for appropriate scope and scope ID.
     *
     * @return \Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\Collection
     */
    public function getNodesCollection()
    {
        $collection = $this->getCollection()->joinCmsPage()->addCmsPageInStoresColumn()->joinMetaData()->applyScope(
            $this->_scope
        )->applyScopeId(
            $this->_scopeId
        )->setOrderByLevel();

        return $collection;
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node::class);
    }

    /**
     * Collect and save tree
     *
     * @param array $data modified nodes data array
     * @param array $remove the removed node ids
     * @return $this
     * @throws LocalizedException|\Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function collectTree($data, $remove)
    {
        if (!is_array($data)) {
            return $this;
        }

        $nodes = [];
        foreach ($data as $nodeData) {
            if (!$this->checkRequiredFields($nodeData)) {
                throw new LocalizedException(__('Please correct the node data.'));
            }
            $this->validateIdentifier($nodeData);
            $parentNodeId = empty($nodeData['parent_node_id']) ? 0 : $nodeData['parent_node_id'];
            $nodes[$parentNodeId][$nodeData['node_id']] = $this->_cmsHierarchy->copyMetaData(
                $nodeData,
                $this->prepareNodeData($nodeData)
            );
        }

        $this->persistTree($nodes, $remove);
        $this->appCache->invalidate(BlockCache::TYPE_IDENTIFIER);

        return $this;
    }

    /**
     * Prepare node data
     *
     * @param array $data
     * @return array
     */
    protected function prepareNodeData(array $data)
    {
        $pageId = empty($data['page_id']) ? null : (int)$data['page_id'];
        return [
            'node_id' => strpos($data['node_id'], '_') === 0 ? null : (int)$data['node_id'],
            'page_id' => $pageId,
            'label' => !$pageId ? $data['label'] : null,
            'identifier' => !$pageId ? $data['identifier'] : null,
            'level' => (int)$data['level'],
            'sort_order' => (int)$data['sort_order'],
            'request_url' => $data['identifier'],
            'scope' => $this->_scope,
            'scope_id' => $this->_scopeId,
        ];
    }

    /**
     * Persist the tree in database
     *
     * @param array $nodes
     * @param array|null $remove
     * @return void
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function persistTree(array $nodes, $remove)
    {
        $this->_getResource()->beginTransaction();
        try {
            // remove deleted nodes
            if (!empty($remove)) {
                $this->_getResource()->dropNodes($remove);
            }
            // recursive node save
            $this->_collectTree($nodes, $this->getId(), $this->getRequestUrl(), $this->getId(), 0);

            $this->_getResource()->addEmptyNode($this->_scope, $this->_scopeId);
            $this->_getResource()->commit();
        } catch (\Exception $e) {
            $this->_getResource()->rollBack();
            throw $e;
        }
    }

    /**
     * Check if all required fields are set
     *
     * @param array $data
     * @return bool
     */
    protected function checkRequiredFields(array $data)
    {
        $required = [
            'node_id', 'parent_node_id', 'page_id', 'label',
            'identifier', 'level', 'sort_order'
        ];
        // validate required node data
        foreach ($required as $field) {
            if (!array_key_exists($field, $data)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Delete Cms Hierarchy of the scope
     *
     * @param string $scope
     * @param int $scopeId
     * @return void
     */
    public function deleteByScope($scope, $scopeId)
    {
        $this->_getResource()->deleteByScope($scope, $scopeId);
    }

    /**
     * Recursive save nodes
     *
     * @param array $nodes
     * @param int $parentNodeId
     * @param string $path
     * @param string $xpath
     * @param int $level
     * @return $this
     */
    protected function _collectTree(array $nodes, $parentNodeId, $path = '', $xpath = '', $level = 0)
    {
        if (!isset($nodes[$level])) {
            return $this;
        }
        foreach ($nodes[$level] as $k => $v) {
            $v['parent_node_id'] = $parentNodeId;
            if ($path != '') {
                $v['request_url'] = $path . '/' . $v['request_url'];
            }

            if ($xpath != '') {
                $v['xpath'] = $xpath . '/';
            } else {
                $v['xpath'] = '';
            }

            $object = clone $this;
            $object->setData($v)->save();

            if (isset($nodes[$k])) {
                $this->_collectTree($nodes, $object->getId(), $object->getRequestUrl(), $object->getXpath(), $k);
            }
        }
        return $this;
    }

    /**
     * Flag to indicate whether append active pages only or not
     *
     * @param bool $flag
     * @return $this
     */
    public function setCollectActivePagesOnly($flag)
    {
        $flag = (bool)$flag;
        $this->setData('collect_active_pages_only', $flag);
        $this->_getResource()->setAppendActivePagesOnly($flag);
        return $this;
    }

    /**
     * Flag to indicate whether append included pages (menu_excluded=0) only or not
     *
     * @param bool $flag
     * @return $this
     */
    public function setCollectIncludedPagesOnly($flag)
    {
        $flag = (bool)$flag;
        $this->setData('collect_included_pages_only', $flag);
        $this->_getResource()->setAppendIncludedPagesOnly($flag);
        return $this;
    }

    /**
     * Retrieve Node or Page identifier
     *
     * @return string
     */
    public function getIdentifier()
    {
        $identifier = $this->_getData('identifier');
        if ($identifier === null) {
            $identifier = $this->_getData('page_identifier');
        }
        return $identifier;
    }

    /**
     * Is Node used original Page Identifier
     *
     * @return bool
     */
    public function isUseDefaultIdentifier()
    {
        return $this->_getData('identifier') === null;
    }

    /**
     * Retrieve Node label or Page title
     *
     * @return string
     */
    public function getLabel()
    {
        $label = $this->_getData('label');
        if ($label === null) {
            $label = $this->_getData('page_title');
        }
        return $label;
    }

    /**
     * Is Node used original Page Label
     *
     * @return bool
     */
    public function isUseDefaultLabel()
    {
        return $this->_getData('label') === null;
    }

    /**
     * Load node by Request Url
     *
     * @param string $url
     * @return $this
     */
    public function loadByRequestUrl($url)
    {
        $this->_getResource()->loadByRequestUrl($this, $url);
        $this->_afterLoad();
        $this->setOrigData();
        return $this;
    }

    /**
     * Retrieve first child node
     *
     * @param int $parentNodeId
     * @return $this
     */
    public function loadFirstChildByParent($parentNodeId)
    {
        $this->_getResource()->loadFirstChildByParent($this, $parentNodeId);
        $this->_afterLoad();
        $this->setOrigData();
        return $this;
    }

    /**
     * Update rewrite for page (if identifier changed)
     *
     * @param Page $page
     * @return $this
     */
    public function updateRewriteUrls(Page $page)
    {
        $xpaths = $this->_getResource()->getTreeXpathsByPage($page->getId());
        foreach ($xpaths as $xpath) {
            $this->_getResource()->updateRequestUrlsForTreeByXpath($xpath);
        }
        return $this;
    }

    /**
     * Check identifier
     *
     * If a CMS Page belongs to a tree (binded to a tree node), it should not be accessed standalone
     * only by URL that identifies it in a hierarchy.
     *
     * Return true if a page binded to a tree node
     *
     * @param string $identifier
     * @param int|\Magento\Store\Model\Store $storeId
     * @return bool
     */
    public function checkIdentifier($identifier, $storeId = null)
    {
        $storeId = $this->_storeManager->getStore($storeId)->getId();
        return $this->_getResource()->checkIdentifier($identifier, $storeId);
    }

    /**
     * Retrieve meta node by specified type for current node's tree.
     * Allowed types:
     *  - chapter       parent node chapter
     *  - section       parent node section
     *  - first         first node in current parent node level
     *  - next          next node (only in current parent node level)
     *  - previous      previous node (only in current parent node level)
     *
     * @param string $type
     * @return $this
     */
    public function getMetaNodeByType($type)
    {
        if (!isset($this->_metaNodes[$type])) {
            /** @var array|bool $data */
            $data = $this->_getResource()->getMetaNodeDataByType($this, $type);
            $model = $this->_nodeFactory->create();
            if ($data !== false) {
                $model->setData($data);
            }

            $this->_metaNodes[$type] = $model;
        }

        return $this->_metaNodes[$type];
    }

    /**
     * Retrieve Page URL
     *
     * @param mixed $store
     * @return string
     */
    public function getUrl($store = null)
    {
        return $this->_storeManager->getStore($store)->getUrl('', ['_direct' => trim($this->getRequestUrl())]);
    }

    /**
     * Setter for tree_max_depth data
     *
     * Maximum tree depth for tree slice, if equals zero - no limitations
     *
     * @param int $depth
     * @return $this
     */
    public function setTreeMaxDepth($depth)
    {
        $this->setData('tree_max_depth', (int)$depth);
        return $this;
    }

    /**
     * Setter for tree_is_brief data
     *
     * Tree Detalization, i.e. brief or detailed
     *
     * @param bool $brief
     * @return $this
     */
    public function setTreeIsBrief($brief)
    {
        $this->setData('tree_is_brief', (bool)$brief);
        return $this;
    }

    /**
     * Retrieve Tree Slice like two level array of node models.
     *
     * @param int $up ,if equals zero - no limitation
     * @param int $down ,if equals zero - no limitation
     * @return array
     */
    public function getTreeSlice($up = 0, $down = 0)
    {
        $data = $this->_getResource()->setTreeMaxDepth(
            $this->_getData('tree_max_depth')
        )->setTreeIsBrief(
            $this->_getData('tree_is_brief')
        )->getTreeSlice(
            $this,
            $up,
            $down
        );

        $blankModel = $this->_nodeFactory->create();
        foreach ($data as $parentId => $children) {
            foreach ($children as $childId => $child) {
                $newModel = clone $blankModel;
                $data[$parentId][$childId] = $newModel->setData($child);
            }
        }
        return $data;
    }

    /**
     * Retrieve parent node's children.
     *
     * @return array
     */
    public function getParentNodeChildren()
    {
        $children = $this->_getResource()->getParentNodeChildren($this);
        $blankModel = $this->_nodeFactory->create();
        foreach ($children as $childId => $child) {
            $newModel = clone $blankModel;
            $children[$childId] = $newModel->setData($child);
        }
        return $children;
    }

    /**
     * Load page data for model if defined page id end undefined page data
     *
     * @return $this
     */
    public function loadPageData()
    {
        if ($this->getPageId() && !$this->getPageIdentifier()) {
            $this->_getResource()->loadPageData($this);
        }

        return $this;
    }

    /**
     * Appending passed page as child node for specified nodes and set it specified sort order.
     *
     * Parent nodes specified as array (parentNodeId => sortOrder)
     *
     * @param Page $page
     * @param array $nodes
     * @return $this
     */
    public function appendPageToNodes($page, $nodes)
    {
        $nodesToFilter = ($nodes === null) ? [] : array_keys($nodes);
        $parentNodes = $this->getCollection()->joinPageExistsNodeInfo(
            $page
        )->applyPageExistsOrNodeIdFilter(
            $nodesToFilter,
            $page
        );

        $pageData = ['page_id' => $page->getId(), 'identifier' => null, 'label' => null];

        $removeFromNodes = [];

        foreach ($parentNodes as $node) {
            /* @var $node \Magento\VersionsCms\Model\Hierarchy\Node */
            if (isset($nodes[$node->getId()])) {
                $sortOrder = $nodes[$node->getId()];
                if ($node->getPageExists()) {
                    continue;
                } else {
                    $node->addData(
                        $pageData
                    )->setParentNodeId(
                        $node->getId()
                    )->unsetData(
                        $this->getIdFieldName()
                    )->setLevel(
                        $node->getLevel() + 1
                    )->setSortOrder(
                        $sortOrder
                    )->setRequestUrl(
                        $node->getRequestUrl() . '/' . $page->getIdentifier()
                    )->setXpath(
                        $node->getXpath() . '/'
                    );

                    $this->saveHierarchyNodePerStore($page, $node);
                }
            } else {
                $removeFromNodes[] = $node->getId();
            }
        }

        if (!empty($removeFromNodes) && $nodes !== null) {
            $this->_getResource()->removePageFromNodes($page->getId(), $removeFromNodes);
        }

        return $this;
    }

    /**
     * Save hierarchy node for stores which the page is related
     *
     * @param Page $page
     * @param Node $node
     * @return void
     */
    private function saveHierarchyNodePerStore(Page $page, Node $node): void
    {
        $storeIds = $page->getStores();
        if ($page->getBack() === 'duplicate') {
            $node->save();
        } else {
            foreach ($storeIds as $storeId) {
                $nodePerStore = clone $node;
                if ($storeId !== self::NODE_SCOPE_DEFAULT_ID) {
                    $nodePerStore->setScope(self::NODE_SCOPE_STORE);
                }
                $nodePerStore->setScopeId($storeId);
                $nodePerStore->save();
            }
        }
    }

    /**
     * Get tree meta data flags for current node's tree.
     *
     * @return array|bool
     */
    public function getTreeMetaData()
    {
        if ($this->_treeMetaData === null) {
            $this->_treeMetaData = $this->_getResource()->getTreeMetaData($this);
        }

        return $this->_treeMetaData;
    }

    /**
     * Return nearest parent params for node pagination
     *
     * @return array|null
     */
    public function getMetadataPagerParams()
    {
        $values = [
            \Magento\VersionsCms\Helper\Hierarchy::METADATA_VISIBILITY_YES,
            \Magento\VersionsCms\Helper\Hierarchy::METADATA_VISIBILITY_NO,
        ];

        return $this->getResource()->getParentMetadataParams($this, 'pager_visibility', $values);
    }

    /**
     * Return nearest parent params for node context menu
     *
     * @return array|null
     */
    public function getMetadataContextMenuParams()
    {
        // Node is excluded from Menu
        if ($this->getData('menu_excluded') == 1) {
            return null;
        }

        // Menu is disabled in some of parent nodes
        $params = $this->getResource()->getParentMetadataParams($this, 'menu_excluded', [1]);
        if ($params !== null && $params['level'] > 1) {
            return null;
        }

        // Root node menu params
        $params = $this->getResource()->getTreeMetaData($this);
        if (isset($params['menu_visibility']) && $params['menu_visibility'] == 1) {
            return $params;
        }

        return null;
    }

    /**
     * Return Hierarchy Menu Layout Info object for Node
     *
     * @return \Magento\Framework\DataObject|null
     */
    public function getMenuLayout()
    {
        $rootParams = $this->_getResource()->getTreeMetaData($this);
        if (!array_key_exists('menu_layout', $rootParams)) {
            return null;
        }
        $layoutName = $rootParams['menu_layout'];
        if (!$layoutName) {
            $layoutName = $this->_scopeConfig->getValue(
                'cms/hierarchy/menu_layout',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }
        if (!$layoutName) {
            return null;
        }
        $layout = $this->_hierarchyConfig->getContextMenuLayout($layoutName);
        return $layout ? $layout : null;
    }

    /**
     * Process additional data after save.
     *
     * @return $this
     */
    public function afterSave()
    {
        parent::afterSave();
        // we save to metadata table not only metadata :(
        //if ($this->_cmsHierarchy->isMetadataEnabled()) {
        $this->_getResource()->saveMetaData($this);
        //}

        return $this;
    }

    /**
     * Copy Cms Hierarchy to another scope
     *
     * @param string $scope
     * @param int $scopeId
     * @return $this
     */
    public function copyTo($scope, $scopeId)
    {
        if ($this->_scope == $scope && $this->_scopeId == $scopeId) {
            return $this;
        }

        $this->getResource()->deleteByScope($scope, $scopeId);

        if (!$this->_copyCollection) {
            $this->_copyCollection = $this->getCollection()->applyScope(
                $this->_scope
            )->applyScopeId(
                $this->_scopeId
            )->joinCmsPage()->joinMetaData();
        }
        $this->getResource()->copyTo($scope, $scopeId, $this->_copyCollection);
        return $this;
    }

    /**
     * Whether the hierarchy is inherited from parent scope
     *
     * @param bool $soft If true then we will not make requests to the DB and will return true if scope is not default
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsInherited($soft = false)
    {
        if ($this->_isInherited === null) {
            if ($this->getScope() === self::NODE_SCOPE_DEFAULT) {
                $this->_isInherited = false;
            } elseif (!$soft) {
                $this->_isInherited = $this->getResource()->getIsInherited($this->_scope, $this->_scopeId);
            } else {
                return true;
            }
        }

        return $this->_isInherited;
    }

    /**
     * Get heritage hierarchy
     *
     * @return $this
     */
    public function getHeritage()
    {
        if ($this->getIsInherited()) {
            $helper = $this->_cmsHierarchy;
            $parentScope = $helper->getParentScope($this->_scope, $this->_scopeId);
            $parentScopeNode = $this->_nodeFactory->create(
                ['data' => ['scope' => $parentScope[0], 'scope_id' => $parentScope[1]]]
            );
            if ($parentScopeNode->getIsInherited()) {
                $parentScope = $helper->getParentScope($parentScope[0], $parentScope[1]);
                $parentScopeNode = $this->_nodeFactory->create(
                    ['data' => ['scope' => $parentScope[0], 'scope_id' => $parentScope[1]]]
                );
            }
            return $parentScopeNode;
        }

        return $this;
    }

    /**
     * Get current scope
     *
     * @return string
     */
    public function getScope()
    {
        return $this->getData(self::SCOPE);
    }

    /**
     * Get current scopeId
     *
     * @return int
     */
    public function getScopeId()
    {
        return $this->getData(self::SCOPE_ID);
    }

    /**
     * Get ID
     *
     * @return int
     */
    public function getId()
    {
        return $this->getData(self::NODE_ID);
    }

    /**
     * Get parent ID
     *
     * @return int
     */
    public function getParentId()
    {
        return $this->getData(self::PARENT_NODE_ID);
    }

    /**
     * Get page ID
     *
     * @return int
     */
    public function getPageId()
    {
        return $this->getData(self::PAGE_ID);
    }

    /**
     * Get level
     *
     * @return int
     */
    public function getLevel()
    {
        return $this->getData(self::LEVEL);
    }

    /**
     * Get sort order
     *
     * @return int
     */
    public function getSortOrder()
    {
        return $this->getData(self::SORT_ORDER);
    }

    /**
     * Get request url
     *
     * @return string
     */
    public function getRequestUrl()
    {
        return $this->getData(self::REQUEST_URL);
    }

    /**
     * Get xpath
     *
     * @return string
     */
    public function getXpath()
    {
        return $this->getData(self::XPATH);
    }

    /**
     * Set ID
     *
     * @param int $id
     * @return \Magento\VersionsCms\Api\Data\HierarchyNodeInterface
     */
    public function setId($id)
    {
        return $this->setData(self::NODE_ID, $id);
    }

    /**
     * Set parent ID
     *
     * @param int $parentId
     * @return \Magento\VersionsCms\Api\Data\HierarchyNodeInterface
     */
    public function setParentId($parentId)
    {
        return $this->setData(self::PARENT_NODE_ID, $parentId);
    }

    /**
     * Set page ID
     *
     * @param int $pageId
     * @return \Magento\VersionsCms\Api\Data\HierarchyNodeInterface
     */
    public function setPageId($pageId)
    {
        return $this->setData(self::PAGE_ID, $pageId);
    }

    /**
     * Set identifier
     *
     * @param string $identifier
     * @return \Magento\VersionsCms\Api\Data\HierarchyNodeInterface
     */
    public function setIdentifier($identifier)
    {
        return $this->setData(self::IDENTIFIER, $identifier);
    }

    /**
     * Set label
     *
     * @param string $label
     * @return \Magento\VersionsCms\Api\Data\HierarchyNodeInterface
     */
    public function setLabel($label)
    {
        return $this->setData(self::LABEL, $label);
    }

    /**
     * Set level
     *
     * @param int $level
     * @return \Magento\VersionsCms\Api\Data\HierarchyNodeInterface
     */
    public function setLevel($level)
    {
        return $this->setData(self::LEVEL, $level);
    }

    /**
     * Set sort order
     *
     * @param string $sortOrder
     * @return \Magento\VersionsCms\Api\Data\HierarchyNodeInterface
     */
    public function setSortOrder($sortOrder)
    {
        return $this->setData(self::SORT_ORDER, $sortOrder);
    }

    /**
     * Set request url
     *
     * @param string $requestUrl
     * @return \Magento\VersionsCms\Api\Data\HierarchyNodeInterface
     */
    public function setRequestUrl($requestUrl)
    {
        return $this->setData(self::REQUEST_URL, $requestUrl);
    }

    /**
     * Set xpath
     *
     * @param string $xpath
     * @return \Magento\VersionsCms\Api\Data\HierarchyNodeInterface
     */
    public function setXpath($xpath)
    {
        return $this->setData(self::XPATH, $xpath);
    }

    /**
     * Validates node identifier if it doesn't matches restricted endpoint names or frontname
     *
     * @param array $nodeData
     * @throws LocalizedException
     */
    private function validateIdentifier(array $nodeData): void
    {
        if (array_key_exists('identifier', $nodeData)) {
            $errors = $this->compositeUrlValidator->validate($nodeData['identifier']);
            if (!empty($errors)) {
                throw new LocalizedException($errors[0]);
            }
        }
    }
}
