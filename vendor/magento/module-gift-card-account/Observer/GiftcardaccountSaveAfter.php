<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\GiftCardAccount\Model\HistoryFactory;
use Magento\GiftCardAccount\Model\ResourceModel\History as HistoryResourceModel;

class GiftcardaccountSaveAfter implements ObserverInterface
{
    /**
     * @var HistoryFactory
     */
    private $accountHistoryFactory;

    /**
     * @var HistoryResourceModel
     */
    private $historyResourceModel;

    /**
     * @param HistoryFactory $historyFactory
     * @param HistoryResourceModel $historyResourceModel
     */
    public function __construct(
        HistoryFactory $historyFactory,
        HistoryResourceModel $historyResourceModel
    ) {
        $this->accountHistoryFactory = $historyFactory;
        $this->historyResourceModel = $historyResourceModel;
    }

    /**
     * Save history on gift card account model save event
     * used for event: magento_giftcardaccount_save_after
     *
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        $giftCardAccount = $observer->getEvent()->getGiftcardaccount();

        if ($giftCardAccount->hasHistoryAction()) {
            $accountHistory = $this->accountHistoryFactory->create();
            $accountHistory->setGiftcardaccount($giftCardAccount);
            $this->historyResourceModel->save($accountHistory);
        }

        return $this;
    }
}
