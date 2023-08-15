<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Logging\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Api\Data\GroupInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;

class ModelLoadAfterObserver implements ObserverInterface
{
    /**
     * Instance of \Magento\Logging\Model\Logging
     *
     * @var \Magento\Logging\Model\Processor
     */
    protected $_processor;

    /**
     * @param \Magento\Logging\Model\Processor $processor
     */
    public function __construct(
        \Magento\Logging\Model\Processor $processor
    ) {
        $this->_processor = $processor;
    }

    /**
     * Model after load observer.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $object = $observer->getEvent()->getObject();

        // skip store instances in order to avoid of circular dependence
        if ($object instanceof StoreInterface
            || $object instanceof WebsiteInterface
            || $object instanceof GroupInterface
        ) {
            return;
        }
        $this->_processor->modelActionAfter($object, 'view');
    }
}
