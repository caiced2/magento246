<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Controller\Adminhtml\Order\Edit;

/**
 * Plugin for order editing
 */
class Plugin
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\CustomerBalance\Helper\Data
     */
    protected $customerBalanceData;

    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $sessionQuote;

    /**
     * @var \Magento\GiftCardAccount\Helper\Data
     */
    protected $giftCardAccountData;

    /**
     * @param \Magento\Backend\Model\Session\Quote $sessionQuote
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\CustomerBalance\Helper\Data $customerBalanceData
     * @param \Magento\GiftCardAccount\Helper\Data $giftCardAccountData
     */
    public function __construct(
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\CustomerBalance\Helper\Data $customerBalanceData,
        \Magento\GiftCardAccount\Helper\Data $giftCardAccountData
    ) {
        $this->messageManager = $messageManager;
        $this->customerBalanceData = $customerBalanceData;
        $this->sessionQuote = $sessionQuote;
        $this->giftCardAccountData = $giftCardAccountData;
    }

    /**
     * Add messages when order paid with gift card
     *
     * @param \Magento\Sales\Controller\Adminhtml\Order\Edit\Index $subject
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeExecute(\Magento\Sales\Controller\Adminhtml\Order\Edit\Index $subject)
    {
        $giftCards = $this->giftCardAccountData->getCards($this->sessionQuote->getOrder());
        if (!empty($giftCards)) {
            $this->messageManager->addNotice(
                __('We will refund the gift card amount to your customer’s store credit')
            );
            if (!$this->customerBalanceData->isEnabled()) {
                $this->messageManager->addError(
                    __('Please enable Store Credit to refund the gift card amount to your customer')
                );
            }
        }
    }
}
