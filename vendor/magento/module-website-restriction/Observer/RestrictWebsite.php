<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\WebsiteRestriction\Observer;

use Magento\Framework\Event\ObserverInterface;

class RestrictWebsite implements ObserverInterface
{
    /**
     * @var \Magento\WebsiteRestriction\Model\ConfigInterface
     */
    protected $config;

    /**
     * Core event manager proxy
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * Website Restrictor
     *
     * @var \Magento\WebsiteRestriction\Model\Restrictor
     */
    protected $restrictor;

    /**
     * @var \Magento\Framework\DataObject\Factory
     */
    protected $objectFactory;

    /**
     * @var \Magento\Framework\App\Response\Http
     */
    protected $response;

    /**
     * @param \Magento\WebsiteRestriction\Model\ConfigInterface $config
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\WebsiteRestriction\Model\Restrictor $restrictor
     * @param \Magento\Framework\DataObject\Factory $objectFactory
     * @param \Magento\Framework\App\Response\Http $response
     */
    public function __construct(
        \Magento\WebsiteRestriction\Model\ConfigInterface $config,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\WebsiteRestriction\Model\Restrictor $restrictor,
        \Magento\Framework\DataObject\Factory $objectFactory,
        \Magento\Framework\App\Response\Http $response
    ) {
        $this->config = $config;
        $this->eventManager = $eventManager;
        $this->restrictor = $restrictor;
        $this->objectFactory = $objectFactory;
        $this->response = $response;
    }

    /**
     * Implement website stub or private sales restriction
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /* @var $controller \Magento\Framework\App\Action\Action */
        $controller = $observer->getEvent()->getControllerAction();

        $dispatchResult = $this->objectFactory->create(['should_proceed' => true, 'customer_logged_in' => false]);
        $this->eventManager->dispatch(
            'websiterestriction_frontend',
            ['controller' => $controller, 'result' => $dispatchResult]
        );

        if (!$dispatchResult->getShouldProceed() || !$this->config->isRestrictionEnabled()) {
            return;
        }

        $this->restrictor->restrict(
            $observer->getEvent()->getRequest(),
            $this->response,
            $dispatchResult->getCustomerLoggedIn()
        );
    }
}
