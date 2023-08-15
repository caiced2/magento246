<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reminder\Block\Adminhtml\Reminder\Edit\Tab;

use Magento\Backend\Block\Widget\Grid;
use Magento\Backend\Block\Widget\Grid\Extended as GridExtended;

/**
 * Matched rule customer grid block
 */
class Customers extends GridExtended
{
    /**
     * Customer Resource Collection
     *
     * @var \Magento\Reminder\Model\ResourceModel\Customer\Collection
     */
    protected $_customerCollection;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Reminder\Model\ResourceModel\Customer\Collection $customerCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Reminder\Model\ResourceModel\Customer\Collection $customerCollection,
        array $data = []
    ) {
        parent::__construct($context, $backendHelper, $data);
        $this->_customerCollection = $customerCollection;
    }

    /**
     * Initialize grid
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('customerGrid');
        $this->setUseAjax(true);
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('ASC');
    }

    /**
     * Instantiate and prepare collection
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        $this->setCollection($this->_customerCollection);

        return parent::_prepareCollection();
    }

    /**
     * Prepare columns for grid
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'grid_entity_id',
            [
                'header' => __('ID'),
                'align' => 'center',
                'width' => 50,
                'index' => 'entity_id',
                'renderer' => \Magento\Reminder\Block\Adminhtml\Widget\Grid\Column\Renderer\Id::class
            ]
        );

        $this->addColumn(
            'grid_email',
            [
                'header' => __('Email'),
                'type' => 'text',
                'align' => 'left',
                'index' => 'email',
                'renderer' => \Magento\Reminder\Block\Adminhtml\Widget\Grid\Column\Renderer\Email::class
            ]
        );

        $this->addColumn(
            'grid_associated_at',
            [
                'header' => __('Matched At'),
                'align' => 'left',
                'width' => 150,
                'type' => 'datetime',
                'default' => '--',
                'index' => 'associated_at'
            ]
        );

        $this->addColumn(
            'grid_is_active',
            [
                'header' => __('Thread Active'),
                'align' => 'left',
                'type' => 'options',
                'index' => 'is_active',
                'options' => ['0' => __('No'), '1' => __('Yes')]
            ]
        );

        $this->addColumn(
            'grid_code',
            ['header' => __('Coupon'), 'align' => 'left', 'default' => __('N/A'), 'index' => 'code']
        );

        $this->addColumn(
            'grid_usage_limit',
            ['header' => __('Coupon Use Limit'), 'align' => 'left', 'default' => '0', 'index' => 'usage_limit']
        );

        $this->addColumn(
            'grid_usage_per_customer',
            [
                'header' => __('Coupon Use Per Customer'),
                'align' => 'left',
                'default' => '0',
                'index' => 'usage_per_customer'
            ]
        );

        $this->addColumn(
            'grid_emails_sent',
            ['header' => __('Emails Sent'), 'align' => 'left', 'default' => '0', 'index' => 'emails_sent']
        );

        $this->addColumn(
            'grid_emails_failed',
            ['header' => __('Emails Failed'), 'align' => 'left', 'index' => 'emails_failed']
        );

        $this->addColumn(
            'grid_last_sent',
            [
                'header' => __('Last Sent'),
                'align' => 'left',
                'width' => 150,
                'type' => 'datetime',
                'default' => '--',
                'index' => 'last_sent'
            ]
        );

        return parent::_prepareColumns();
    }
}
