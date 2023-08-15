<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Block\Checkout\Cart;

use Magento\Checkout\Helper\Data;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Model\ConfigInterface;

/**
 * Gift wrapping total block for Gift Card Account
 */
class Total extends \Magento\Checkout\Block\Total\DefaultTotal
{
    /**
     * @var string
     */
    protected $_template = 'Magento_GiftCardAccount::cart/total.phtml';

    /**
     * @var \Magento\GiftCardAccount\Helper\Data|null
     */
    protected $_giftCardAccountData = null;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param ConfigInterface $salesConfig
     * @param \Magento\GiftCardAccount\Helper\Data $giftCardAccountData
     * @param array $layoutProcessors
     * @param array $data
     * @param Data|null $checkoutHelper
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        ConfigInterface $salesConfig,
        \Magento\GiftCardAccount\Helper\Data $giftCardAccountData,
        array $layoutProcessors = [],
        array $data = [],
        Data $checkoutHelper = null
    ) {
        $data['checkoutHelper'] = $checkoutHelper ?? ObjectManager::getInstance()->get(Data::class);
        $this->_giftCardAccountData = $giftCardAccountData;
        parent::__construct($context, $customerSession, $checkoutSession, $salesConfig, $layoutProcessors, $data);
        $this->_isScopePrivate = true;
    }

    /**
     * Get sales quote
     *
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote()
    {
        return $this->_checkoutSession->getQuote();
    }

    /**
     * Get gift card list from quote
     *
     * @return mixed
     */
    public function getQuoteGiftCards()
    {
        return $this->_giftCardAccountData->getCards($this->getQuote());
    }
}
