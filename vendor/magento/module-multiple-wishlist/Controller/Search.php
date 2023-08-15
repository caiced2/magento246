<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MultipleWishlist\Controller;

use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Filter\LocalizedToNormalized;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Module\Manager;
use Magento\Framework\Registry;
use Magento\MultipleWishlist\Model\Search\Strategy\EmailFactory;
use Magento\MultipleWishlist\Model\Search\Strategy\NameFactory;
use Magento\MultipleWishlist\Model\SearchFactory;
use Magento\Wishlist\Model\ItemFactory;
use Magento\Wishlist\Model\WishlistFactory;

/**
 * Multiple wishlist frontend search controller
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class Search extends Action
{
    /**
     * Localization filter
     *
     * @var LocalizedToNormalized
     */
    protected $_localFilter;

    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var ResolverInterface
     */
    protected $_localeResolver;

    /**
     * @var CustomerSession
     */
    protected $_customerSession;

    /**
     * @var Cart
     */
    protected $_checkoutCart;

    /**
     * @var CheckoutSession
     */
    protected $_checkoutSession;

    /**
     * @var NameFactory
     */
    protected $_strategyNameFactory;

    /**
     * @var EmailFactory
     */
    protected $_strategyEmailFactory;

    /**
     * @var SearchFactory
     */
    protected $_searchFactory;

    /**
     * @var WishlistFactory
     */
    protected $_wishlistFactory;

    /**
     * Item model factory
     *
     * @var ItemFactory
     */
    protected $_itemFactory;

    /**
     * @var Manager
     */
    protected $moduleManager;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param ItemFactory $itemFactory
     * @param WishlistFactory $wishlistFactory
     * @param SearchFactory $searchFactory
     * @param EmailFactory $strategyEmailFactory
     * @param NameFactory $strategyNameFactory
     * @param CheckoutSession $checkoutSession
     * @param Cart $checkoutCart
     * @param CustomerSession $customerSession
     * @param ResolverInterface $localeResolver
     * @param Manager $moduleManager
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        ItemFactory $itemFactory,
        WishlistFactory $wishlistFactory,
        SearchFactory $searchFactory,
        EmailFactory $strategyEmailFactory,
        NameFactory $strategyNameFactory,
        CheckoutSession $checkoutSession,
        Cart $checkoutCart,
        CustomerSession $customerSession,
        ResolverInterface $localeResolver,
        Manager $moduleManager
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_itemFactory = $itemFactory;
        $this->_wishlistFactory = $wishlistFactory;
        $this->_searchFactory = $searchFactory;
        $this->_strategyEmailFactory = $strategyEmailFactory;
        $this->_strategyNameFactory = $strategyNameFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_checkoutCart = $checkoutCart;
        $this->_customerSession = $customerSession;
        $this->_localeResolver = $localeResolver;
        $this->moduleManager = $moduleManager;
        parent::__construct($context);
    }

    /**
     * Check if multiple wishlist is enabled on current store before all other actions
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws NotFoundException
     */
    public function dispatch(RequestInterface $request)
    {
        if (!$this->moduleManager->isEnabled('Magento_MultipleWishlist')) {
            throw new NotFoundException(__('Page not found.'));
        }
        return parent::dispatch($request);
    }
}
