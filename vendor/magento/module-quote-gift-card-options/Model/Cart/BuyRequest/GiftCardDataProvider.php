<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuoteGiftCardOptions\Model\Cart\BuyRequest;

use Magento\Framework\Exception\LocalizedException;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard;
use Magento\GiftCard\Model\Giftcard\Option;
use Magento\Quote\Model\Cart\BuyRequest\BuyRequestDataProviderInterface;
use Magento\Quote\Model\Cart\Data\CartItem;

/**
 * Data provider for gift card product buy requests
 */
class GiftCardDataProvider implements BuyRequestDataProviderInterface
{
    /**
     * @inheritdoc
     *
     * @throws LocalizedException
     */
    public function execute(CartItem $cartItem): array
    {
        $giftCardOptionsData = [];

        foreach ($cartItem->getSelectedOptions() as $optionData) {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $optionData = \explode('/', base64_decode($optionData->getId()));

            if ($this->isProviderApplicable($optionData) === false) {
                continue;
            }

            [$optionType, $optionId] = $optionData;

            if ($optionType === Giftcard::TYPE_GIFTCARD && $optionId !== Option::KEY_CUSTOM_GIFTCARD_AMOUNT) {
                [,,$giftCardAmount] = $optionData;
                $giftCardOptionsData[$optionId] = $giftCardAmount;
            }
        }

        foreach ($cartItem->getEnteredOptions() as $option) {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $optionData = \explode('/', base64_decode($option->getUid()));

            if ($this->isProviderApplicable($optionData) === false) {
                continue;
            }
            $this->validateInput($optionData);

            [$optionType, $optionId] = $optionData;
            if ($optionType === Giftcard::TYPE_GIFTCARD) {
                if ($optionId === Option::KEY_CUSTOM_GIFTCARD_AMOUNT) {
                    $giftCardOptionsData[Option::KEY_AMOUNT] = 'custom';
                }
                $giftCardOptionsData[$optionId] = $option->getValue();
            }
        }

        return $giftCardOptionsData;
    }

    /**
     * Checks whether this provider is applicable for the current option
     *
     * @param array $optionData
     * @return bool
     */
    private function isProviderApplicable(array $optionData): bool
    {
        return $optionData[0] === Giftcard::TYPE_GIFTCARD
            && in_array($optionData[1], [
                Option::KEY_AMOUNT,
                Option::KEY_CUSTOM_GIFTCARD_AMOUNT,
                Option::KEY_SENDER_NAME,
                Option::KEY_SENDER_EMAIL,
                Option::KEY_RECIPIENT_NAME,
                Option::KEY_RECIPIENT_EMAIL,
                Option::KEY_MESSAGE
            ], true);
    }

    /**
     * Validates the provided options structure
     *
     * @param array $optionData
     * @throws LocalizedException
     */
    private function validateInput(array $optionData): void
    {
        if (count($optionData) !== 2) {
            throw new LocalizedException(
                __('Wrong format of the entered option data')
            );
        }
    }
}
