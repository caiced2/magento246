<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesArchive\Cron;

class ArchiveOrdersByCron
{
    /**
     * @var \Magento\SalesArchive\Model\Config
     */
    protected $_config;

    /**
     * @var \Magento\SalesArchive\Model\ArchiveFactory
     */
    protected $_archiveFactory;

    /**
     * @param \Magento\SalesArchive\Model\Config $config
     * @param \Magento\SalesArchive\Model\ArchiveFactory $archiveFactory
     */
    public function __construct(
        \Magento\SalesArchive\Model\Config $config,
        \Magento\SalesArchive\Model\ArchiveFactory $archiveFactory
    ) {
        $this->_config = $config;
        $this->_archiveFactory = $archiveFactory;
    }

    /**
     * Archive order by cron
     *
     * @return $this
     */
    public function execute()
    {
        if ($this->_config->isArchiveActive()) {
            $this->_archiveFactory->create()->archiveOrders();
        }

        return $this;
    }
}
