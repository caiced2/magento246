<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SalesArchive\Observer;

use Magento\Framework\Event\ObserverInterface;

class GridSyncInsertObserver implements ObserverInterface
{
    /**
     * Archived Entity grid model.
     * You can put sales related entity to archive(Orders, Invoices, Shipments, Credit memos)
     * To enable Sales Archive functionality:
     * Stores>Configuration>SALES>Sales>Orders, Invoices, Shipments, Credit Memos Archiving > Enable Archiving = Yes
     *
     * @var \Magento\Sales\Model\ResourceModel\GridInterface
     */
    private $entityGrid;

    /**
     * Global configuration storage.
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $globalConfig;

    /**
     * @var \Magento\SalesArchive\Model\Config
     */
    private $config;

    /**
     * @var \Magento\SalesArchive\Model\ArchivalList
     */
    private $archivalList;

    /**
     * @var \Magento\SalesArchive\Model\ArchiveFactory
     */
    private $archiveFactory;

    /**
     * GridSyncInsertObserver constructor.
     * @param \Magento\Sales\Model\ResourceModel\GridInterface $entityGrid
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $globalConfig
     * @param \Magento\SalesArchive\Model\Config $config
     * @param \Magento\SalesArchive\Model\ArchiveFactory $archiveFactory
     * @param \Magento\SalesArchive\Model\ArchivalList $archivalList
     */
    public function __construct(
        \Magento\Sales\Model\ResourceModel\GridInterface $entityGrid,
        \Magento\Framework\App\Config\ScopeConfigInterface $globalConfig,
        \Magento\SalesArchive\Model\Config $config,
        \Magento\SalesArchive\Model\ArchiveFactory $archiveFactory,
        \Magento\SalesArchive\Model\ArchivalList $archivalList
    ) {
        $this->entityGrid = $entityGrid;
        $this->globalConfig = $globalConfig;
        $this->config = $config;
        $this->archiveFactory = $archiveFactory;
        $this->archivalList = $archivalList;
    }

    /**
     * Handles synchronous insertion of the new/updated entity into
     * corresponding archived grid on certain events.
     *
     * Used in the next events:
     *
     *  - sales_order_process_relation
     *  - sales_order_invoice_process_relation
     *  - sales_order_shipment_process_relation
     *  - sales_order_creditmemo_process_relation
     *
     * Works only if asynchronous grid indexing is disabled
     * in global settings.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        if (!$this->config->isArchiveActive()) {
            return $this;
        }
        $object = $observer->getObject();
        $archive = $this->archiveFactory->create();
        $archiveEntity = $this->archivalList->getEntityByObject($object->getResource());

        if (!$archiveEntity) {
            return $this;
        }

        $id = $object->getId();
        $ids = [$id];
        $idsInArchive = $archive->getIdsInArchive($archiveEntity, $ids);
        // Exclude archive records from default grid rows update
        $ids = array_diff($ids, $idsInArchive);
        // Check for newly created shipments, creditmemos, invoices
        if ($archiveEntity != \Magento\SalesArchive\Model\ArchivalList::ORDER && !empty($ids)) {
            $relatedIds = $archive->getRelatedIds($archiveEntity, $ids);
            $idsInArchive = array_merge($idsInArchive, $relatedIds);
        }

        if (!empty($idsInArchive) && !$this->globalConfig->getValue('dev/grid/async_indexing')) {
            $this->entityGrid->refresh($id);
        }
    }
}
