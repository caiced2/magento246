<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\VersionsCms\Api\Data\HierarchyNodeInterface;
use Magento\VersionsCms\Api\HierarchyNodeRepositoryInterface;
use Magento\VersionsCms\Model\Hierarchy\Node;
use Magento\VersionsCms\Model\Hierarchy\NodeFactory;

/**
 * Implements CMS Hierarchy Node resolver
 *
 * This resolver replaces usage of Registry object
 * which stored CMS Hierarchy Node object as 'current_cms_hierarchy_node' registry record.
 *
 * The resolver allows to get CMS Hierarchy Node by page_id parameter of request object.
 */
class CurrentNodeResolver implements CurrentNodeResolverInterface
{
    /**
     * @var Hierarchy\NodeFactory
     */
    private $hierarchyNodeFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var HierarchyNodeRepositoryInterface
     */
    private $hierarchyNodeRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * Cache of already created and loaded nodes
     *
     * @var array
     */
    private $nodesPool = [];

    /**
     * @param NodeFactory $hierarchyNodeFactory
     * @param StoreManagerInterface $storeManager
     * @param HierarchyNodeRepositoryInterface $hierarchyNodeRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        NodeFactory $hierarchyNodeFactory,
        StoreManagerInterface $storeManager,
        HierarchyNodeRepositoryInterface $hierarchyNodeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->hierarchyNodeFactory = $hierarchyNodeFactory;
        $this->storeManager = $storeManager;
        $this->hierarchyNodeRepository = $hierarchyNodeRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Get CMS Hierarchy Node instance, requested by page_id
     *
     * 1. Finds CMS Hierarchy Node by search criteria with requested page_id and scope values.
     * 2. Loads CMS Hierarchy Node object by request_url value.
     * 3. Store loaded CMS Hierarchy Node object into internal cache.
     *
     * @param RequestInterface $request
     * @return \Magento\VersionsCms\Api\Data\HierarchyNodeInterface|null
     */
    public function get(RequestInterface $request)
    {
        $nodeIdentifier = $pageId = $request->getParam('page_id', false);
        if (method_exists($request, 'getPathInfo')) {
            $nodeIdentifier = $requestUrl = trim($request->getPathInfo(), '/');
        }
        if (!isset($this->nodesPool[$nodeIdentifier])) {
            /*
             * Define actual node scope, scope_id values
             */
            /* @var $node Node */
            $node = $this->hierarchyNodeFactory->create(
                [
                    'data' => [
                        'scope' => Node::NODE_SCOPE_STORE,
                        'scope_id' => $this->storeManager->getStore()->getId(),
                    ],
                ]
            )->getHeritage();

            /*
             * Retrieve node data by page_id value
             */
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(HierarchyNodeInterface::PAGE_ID, $pageId)
                ->addFilter(HierarchyNodeInterface::SCOPE, $node->getScope())
                ->addFilter(HierarchyNodeInterface::SCOPE_ID, $node->getScopeId())
                ->create();

            $nodes = $this->hierarchyNodeRepository->getList($searchCriteria)->getItems();

            /*
             * Retrieve node object by request_url value
             */
            $itemId = isset($requestUrl) ? (int)array_search($requestUrl, array_column($nodes, 'request_url')) : 0;
            if (isset($nodes[$itemId]['request_url'])) {
                $node->loadByRequestUrl($nodes[$itemId]['request_url']);
                $this->nodesPool[$nodeIdentifier] = $node->getId() ? $node : null;
            } else {
                $this->nodesPool[$nodeIdentifier] = null;
            }
        }

        return $this->nodesPool[$nodeIdentifier];
    }
}
