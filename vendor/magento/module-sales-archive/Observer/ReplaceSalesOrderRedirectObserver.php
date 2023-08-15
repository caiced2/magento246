<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesArchive\Observer;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\SalesArchive\Model\Config;

class ReplaceSalesOrderRedirectObserver implements ObserverInterface
{
    /**
     * @var Config|null
     */
    private $config;

    /**
     * @param Config|null $config
     */
    public function __construct(Config $config = null)
    {
        $this->config = $config ?? ObjectManager::getInstance()->get(Config::class);
    }

    /**
     * Replaces redirects from orders grid to archive grid
     *
     * Redirects when mass action performed from archive orders list page
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        /**
         * @var \Magento\Backend\App\Action $controller
         */
        $controller = $observer->getControllerAction();
        /**
         * @var \Magento\Framework\App\ResponseInterface $response
         */
        $response = $controller->getResponse();
        /**
         * @var \Magento\Framework\App\RequestInterface $request
         */
        $request = $controller->getRequest();

        if (!$this->config->isArchiveActive() ||
            !$response->isRedirect() ||
            $request->getParam('origin') != 'archive'
        ) {
            return $this;
        }

        $ids = $request->getParam('order_ids');
        $createdFromOrders = !empty($ids);

        if ($createdFromOrders) {
            $response->setRedirect($controller->getUrl('sales/archive/orders'));
        } else {
            $response->setRedirect($controller->getUrl('sales/archive/shipments'));
        }

        return $this;
    }
}
