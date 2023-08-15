<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GiftCardAccount\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\GiftCardAccount\Api\GiftCardAccountRepositoryInterface;
use Magento\GiftCardAccount\Api\GiftCardRedeemerInterface;
use Magento\GiftCardAccount\Model\Spi\GiftCardAccountManagerInterface;
use Magento\CustomerBalance\Helper\Data as CustomerBalanceHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\CustomerBalance\Model\BalanceFactory;
use Magento\CustomerBalance\Model\Balance;

/**
 * @inheritDoc
 */
class Redeemer implements GiftCardRedeemerInterface
{
    /**
     * @var GiftCardAccountManagerInterface
     */
    private $manager;

    /**
     * @var CustomerBalanceHelper
     */
    private $balanceHelper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepo;

    /**
     * @var BalanceFactory
     */
    private $balanceFactory;

    /**
     * @var GiftCardAccountRepositoryInterface
     */
    private $repo;

    /**
     * @param GiftCardAccountManagerInterface $manager
     * @param CustomerBalanceHelper $balanceHelper
     * @param StoreManagerInterface $storeManager
     * @param CustomerRepositoryInterface $customerRepo
     * @param BalanceFactory $balanceFactory
     * @param GiftCardAccountRepositoryInterface $repo
     */
    public function __construct(
        GiftCardAccountManagerInterface $manager,
        CustomerBalanceHelper $balanceHelper,
        StoreManagerInterface $storeManager,
        CustomerRepositoryInterface $customerRepo,
        BalanceFactory $balanceFactory,
        GiftCardAccountRepositoryInterface $repo
    ) {
        $this->manager = $manager;
        $this->balanceHelper = $balanceHelper;
        $this->storeManager = $storeManager;
        $this->customerRepo = $customerRepo;
        $this->balanceFactory = $balanceFactory;
        $this->repo = $repo;
    }

    /**
     * @inheritDoc
     *
     * For storefront $forCustomerId must be enforced to be logged-in customer ID in presentation area
     * (action, endpoint declaration etc).
     */
    public function redeem(string $code, int $forCustomerId): void
    {
        if (!$this->balanceHelper->isEnabled()) {
            throw new CouldNotSaveException(__("You can't redeem a gift card now."));
        }

        //Validating the card and the customer
        try {
            /** @var Giftcardaccount $card */
            $card = $this->manager->requestByCode(
                $code,
                $websiteId = (int)$this->storeManager->getWebsite()->getId(),
                0.0,
                true,
                true
            );
        } catch (NoSuchEntityException|\InvalidArgumentException $exception) {
            throw new NoSuchEntityException(__('Gift card not found'));
        }
        $card->setIsRedeemed(true);
        if ($card->getIsRedeemable() != Giftcardaccount::REDEEMABLE) {
            throw new CouldNotSaveException(__('Gift card account is not redeemable.'));
        }
        try {
            $this->customerRepo->getById($forCustomerId);
        } catch (NoSuchEntityException $exception) {
            throw new CouldNotSaveException(__('Cannot find the customer to update balance'));
        }

        //Saving the balance and the card.
        $additionalInfo = __('Gift Card Redeemed: %1. For customer #%2.', $code, $forCustomerId);
        /** @var Balance $balance */
        $balance = $this->balanceFactory->create();
        $balance->setCustomerId(
            $forCustomerId
        )->setWebsiteId(
            $websiteId
        )->setAmountDelta(
            $card->getBalance()
        )->setNotifyByEmail(
            false
        )->setUpdatedActionAdditionalInfo(
            $additionalInfo
        );
        $balance->save();

        $card->setHistoryAction(
            History::ACTION_REDEEMED
        )->setBalance(
            0
        )->setCustomerId(
            $forCustomerId
        )->setBalanceDelta(
            -$card->getBalance()
        );
        $this->repo->save($card);
    }
}
