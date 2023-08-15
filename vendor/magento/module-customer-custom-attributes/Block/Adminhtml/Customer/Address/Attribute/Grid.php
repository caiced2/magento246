<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Block\Adminhtml\Customer\Address\Attribute;

/**
 * Customer Address Attributes Grid Block
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 */
class Grid extends \Magento\Eav\Block\Adminhtml\Attribute\Grid\AbstractGrid
{
    /**
     * @var \Magento\Customer\Model\ResourceModel\Address\Attribute\CollectionFactory
     */
    protected $_addressesFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Customer\Model\ResourceModel\Address\Attribute\CollectionFactory $addressesFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Customer\Model\ResourceModel\Address\Attribute\CollectionFactory $addressesFactory,
        array $data = []
    ) {
        $this->_addressesFactory = $addressesFactory;
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
        $this->setDefaultSort('sort_order');
        $this->setId('customerAddressAttributeGrid');
    }

    /**
     * Prepare customer address attributes grid collection object
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        /** @var $collection \Magento\Customer\Model\ResourceModel\Address\Attribute\Collection */
        $collection = $this->_addressesFactory->create();
        $collection->addSystemHiddenFilter()->addExcludeHiddenFrontendFilter();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Prepare customer address attributes grid columns
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
                'options' => ['0' => __('No'), '1' => __('Yes')],
                'align' => 'center'
            ]
        );

        $this->addColumn(
            'sort_order',
            ['header' => __('Sort Order'), 'sortable' => true, 'align' => 'center', 'index' => 'sort_order']
        );

        return $this;
    }
}
