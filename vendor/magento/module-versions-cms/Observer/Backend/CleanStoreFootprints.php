<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Observer\Backend;

class CleanStoreFootprints
{
    /**
     * @var \Magento\VersionsCms\Model\Hierarchy\NodeFactory
     */
    protected $hierarchyNodeFactory;

    /**
     * @var \Magento\Widget\Model\ResourceModel\Widget\Instance\CollectionFactory
     */
    protected $widgetCollectionFactory;

    /**
     * @param \Magento\VersionsCms\Model\Hierarchy\NodeFactory $hierarchyNodeFactory
     * @param \Magento\Widget\Model\ResourceModel\Widget\Instance\CollectionFactory $widgetCollectionFactory
     */
    public function __construct(
        \Magento\VersionsCms\Model\Hierarchy\NodeFactory $hierarchyNodeFactory,
        \Magento\Widget\Model\ResourceModel\Widget\Instance\CollectionFactory $widgetCollectionFactory
    ) {
        $this->hierarchyNodeFactory = $hierarchyNodeFactory;
        $this->widgetCollectionFactory = $widgetCollectionFactory;
    }

    /**
     * Clean up information about deleted store from the widgets and hierarchy nodes
     *
     * @param int $storeId
     * @return void
     */
    public function clean($storeId)
    {
        $storeScope = \Magento\VersionsCms\Model\Hierarchy\Node::NODE_SCOPE_STORE;
        $this->hierarchyNodeFactory->create()->deleteByScope($storeScope, $storeId);

        /** @var \Magento\Widget\Model\ResourceModel\Widget\Instance\Collection $widgets */
        $widgets = $this->widgetCollectionFactory->create()
            ->addStoreFilter([$storeId, false])
            ->addFieldToFilter(
                'instance_type',
                \Magento\VersionsCms\Block\Widget\Node::class
            );

        /** @var \Magento\Widget\Model\Widget\Instance $widgetInstance */
        foreach ($widgets as $widgetInstance) {
            $storeIds = $widgetInstance->getStoreIds();
            foreach ($storeIds as $key => $value) {
                if ($value == $storeId) {
                    unset($storeIds[$key]);
                }
            }
            $widgetInstance->setStoreIds($storeIds);

            $widgetParams = $widgetInstance->getWidgetParameters();
            unset($widgetParams['anchor_text_' . $storeId]);
            unset($widgetParams['title_' . $storeId]);
            unset($widgetParams['node_id_' . $storeId]);
            unset($widgetParams['template_' . $storeId]);
            $widgetInstance->setWidgetParameters($widgetParams);

            $widgetInstance->save();
        }
    }
}
