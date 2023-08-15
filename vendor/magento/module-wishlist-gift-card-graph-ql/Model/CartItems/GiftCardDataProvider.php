<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Magento\WishlistGiftCardGraphQl\Model\CartItems;

use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard;
use Magento\GiftCard\Model\Giftcard\Option;
use Magento\Wishlist\Model\Item;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\WishlistGraphQl\Model\CartItems\CartItemsRequestDataProviderInterface;

/**
 * Data provider for bundlue product cart item request
 */
class GiftCardDataProvider implements CartItemsRequestDataProviderInterface
{
    /**
     * @var Uid
     */
    private $uidEncoder;

    /**
     * @param Uid $uidEncoder
     */
    public function __construct(
        Uid $uidEncoder
    ) {
        $this->uidEncoder = $uidEncoder;
    }

    /**
     * @inheritdoc
     */
    public function execute(Item $wishlistItem, ?string $sku): array
    {
        $buyRequest = $wishlistItem->getBuyRequest();
        $product = $wishlistItem->getProduct();
        $cartItems = [];
        $selectedOptions = [];
        $enteredOptions = [];
        if ($product->getTypeId() === Giftcard::TYPE_GIFTCARD) {
            if (!empty($buyRequest['giftcard_sender_email']) &&
                !empty($buyRequest['giftcard_sender_name']) &&
                !empty($buyRequest['giftcard_recipient_name']) &&
                !empty($buyRequest['giftcard_recipient_email'])
            ) {
                $enteredOptions[] = [
                    'uid' => $this->uidEncoder->encode("giftcard/giftcard_sender_name"),
                    'value' => $buyRequest['giftcard_sender_name'],
                ];
                $enteredOptions[] = [
                    'uid' => $this->uidEncoder->encode("giftcard/giftcard_sender_email"),
                    'value' => $buyRequest['giftcard_sender_email'],
                ];
                $enteredOptions[] = [
                    'uid' => $this->uidEncoder->encode("giftcard/giftcard_recipient_name"),
                    'value' => $buyRequest['giftcard_sender_name'],
                ];
                $enteredOptions[] = [
                    'uid' => $this->uidEncoder->encode("giftcard/giftcard_recipient_email"),
                    'value' => $buyRequest['giftcard_recipient_email'],
                ];
            }
            if (isset($buyRequest['giftcard_message'])) {
                $enteredOptions[] = [
                    'uid' => $this->uidEncoder->encode("giftcard/giftcard_message"),
                    'value' => $buyRequest['giftcard_message'],
                ];
            }

            if (isset($buyRequest['giftcard_amount']) && $buyRequest['giftcard_amount'] == "custom") {
                $enteredOptions[] = [
                    'uid' => $this->uidEncoder->encode("giftcard/custom_giftcard_amount"),
                    'value' => $buyRequest['custom_giftcard_amount'],
                ];
            } elseif (isset($buyRequest['giftcard_amount'])) {
                $selectedOptions[] = $this->encodeOption("giftcard_amount", floatval($buyRequest['giftcard_amount']));
            }

        }
        $cartItems['selected_options'] = $selectedOptions;
        $cartItems['entered_options'] = $enteredOptions;

        return $cartItems;
    }

    /**
     * Returns uid of the selected custom option
     *
     * @param string $option
     * @param float $optionValue = null
     *
     * @return string
     */
    private function encodeOption(string $option, float $optionValue = null): string
    {
        if ($optionValue) {
            return $this->uidEncoder->encode("giftcard/$option/$optionValue");
        }

        return $this->uidEncoder->encode("giftcard/$option");
    }
}
