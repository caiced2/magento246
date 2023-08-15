<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedSalesRule\Observer;

use Magento\Framework\Event\ObserverInterface;

class ReindexSalesRule implements ObserverInterface
{
    /**
     * @var \Magento\AdvancedSalesRule\Model\Indexer\SalesRule\Processor
     */
    protected $indexerProcessor;

    /**
     * @param \Magento\AdvancedSalesRule\Model\Indexer\SalesRule\Processor $indexerProcessor
     */
    public function __construct(
        \Magento\AdvancedSalesRule\Model\Indexer\SalesRule\Processor $indexerProcessor
    ) {
        $this->indexerProcessor = $indexerProcessor;
    }

    /**
     * Reindex updated sales rules
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return \Magento\Banner\Observer\PrepareRuleSave
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $ids = $observer->getData('entity_ids');
        $this->indexerProcessor->reindexList($ids);
    }
}
