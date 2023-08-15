<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PricePermissions\Observer;

use Magento\Backend\Block\Template;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Backend\Block\Widget\Grid;
use Magento\Framework\Event\ObserverInterface;

class AdminControllerPredispatchObserver implements ObserverInterface
{
    /**
     * Price permissions data
     *
     * @var \Magento\PricePermissions\Helper\Data
     */
    protected $_pricePermData = null;

    /**
     * Backend authorization session
     *
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $_authSession;

    /**
     * @var ObserverData
     */
    protected $observerData;

    /**
     * @param \Magento\PricePermissions\Helper\Data $pricePermData
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param ObserverData $observerData
     * @param array $data
     */
    public function __construct(
        \Magento\PricePermissions\Helper\Data $pricePermData,
        \Magento\Backend\Model\Auth\Session $authSession,
        ObserverData $observerData,
        array $data = []
    ) {
        $this->_pricePermData = $pricePermData;
        $this->_authSession = $authSession;
        $this->observerData = $observerData;
        if (isset($data['can_edit_product_price']) && false === $data['can_edit_product_price']) {
            $this->observerData->setCanEditProductPrice(false);
        }
        if (isset($data['can_read_product_price']) && false === $data['can_read_product_price']) {
            $this->observerData->setCanReadProductPrice(false);
        }
        if (isset($data['can_edit_product_status']) && false === $data['can_edit_product_status']) {
            $this->observerData->setCanEditProductStatus(false);
        }
        if (isset($data['default_product_price_string'])) {
            $this->observerData->setDefaultProductPriceString($data['default_product_price_string']);
        }
    }

    /**
     * Reinit stores only with allowed scopes
     *
     * @param EventObserver $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        // load role with true websites and store groups
        if ($this->_authSession->isLoggedIn() && $this->_authSession->getUser()->getRole()) {
            // Set all necessary flags
            /** @var $helper \Magento\PricePermissions\Helper\Data */
            $helper = $this->_pricePermData;
            $this->observerData->setCanEditProductPrice($helper->getCanAdminEditProductPrice());
            $this->observerData->setCanReadProductPrice($helper->getCanAdminReadProductPrice());
            $this->observerData->setCanEditProductStatus($helper->getCanAdminEditProductStatus());
            // Retrieve value of the default product price
            $this->observerData->setDefaultProductPriceString($helper->getDefaultProductPriceString());
        }
    }
}
