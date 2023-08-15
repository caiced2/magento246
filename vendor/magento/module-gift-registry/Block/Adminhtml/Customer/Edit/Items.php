<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Block\Adminhtml\Customer\Edit;

/**
 * Adminhtml customer view gift registry items block
 * @codeCoverageIgnore
 */
class Items extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Magento\GiftRegistry\Model\ItemFactory
     */
    protected $itemFactory;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\GiftRegistry\Model\ItemFactory $itemFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\GiftRegistry\Model\ItemFactory $itemFactory,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->itemFactory = $itemFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('giftregistry_customer_items_grid');
        $this->setSortable(false);
        $this->setPagerVisibility(false);
        $this->setFilterVisibility(false);
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = $this->itemFactory->create()->getCollection()->addRegistryFilter($this->getEntity()->getId());

        $collection->updateItemAttributes();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'product_id',
            ['header' => __('ID'), 'index' => 'product_id', 'type' => 'number', 'width' => '120px']
        );

        $this->addColumn('name', ['header' => __('Product'), 'index' => 'product_name']);

        $this->addColumn('sku', ['header' => __('SKU'), 'index' => 'sku', 'width' => '200px']);

        $this->addColumn(
            'price',
            [
                'header' => __('Price'),
                'index' => 'price',
                'type' => 'currency',
                'width' => '120px',
                'currency_code' => (string)$this->_scopeConfig->getValue(
                    \Magento\Directory\Model\Currency::XML_PATH_CURRENCY_BASE,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
            ]
        );

        $this->addColumn(
            'qty',
            [
                'header' => __('Requested'),
                'index' => 'qty',
                'width' => '120px',
                'renderer' => \Magento\GiftRegistry\Block\Adminhtml\Widget\Grid\Column\Renderer\Qty::class
            ]
        );

        $this->addColumn(
            'qty_fulfilled',
            ['header' => __('Fulfilled'), 'index' => 'qty_fulfilled', 'type' => 'number', 'width' => '120px']
        );

        $this->addColumn('note', ['header' => __('Note'), 'index' => 'note', 'width' => '120px']);

        $this->addColumn(
            'action',
            [
                'header' => __('Action'),
                'width' => '120px',
                'options' => [
                    0 => __('Action'),
                    'update' => __('Update Quantity'),
                    'remove' => __('Remove Item'),
                ],
                'renderer' => \Magento\GiftRegistry\Block\Adminhtml\Widget\Grid\Column\Renderer\Action::class
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * Return grid row url
     *
     * @param \Magento\Catalog\Model\Product|\Magento\Framework\DataObject $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('catalog/product/edit', ['id' => $row->getProductId()]);
    }

    /**
     * Return gift registry entity object
     *
     * @return \Magento\GiftRegistry\Model\Entity
     */
    public function getEntity()
    {
        return $this->_coreRegistry->registry('current_giftregistry_entity');
    }
}
