<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Logging\Block\Adminhtml\Details;

/**
 * Admin Actions Log Archive grid
 *
 * @api
 * @since 100.0.2
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Logging\Model\ResourceModel\Event\Changes\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Logging\Model\ResourceModel\Event\Changes\CollectionFactory $collectionFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Logging\Model\ResourceModel\Event\Changes\CollectionFactory $collectionFactory,
        \Magento\Framework\Registry $coreRegistry,
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Initialize default sorting and html ID
     *
     * @return void
     */
    protected function _construct()
    {
        $this->setId('loggingDetailsGrid');
        $this->setPagerVisibility(false);
        $this->setFilterVisibility(false);
    }

    /**
     * Prepare grid collection
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        $event = $this->_coreRegistry->registry('current_event');
        $collection = $this->collectionFactory->create()->addFieldToFilter('event_id', $event->getId());
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Prepare grid columns
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'source_name',
            [
                'header' => __('Source Data'),
                'sortable' => false,
                'renderer' => \Magento\Logging\Block\Adminhtml\Details\Renderer\Sourcename::class,
                'index' => 'source_name',
                'width' => 1
            ]
        );

        $this->addColumn(
            'original_data',
            [
                'header' => __('Value Before Change'),
                'sortable' => false,
                'renderer' => \Magento\Logging\Block\Adminhtml\Details\Renderer\Diff::class,
                'index' => 'original_data'
            ]
        );

        $this->addColumn(
            'result_data',
            [
                'header' => __('Value After Change'),
                'sortable' => false,
                'renderer' => \Magento\Logging\Block\Adminhtml\Details\Renderer\Diff::class,
                'index' => 'result_data'
            ]
        );

        return parent::_prepareColumns();
    }
}
