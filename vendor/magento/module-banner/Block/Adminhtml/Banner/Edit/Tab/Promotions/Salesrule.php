<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Block\Adminhtml\Banner\Edit\Tab\Promotions;

use Magento\Backend\Block\Widget\Grid\Column;

/**
 * @api
 * @since 100.0.2
 * @deprecated Banner form configuration has been moved on ui component declaration
 * @see app/code/Magento/Banner/view/adminhtml/ui_component/banner_form.xml
 */
class Salesrule extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\SalesRule\Model\ResourceModel\Rule\Collection $ruleCollection
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\SalesRule\Model\ResourceModel\Rule\Collection $ruleCollection,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_registry = $registry;
        parent::__construct($context, $backendHelper, $data);
        $this->setCollection($ruleCollection);
    }

    /**
     * Initialize grid, set defaults
     *
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->setId('related_salesrule_grid');
        $this->setDefaultSort('name');
        $this->setDefaultDir('ASC');
        $this->setUseAjax(true);
        $this->setSaveParametersInSession(true);
        $this->setVarNameFilter('related_salesrule_filter');
        if ($this->_getBanner() && $this->_getBanner()->getId()) {
            $this->setDefaultFilter(['in_banner_salesrule' => 1]);
        }
    }

    /**
     * Set custom filter for in banner salesrule flag
     *
     * @param Column $column
     * @return $this
     */
    protected function _addColumnFilterToCollection($column)
    {
        if ($column->getId() == 'in_banner_salesrule') {
            $ruleIds = $this->_getSelectedRules();
            if (empty($ruleIds)) {
                $ruleIds = 0;
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('main_table.rule_id', ['in' => $ruleIds]);
            } else {
                if ($ruleIds) {
                    $this->getCollection()->addFieldToFilter('main_table.rule_id', ['nin' => $ruleIds]);
                }
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }
        return $this;
    }

    /**
     * Create grid columns
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'in_banner_salesrule',
            [
                'type' => 'checkbox',
                'name' => 'in_banner_salesrule',
                'values' => $this->_getSelectedRules(),
                'index' => 'rule_id',
                'header_css_class' => 'col-select col-massaction',
                'column_css_class' => 'col-select col-massaction'
            ]
        );
        $this->addColumn(
            'salesrule_rule_id',
            [
                'header' => __('ID'),
                'index' => 'rule_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );

        $this->addColumn(
            'salesrule_name',
            [
                'header' => __('Rule'),
                'index' => 'name',
                'header_css_class' => 'col-name',
                'column_css_class' => 'col-name'
            ]
        );

        $this->addColumn(
            'salesrule_from_date',
            [
                'header' => __('Start Date'),
                'type' => 'date',
                'index' => 'from_date',
                'header_css_class' => 'col-date',
                'column_css_class' => 'col-date'
            ]
        );

        $this->addColumn(
            'salesrule_to_date',
            [
                'header' => __('End Date'),
                'type' => 'date',
                'default' => '--',
                'index' => 'to_date',
                'header_css_class' => 'col-date',
                'column_css_class' => 'col-date'
            ]
        );

        $this->addColumn(
            'salesrule_is_active',
            [
                'header' => __('Status'),
                'index' => 'is_active',
                'type' => 'options',
                'options' => [1 => 'Active', 0 => 'Inactive'],
                'header_css_class' => 'col-status',
                'column_css_class' => 'col-status'
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * Ajax grid URL getter
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('adminhtml/*/salesRuleGrid', ['_current' => true]);
    }

    /**
     * Get selected rules ids for in banner salesrule flag
     *
     * @return array
     */
    protected function _getSelectedRules()
    {
        $rules = $this->getSelectedSalesRules();
        if ($rules === null) {
            $rules = $this->getRelatedSalesRule();
        }
        return $rules;
    }

    /**
     * Get related sales rules by current banner
     *
     * @return array
     */
    public function getRelatedSalesRule()
    {
        return $this->_getBanner()->getRelatedSalesRule();
    }

    /**
     * Get current banner model
     *
     * @return \Magento\Banner\Model\Banner
     */
    protected function _getBanner()
    {
        return $this->_registry->registry('current_banner');
    }

    /**
     * {@inheritdoc}
     */
    public function getRowUrl($item)
    {
        return '';
    }
}
