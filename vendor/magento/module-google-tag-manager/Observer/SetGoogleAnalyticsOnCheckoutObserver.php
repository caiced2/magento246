<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GoogleTagManager\Observer;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\GoogleTagManager\Block\ListJson;
use Magento\GoogleTagManager\Helper\Data;

/**
 * Set Gtag on checkout observer
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class SetGoogleAnalyticsOnCheckoutObserver implements ObserverInterface
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param Data $helper
     * @param Session $checkoutSession
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Data $helper,
        Session $checkoutSession,
        SerializerInterface $jsonHelper,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->helper = $helper;
        $this->checkoutSession = $checkoutSession;
        $this->jsonHelper = $jsonHelper;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Adds to checkout shipping address step and review step GA block with related data
     *
     * Fired by controller_action_postdispatch_checkout event
     *
     * @param Observer $observer
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute(Observer $observer)
    {
        if (!$this->helper->isTagManagerAvailable()) {
            return $this;
        }
        /** @var \Magento\Checkout\Controller\Onepage $controllerAction */
        $controllerAction = $observer->getEvent()->getControllerAction();
        $action = $controllerAction->getRequest()->getActionName();
        $body = [];
        switch ($action) {
            case 'saveBilling':
                $encodedBody = $controllerAction->getResponse()->getBody();
                if ($encodedBody) {
                    $body = $this->jsonHelper->unserialize($encodedBody);
                }

                if ($body['goto_section'] == 'shipping') {
                    $shippingBlock = $controllerAction->getLayout()
                        ->createBlock(ListJson::class)
                        ->setTemplate('Magento_GoogleTagManager::checkout/step.phtml')
                        ->setStepName('shipping');
                    $body['update_section']['name'] = 'shipping';
                    $body['update_section']['html'] = '<div id="checkout-shipping-load"></div>'
                        . $shippingBlock->toHtml();
                    $controllerAction->getResponse()->setBody($this->jsonHelper->serialize($body));
                }
                break;
        }

        return $this;
    }
}
