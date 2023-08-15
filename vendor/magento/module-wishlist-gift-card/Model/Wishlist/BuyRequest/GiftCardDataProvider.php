<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\WishlistGiftCard\Model\Wishlist\BuyRequest;

use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard;
use Magento\GiftCard\Model\Giftcard\Option;
use Magento\Wishlist\Model\Wishlist\BuyRequest\BuyRequestDataProviderInterface;
use Magento\Wishlist\Model\Wishlist\Data\WishlistItem;

/**
 * Data provider for gift card product buy requests
 */
class GiftCardDataProvider implements BuyRequestDataProviderInterface
{
    /**
     * Building the data for gift card buyRequest
     *
     * @param WishlistItem $wishlistItemData
     * @param int|null $productId
     *
     * @return array
     *
     * @phpcs:disable Magento2.Functions.DiscouragedFunction
     */
    public function execute(WishlistItem $wishlistItemData, ?int $productId): array
    {
        $giftCardOptionsData = [];

        foreach ($wishlistItemData->getSelectedOptions() as $optionData) {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $optionData = \explode('/', base64_decode($optionData->getId()));

            if ($this->isProviderApplicable($optionData) === false) {
                continue;
            }

            [$optionType, $optionId, $giftCardAmount] = $optionData;
            if ($optionType === Giftcard::TYPE_GIFTCARD) {
                $giftCardOptionsData[$optionId] = $giftCardAmount;
            }
        }

        foreach ($wishlistItemData->getEnteredOptions() as $option) {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $optionData = \explode('/', base64_decode($option->getUId()));

            if ($this->isProviderApplicable($optionData) === false) {
                continue;
            }

            [$optionType, $optionId] = $optionData;
            if ($optionType === Giftcard::TYPE_GIFTCARD) {
                if ($optionId === Option::KEY_CUSTOM_GIFTCARD_AMOUNT) {
                    $giftCardOptionsData[Option::KEY_AMOUNT] = 'custom';
                }
                $giftCardOptionsData[$optionId] = $option->getValue();
            }
        }

        return $this->prepareGiftCardData($giftCardOptionsData, $productId);
    }

    /**
     * Prepare the gift card data
     *
     * @param array $giftCardOptionsData
     * @param int|null $productId
     *
     * @return array
     */
    private function prepareGiftCardData(array $giftCardOptionsData, ?int $productId): array
    {
        if (empty($giftCardOptionsData)) {
            return $giftCardOptionsData;
        }

        $giftCardOptionsData += $productId ? ['product' => $productId] : [];

        return $giftCardOptionsData;
    }

    /**
     * Checks whether this provider is applicable for the current option
     *
     * @param array $optionData
     *
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
}
