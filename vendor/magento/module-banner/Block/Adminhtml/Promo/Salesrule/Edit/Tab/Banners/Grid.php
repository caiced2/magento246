<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Block\Adminhtml\Promo\Salesrule\Edit\Tab\Banners;

use Magento\Backend\Block\Widget\Grid\Column;

/**
 * @api
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 * @since 100.0.2
 */
class Grid extends \Magento\Banner\Block\Adminhtml\Banner\Grid
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @var \Magento\Banner\Model\BannerFactory
     */
    protected $_bannerFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Banner\Model\ResourceModel\Banner\CollectionFactory $bannerColFactory
     * @param \Magento\Banner\Model\Config $bannerConfig
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Banner\Model\BannerFactory $bannerFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Banner\Model\ResourceModel\Banner\CollectionFactory $bannerColFactory,
        \Magento\Banner\Model\Config $bannerConfig,
        \Magento\Framework\Registry $registry,
        \Magento\Banner\Model\BannerFactory $bannerFactory,
        array $data = []
    ) {
        $this->_registry = $registry;
        parent::__construct($context, $backendHelper, $bannerColFactory, $bannerConfig, $data);
        $this->_bannerFactory = $bannerFactory;
    }

    /**
     * Initialize grid, set promo sales rule grid ID
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('related_salesrule_banners_grid');
        $this->setVarNameFilter('related_salesrule_banners_filter');
        if ($this->_getRule() && $this->_getRule()->getId()) {
            $this->setDefaultFilter(['in_banners' => 1]);
        }
    }

    /**
     * Create grid columns
     *
     * @return void
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'in_banners',
            [
                'header_css_class' => 'a-center',
                'type' => 'checkbox',
                'name' => 'in_banners',
                'values' => $this->_getSelectedBanners(),
                'align' => 'center',
                'index' => 'banner_id'
            ]
        );
        parent::_prepareColumns();
    }

    /**
     * Set custom filter for in banner flag
     *
     * @param Column $column
     * @return $this
     */
    protected function _addColumnFilterToCollection($column)
    {
        if ($column->getId() == 'in_banners') {
            $bannerIds = $this->_getSelectedBanners();
            if (empty($bannerIds)) {
                $bannerIds = 0;
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('main_table.banner_id', ['in' => $bannerIds]);
            } else {
                if ($bannerIds) {
                    $this->getCollection()->addFieldToFilter('main_table.banner_id', ['nin' => $bannerIds]);
                }
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }
        return $this;
    }

    /**
     * Disable massaction functioanality
     *
     * @return $this
     */
    protected function _prepareMassaction()
    {
        return $this;
    }

    /**
     * Ajax grid URL getter
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('adminhtml/banner/salesRuleBannersGrid', ['_current' => true]);
    }

    /**
     * Define row click callback
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getRowUrl($row)
    {
        return '';
    }

    /**
     * Get selected banners ids for in banner flag
     *
     * @return array
     */
    protected function _getSelectedBanners()
    {
        $banners = $this->getSelectedSalesruleBanners();
        if ($banners === null) {
            $banners = $this->getRelatedBannersByRule();
        }
        return $banners;
    }

    /**
     * Get related banners by current rule
     *
     * @return array
     */
    public function getRelatedBannersByRule()
    {
        if ($rule = $this->_getRule()) {
            return $this->_bannerFactory->create()->getRelatedBannersBySalesRuleId($rule->getRuleId());
        }

        return [];
    }

    /**
     * Get current sales rule model
     *
     * @return \Magento\SalesRule\Model\Rule
     */
    protected function _getRule()
    {
        return $this->_registry->registry(\Magento\SalesRule\Model\RegistryConstants::CURRENT_SALES_RULE);
    }
}
