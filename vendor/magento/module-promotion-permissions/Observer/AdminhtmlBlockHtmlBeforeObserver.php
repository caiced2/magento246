<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Promotion Permissions Observer
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\PromotionPermissions\Observer;

use Magento\Framework\Event\ObserverInterface;

class AdminhtmlBlockHtmlBeforeObserver implements ObserverInterface
{
    /**
     * Edit Catalog Rules flag
     *
     * @var boolean
     */
    protected $_canEditCatalogRules;

    /**
     * Edit Sales Rules flag
     *
     * @var boolean
     */
    protected $_canEditSalesRules;

    /**
     * Edit Reminder Rules flag
     *
     * @var boolean
     */
    protected $_canEditReminderRules;

    /**
     * \Magento\Banner flag
     *
     * @var boolean
     */
    protected $_isEnterpriseBannerEnabled;

    /**
     * \Magento\Reminder flag
     *
     * @var boolean
     */
    protected $_isEnterpriseReminderEnabled;

    /**
     * @var \Magento\Banner\Model\ResourceModel\Banner\Collection
     */
    protected $_bannerCollection;

    /**
     * @param \Magento\PromotionPermissions\Helper\Data $promoPermData
     * @param \Magento\Banner\Model\ResourceModel\Banner\Collection $bannerCollection
     * @param \Magento\Framework\Module\Manager $moduleManager
     */
    public function __construct(
        \Magento\PromotionPermissions\Helper\Data $promoPermData,
        \Magento\Banner\Model\ResourceModel\Banner\Collection $bannerCollection,
        \Magento\Framework\Module\Manager $moduleManager
    ) {
        $this->_bannerCollection = $bannerCollection;
        $this->_canEditCatalogRules = $promoPermData->getCanAdminEditCatalogRules();
        $this->_canEditSalesRules = $promoPermData->getCanAdminEditSalesRules();
        $this->_canEditReminderRules = $promoPermData->getCanAdminEditReminderRules();
        $this->_isEnterpriseBannerEnabled = $moduleManager->isEnabled('Magento_Banner');
        $this->_isEnterpriseReminderEnabled = $moduleManager->isEnabled('Magento_Reminder');
    }

    /**
     * Handle adminhtml_block_html_before event
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var $block \Magento\Backend\Block\Template */
        $block = $observer->getBlock();
        $blockNameInLayout = $block->getNameInLayout();
        switch ($blockNameInLayout) {
            // Handle blocks related to \Magento\CatalogRule module
            case 'promo_catalog_edit_tab_main':
            case 'promo_catalog_edit_tab_actions':
            case 'promo_catalog_edit_tab_conditions':
                if (!$this->_canEditCatalogRules) {
                    $block->getForm()->setReadonly(true, true);
                }
                break;
            // Handle blocks related to \Magento\SalesRule module
            case 'promo_quote_edit_tab_main':
                if (!$this->_canEditSalesRules) {
                    $block->unsetChild('form_after');
                }
                // no break needed
            case 'promo_quote_edit_tab_actions':
            case 'promo_quote_edit_tab_conditions':
            case 'promo_quote_edit_tab_labels':
                if (!$this->_canEditSalesRules) {
                    $block->getForm()->setReadonly(true, true);
                }
                break;
            // Handle blocks related to \Magento\Reminder module
            case 'adminhtml_reminder_edit_tab_conditions':
            case 'adminhtml_reminder_edit_tab_templates':
                if (!$this->_canEditReminderRules) {
                    $block->getForm()->setReadonly(true, true);
                }
                break;
            // Handle blocks related to \Magento\Banner module
            case 'related_catalogrule_banners_grid':
                if ($this->_isEnterpriseBannerEnabled && !$this->_canEditCatalogRules) {
                    $block->getColumn('in_banners')->setDisabledValues($this->_bannerCollection->getAllIds());
                    $block->getColumn('in_banners')->setDisabled(true);
                }
                break;
            case 'related_salesrule_banners_grid':
                if ($this->_isEnterpriseBannerEnabled && !$this->_canEditSalesRules) {
                    $block->getColumn('in_banners')->setDisabledValues($this->_bannerCollection->getAllIds());
                    $block->getColumn('in_banners')->setDisabled(true);
                }
                break;
            case 'promo_quote_edit_tabs':
                if ($this->_isEnterpriseBannerEnabled && !$this->_canEditSalesRules) {
                    $relatedBannersBlock = $block->getChildBlock('salesrule.related.banners');
                    if ($relatedBannersBlock instanceof \Magento\Framework\View\Element\AbstractBlock) {
                        $relatedBannersBlock->unsetChild('banners_grid_serializer');
                    }
                }
                break;
            case 'promo_catalog_edit_tabs':
                if ($this->_isEnterpriseBannerEnabled && !$this->_canEditCatalogRules) {
                    $relatedBannersBlock = $block->getChildBlock('catalogrule.related.banners');
                    if ($relatedBannersBlock) {
                        $relatedBannersBlock->unsetChild('banners_grid_serializer');
                    }
                }
                break;
        }
    }
}
