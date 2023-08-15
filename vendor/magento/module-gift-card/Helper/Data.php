<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftCard\Helper;

/**
 * Giftcard module helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    protected $_layout;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\View\LayoutInterface $layout
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\View\LayoutInterface $layout
    ) {
        $this->_layout = $layout;
        parent::__construct($context);
    }

    /**
     * Instantiate giftardaccounts block when a gift card email should be sent
     *
     * @return \Magento\GiftCard\Block\Generated
     */
    public function getEmailGeneratedItemsBlock()
    {
        /** @var $block \Magento\GiftCard\Block\Generated */
        $block = $this->_layout->createBlock(\Magento\GiftCard\Block\Generated::class);
        $block->setTemplate('Magento_GiftCard::email/generated.phtml');
        return $block;
    }
}
