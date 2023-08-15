<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Controller\Tracking;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Registry;
use Magento\Rma\Model\Shipping\Info;

/**
 * Class to load Rma Packages
 */
class Package extends \Magento\Rma\Controller\Tracking implements HttpGetActionInterface
{
    /**
     * @var Info
     */
    private $shippingInfo;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param FileFactory $fileResponseFactory
     * @param Info $shippingInfo
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        FileFactory $fileResponseFactory,
        Info $shippingInfo
    ) {
        parent::__construct($context, $coreRegistry, $fileResponseFactory);
        $this->shippingInfo = $shippingInfo;
    }

    /**
     * Popup package action.
     *
     * Shows package info if it's present, otherwise redirects to 404.
     *
     * @return void
     * @throws NotFoundException
     */
    public function execute()
    {
        /** @var \Magento\Rma\Model\Shipping $shippingLabel */
        $shippingLabel = $this->shippingInfo->loadPackage($this->getRequest()->getParam('hash'));
        if (empty($shippingLabel) || !$shippingLabel->getPackages()) {
            throw new NotFoundException(__('Page not found.'));
        }

        $this->_coreRegistry->register('rma_package_shipping', $shippingLabel);
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}
