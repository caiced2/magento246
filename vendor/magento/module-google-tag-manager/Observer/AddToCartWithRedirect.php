<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GoogleTagManager\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Framework\Exception\InputException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\GoogleTagManager\Model\Config\TagManagerConfig;
use Magento\Store\Model\ScopeInterface;
use Magento\GoogleTagManager\Helper\Data as DataHelper;
use Magento\Catalog\Model\Product;

/**
 * Observer for handling GTM-related cookie
 *
 * Used for case when redirect to shopping cart is turned on
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class AddToCartWithRedirect implements ObserverInterface
{
    /**
     * @var DataHelper
     */
    private $helper;

    /**
     * @var CookieManagerInterface
     */
    private $cookieManager;

    /**
     * @var CookieMetadataFactory
     */
    private $cookieMetadataFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param DataHelper $helper
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        DataHelper $helper,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->helper = $helper;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Send cookies after add to cart action
     *
     * @param Observer $observer
     * @return void
     * @throws FailureToSendException If cookie couldn't be sent to the browser.
     * @throws CookieSizeLimitReachedException Thrown when the cookie is too big to store any additional data.
     * @throws InputException If the cookie name is empty or contains invalid characters.
     */
    public function execute(Observer $observer)
    {
        if ((!$this->helper->isTagManagerAvailable()) || !$this->isRedirectToCartEnabled()) {
            return;
        }

        /** @var Product $product */
        $product = $observer->getEvent()->getProduct();
        $productsToAdd = [
            [
                'sku' => $product->getSku(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
                'qty' => $product->getQty(),
            ]
        ];

        /** @var PublicCookieMetadata $publicCookieMetadata */
        $publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata()
            ->setDuration(3600)
            ->setPath('/')
            ->setHttpOnly(false)
            ->setSameSite('Strict');

        $this->cookieManager->setPublicCookie(
            TagManagerConfig::GOOGLE_ANALYTICS_COOKIE_NAME,
            \rawurlencode(\json_encode($productsToAdd)),
            $publicCookieMetadata
        );
    }

    /**
     * Is redirect should be performed after the product was added to cart.
     *
     * @return bool
     */
    private function isRedirectToCartEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            'checkout/cart/redirect_to_cart',
            ScopeInterface::SCOPE_STORE
        );
    }
}
