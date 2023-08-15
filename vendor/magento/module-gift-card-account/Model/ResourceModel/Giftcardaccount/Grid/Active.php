<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * GiftCardAccount Resource Collection
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\GiftCardAccount\Model\ResourceModel\Giftcardaccount\Grid;

class Active implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Return options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            \Magento\GiftCardAccount\Model\Giftcardaccount::STATUS_ENABLED => __('Yes'),
            \Magento\GiftCardAccount\Model\Giftcardaccount::STATUS_DISABLED => __('No')
        ];
    }
}
