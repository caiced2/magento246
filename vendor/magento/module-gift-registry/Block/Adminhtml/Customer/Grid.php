<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Block\Adminhtml\Customer;

/**
 * @api
 * @codeCoverageIgnore
 * @since 100.0.2
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Magento\GiftRegistry\Model\EntityFactory
     */
    protected $entityFactory;

    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $systemStore;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\GiftRegistry\Model\EntityFactory $entityFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\GiftRegistry\Model\EntityFactory $entityFactory,
        \Magento\Store\Model\System\Store $systemStore,
        array $data = []
    ) {
        $this->entityFactory = $entityFactory;
        parent::__construct($context, $backendHelper, $data);

        $this->systemStore = $systemStore;
    }

    /**
     * Set default sort
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('customerGrid');
        $this->setUseAjax(true);
        $this->setDefaultSort('registry_id');
        $this->setDefaultDir('ASC');
    }

    /**
     * Instantiate and prepare collection
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        /** @var $collection \Magento\GiftRegistry\Model\ResourceModel\Entity\Collection */
        $collection = $this->entityFactory->create()->getCollection();
        $collection->filterByCustomerId($this->getRequest()->getParam('id'));
        $collection->addRegistryInfo();

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Prepare columns for grid
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn('title', ['header' => __('Event'), 'index' => 'title']);

        $this->addColumn('registrants', ['header' => __('Registrants'), 'index' => 'registrants']);

        $this->addColumn(
            'event_date',
            ['header' => __('Event Date'), 'index' => 'event_date', 'type' => 'date', 'default' => '--']
        );

        $this->addColumn('qty', ['header' => __('Total Items'), 'index' => 'qty', 'type' => 'number']);

        $this->addColumn(
            'qty_fulfilled',
            ['header' => __('Fulfilled'), 'index' => 'qty_fulfilled', 'type' => 'number']
        );

        $this->addColumn(
            'qty_remaining',
            ['header' => __('Remaining'), 'index' => 'qty_remaining', 'type' => 'number']
        );

        $this->addColumn(
            'is_public',
            [
                'header' => __('Public'),
                'index' => 'is_public',
                'type' => 'options',
                'options' => ['0' => __('No'), '1' => __('Yes')]
            ]
        );

        if (!$this->_storeManager->isSingleStoreMode()) {
            $this->addColumn(
                'website_id',
                [
                    'header' => __('Website'),
                    'index' => 'website_id',
                    'type' => 'options',
                    'options' => $this->systemStore->getWebsiteOptionHash()
                ]
            );
        }

        return parent::_prepareColumns();
    }

    /**
     * Retrieve row url
     *
     * @param \Magento\Catalog\Model\Product|\Magento\Framework\DataObject $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('adminhtml/*/edit', ['id' => $row->getId(), 'customer' => $row->getCustomerId()]);
    }

    /**
     * Retrieve grid URL
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('adminhtml/*/grid', ['_current' => true]);
    }
}
