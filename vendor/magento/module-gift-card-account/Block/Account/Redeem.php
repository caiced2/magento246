<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Block\Account;

/**
 * @api
 * @since 100.0.2
 */
class Redeem extends \Magento\Framework\View\Element\Template
{
    /**
     * Customer balance data
     *
     * @var \Magento\CustomerBalance\Helper\Data
     */
    protected $_customerBalanceData = null;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\CustomerBalance\Helper\Data $customerBalanceData
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\CustomerBalance\Helper\Data $customerBalanceData,
        array $data = []
    ) {
        $this->_customerBalanceData = $customerBalanceData;
        parent::__construct($context, $data);
    }

    /**
     * Stub for future ability to implement redeem limitations based on customer/settings
     *
     * @return bool
     */
    public function canRedeem()
    {
        return $this->_customerBalanceData->isEnabled();
    }

    /**
     * Retrieve gift card code from url, empty if none
     *
     * @return string
     */
    public function getCurrentGiftcard()
    {
        $code = $this->getRequest()->getParam('giftcard', '');

        return $this->escapeHtml($code);
    }
}
