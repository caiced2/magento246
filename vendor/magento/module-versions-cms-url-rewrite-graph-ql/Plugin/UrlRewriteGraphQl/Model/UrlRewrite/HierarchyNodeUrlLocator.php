<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VersionsCmsUrlRewriteGraphQl\Plugin\UrlRewriteGraphQl\Model\UrlRewrite;

use Magento\GraphQl\Model\Query\ContextFactoryInterface;
use Magento\UrlRewriteGraphQl\Model\Resolver\UrlRewrite\CustomUrlLocator;
use Magento\VersionsCms\Helper\Hierarchy as CmsHierarchy;
use Magento\VersionsCms\Model\Hierarchy\Node as HierarchyNode;
use Magento\VersionsCms\Model\Hierarchy\NodeFactory as HierarchyNodeFactory;

/**
 * The plugin locates Url for hierarchy nodes
 */
class HierarchyNodeUrlLocator
{
    /**
     * @var CmsHierarchy
     */
    private $cmsHierarchy;

    /**
     * @var HierarchyNodeFactory
     */
    private $hierarchyNodeFactory;

    /**
     * @var ContextFactoryInterface
     */
    private $contextFactory;

    /**
     * HierarchyNodeUrlLocator constructor.
     *
     * @param CmsHierarchy $cmsHierarchy
     * @param HierarchyNodeFactory $hierarchyNodeFactory
     * @param ContextFactoryInterface $contextFactory
     */
    public function __construct(
        CmsHierarchy $cmsHierarchy,
        HierarchyNodeFactory $hierarchyNodeFactory,
        ContextFactoryInterface $contextFactory
    ) {
        $this->cmsHierarchy = $cmsHierarchy;
        $this->hierarchyNodeFactory = $hierarchyNodeFactory;
        $this->contextFactory = $contextFactory;
    }

    /**
     * Resolve URL based on hierarchy node features.
     *
     * @param CustomUrlLocator $subject
     * @param null|string $result
     * @param string $urlKey
     * @return null|string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterLocateUrl(
        CustomUrlLocator $subject,
        ?string $result,
        string $urlKey
    ): ?string {
        if ($result !== null || !$this->cmsHierarchy->isEnabled()) {
            return $result;
        }
        $context = $this->contextFactory->create();
        /** @var \Magento\Store\Api\Data\StoreInterface $store */
        $store = $context->getExtensionAttributes()->getStore();
        $node = $this->hierarchyNodeFactory->create(
            [
                'data' => [
                    'scope' => HierarchyNode::NODE_SCOPE_STORE,
                    'scope_id' => $store->getId(),
                ],
            ]
        )->getHeritage();
        $node->loadByRequestUrl($urlKey);

        if ($node->getId()) {
            $result = $node->getIdentifier();
        }

        return $result;
    }
}
