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
class CoreConfigSaveCommitAfterObserver implements ObserverInterface
{
    /**
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor
     */
    protected $_productRuleIndexerProcessor;

    /**
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule
     */
    protected $_productRuleIndexer;

    /**
     * @param \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor $productRuleIndexerProcessor
     * @param \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule $productRuleIndexer
     */
    public function __construct(
        \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor $productRuleIndexerProcessor,
        \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule $productRuleIndexer
    ) {
        $this->_productRuleIndexerProcessor = $productRuleIndexerProcessor;
        $this->_productRuleIndexer = $productRuleIndexer;
    }

    /**
     * Clear customer segment indexer if customer segment is on|off on backend
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($observer->getDataObject()->getPath() == 'customer/magento_customersegment/is_enabled' &&
            $observer->getDataObject()->isValueChanged()
        ) {
            $this->_productRuleIndexerProcessor->markIndexerAsInvalid();
        }
        return $this;
    }
}
