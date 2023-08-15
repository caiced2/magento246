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

class ViewBlockAbstractToHtmlBeforeObserver implements ObserverInterface
{
    /**
     * Edit Reminder Rules flag
     *
     * @var boolean
     */
    protected $_canEditReminderRules;

    /**
     * @param \Magento\PromotionPermissions\Helper\Data $promoPermData
     */
    public function __construct(
        \Magento\PromotionPermissions\Helper\Data $promoPermData
    ) {
        $this->_canEditReminderRules = $promoPermData->getCanAdminEditReminderRules();
    }

    /**
     * Handle view_block_abstract_to_html_before event
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var $block \Magento\Framework\View\Element\AbstractBlock */
        $block = $observer->getBlock();
        $blockNameInLayout = $block->getNameInLayout();
        switch ($blockNameInLayout) {
            // Handle General Tab on Edit Reminder Rule page
            case 'adminhtml_reminder_edit_tab_general':
                if (!$this->_canEditReminderRules) {
                    $block->setCanEditReminderRule(false);
                }
                break;
        }
    }
}
