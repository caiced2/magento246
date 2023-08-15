<?php
/**
 * @category    Magento
 * @package     Magento_TargetRule
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Model\Indexer\TargetRule;

abstract class AbstractProcessor extends \Magento\Framework\Indexer\AbstractProcessor
{
    /**
     * State container
     *
     * @var Status\Container
     */
    protected $_statusContainer;

    /**
     * @param \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry
     * @param Status\Container $statusContainer
     */
    public function __construct(
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        \Magento\TargetRule\Model\Indexer\TargetRule\Status\Container $statusContainer
    ) {
        parent::__construct($indexerRegistry);
        $this->_statusContainer = $statusContainer;
    }

    /**
     * Get processor state container
     *
     * @return Status\Container
     */
    public function getStatusContainer()
    {
        return $this->_statusContainer;
    }

    /**
     * Is full reindex passed
     *
     * @return bool
     */
    public function isFullReindexPassed()
    {
        return $this->getStatusContainer()->isFullReindexPassed($this->getIndexerId());
    }

    /**
     * Set full reindex passed
     *
     * @return void
     */
    public function setFullReindexPassed()
    {
        $this->getIndexer()->getState()->setStatus(\Magento\Framework\Indexer\StateInterface::STATUS_VALID)->save();
        $this->getStatusContainer()->setFullReindexPassed($this->getIndexerId());
    }
}
