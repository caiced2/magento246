<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Block\Cms;

use Magento\Store\Model\ScopeInterface;

/**
 * Cms page content block
 *
 * @api
 * @since 100.0.2
 */
class Page extends \Magento\Cms\Block\Page
{
    /**
     *
     * @var \Magento\Framework\Registry Core registry
     *
     * @deprecated 100.1.0 The property can be removed in a future major release,
     * when constructor signature can be changed.
     * @see \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Magento\VersionsCms\Model\Hierarchy\NodeFactory
     */
    protected $_hierarchyNodeFactory;

    /**
     * @var \Magento\VersionsCms\Model\CurrentNodeResolverInterface
     */
    private $currentNodeResolver;

    /**
     * Construct
     *
     * @param \Magento\Framework\View\Element\Context $context
     * @param \Magento\Cms\Model\Page $page
     * @param \Magento\Cms\Model\Template\FilterProvider $filterProvider
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Cms\Model\PageFactory $pageFactory
     * @param \Magento\Framework\View\Page\Config $pageConfig
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\VersionsCms\Model\Hierarchy\NodeFactory $hierarchyNodeFactory
     * @param \Magento\VersionsCms\Model\CurrentNodeResolverInterface $currentNodeResolver
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        \Magento\Cms\Model\Page $page,
        \Magento\Cms\Model\Template\FilterProvider $filterProvider,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Cms\Model\PageFactory $pageFactory,
        \Magento\Framework\View\Page\Config $pageConfig,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\VersionsCms\Model\Hierarchy\NodeFactory $hierarchyNodeFactory,
        \Magento\VersionsCms\Model\CurrentNodeResolverInterface $currentNodeResolver = null
    ) {
        parent::__construct($context, $page, $filterProvider, $storeManager, $pageFactory, $pageConfig);

        $this->_coreRegistry = $coreRegistry;
        $this->_hierarchyNodeFactory = $hierarchyNodeFactory;
        $this->currentNodeResolver = $currentNodeResolver ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\VersionsCms\Model\CurrentNodeResolverInterface::class);
    }

    /**
     * Prepare breadcrumbs
     *
     * @param \Magento\Cms\Model\Page $page
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _addBreadcrumbs(\Magento\Cms\Model\Page $page)
    {
        if (!$this->_scopeConfig->isSetFlag(
            'cms/hierarchy/enabled',
            ScopeInterface::SCOPE_STORE
        )) {
            // phpstan:ignore
            return parent::_addBreadcrumbs($page);
        }

        $breadcrumbs = [];
        if ($this->_scopeConfig->getValue('web/default/show_cms_breadcrumbs', ScopeInterface::SCOPE_STORE)
            && ($breadcrumbsBlock = $this->getLayout()->getBlock('breadcrumbs'))
            && $page->getIdentifier() !== $this->getHomePageIdentifier()
            && $page->getIdentifier() !== $this->getNoRouteIdentifier()
        ) {
            $breadcrumbsBlock->addCrumb(
                'home',
                [
                    'label' => __('Home'),
                    'title' => __('Go to Home Page'),
                    'link' => $this->_storeManager->getStore()->getBaseUrl()
                ]
            );

            if ($currentNode = $this->currentNodeResolver->get($this->getRequest())) {
                $nodePathIds = explode('/', $currentNode->getXpath());
                foreach ($nodePathIds as $nodeId) {
                    if ($currentNode->getId() != $nodeId) {
                        $nodeModel = $this->_hierarchyNodeFactory->create();
                        $node = $nodeModel->load($nodeId);
                        $breadcrumbs[] = [
                            'crumbName' => 'cms_node_' . $node->getId(),
                            'crumbInfo' => [
                                'label' => $node->getLabel(),
                                'link' => $node->getUrl(),
                                'title' => $node->getLabel()]];
                    }
                }
            }

            foreach ($breadcrumbs as $breadcrumbsItem) {
                $breadcrumbsBlock->addCrumb($breadcrumbsItem['crumbName'], $breadcrumbsItem['crumbInfo']);
            }

            $breadcrumbsBlock->addCrumb('cms_page', ['label' => $page->getTitle(), 'title' => $page->getTitle()]);
        }
    }

    /**
     * Get Home Page Identifier from config.
     *
     * @return string
     */
    private function getHomePageIdentifier() : string
    {
        $homePageIdentifier = $this->_scopeConfig->getValue(
            'web/default/cms_home_page',
            ScopeInterface::SCOPE_STORE
        );
        $homePageDelimiterPosition = strrpos($homePageIdentifier, '|');

        if ($homePageDelimiterPosition) {
            $homePageIdentifier = substr($homePageIdentifier, 0, $homePageDelimiterPosition);
        }

        return $homePageIdentifier;
    }

    /**
     * Get No Route Page Identifier from config.
     *
     * @return string
     */
    private function getNoRouteIdentifier() : string
    {
        $noRouteIdentifier = $this->_scopeConfig->getValue(
            'web/default/cms_no_route',
            ScopeInterface::SCOPE_STORE
        );
        $noRouteDelimiterPosition = strrpos($noRouteIdentifier, '|');

        if ($noRouteDelimiterPosition) {
            $noRouteIdentifier = substr($noRouteIdentifier, 0, $noRouteDelimiterPosition);
        }

        return $noRouteIdentifier;
    }
}
