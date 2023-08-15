<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PricePermissions\Block\Adminhtml\ConfigurableProduct\Product\Edit\Tab\Variations\Plugin;

class Config
{
    /**
     * Backend authorization session
     *
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $authSession;

    /**
     * Helper data
     *
     * @var \Magento\PricePermissions\Helper\Data
     */
    protected $pricePermData;

    /**
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\PricePermissions\Helper\Data $pricePermData
     */
    public function __construct(
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\PricePermissions\Helper\Data $pricePermData
    ) {
        $this->pricePermData = $pricePermData;
        $this->authSession = $authSession;
    }

    /**
     * Check edit and read configurable price permissions and set it to false if needed
     *
     * @param \Magento\ConfigurableProduct\Block\Adminhtml\Product\Edit\Tab\Variations\Config $subject
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeToHtml(
        \Magento\ConfigurableProduct\Block\Adminhtml\Product\Edit\Tab\Variations\Config $subject
    ) {
        $canEditProductPrice = false;
        $canReadProductPrice = false;
        if ($this->authSession->isLoggedIn() && $this->authSession->getUser()->getRole()) {
            $canEditProductPrice = $this->pricePermData->getCanAdminEditProductPrice();
            $canReadProductPrice = $this->pricePermData->getCanAdminReadProductPrice();
        }
        if (!$canEditProductPrice) {
            $subject->setCanEditPrice(false);
        }
        if (!$canReadProductPrice) {
            $subject->setCanReadPrice(false);
        }
    }
}
