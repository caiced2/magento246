<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Block\Product\View;

use Magento\Framework\View\Element\Template;

class Plugin
{
    /**
     * Return wishlist widget options
     *
     * @param Template $subject
     * @param array $result
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetWishlistOptions(Template $subject, $result)
    {
        return array_merge($result, ['giftcardInfo' => '[id^=giftcard]']);
    }
}
