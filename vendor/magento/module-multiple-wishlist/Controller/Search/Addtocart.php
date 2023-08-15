<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MultipleWishlist\Controller\Search;

use Magento\Catalog\Model\Product\Exception as ProductException;
use Magento\Framework\Controller\ResultFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Addtocart extends \Magento\MultipleWishlist\Controller\Search
{
    /**
     * @var \Magento\Wishlist\Model\LocaleQuantityProcessor
     */
    protected $quantityProcessor;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Wishlist\Model\ItemFactory $itemFactory
     * @param \Magento\Wishlist\Model\WishlistFactory $wishlistFactory
     * @param \Magento\MultipleWishlist\Model\SearchFactory $searchFactory
     * @param \Magento\MultipleWishlist\Model\Search\Strategy\EmailFactory $strategyEmailFactory
     * @param \Magento\MultipleWishlist\Model\Search\Strategy\NameFactory $strategyNameFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Checkout\Model\Cart $checkoutCart
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Wishlist\Model\LocaleQuantityProcessor $quantityProcessor
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Wishlist\Model\ItemFactory $itemFactory,
        \Magento\Wishlist\Model\WishlistFactory $wishlistFactory,
        \Magento\MultipleWishlist\Model\SearchFactory $searchFactory,
        \Magento\MultipleWishlist\Model\Search\Strategy\EmailFactory $strategyEmailFactory,
        \Magento\MultipleWishlist\Model\Search\Strategy\NameFactory $strategyNameFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Checkout\Model\Cart $checkoutCart,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Wishlist\Model\LocaleQuantityProcessor $quantityProcessor
    ) {
        $this->quantityProcessor = $quantityProcessor;

        parent::__construct(
            $context,
            $coreRegistry,
            $itemFactory,
            $wishlistFactory,
            $searchFactory,
            $strategyEmailFactory,
            $strategyNameFactory,
            $checkoutSession,
            $checkoutCart,
            $customerSession,
            $localeResolver,
            $moduleManager
        );
    }

    /**
     * Add wishlist item to cart
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $messages = [];
        $addedItems = [];
        $notSalable = [];
        $hasOptions = [];

        /** @var \Magento\Checkout\Model\Cart $cart  */
        $cart = $this->_checkoutCart;
        $qtys = (array)$this->getRequest()->getParam('qty');
        $selected = (array)$this->getRequest()->getParam('selected');
        foreach ($qtys as $itemId => $qty) {
            if ($qty && isset($selected[$itemId])) {
                /** @var \Magento\Wishlist\Model\Item $item*/
                $item = $this->_itemFactory->create();
                try {
                    $item->loadWithOptions($itemId);
                    $item->unsProduct();
                    $qty = $this->quantityProcessor->process($qty);
                    if ($qty) {
                        $item->setQty($qty);
                    }
                    if ($item->addToCart($cart, false)) {
                        $addedItems[] = $item->getProduct();
                    }
                } catch (ProductException $e) {
                    $notSalable[] = $item;
                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    $messages[] = __('%1 for "%2"', trim($e->getMessage(), '.'), $item->getProduct()->getName());
                } catch (\Exception $e) {
                    $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
                    $messages[] = __('We can\'t add the item to shopping cart.');
                }
            }
        }

        $redirectUrl = '';
        if ($this->_objectManager->get(\Magento\Checkout\Helper\Cart::class)->getShouldRedirectToCart()) {
            $redirectUrl = $this->_objectManager->get(\Magento\Checkout\Helper\Cart::class)->getCartUrl();
        } elseif ($this->_redirect->getRefererUrl()) {
            $redirectUrl = $this->_redirect->getRefererUrl();
        }

        if ($notSalable) {
            $products = [];
            foreach ($notSalable as $item) {
                $products[] = '"' . $item->getProduct()->getName() . '"';
            }
            $messages[] = __('We can\'t add the following product(s) to shopping cart: %1.', join(', ', $products));
        }

        if ($hasOptions) {
            $products = [];
            foreach ($hasOptions as $item) {
                $products[] = '"' . $item->getProduct()->getName() . '"';
            }
            $messages[] = __(
                'Product(s) %1 have required options. Each product can only be added individually.',
                join(', ', $products)
            );
        }

        if ($messages) {
            if (count($messages) == 1 && count($hasOptions) == 1) {
                $item = $hasOptions[0];
                $redirectUrl = $item->getProductUrl();
            } else {
                foreach ($messages as $message) {
                    $this->messageManager->addError($message);
                }
            }
        }

        if ($addedItems) {
            $products = [];
            foreach ($addedItems as $product) {
                $products[] = '"' . $product->getName() . '"';
            }

            $this->messageManager->addSuccess(
                __('%1 product(s) have been added to shopping cart: %2.', count($addedItems), join(', ', $products))
            );
        }

        // save cart and collect totals
        $cart->save()->getQuote()->collectTotals();

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($redirectUrl);
        return $resultRedirect;
    }
}
