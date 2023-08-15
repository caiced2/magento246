<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdminGws\Observer;

use Magento\Framework\Event\ObserverInterface;

class AddSalesSaleCollectionStoreFilter implements ObserverInterface
{
    /**
     * @var \Magento\AdminGws\Model\Collections
     */
    private $collections;

    /**
     * @param \Magento\AdminGws\Model\Collections $collections
     */
    public function __construct(
        \Magento\AdminGws\Model\Collections $collections
    ) {
        $this->collections = $collections;
    }

    /**
     * Filter sales collection by allowed stores
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->collections->addSalesSaleCollectionStoreFilter($observer);
    }
}
