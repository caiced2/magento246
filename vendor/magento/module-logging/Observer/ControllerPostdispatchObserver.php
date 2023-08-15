<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Logging\Observer;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer as Event;
use Magento\Framework\Event\ObserverInterface;
use Magento\Logging\Model\Processor;

/**
 * Class is responsible for logging actions performed in Backend Actions
 */
class ControllerPostdispatchObserver implements ObserverInterface
{
    /**
     * Instance of \Magento\Logging\Model\Logging
     *
     * @var Processor
     */
    protected $_processor;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param Processor $processor
     * @param RequestInterface $request
     */
    public function __construct(Processor $processor, RequestInterface $request)
    {
        $this->_processor = $processor;
        $this->request = $request ?? ObjectManager::getInstance()->get(RequestInterface::class);
    }

    /**
     * Log marked actions
     *
     * @param Event $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(Event $observer)
    {
        if ($this->request->isDispatched()) {
            $this->_processor->logAction();
        }
    }
}
