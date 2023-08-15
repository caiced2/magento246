<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Controller\Cart;

use Magento\Framework\App\RequestInterface;

class Remove extends \Magento\Framework\App\Action\Action
{
    /**
     * Only logged in users can use this functionality,
     * this function checks if user is logged in before all other actions
     *
     * @param RequestInterface $request
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function dispatch(RequestInterface $request)
    {
        if (!$this->_objectManager->get(\Magento\Customer\Model\Session::class)->authenticate()) {
            $this->_actionFlag->set('', 'no-dispatch', true);
        }
        return parent::dispatch($request);
    }

    /**
     * Remove Store Credit from current quote
     *
     * @return void
     */
    public function execute()
    {
        if (!$this->_objectManager->get(\Magento\CustomerBalance\Helper\Data::class)->isEnabled()) {
            $this->_redirect('customer/account/');
            return;
        }

        $quote = $this->_objectManager->get(\Magento\Checkout\Model\Session::class)->getQuote();
        if ($quote->getUseCustomerBalance()) {
            $this->messageManager->addSuccess(__('The store credit payment has been removed from shopping cart.'));
            $quote->setUseCustomerBalance(false)->collectTotals()->save();
        } else {
            $this->messageManager->addError(__('You are not using store credit in your shopping cart.'));
        }

        $this->_redirect('checkout/cart');
    }
}
