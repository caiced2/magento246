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
class CatalogProductDeleteCommitAfterObserver implements ObserverInterface
{
    /**
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule
     */
    protected $_productRuleIndexer;

    /**
     * @param \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule $productRuleIndexer
     */
    public function __construct(
        \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule $productRuleIndexer
    ) {
        $this->_productRuleIndexer = $productRuleIndexer;
    }

    /**
     * Process event on 'delete_commit_after' event
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var $product \Magento\Catalog\Model\Product */
        $product = $observer->getEvent()->getProduct();

        $this->_productRuleIndexer->cleanAfterProductDelete($product->getId());
    }
}
