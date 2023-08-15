<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\SalesArchive\Plugin;

use Magento\Framework\Event\Observer;
use Magento\Sales\Observer\GridSyncInsertObserver;
use Magento\SalesArchive\Model\ArchivalList;
use Magento\SalesArchive\Model\ArchiveFactory;

class GridSyncInsertObserverPlugin
{
    /**
     * @var ArchivalList
     */
    private $archivalList;

    /**
     * @var ArchiveFactory
     */
    private $archiveFactory;

    /**
     * @param ArchiveFactory $archiveFactory
     * @param ArchivalList $archivalList
     */
    public function __construct(
        ArchiveFactory $archiveFactory,
        ArchivalList $archivalList
    ) {
        $this->archiveFactory = $archiveFactory;
        $this->archivalList = $archivalList;
    }

    /**
     * Modify the behavior of the grid sync observer in cases when the order is archived
     *
     * @param GridSyncInsertObserver $subject
     * @param \Closure $proceed
     * @param Observer $observer
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        GridSyncInsertObserver $subject,
        \Closure $proceed,
        Observer $observer
    ) {
        $object = $observer->getObject();
        $archive = $this->archiveFactory->create();
        $archiveEntity = $this->archivalList->getEntityByObject($object->getResource());
        $id = $object->getId();
        $idsInArchive = $archive->getIdsInArchive($archiveEntity, [$id]);

        if (!in_array($id, $idsInArchive)) {
            $proceed($observer);
        }
    }
}
