<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AdvancedCheckout\Controller\Cart;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Action\Context;

class UpdateFailedItemOptions extends \Magento\AdvancedCheckout\Controller\Cart
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @codeCoverageIgnore
     * @param Context $context
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        Context $context,
        ProductRepositoryInterface $productRepository
    ) {
        parent::__construct($context);
        $this->productRepository = $productRepository;
    }

    /**
     * Update failed items options data and add it to cart
     *
     * @return void
     */
    public function execute()
    {
        $hasError = false;
        $id = (int)$this->getRequest()->getParam('id');
        $buyRequest = new \Magento\Framework\DataObject($this->getRequest()->getParams());
        try {
            $cart = $this->_getCart();

            $product = $this->productRepository->getById(
                $id,
                false,
                $this->_objectManager->get(\Magento\Store\Model\StoreManager::class)->getStore()->getId()
            );

            $cart->addProduct($product, $buyRequest)->save();

            $this->_getFailedItemsCart()->removeAffectedItem($this->getRequest()->getParam('sku'));

            if (!$this->_getSession()->getNoCartRedirect(true)) {
                if (!$cart->getQuote()->getHasError()) {
                    $productName = $this->_objectManager->get(
                        \Magento\Framework\Escaper::class
                    )->escapeHtml($product->getName());
                    $message = __('You added %1 to your shopping cart.', $productName);
                    $this->messageManager->addSuccess($message);
                }
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
            $hasError = true;
        } catch (\Exception $e) {
            $this->messageManager->addError(__('You cannot add a product.'));
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            $hasError = true;
        }

        if ($hasError) {
            $this->_redirect('checkout/cart/configureFailed', ['id' => $id, 'sku' => $buyRequest->getSku()]);
        } else {
            $this->_redirect('checkout/cart');
        }
    }

    /**
     * Get checkout session model instance
     *
     * @codeCoverageIgnore
     * @return \Magento\Checkout\Model\Session
     */
    protected function _getSession()
    {
        return $this->_objectManager->get(\Magento\Checkout\Model\Session::class);
    }

    /**
     * Get cart model instance
     *
     * @codeCoverageIgnore
     * @return \Magento\Checkout\Model\Cart
     */
    protected function _getCart()
    {
        return $this->_objectManager->get(\Magento\Checkout\Model\Cart::class);
    }
}
