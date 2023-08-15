<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GiftCardAccount\Model\Service;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ObjectManager;
use Magento\GiftCardAccount\Api\GiftCardAccountRepositoryInterface;
use Magento\GiftCardAccount\Model\Giftcardaccount as GiftCardAccount;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\GiftCardAccount\Api\Exception\TooManyAttemptsException;
use Magento\GiftCardAccount\Model\Spi\GiftCardAccountManagerInterface;
use Magento\GiftCardAccount\Api\GiftCardAccountManagementInterface;
use Magento\CustomerBalance\Helper\Data as CustomerBalanceHelper;

/**
 * Class GiftCardAccountManagement
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GiftCardAccountManagement implements GiftCardAccountManagementInterface
{
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Magento\GiftCardAccount\Helper\Data
     */
    protected $giftCardHelper;

    /**
     * @var \Magento\GiftCardAccount\Model\GiftcardaccountFactory
     */
    protected $giftCardAccountFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var GiftCardAccountRepositoryInterface
     */
    private $repo;

    /**
     * @var CustomerBalanceHelper
     */
    private $customerBalanceHelper;

    /**
     * @var GiftCardAccountManagerInterface
     */
    private $manager;

    /**
     * @var SearchCriteriaBuilder
     */
    private $criteriaBuilder;

    /**
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\GiftCardAccount\Helper\Data $giftCardHelper
     * @param \Magento\GiftCardAccount\Model\GiftcardaccountFactory $giftCardAccountFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param GiftCardAccountManagerInterface|null $manager
     * @param GiftCardAccountRepositoryInterface|null $repo
     * @param CustomerBalanceHelper|null $customerBalance
     * @param SearchCriteriaBuilder|null $criteriaBuilder
     */
    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\GiftCardAccount\Helper\Data $giftCardHelper,
        \Magento\GiftCardAccount\Model\GiftcardaccountFactory $giftCardAccountFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        ?GiftCardAccountManagerInterface $manager = null,
        ?GiftCardAccountRepositoryInterface $repo = null,
        ?CustomerBalanceHelper $customerBalance = null,
        ?SearchCriteriaBuilder $criteriaBuilder = null
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->giftCardHelper = $giftCardHelper;
        $this->giftCardAccountFactory = $giftCardAccountFactory;
        $this->storeManager = $storeManager;
        $this->manager = $manager
            ?? ObjectManager::getInstance()
                ->get(GiftCardAccountManagerInterface::class);
        $this->repo = $repo ?? ObjectManager::getInstance()->get(GiftCardAccountRepositoryInterface::class);
        $this->customerBalanceHelper = $customerBalance
            ?? ObjectManager::getInstance()->get(CustomerBalanceHelper::class);
        $this->criteriaBuilder = $criteriaBuilder ?? ObjectManager::getInstance()->get(SearchCriteriaBuilder::class);
    }

    /**
     * @inheritDoc
     */
    public function deleteByQuoteId($cartId, $giftCardCode)
    {
        /** @var  \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        if (!$quote->getItemsCount()) {
            throw new CouldNotDeleteException(__('The "%1" Cart doesn\'t contain products.', $cartId));
        }

        try {
            $found = $this->repo->getList(
                $this->criteriaBuilder->addFilter('code', $giftCardCode)->setPageSize(1)->create()
            )->getItems();
            /** @var GiftCardAccount $giftCard */
            $giftCard = array_pop($found);
            $giftCard->removeFromCart(true, $quote);
        } catch (\Throwable $e) {
            throw new CouldNotDeleteException(__("The gift card couldn't be deleted from the quote."));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getListByQuoteId($cartId)
    {
        /** @var  \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        $giftCards = $this->giftCardHelper->getCards($quote);
        $cards = [];
        foreach ($giftCards as $giftCard) {
            $cards[] = $giftCard[GiftCardAccount::CODE];
        }
        $data = [
            GiftCardAccount::GIFT_CARDS => $cards,
            GiftCardAccount::GIFT_CARDS_AMOUNT => $quote->getGiftCardsAmount(),
            GiftCardAccount::BASE_GIFT_CARDS_AMOUNT => $quote->getBaseGiftCardsAmount(),
            GiftCardAccount::GIFT_CARDS_AMOUNT_USED => $quote->getGiftCardsAmountUsed(),
            GiftCardAccount::BASE_GIFT_CARDS_AMOUNT_USED => $quote->getBaseGiftCardsAmountUsed(),
        ];

        /** @var GiftCardAccount $giftCardAccount */
        $giftCardAccount = $this->giftCardAccountFactory->create(['data' => $data]);
        return $giftCardAccount;
    }

    /**
     * @inheritDoc
     */
    public function saveByQuoteId(
        $cartId,
        \Magento\GiftCardAccount\Api\Data\GiftCardAccountInterface $giftCardAccountData
    ) {
        if (!$giftCardAccountData->getGiftCards()) {
            throw new CouldNotSaveException(__('Requiring a composite gift card account.'));
        }
        /** @var  \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        if (!$quote->getItemsCount()) {
            throw new CouldNotSaveException(__('The "%1" Cart doesn\'t contain products.', $cartId));
        }
        $cardCode = $giftCardAccountData->getGiftCards();
        $cardCode = array_shift($cardCode);

        try {
            $giftCard = $this->manager->requestByCode($cardCode, null, null, false, false);
            $giftCard->addToCart(true, $quote);
        } catch (TooManyAttemptsException $exception) {
            throw $exception;
        } catch (\Exception $e) {
            throw new CouldNotSaveException(
                __("The gift card code couldn't be added. Verify your information and try again."),
                $e
            );
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function checkGiftCard($cartId, $giftCardCode)
    {
        $quote = $this->quoteRepository->getActive($cartId);
        try {
            $giftCard = $this->manager->requestByCode(
                $giftCardCode,
                (int)$this->storeManager->getWebsite()->getId(),
                null,
                true,
                true
            );
        } catch (\InvalidArgumentException | NoSuchEntityException $e) {
            throw new NoSuchEntityException(
                __("The gift card code is either incorrect or expired. Verify and try again."),
                $e
            );
        }
        /** @var \Magento\Directory\Model\Currency $currency */
        $currency = $this->storeManager->getStore()->getBaseCurrency();
        return $currency->convert($giftCard->getBalance(), $quote->getQuoteCurrencyCode());
    }
}
