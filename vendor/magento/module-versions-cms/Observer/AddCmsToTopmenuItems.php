<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Versions cms page observer
 */
namespace Magento\VersionsCms\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class AddCmsToTopmenuItems implements ObserverInterface
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     *
     * @deprecated 100.1.0 The property can be removed in a future release, when constructor signature can be changed.
     */
    protected $coreRegistry;

    /**
     * @var \Magento\VersionsCms\Model\Hierarchy\NodeFactory
     */
    protected $hierarchyNodeFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\VersionsCms\Model\CurrentNodeResolverInterface
     */
    private $currentNodeResolver;

    /**
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\VersionsCms\Model\Hierarchy\NodeFactory $hierarchyNodeFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\VersionsCms\Model\CurrentNodeResolverInterface $currentNodeResolver
     */
    public function __construct(
        \Magento\Framework\Registry $coreRegistry,
        \Magento\VersionsCms\Model\Hierarchy\NodeFactory $hierarchyNodeFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\VersionsCms\Model\CurrentNodeResolverInterface $currentNodeResolver = null
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->hierarchyNodeFactory = $hierarchyNodeFactory;
        $this->storeManager = $storeManager;
        $this->currentNodeResolver = $currentNodeResolver ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\VersionsCms\Model\CurrentNodeResolverInterface::class);
    }

    /**
     * Adds CMS hierarchy menu item to top menu
     *
     * This method requires RequestInterface object in EventObserver object properties
     * in order to resolve current CMS Hierarchy Node by request.
     *
     * @param EventObserver $observer
     *   EventObserver object parameters:
     *     request - \Magento\Framework\App\RequestInterface object
     *     menu - \Magento\Framework\Data\Tree\Node root node object
     *
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute(EventObserver $observer)
    {
        /** @var \Magento\Framework\App\RequestInterface $request */
        $request = $observer->getRequest();

        /**
         * @var $topMenuRootNode \Magento\Framework\Data\Tree\Node
         */
        $topMenuRootNode = $observer->getMenu();

        /** @var \Magento\VersionsCms\Model\Hierarchy\Node $hierarchyModel */
        $hierarchyModel = $this->hierarchyNodeFactory->create(
            [
                'data' => [
                    'scope' => \Magento\VersionsCms\Model\Hierarchy\Node::NODE_SCOPE_STORE,
                    'scope_id' => $this->storeManager->getStore()->getId(),
                ],
            ]
        )->getHeritage();

        $nodes = $hierarchyModel->getNodesData();
        $tree = $topMenuRootNode->getTree();

        $nodesFlatList = [$topMenuRootNode->getId() => $topMenuRootNode];

        /** @var \Magento\VersionsCms\Model\Hierarchy\Node $nodeModel */
        $nodeModel = $this->hierarchyNodeFactory->create();

        foreach ($nodes as $node) {
            $nodeData = $nodeModel->load($node['node_id']);

            if (!$nodeData ||
                $nodeData->getParentNodeId() == null && !$nodeData->getTopMenuVisibility() ||
                $nodeData->getParentNodeId() != null && $nodeData->getTopMenuExcluded() ||
                $nodeData->getPageId() && !$nodeData->getPageIsActive()
            ) {
                continue;
            }

            $menuNodeId = 'cms-hierarchy-node-' . $node['node_id'];
            $menuNodeData = [
                'name' => $nodeData->getLabel(),
                'id' => $menuNodeId,
                'url' => $nodeData->getUrl(),
                'is_active' => $this->isCmsNodeActive($nodeData, $request),
            ];

            $parentNodeId = !isset(
                $node['parent_node_id']
            ) ? $topMenuRootNode->getId() : 'cms-hierarchy-node-' . $node['parent_node_id'];
            $parentNode = isset($nodesFlatList[$parentNodeId]) ? $nodesFlatList[$parentNodeId] : null;

            if (!$parentNode) {
                continue;
            }

            $menuNode = new \Magento\Framework\Data\Tree\Node($menuNodeData, 'id', $tree, $parentNode);
            $parentNode->addChild($menuNode);

            $nodesFlatList[$menuNodeId] = $menuNode;
        }
    }

    /**
     * Checks whether node belongs to currently active node's path
     *
     * Method scope changed to private in accordance to Magento Technical Vision.
     * Method was renamed to avoid usage of underscore in accordance to Magento Coding Standard.
     *
     * @param \Magento\VersionsCms\Model\Hierarchy\Node $cmsNode
     * @param \Magento\Framework\App\RequestInterface $request Request object which contains page_id value
     *   required by current node resolver.
     * @return bool
     */
    private function isCmsNodeActive($cmsNode, \Magento\Framework\App\RequestInterface $request)
    {
        $currentNode = $this->currentNodeResolver->get($request);
        if (!$currentNode) {
            return false;
        }

        $nodePathIds = explode('/', $currentNode->getXpath());

        return in_array($cmsNode->getId(), $nodePathIds);
    }
}
