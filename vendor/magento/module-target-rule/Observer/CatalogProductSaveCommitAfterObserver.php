<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\TargetRule\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * TargetRule observer
 *
 */
class CatalogProductSaveCommitAfterObserver implements ObserverInterface
{
    /**
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor
     */
    protected $_productRuleIndexerProcessor;

    /**
     * @param \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor $productRuleIndexerProcessor
     */
    public function __construct(
        \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor $productRuleIndexerProcessor
    ) {
        $this->_productRuleIndexerProcessor = $productRuleIndexerProcessor;
    }

    /**
     * Process event on 'save_commit_after' event
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var $product \Magento\Catalog\Model\Product */
        $product = $observer->getEvent()->getProduct();

        $this->_productRuleIndexerProcessor->reindexRow($product->getId());
    }
}
