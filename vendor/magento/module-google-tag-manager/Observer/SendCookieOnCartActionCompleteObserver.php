<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GoogleTagManager\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\GoogleTagManager\Model\Config\TagManagerConfig;

/**
 * Observer for Cart Changes
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class SendCookieOnCartActionCompleteObserver implements ObserverInterface
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @var \Magento\GoogleTagManager\Helper\Data
     */
    protected $helper;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var Data
     */
    protected $jsonHelper;

    /**
     * @var CookieMetadataFactory
     */
    protected $cookieMetadataFactory;

    /**
     * @param \Magento\GoogleTagManager\Helper\Data $helper
     * @param Registry $registry
     * @param CookieManagerInterface $cookieManager
     * @param Data $jsonHelper
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param RequestInterface $httpRequest
     */
    public function __construct(
        \Magento\GoogleTagManager\Helper\Data $helper,
        Registry $registry,
        CookieManagerInterface $cookieManager,
        Data $jsonHelper,
        CookieMetadataFactory $cookieMetadataFactory,
        RequestInterface $httpRequest
    ) {
        $this->helper = $helper;
        $this->registry = $registry;
        $this->cookieManager = $cookieManager;
        $this->jsonHelper = $jsonHelper;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->request = $httpRequest;
    }

    /**
     * Send cookies after cart action
     *
     * @param Observer $observer
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(Observer $observer)
    {
        if (!$this->helper->isTagManagerAvailable()) {
            return $this;
        }
        $productsToAdd = $this->registry->registry('GoogleTagManager_products_addtocart');
        $publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata()
            ->setDuration(3600)
            ->setPath('/')
            ->setHttpOnly(false)
            ->setSameSite('Strict');

        if (!empty($productsToAdd) && !$this->request->isXmlHttpRequest()) {
            $this->cookieManager->setPublicCookie(
                TagManagerConfig::GOOGLE_ANALYTICS_COOKIE_NAME,
                rawurlencode(json_encode($productsToAdd)),
                $publicCookieMetadata
            );
        }
        $productsToRemove = $this->registry->registry('GoogleTagManager_products_to_remove');
        if (!empty($productsToRemove && !$this->request->isXmlHttpRequest())) {
            $this->cookieManager->setPublicCookie(
                TagManagerConfig::GOOGLE_ANALYTICS_COOKIE_REMOVE_FROM_CART,
                rawurlencode($this->jsonHelper->jsonEncode($productsToRemove)),
                $publicCookieMetadata
            );
        }
        return $this;
    }
}
