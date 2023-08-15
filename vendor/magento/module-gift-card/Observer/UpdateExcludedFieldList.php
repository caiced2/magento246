<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftCard\Observer;

use Magento\Framework\Event\ObserverInterface;

class UpdateExcludedFieldList implements ObserverInterface
{
    const ATTRIBUTE_CODE = 'giftcard_amounts';

    /**
     * Set giftcard amounts field as not used in mass update
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        //adminhtml_catalog_product_form_prepare_excluded_field_list

        $block = $observer->getEvent()->getObject();
        $list = $block->getFormExcludedFieldList();
        $list[] = self::ATTRIBUTE_CODE;
        $block->setFormExcludedFieldList($list);
    }
}
