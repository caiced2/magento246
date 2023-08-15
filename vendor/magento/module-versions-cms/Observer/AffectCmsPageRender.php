<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VersionsCms\Observer;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class AffectCmsPageRender implements ObserverInterface
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
     * @var \Magento\Framework\App\ViewInterface
     */
    protected $view;

    /**
     * @var \Magento\VersionsCms\Model\CurrentNodeResolverInterface
     */
    private $currentNodeResolver;

    /**
     * @param \Magento\VersionsCms\Helper\Hierarchy $cmsHierarchy
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\App\ViewInterface $view
     * @param \Magento\VersionsCms\Model\CurrentNodeResolverInterface $currentNodeResolver
     */
    public function __construct(
        \Magento\VersionsCms\Helper\Hierarchy $cmsHierarchy,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\ViewInterface $view,
        \Magento\VersionsCms\Model\CurrentNodeResolverInterface $currentNodeResolver = null
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->cmsHierarchy = $cmsHierarchy;
        $this->view = $view;
        $this->currentNodeResolver = $currentNodeResolver ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\VersionsCms\Model\CurrentNodeResolverInterface::class);
    }

    /**
     * Add Hierarchy Menu layout handle to Cms page rendering
     *
     * This method requires RequestInterface object in EventObserver object properties
     * in order to resolve current CMS Hierarchy Node by request.
     *
     * @param EventObserver $observer
     *   EventObserver object parameters:
     *     request - \Magento\Framework\App\RequestInterface object
     *     page - \Magento\Cms\Model\Page object
     *
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(EventObserver $observer)
    {
        /** @var RequestInterface $request */
        $request = $observer->getRequest();

        /* @var $node \Magento\VersionsCms\Model\Hierarchy\Node */
        $node = $this->currentNodeResolver->get($request);

        if (!is_object($node)
            || !$this->cmsHierarchy->isEnabled()) {
            return $this;
        }

        // collect loaded handles for cms page
        $loadedHandles = $this->view->getLayout()->getUpdate()->getHandles();

        $page = $observer->getPage();
        if ($page instanceof \Magento\Cms\Model\Page) {
            $loadedHandles[] = $page->getPageLayout();
        }

        $menuLayout = $node->getMenuLayout();
        if ($menuLayout === null) {
            return $this;
        }

        // check whether menu handle is compatible with page handles
        $allowedHandles = $menuLayout['pageLayoutHandles'];
        if (is_array($allowedHandles) && count($allowedHandles) > 0) {
            if (count(array_intersect($allowedHandles, $loadedHandles)) == 0) {
                return $this;
            }
        }

        // add menu handle to layout update
        $this->view->getLayout()->getUpdate()->addHandle($menuLayout['handle']);

        return $this;
    }
}
