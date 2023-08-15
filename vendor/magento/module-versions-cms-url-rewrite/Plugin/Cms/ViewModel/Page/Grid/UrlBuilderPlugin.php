<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VersionsCmsUrlRewrite\Plugin\Cms\ViewModel\Page\Grid;

use Magento\Store\Model\StoreManagerInterface;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use Magento\VersionsCms\Helper\Hierarchy;
use Magento\VersionsCms\Model\Hierarchy\Node;
use Magento\VersionsCms\Model\Hierarchy\NodeFactory;
use Magento\Cms\ViewModel\Page\Grid\UrlBuilder;

/**
 * Plugin to get requestUrl from hierarchy node in case it cannot be found in url rewrites
 */
class UrlBuilderPlugin
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var UrlFinderInterface
     */
    private $urlFinder;

    /**
     * @var Hierarchy
     */
    private $cmsHierarchy;

    /**
     * @var NodeFactory
     */
    private $hierarchyNodeFactory;

    /**
     * @param StoreManagerInterface $storeManager
     * @param Hierarchy $cmsHierarchy
     * @param UrlFinderInterface $urlFinder
     * @param NodeFactory $hierarchyNodeFactory
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Hierarchy $cmsHierarchy,
        UrlFinderInterface $urlFinder,
        NodeFactory $hierarchyNodeFactory
    ) {
        $this->storeManager = $storeManager;
        $this->cmsHierarchy = $cmsHierarchy;
        $this->urlFinder = $urlFinder;
        $this->hierarchyNodeFactory = $hierarchyNodeFactory;
    }

    /**
     * Change requestUrl parameter from hierarchy node in case it cannot be found in url rewrites
     *
     * @param UrlBuilder $subject
     * @param string $requestUrl
     * @param string $scope
     * @param string $store
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeGetUrl(UrlBuilder $subject, $requestUrl, $scope, $store)
    {
        if (!$this->cmsHierarchy->isEnabled() || !$scope || $store === null) {
            return [$requestUrl, $scope, $store];
        }
        $storeId = $this->storeManager->getStore($store)->getId();
        $urlRewrite = $this->getRewrite($requestUrl, $storeId);
        if (!$urlRewrite) {
            $node = $this->getNode($storeId);
            $node->loadByRequestUrl($requestUrl);
            if ($node->checkIdentifier($requestUrl, $storeId) && !$node->getId()) {
                $collection = $node->getNodesCollection();
                foreach ($collection as $item) {
                    if ($item->getPageIdentifier() == $requestUrl) {
                        $requestUrl = $item->getRequestUrl();
                        break;
                    }
                }
            }
        }
        return [$requestUrl, $scope, $store];
    }

    /**
     * Get rewrite based on request data
     *
     * @param string $requestPath
     * @param int $storeId
     * @return UrlRewrite|null
     */
    private function getRewrite($requestPath, $storeId): ?UrlRewrite
    {
        return $this->urlFinder->findOneByData(
            [
                UrlRewrite::REQUEST_PATH => ltrim($requestPath, '/'),
                UrlRewrite::STORE_ID => $storeId,
            ]
        );
    }

    /**
     * Return node based on store
     *
     * @param int $storeId
     * @return Node|null
     */
    private function getNode($storeId): ?Node
    {
        $node = $this->hierarchyNodeFactory->create(
            [
                'data' => [
                    'scope' => Node::NODE_SCOPE_STORE,
                    'scope_id' => $storeId,
                ],
            ]
        )->getHeritage();
        return $node;
    }
}
