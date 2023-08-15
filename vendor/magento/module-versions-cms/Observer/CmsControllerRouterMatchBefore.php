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

class CmsControllerRouterMatchBefore implements ObserverInterface
{
    /**
     * Cms hierarchy
     *
     * @var \Magento\VersionsCms\Helper\Hierarchy
     */
    protected $cmsHierarchy;

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
     * @var \Magento\Framework\UrlInterface
     */
    protected $coreUrl;

    /**
     * @param \Magento\VersionsCms\Helper\Hierarchy $cmsHierarchy
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\VersionsCms\Model\Hierarchy\NodeFactory $hierarchyNodeFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\UrlInterface $coreUrl
     */
    public function __construct(
        \Magento\VersionsCms\Helper\Hierarchy $cmsHierarchy,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\VersionsCms\Model\Hierarchy\NodeFactory $hierarchyNodeFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\UrlInterface $coreUrl
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->cmsHierarchy = $cmsHierarchy;
        $this->hierarchyNodeFactory = $hierarchyNodeFactory;
        $this->storeManager = $storeManager;
        $this->coreUrl = $coreUrl;
    }

    /**
     * Validate and render Cms hierarchy page
     *
     * @param EventObserver $observer
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @deprecated 100.1.3 because CMS Router will be replaced by upcoming unified routing system.
     */
    public function execute(EventObserver $observer)
    {
        if (!$this->cmsHierarchy->isEnabled()) {
            return $this;
        }

        $condition = $observer->getEvent()->getCondition();

        /**
         * Validate Request and modify router match condition
         */
        /* @var $node \Magento\VersionsCms\Model\Hierarchy\Node */
        $node = $this->hierarchyNodeFactory->create(
            [
                'data' => [
                    'scope' => \Magento\VersionsCms\Model\Hierarchy\Node::NODE_SCOPE_STORE,
                    'scope_id' => $this->storeManager->getStore()->getId(),
                ],
            ]
        )->getHeritage();
        $requestUrl = $condition->getIdentifier();
        $node->loadByRequestUrl($requestUrl);

        if ($node->checkIdentifier($requestUrl, $this->storeManager->getStore())) {
            $condition->setContinue(false);
            if (!$node->getId()) {
                $collection = $node->getNodesCollection();
                foreach ($collection as $item) {
                    if ($item->getPageIdentifier() == $requestUrl) {
                        $url = $this->coreUrl->getUrl('', ['_direct' => $item->getRequestUrl()]);
                        $condition->setRedirectUrl($url);
                        break;
                    }
                }
            }
        }
        if (!$node->getId()) {
            return $this;
        }

        if (!$node->getPageId()) {
            /* @var $child \Magento\VersionsCms\Model\Hierarchy\Node */
            $child = $this->hierarchyNodeFactory->create(
                ['data' => ['scope' => $node->getScope(), 'scope_id' => $node->getScopeId()]]
            );
            $child->loadFirstChildByParent($node->getId());
            if (!$child->getId()) {
                return $this;
            }
            $url = $this->coreUrl->getUrl('', ['_direct' => $child->getRequestUrl()]);
            $condition->setRedirectUrl($url);
        } else {
            if (!$node->getPageIsActive()) {
                return $this;
            }

            $condition->setContinue(true);
            $condition->setIdentifier($node->getPageIdentifier());
        }

        return $this;
    }
}
