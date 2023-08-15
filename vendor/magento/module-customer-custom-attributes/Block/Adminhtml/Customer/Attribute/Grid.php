<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Block\Adminhtml\Customer\Attribute;

/**
 * Customer Attributes Grid Block
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 */
class Grid extends \Magento\Eav\Block\Adminhtml\Attribute\Grid\AbstractGrid
{
    /**
     * @var \Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory
     */
    protected $_attributesFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory $attributesFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Customer\Model\ResourceModel\Attribute\CollectionFactory $attributesFactory,
        array $data = []
    ) {
        $this->_attributesFactory = $attributesFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Initialize grid, set grid Id
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('customerAttributeGrid');
        $this->setDefaultSort('sort_order');
    }

    /**
     * Prepare customer attributes grid collection object
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        /** @var $collection \Magento\Customer\Model\ResourceModel\Attribute\Collection */
        $collection = $this->_attributesFactory->create();
        $collection->addSystemHiddenFilter()->addExcludeHiddenFrontendFilter();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Prepare customer attributes grid columns
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        parent::_prepareColumns();

        $this->addColumn(
            'is_visible',
            [
                'header' => __('Visible to Customer'),
                'sortable' => true,
                'index' => 'is_visible',
                'type' => 'options',
                'options' => ['0' => __('No'), '1' => __('Yes')]
            ]
        );

        $this->addColumn(
            'sort_order',
            ['header' => __('Sort Order'), 'sortable' => true, 'index' => 'sort_order']
        );

        return $this;
    }
}
