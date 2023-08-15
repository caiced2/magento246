<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Observer\Backend;

use Exception;
use Magento\Cms\Model\Page;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\ScopeResolver;
use Magento\Store\Model\Store;
use Magento\VersionsCms\Helper\Hierarchy;
use Magento\VersionsCms\Model\Hierarchy\Node as HierarchyNode;
use Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node;
use Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\Collection;
use Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\CollectionFactory;
use Magento\VersionsCms\Model\Hierarchy\NodeFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Create and delete nodes after cms page save
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CmsPageSaveAfterObserver implements ObserverInterface
{
    /**
     * @var Hierarchy
     */
    protected $cmsHierarchy;

    /**
     * @var HierarchyNode
     */
    protected $hierarchyNode;

    /**
     * @var Node
     */
    protected $hierarchyNodeResource;

    /**
     * @var CollectionFactory
     */
    private $nodeCollectionFactory;

    /**
     * @var ScopeResolver
     */
    private $scopeResolver;

    /**
     * @var NodeFactory
     */
    private $nodeFactory;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @param Hierarchy $cmsHierarchy
     * @param HierarchyNode $hierarchyNode
     * @param Node $hierarchyNodeResource
     * @param CollectionFactory $nodeCollectionFactory
     * @param ScopeResolver $scopeResolver
     * @param NodeFactory $nodeFactory
     * @param Json $jsonSerializer
     */
    public function __construct(
        Hierarchy $cmsHierarchy,
        HierarchyNode $hierarchyNode,
        Node $hierarchyNodeResource,
        CollectionFactory $nodeCollectionFactory,
        ScopeResolver $scopeResolver,
        NodeFactory $nodeFactory,
        Json $jsonSerializer
    ) {
        $this->cmsHierarchy = $cmsHierarchy;
        $this->hierarchyNode = $hierarchyNode;
        $this->hierarchyNodeResource = $hierarchyNodeResource;
        $this->nodeCollectionFactory = $nodeCollectionFactory;
        $this->scopeResolver = $scopeResolver;
        $this->nodeFactory = $nodeFactory;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * Process extra data after cms page saved
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        /** @var Page $page */
        $page = $observer->getEvent()->getObject();

        if (!$this->cmsHierarchy->isEnabled()) {
            return $this;
        }

        // Rebuild URL rewrites if page has changed for identifier
        if ($page->dataHasChangedFor('identifier')) {
            $this->hierarchyNode->updateRewriteUrls($page);
        }

        $this->appendPageToNodes($page);

        /**
         * Update sort order for nodes in parent nodes which have current page as child
         */
        foreach ($page->getNodesSortOrder() as $nodeId => $value) {
            $this->hierarchyNodeResource->updateSortOrder($nodeId, $value);
        }

        return $this;
    }

    /**
     * Append page to selected nodes. Removing page nodes with wrong scope after changing store in "Page in Websites"
     *
     * @param Page $page
     * @return $this
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function appendPageToNodes(Page $page)
    {
        $nodes = $page->getAppendToNodes();
        $parentNodes = $this->getParentNodes($nodes, $page);
        $pageData = ['page_id' => $page->getId(), 'identifier' => null, 'label' => null];
        $removeFromNodes = [];
        $scopeIds = [];
        foreach ($parentNodes as $parentNode) {
            /* @var $parentNode HierarchyNode */
            if (!isset($nodes[$parentNode->getId()])) {
                //Delete node after uncheck checkbox
                $removeFromNodes[] = $parentNode->getId();
                $scopeIds[] = $parentNode->getScopeId();
                continue;
            }
            $nodeScopeId = (int)$parentNode->getScopeId();

            if (!$this->isBelongsToNodeScope($parentNode->getScope(), $nodeScopeId, (array)$page->getStoreId())) {
                //If parent node scope_id assigned to store which not in "Page In Websites" - delete node
                $scopeIds[] = $nodeScopeId;
                $removeFromNodes[] = $parentNode->getId();
                continue;
            }

            $requestUrl = $parentNode->getRequestUrl() . '/' . $page->getIdentifier();
            if ($this->isNodeExist($requestUrl, $nodeScopeId, (int)$parentNode->getId(), (int)$page->getId())) {
                throw new LocalizedException(
                    __(
                        'This page cannot be assigned to node, because a node or page with'
                        . ' the same URL Key already exists in this tree part.'
                    )
                );
            }
            if (!$this->isNodeExist($requestUrl, $nodeScopeId, (int)$parentNode->getId())) {
                $sortOrder = $nodes[$parentNode->getId()];
                $isPageNodeExist = $page->getId()
                    && $this->isPageNodeExist($nodeScopeId, (int)$parentNode->getId(), $page->getId());
                if (!$isPageNodeExist) {
                    $this->createNewNode($pageData, $sortOrder, $page->getIdentifier(), $parentNode);
                }
            }
        }
        if ($page->getData('assign_to_root') == 'true') {
            $pageNodesData = $this->jsonSerializer->unserialize($page->getNodesData());
            $pageStores = array_values($page->getStores());
            $sortOrder = 0;
            if (isset($pageNodesData['_0']['sort_order'])) {
                $sortOrder = $pageNodesData['_0']['sort_order'];
            }
            if (count($pageStores) === 1 && $pageStores[0] == Store::DEFAULT_STORE_ID) {
                $pageData[HierarchyNode::SCOPE] = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
                $pageData[HierarchyNode::SCOPE_ID] = Store::DEFAULT_STORE_ID;
                $this->createNewNode($pageData, $sortOrder, $page->getIdentifier(), null);
            } else {
                foreach ($pageStores as $storeId) {
                    $pageData[HierarchyNode::SCOPE] = ScopeInterface::SCOPE_STORE;
                    $pageData[HierarchyNode::SCOPE_ID] = $storeId;
                    $this->createNewNode($pageData, $sortOrder, $page->getIdentifier(), null);
                }
            }
        } elseif ($page->getData('assign_to_root') == 'false') {
            $pageStores = array_values($page->getStores());
            $nodeCollection = $this->nodeCollectionFactory->create();
            $nodeCollection->addFieldToFilter('page_id', $page->getId());
            $this->hierarchyNodeResource->removePageFromNodes($page->getId(), [], $pageStores);
        }
        if (!empty($removeFromNodes) && $nodes !== null && !empty($scopeIds)) {
            $this->hierarchyNodeResource->removePageFromNodes($page->getId(), $removeFromNodes, $scopeIds);
        }

        return $this;
    }

    /**
     * Check if node scope is "All store view" or it is same as page scope
     *
     * @param string $nodeScope
     * @param int $nodeScopeId
     * @param array $pageStoreIds
     * @return bool
     */
    private function isBelongsToNodeScope(string $nodeScope, int $nodeScopeId, array $pageStoreIds): bool
    {
        if (empty($pageStoreIds)) {
            return false;
        }

        foreach ($pageStoreIds as $storeId) {
            if ((int)$storeId === Store::DEFAULT_STORE_ID) {
                return true;
            }
            $isScopeValid = $this->scopeResolver->isBelongsToScope(
                $nodeScope,
                $nodeScopeId,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
            if ($isScopeValid) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create new page node
     *
     * @param array $pageData
     * @param int $sortOrder
     * @param string $pageIdentifier
     * @param HierarchyNode|null $parentNode
     * @return mixed
     * @throws Exception
     */
    private function createNewNode(
        array $pageData,
        int $sortOrder,
        string $pageIdentifier,
        HierarchyNode $parentNode = null
    ) {
        if ($parentNode !== null) {
            $newNode = clone $parentNode;
            $requestUrl = $newNode->getRequestUrl() . '/' . $pageIdentifier;
            $xPath = $newNode->getXpath() . '/';
        } else {
            $newNode = $this->nodeFactory->create();
            $requestUrl = $pageIdentifier;
            $xPath = $newNode->getXpath();
        }

        $newNode->addData(
            $pageData
        )->setParentNodeId(
            $newNode->getId()
        )->unsetData(
            $this->hierarchyNode->getIdFieldName()
        )->setLevel(
            $newNode->getLevel() + 1
        )->setSortOrder(
            $sortOrder
        )->setRequestUrl(
            $requestUrl
        )->setXpath(
            $xPath
        );
        $newNode->save();

        return $newNode;
    }

    /**
     * Return parent nodes collection
     *
     * @param array|null $nodes
     * @param Page $page
     * @return Collection
     */
    private function getParentNodes(?array $nodes, Page $page)
    {
        $nodesToFilter = ($nodes === null) ? [] : array_keys($nodes);
        $nodeCollection = $this->nodeCollectionFactory->create();
        $parentNodes = $nodeCollection->joinPageExistsNodeInfo(
            $page
        )->applyPageExistsOrNodeIdFilter(
            $nodesToFilter,
            $page
        );

        return $parentNodes;
    }

    /**
     * Check if current page node is exist
     *
     * @param string $requestUrl
     * @param int $scopeId
     * @param int $parentNodeId
     * @param int|null $currentPageId
     * @return bool
     */
    private function isNodeExist(string $requestUrl, int $scopeId, int $parentNodeId, ?int $currentPageId = null): bool
    {
        $nodeCollection = $this->nodeCollectionFactory->create();
        $nodeCollection->addFieldToFilter('request_url', $requestUrl)
            ->addFieldToFilter('scope_id', $scopeId)
            ->addFieldToFilter('parent_node_id', $parentNodeId);

        if ($currentPageId !== null) {
            $nodeCollection->addFieldToFilter('main_table.page_id', ['neq' => $currentPageId]);
        }
        return $nodeCollection->getSize() ? true : false;
    }

    /**
     * Check if page node under the parent node is exist
     *
     * @param int $scopeId
     * @param int $parentNodeId
     * @param int $currentPageId
     * @return bool
     */
    private function isPageNodeExist(int $scopeId, int $parentNodeId, int $currentPageId): bool
    {
        $nodeCollection = $this->nodeCollectionFactory->create();
        $nodeCollection->addFieldToFilter('scope_id', $scopeId)
            ->addFieldToFilter('parent_node_id', $parentNodeId)
            ->addFieldToFilter('main_table.page_id', $currentPageId);

        return (bool) $nodeCollection->getSize();
    }
}
