<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftCardAccount\Controller\Cart;

use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\GiftCardAccount\Api\GiftCardAccountManagementInterface;

/**
 * @inheritDoc
 */
class Remove extends \Magento\Checkout\Controller\Cart implements HttpPostActionInterface
{
    /**
     * @var GiftCardAccountManagementInterface
     */
    private $management;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param CustomerCart $cart
     * @param GiftCardAccountManagementInterface|null $management
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        CustomerCart $cart,
        ?GiftCardAccountManagementInterface $management = null
    ) {
        parent::__construct($context, $scopeConfig, $checkoutSession, $storeManager, $formKeyValidator, $cart);
        $this->management = $management
            ?? ObjectManager::getInstance()->get(GiftCardAccountManagementInterface::class);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $code = $this->getRequest()->getParam('code');
        if ($code) {
            try {
                $this->management->deleteByQuoteId($this->cart->getQuote()->getId(), $code);
                $this->messageManager->addSuccess(
                    __(
                        'Gift Card "%1" was removed.',
                        $this->_objectManager->get(\Magento\Framework\Escaper::class)->escapeHtml($code)
                    )
                );
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Throwable $e) {
                $this->messageManager->addException($e, __('You can\'t remove this gift card.'));
            }
            return $this->_goBack();
        } else {
            $this->_forward('noroute');
        }
    }
}
