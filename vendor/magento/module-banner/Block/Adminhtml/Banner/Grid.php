<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Block\Adminhtml\Banner;

/**
 * Adminhtml banner grid.
 *
 * @api
 * @since 100.0.2
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * Banner resource collection factory
     *
     * @var \Magento\Banner\Model\ResourceModel\Banner\CollectionFactory
     */
    protected $_bannerColFactory = null;

    /**
     * Banner config
     *
     * @var \Magento\Banner\Model\Config
     */
    protected $_bannerConfig = null;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Banner\Model\ResourceModel\Banner\CollectionFactory $bannerColFactory
     * @param \Magento\Banner\Model\Config $bannerConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Banner\Model\ResourceModel\Banner\CollectionFactory $bannerColFactory,
        \Magento\Banner\Model\Config $bannerConfig,
        array $data = []
    ) {
        parent::__construct($context, $backendHelper, $data);
        $this->_bannerColFactory = $bannerColFactory;
        $this->_bannerConfig = $bannerConfig;
    }

    /**
     * Set defaults
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('bannerGrid');
        $this->setDefaultSort('banner_id');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('banner_filter');
    }

    /**
     * Instantiate and prepare collection
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = $this->_bannerColFactory->create()->addStoresVisibility();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Define grid columns
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'banner_id',
            [
                'header' => __('ID'),
                'type' => 'number',
                'index' => 'banner_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );

        $this->addColumn(
            'banner_name',
            ['header' => __('Dynamic Block'), 'type' => 'text', 'index' => 'name', 'escape' => true]
        );

        $this->addColumn(
            'banner_types',
            [
                'header' => __('Dynamic Block Type'),
                'type' => 'options',
                'options' => $this->_bannerConfig->toOptionArray(true, false),
                'index' => 'types',
                'filter' => false // TODO implement
            ]
        );

        $this->addColumn(
            'banner_is_enabled',
            [
                'header' => __('Status'),
                'align' => 'center',
                'width' => 1,
                'index' => 'is_enabled',
                'type' => 'options',
                'options' => [
                    \Magento\Banner\Model\Banner::STATUS_ENABLED => __('Active'),
                    \Magento\Banner\Model\Banner::STATUS_DISABLED => __('Inactive'),
                ]
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * Prepare mass action options for this grid
     *
     * @return $this
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('banner_id');
        $this->getMassactionBlock()->setFormFieldName('banner');

        $this->getMassactionBlock()->addItem(
            'delete',
            [
                'label' => __('Delete'),
                'url' => $this->getUrl('adminhtml/*/massDelete'),
                'confirm' => __('Are you sure you want to delete these dynamic blocks?')
            ]
        );

        return $this;
    }

    /**
     * Grid row URL getter
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('adminhtml/*/edit', ['id' => $row->getBannerId()]);
    }

    /**
     * Define row click callback
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('adminhtml/*/grid', ['_current' => true]);
    }

    /**
     * Add store filter
     *
     * @param \Magento\Backend\Block\Widget\Grid\Column  $column
     * @return $this
     */
    protected function _addColumnFilterToCollection($column)
    {
        if ($column->getIndex() == 'stores') {
            $this->getCollection()->addStoreFilter($column->getFilter()->getCondition(), false);
        } else {
            parent::_addColumnFilterToCollection($column);
        }
        return $this;
    }
}
