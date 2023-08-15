<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Controller\Adminhtml\Cms\Page\InlineEdit;

use Magento\VersionsCms\Model\Hierarchy\Node;

/**
 * Plugin for cms page grid inline edit that adds information about hierarchy nodes
 */
class Plugin
{
    /**
     * @var \Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\CollectionFactory
     */
    protected $nodeCollectionFactory;

    /**
     * @var \Magento\Cms\Model\ResourceModel\Page\CollectionFactory
     */
    protected $cmsPageCollectionFactory;

    /**
     * @param \Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\CollectionFactory $nodeCollectionFactory
     * @param \Magento\Cms\Model\ResourceModel\Page\CollectionFactory $cmsPageCollectionFactory
     */
    public function __construct(
        \Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\CollectionFactory $nodeCollectionFactory,
        \Magento\Cms\Model\ResourceModel\Page\CollectionFactory $cmsPageCollectionFactory
    ) {
        $this->nodeCollectionFactory = $nodeCollectionFactory;
        $this->cmsPageCollectionFactory = $cmsPageCollectionFactory;
    }

    /**
     * Add nodes data to cms page data
     *
     * @param \Magento\Cms\Controller\Adminhtml\Page\InlineEdit $subject
     * @param \Magento\Cms\Model\Page $page
     * @param array $extendedPageData
     * @param array $pageData
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSetCmsPageData(
        \Magento\Cms\Controller\Adminhtml\Page\InlineEdit $subject,
        \Magento\Cms\Model\Page $page,
        array $extendedPageData,
        array $pageData
    ) {
        $nodesData = [];
        $nodeId = null;
        $pageId = $page->getId();
        $cmsPageCollection = $this->getCmsPageCollection();
        $nodeCollection = $this->getNodesCollection()->joinPageExistsNodeInfo($page);
        $nodeCollectionData = $nodeCollection->getData();
        foreach ($nodeCollectionData as $node) {
            if ($node['level'] == Node::NODE_LEVEL_FAKE) {
                continue;
            }
            $nodesData[$node['node_id']] = [
                'node_id' => $node['node_id'],
                'page_id' => $node['page_id'],
                'parent_node_id' => $node['parent_node_id'],
                'label' => $node['label'] ?: $cmsPageCollection->getItemById($node['page_id'])->getData('title'),
                'sort_order' => $node['sort_order'],
                'current_page' => boolval($node['current_page']),
                'page_exists' => boolval($node['page_exists'])
            ];
            if ($pageId == $node['page_id']) {
                $nodeId = $node['parent_node_id'];
            }
        }
        $nodesData['_0'] = [
            'node_id' => '_0',
            'page_id' => $pageId,
            'parent_node_id' => null,
            'label' => $cmsPageCollection->getItemById($pageId)->getData('title'),
            'current_page' => true
        ];

        $result = [];
        $result['nodes_data'] = json_encode($nodesData);
        $result['node_ids'] = $nodeId === null ? '' : $nodeId;
        $page->setData($result);
    }

    /**
     * Get nodes collection model populated with data
     *
     * @return \Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\Collection
     */
    protected function getNodesCollection()
    {
        /** @var \Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\Collection $nodeCollection */
        $nodeCollection = $this->nodeCollectionFactory->create();
        $nodeCollection->load();
        return $nodeCollection;
    }

    /**
     * Get cms page collection model populated with data
     *
     * @return \Magento\Cms\Model\ResourceModel\Page\Collection
     */
    protected function getCmsPageCollection()
    {
        /** @var \Magento\Cms\Model\ResourceModel\Page\Collection $cmsPageCollection */
        $cmsPageCollection = $this->cmsPageCollectionFactory->create();
        $cmsPageCollection->load();
        return $cmsPageCollection;
    }
}
