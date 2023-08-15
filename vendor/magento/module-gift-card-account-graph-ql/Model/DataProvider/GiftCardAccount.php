<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccountGraphQl\Model\DataProvider;

use Magento\Framework\Exception\LocalizedException;
use Magento\GiftCardAccount\Model\Spi\GiftCardAccountManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\GiftCardAccount\Api\Exception\TooManyAttemptsException;
use Magento\GiftCardAccountGraphQl\Model\Money\Formatter as MoneyFormatter;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Retrieve a gift card account formatted for GraphQl output
 */
class GiftCardAccount
{
    /**
     * @var GiftCardAccountManagerInterface
     */
    private $giftCardAccountManager;

    /**
     * @var MoneyFormatter
     */
    private $moneyFormatter;

    /**
     * @param GiftCardAccountManagerInterface $giftCardAccountManager
     * @param MoneyFormatter $moneyFormatter
     */
    public function __construct(
        GiftCardAccountManagerInterface $giftCardAccountManager,
        MoneyFormatter $moneyFormatter
    ) {
        $this->giftCardAccountManager = $giftCardAccountManager;
        $this->moneyFormatter = $moneyFormatter;
    }

    /**
     * Retrieve gift card account and format for resolver output
     *
     * @param string $giftCardCode
     * @param StoreInterface $store
     * @return array
     * @throws GraphQlInputException
     * @throws GraphQlNoSuchEntityException
     * @throws LocalizedException
     */
    public function getByCode(string $giftCardCode, StoreInterface $store): array
    {
        try {
            /** @var \Magento\GiftCardAccount\Model\Giftcardaccount $giftCardAccount */
            $giftCardAccount = $this->giftCardAccountManager->requestByCode($giftCardCode, (int)$store->getWebsiteId());
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        } catch (TooManyAttemptsException $e) {
            throw new GraphQlInputException(__($e->getMessage()), $e);
        } catch (\InvalidArgumentException $e) {
            throw new GraphQlInputException(__('Invalid gift card'), $e);
        }

        return [
            'code' => $giftCardAccount->getCode(),
            'balance' => $this->moneyFormatter->formatAmountAsMoney($giftCardAccount->getBalance(), $store),
            'expiration_date' => $giftCardAccount->getDateExpires()
        ];
    }
}
