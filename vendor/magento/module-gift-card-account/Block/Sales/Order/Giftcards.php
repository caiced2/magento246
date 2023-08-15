<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Block\Sales\Order;

use Magento\GiftCardAccount\Model\Giftcardaccount;

/**
 * @api
 * @since 100.0.2
 */
class Giftcards extends \Magento\Framework\View\Element\Template
{
    /**
     * Gift card account data
     *
     * @var \Magento\GiftCardAccount\Helper\Data
     */
    protected $_giftCardAccountData = null;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\GiftCardAccount\Helper\Data $giftCardAccountData
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\GiftCardAccount\Helper\Data $giftCardAccountData,
        array $data = []
    ) {
        $this->_giftCardAccountData = $giftCardAccountData;
        parent::__construct($context, $data);
        $this->_isScopePrivate = true;
    }

    /**
     * Retrieve current order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->getParentBlock()->getOrder();
    }

    /**
     * @return mixed
     */
    public function getSource()
    {
        return $this->getParentBlock()->getSource();
    }

    /**
     * Retrieve gift cards applied to current order
     *
     * @return array
     */
    public function getGiftCards()
    {
        $result = [];
        $source = $this->getSource();
        if (!$source instanceof \Magento\Sales\Model\Order) {
            return $result;
        }
        $cards = $this->_giftCardAccountData->getCards($this->getOrder());
        foreach ($cards as $card) {
            $obj = new \Magento\Framework\DataObject();
            $obj->setBaseAmount($card[Giftcardaccount::BASE_AMOUNT])
                ->setAmount($card[Giftcardaccount::AMOUNT])
                ->setCode($card[Giftcardaccount::CODE]);

            $result[] = $obj;
        }
        return $result;
    }

    /**
     * Initialize giftcard order total
     *
     * @return $this
     */
    public function initTotals()
    {
        $total = new \Magento\Framework\DataObject(
            [
                'code' => $this->getNameInLayout(),
                'block_name' => $this->getNameInLayout(),
                'area' => $this->getArea(),
            ]
        );
        $this->getParentBlock()->addTotalBefore($total, ['customerbalance', 'grand_total']);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLabelProperties()
    {
        return $this->getParentBlock()->getLabelProperties();
    }

    /**
     * @return mixed
     */
    public function getValueProperties()
    {
        return $this->getParentBlock()->getValueProperties();
    }
}
