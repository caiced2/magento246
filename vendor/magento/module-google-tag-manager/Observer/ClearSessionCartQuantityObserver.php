<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GoogleTagManager\Observer;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\GoogleTagManager\Helper\Data;
use Magento\GoogleTagManager\Model\Config\TagManagerConfig;

/**
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class ClearSessionCartQuantityObserver implements ObserverInterface
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
     * @param Data $helper
     * @param Session $checkoutSession
     */
    public function __construct(
        Data $helper,
        Session $checkoutSession
    ) {
        $this->helper = $helper;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * When shopping cart is cleaned the remembered quantities in a session needs also to be deleted
     *
     * Fired by controller_action_postdispatch_checkout_cart_updatePost event
     *
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        if (!$this->helper->isTagManagerAvailable()) {
            return $this;
        }
        /** @var Action $controllerAction */
        $controllerAction = $observer->getEvent()->getControllerAction();
        $updateAction = (string)$controllerAction->getRequest()->getParam('update_cart_action');
        if ($updateAction == 'empty_cart') {
            $this->checkoutSession->unsetData(
                TagManagerConfig::PRODUCT_QUANTITIES_BEFORE_ADDTOCART
            );
        }

        return $this;
    }
}
