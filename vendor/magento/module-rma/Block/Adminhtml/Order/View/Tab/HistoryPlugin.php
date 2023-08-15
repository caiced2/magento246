<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Block\Adminhtml\Order\View\Tab;

use Magento\Rma\Model\ResourceModel\Rma\Collection;
use Magento\Rma\Model\ResourceModel\Rma\Status\History\CollectionFactory as HistoryCollectionFactory;
use Magento\Rma\Model\Rma\Source\Status;
use Magento\Rma\Model\Rma\Status\History as StatusHistory;
use Magento\Sales\Block\Adminhtml\Order\View\Tab\History;

/**
 * Plugin for \Magento\Sales\Block\Adminhtml\Order\View\Tab\History
 */
class HistoryPlugin
{
    /**
     * @var Collection
     */
    private $rmaCollection;

    /**
     * @var HistoryCollectionFactory
     */
    private $historyCollectionFactory;

    /**
     * @param Collection $rmaCollection
     * @param HistoryCollectionFactory $historyCollectionFactory
     */
    public function __construct(Collection $rmaCollection, HistoryCollectionFactory $historyCollectionFactory)
    {
        $this->rmaCollection = $rmaCollection;
        $this->historyCollectionFactory = $historyCollectionFactory;
    }

    /**
     * Add Returns to Order Comments history
     *
     * @param History $subject
     * @param array $history
     * @return array
     */
    public function afterGetFullHistory(History $subject, array $history)
    {
        $collection = $this->rmaCollection->addFieldToFilter('order_id', $subject->getOrder()->getId())->load();

        if (!$collection->getSize()) {
            return $history;
        }

        $creationSystemComment = StatusHistory::getSystemCommentByStatus(Status::STATE_PENDING);

        /** @var $historyCollection \Magento\Rma\Model\ResourceModel\Rma\Status\History\Collection */
        $historyCollection = $this->historyCollectionFactory->create();
        $rmaIds = [];
        $incrementIds = [];
        
        /** @var \Magento\Rma\Model\Rma $rma */
        foreach ($collection as $rma) {
            $rmaIds[] = $rma->getId();
            $incrementIds[$rma->getId()] = $rma->getIncrementId();
        }

        $historyCollection->addFieldToFilter('rma_entity_id', ['in' => $rmaIds]);
        $comments = $historyCollection->getItems();

        foreach ($comments as $comment) {
            if ($comment->getComment() == $creationSystemComment) {
                $history[] = [
                    'title' => sprintf('Return #%s created', $incrementIds[$comment->getRmaEntityId()]),
                    'notified' => $comment->getIsCustomerNotified(),
                    'comment' => '',
                    'created_at' => $comment->getCreatedAtDate(),
                ];
            }
        }

        usort($history, [get_class($subject), 'sortHistoryByTimestamp']);

        return $history;
    }
}
