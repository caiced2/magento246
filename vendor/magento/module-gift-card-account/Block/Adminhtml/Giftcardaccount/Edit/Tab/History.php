<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Block\Adminhtml\Giftcardaccount\Edit\Tab;

use Magento\GiftCardAccount\Model\ResourceModel\History\Collection;

class History extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var Collection
     */
    protected $_collection;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * History factory
     *
     * @var \Magento\GiftCardAccount\Model\HistoryFactory
     */
    protected $_historyFactory = null;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\GiftCardAccount\Model\HistoryFactory $historyFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\GiftCardAccount\Model\HistoryFactory $historyFactory,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context, $backendHelper, $data);
        $this->_historyFactory = $historyFactory;
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('historyGrid');
        $this->setUseAjax(true);
        $this->setDefaultSort('id');
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = $this->_historyFactory->create()->getCollection()->addFieldToFilter(
            'giftcardaccount_id',
            $this->_coreRegistry->registry('current_giftcardaccount')->getId()
        );
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn('id', ['header' => __('ID'), 'index' => 'history_id', 'type' => 'int', 'width' => 50]);

        $this->addColumn(
            'updated_at',
            [
                'header' => __('Date'),
                'index' => 'updated_at',
                'type' => 'datetime',
                'filter' => false,
                'width' => 100
            ]
        );

        $this->addColumn(
            'action',
            [
                'header' => __('Action'),
                'width' => 100,
                'index' => 'action',
                'sortable' => false,
                'type' => 'options',
                'options' => $this->_historyFactory->create()->getActionNamesArray()
            ]
        );

        $giftCardAccount = $this->_coreRegistry->registry('current_giftcardaccount');
        $currency = $this->_storeManager->getWebsite($giftCardAccount->getWebsiteId())->getBaseCurrencyCode();
        $this->addColumn(
            'balance_delta',
            [
                'header' => __('Balance Change'),
                'width' => 50,
                'index' => 'balance_delta',
                'sortable' => false,
                'filter' => false,
                'type' => 'price',
                'currency_code' => $currency
            ]
        );

        $this->addColumn(
            'balance_amount',
            [
                'header' => __('Balance'),
                'width' => 50,
                'index' => 'balance_amount',
                'sortable' => false,
                'filter' => false,
                'type' => 'price',
                'currency_code' => $currency
            ]
        );

        $this->addColumn(
            'additional_info',
            ['header' => __('More Information'), 'index' => 'additional_info', 'sortable' => false]
        );

        return parent::_prepareColumns();
    }

    /**
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('adminhtml/*/gridHistory', ['_current' => true]);
    }
}
