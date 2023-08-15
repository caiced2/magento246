<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Math\FloatComparator;
use Magento\GiftCardAccount\Api\GiftCardAccountRepositoryInterface;
use Magento\GiftCardAccount\Helper\Data as GiftCardAccountDataHelper;
use Magento\GiftCardAccount\Model\CommentsHistoryUpdater;
use Magento\GiftCardAccount\Model\Giftcardaccount;
use Magento\GiftCardAccount\Model\GiftcardaccountFactory;
use Magento\GiftCardAccount\Model\RefundStrategy;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;

class CreditmemoSaveAfter implements ObserverInterface
{
    /**
     * @var string
     */
    private static $messageRefundToGiftCard = "We refunded %1 to Gift Card (%2)";

    /**
     * @var string
     */
    private static $messageRefundToStoreCredit = "We refunded %1 to Store Credit from Gift Card (%2)";

    /**
     * @var GiftCardAccountDataHelper
     */
    private $giftCardAccountHelper;

    /**
     * @var GiftcardaccountFactory
     */
    private $giftCardAccountFactory;

    /**
     * @var GiftCardAccountRepositoryInterface
     */
    private $giftCardAccountRepository;

    /**
     * @var CommentsHistoryUpdater
     */
    private $historyUpdater;

    /**
     * @var RefundStrategy
     */
    private $refundStrategy;

    /**
     * @var FloatComparator
     */
    private $floatComparator;

    /**
     * @param GiftCardAccountDataHelper $giftCardAccountHelper
     * @param GiftcardaccountFactory $giftCardAccountFactory
     * @param GiftCardAccountRepositoryInterface $giftCardAccountRepository
     * @param CommentsHistoryUpdater $historyUpdater
     * @param RefundStrategy $refundStrategy
     * @param FloatComparator $floatComparator
     */
    public function __construct(
        GiftCardAccountDataHelper $giftCardAccountHelper,
        GiftcardaccountFactory $giftCardAccountFactory,
        GiftCardAccountRepositoryInterface $giftCardAccountRepository,
        CommentsHistoryUpdater $historyUpdater,
        RefundStrategy $refundStrategy,
        FloatComparator $floatComparator
    ) {
        $this->giftCardAccountHelper = $giftCardAccountHelper;
        $this->giftCardAccountFactory = $giftCardAccountFactory;
        $this->giftCardAccountRepository = $giftCardAccountRepository;
        $this->historyUpdater = $historyUpdater;
        $this->refundStrategy = $refundStrategy;
        $this->floatComparator = $floatComparator;
    }

    /**
     * Refunds process.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var Creditmemo $creditmemo */
        $creditmemo = $observer->getEvent()
            ->getCreditmemo();

        if (!$this->refundStrategy->isRefundToStoreCredit($creditmemo)) {
            $this->refundToGiftCardAccount($creditmemo);
            return;
        }

        $this->addRefundToStoreCreditComments($creditmemo);
    }

    /**
     * Refunds Gift Card amount to Store Credit comments.
     * Customer is not guest and Credit Store enabled.
     *
     * @param Creditmemo $creditmemo
     * @return void
     */
    private function addRefundToStoreCreditComments(Creditmemo $creditmemo)
    {
        $totalAmount = $creditmemo->getBaseGiftCardsAmount();
        if ($totalAmount <= 0) {
            return;
        }

        /** @var Order $order */
        $order = $creditmemo->getOrder();
        $cards = $this->getGiftCardList($order);
        $customerBalanceRefunded = $creditmemo->getBsCustomerBalTotalRefunded();
        foreach ($cards as $card) {
            if ($totalAmount <= 0 || $customerBalanceRefunded <= 0) {
                break;
            }

            $giftCardAmount = $this->getGiftCardAmount($card, $totalAmount);
            // admin can enter less amount than Gift Card has
            if ($this->floatComparator->greaterThan($giftCardAmount, $creditmemo->getBsCustomerBalTotalRefunded())) {
                $giftCardAmount = $customerBalanceRefunded;
            }

            $totalAmount -= $giftCardAmount;
            $customerBalanceRefunded -= $giftCardAmount;

            /** @var Giftcardaccount $account */
            $giftCardCode = $card[Giftcardaccount::CODE];
            $comment = __(
                self::$messageRefundToStoreCredit,
                $order->getBaseCurrency()->formatTxt($giftCardAmount),
                $giftCardCode
            );
            $this->historyUpdater->addCommentToHistory($order, $comment);
        }
    }

    /**
     * Refunds to Gift Card Account if customer is a guest or Credit Store disabled.
     *
     * @param Creditmemo $creditmemo
     * @return void
     */
    private function refundToGiftCardAccount(Creditmemo $creditmemo)
    {
        $totalAmount = $creditmemo->getBaseGiftCardsAmount();
        if ($totalAmount <= 0) {
            return;
        }

        /** @var Order $order */
        $order = $creditmemo->getOrder();
        $cards = $this->getGiftCardList($order);
        foreach ($cards as $card) {
            if ($this->floatComparator->greaterThanOrEqual(0, $totalAmount)) {
                break;
            }

            $giftCardAmount = $this->getGiftCardAmount($card, $totalAmount);
            $totalAmount -= $giftCardAmount;

            /** @var Giftcardaccount $account */
            $account = $this->giftCardAccountFactory->create();
            $giftCardCode = $card[Giftcardaccount::CODE];
            $account->loadByCode($giftCardCode);
            // The gift card was removed manually or by cron job
            if (!$account->getId()) {
                $account = $this->createGiftCardAccount($order, $card);
            }

            $account->revert($giftCardAmount);
            $comment = __(
                self::$messageRefundToGiftCard,
                $order->getBaseCurrency()->formatTxt($giftCardAmount),
                $giftCardCode
            );
            $this->historyUpdater->addCommentToHistory($order, $comment);
            $this->giftCardAccountRepository->save($account);
        }
    }

    /**
     * Creates and initializes new gift card account.
     *
     * @param Order $order
     * @param array $card
     * @return Giftcardaccount
     */
    private function createGiftCardAccount(Order $order, array $card)
    {
        /** @var Giftcardaccount $account */
        $newAccount = $this->giftCardAccountFactory
            ->create()
            ->setStatus(Giftcardaccount::STATUS_ENABLED)
            ->setWebsiteId($order->getStore()->getWebsiteId())
            ->setCode($card[Giftcardaccount::CODE])
            ->setBalance(0)
            ->setOrder($order);

        $this->giftCardAccountRepository->save($newAccount);

        return $newAccount;
    }

    /**
     * Gets all available Gift Cards for the provided order.
     *
     * @param Order $order
     * @return array|\Generator
     */
    private function getGiftCardList(Order $order)
    {
        $cards = $this->giftCardAccountHelper->getCards($order);
        if (is_array($cards)) {
            foreach ($cards as $card) {
                yield $card;
            }
        }

        return [];
    }

    /**
     * Gets Gift Card amount based on available card amount and total amount.
     *
     * @param array $card
     * @param float $totalAmount
     * @return float
     */
    private function getGiftCardAmount(array $card, float $totalAmount): float
    {
        // single Gift Card amount can be less than total Gift Cards amount used for order (multiple Gift Cards)
        return $this->floatComparator->greaterThanOrEqual($totalAmount, $card[Giftcardaccount::AMOUNT])
            ? $card[Giftcardaccount::AMOUNT]
            : $totalAmount;
    }
}
