<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Block\Adminhtml\Customersegment;

/**
 * Customer Segments Grid
 *
 * @author Magento Core Team <core@magentocommerce.com>
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Magento\CustomerSegment\Model\SegmentFactory
     */
    protected $_segmentFactory;

    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param \Magento\CustomerSegment\Model\SegmentFactory $segmentFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Store\Model\System\Store $systemStore,
        \Magento\CustomerSegment\Model\SegmentFactory $segmentFactory,
        array $data = []
    ) {
        $this->_systemStore = $systemStore;
        $this->_segmentFactory = $segmentFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Initialize grid
     * Set sort settings
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('customersegmentGrid');
        $this->setDefaultSort('name');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    /**
     * Add websites to customer segments collection
     * Set collection
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        /** @var $collection \Magento\CustomerSegment\Model\ResourceModel\Segment\Collection */
        $collection = $this->_segmentFactory->create()->getCollection();
        $collection->addWebsitesToResult();
        $this->setCollection($collection);

        parent::_prepareCollection();
        return $this;
    }

    /**
     * Add grid columns
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        // this column is mandatory for the chooser mode. It needs to be first
        $this->addColumn(
            'grid_segment_id',
            [
                'header' => __('ID'),
                'index' => 'segment_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );

        $this->addColumn('grid_segment_name', ['header' => __('Segment'), 'index' => 'name']);

        $this->addColumn(
            'grid_segment_is_active',
            [
                'header' => __('Status'),
                'index' => 'is_active',
                'type' => 'options',
                'options' => [1 => 'Active', 0 => 'Inactive']
            ]
        );

        if (!$this->_storeManager->isSingleStoreMode()) {
            $this->addColumn(
                'grid_segment_website',
                [
                    'header' => __('Website'),
                    'index' => 'website_ids',
                    'type' => 'options',
                    'sortable' => false,
                    'options' => $this->_systemStore->getWebsiteOptionHash()
                ]
            );
        }

        parent::_prepareColumns();
        return $this;
    }

    /**
     * Retrieve row click URL
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function getRowUrl($row)
    {
        if ($this->getIsChooserMode()) {
            return null;
        }
        return $this->getUrl('*/*/edit', ['id' => $row->getSegmentId()]);
    }

    /**
     * Row click javascript callback getter
     *
     * @return string
     */
    public function getRowClickCallback()
    {
        if ($this->getIsChooserMode() && ($elementId = $this->getRequest()->getParam('value_element_id'))) {
            return 'function (grid, event) {
                var trElement = Event.findElement(event, "tr");
                if (trElement) {
                    $(\'' .
                $elementId .
                '\').value = trElement.down("td").innerHTML;
                    $(grid.containerId).up().hide();
                }}';
        }
        return 'openGridRow';
    }

    /**
     * Grid URL getter for ajax mode
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('customersegment/index/grid', ['_current' => true]);
    }
}
