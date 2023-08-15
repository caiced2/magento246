<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Block\Adminhtml\Sales\Order\Create;

use Magento\GiftCardAccount\Model\Giftcardaccount;

/**
 * @api
 * @since 100.0.2
 */
class Payment extends \Magento\Framework\View\Element\Template
{
    /**
     * Gift card account data
     *
     * @var \Magento\GiftCardAccount\Helper\Data
     */
    protected $_giftCardAccountData = null;

    /**
     * @var \Magento\Sales\Model\AdminOrder\Create
     */
    protected $_orderCreate = null;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\GiftCardAccount\Helper\Data $giftCardAccountData
     * @param \Magento\Sales\Model\AdminOrder\Create $orderCreate
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\GiftCardAccount\Helper\Data $giftCardAccountData,
        \Magento\Sales\Model\AdminOrder\Create $orderCreate,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_giftCardAccountData = $giftCardAccountData;
        $this->_orderCreate = $orderCreate;
    }

    /**
     * Retrieve order create model
     *
     * @return \Magento\Sales\Model\AdminOrder\Create
     */
    protected function _getOrderCreateModel()
    {
        return $this->_orderCreate;
    }

    /**
     * @return array
     */
    public function getGiftCards()
    {
        $result = [];
        $quote = $this->_orderCreate->getQuote();
        $cards = $this->_giftCardAccountData->getCards($quote);
        foreach ($cards as $card) {
            $result[] = $card[Giftcardaccount::CODE];
        }
        return $result;
    }

    /**
     * Check whether quote uses gift cards
     *
     * @return bool
     */
    public function isUsed()
    {
        $quote = $this->_getOrderCreateModel()->getQuote();

        return $quote->getGiftCardsAmount() > 0;
    }

    /**
     * @return bool
     */
    public function isFullyPaid()
    {
        $quote = $this->_orderCreate->getQuote();
        if (!$quote->getGiftCardsAmount() ||
            $quote->getBaseGrandTotal() > 0 ||
            $quote->getCustomerBalanceAmountUsed() > 0
        ) {
            return false;
        }

        return true;
    }
}
