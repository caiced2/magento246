<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardGraphQl\Model\Resolver\GiftCardWishlistItem;

use Magento\Catalog\Model\Product\Configuration\Item\ItemInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\GiftCard\Model\Giftcard\Option as GiftcardOption;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Giftcard options resolver
 */
class GiftCardOptions implements ResolverInterface
{
    /**
     * @var Json
     */
    private $serializer;

    /**
     * @param Json $serializer
     */
    public function __construct(Json $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!$value['itemModel'] instanceof ItemInterface) {
            throw new LocalizedException(__('"itemModel" should be a "%instance" instance', [
                'instance' => ItemInterface::class
            ]));
        }
        /** @var ItemInterface $wishlistItem */
        $wishlistGiftCardItem = $value['itemModel'];
        $giftCardOptions = $wishlistGiftCardItem->getOptionsByCode();
        $currency = $context->getExtensionAttributes()->getStore()->getCurrentCurrency()->getCurrencyCode();
        $amountKey = $this->hasCustomAmount($wishlistGiftCardItem) ? 'custom_giftcard_amount' : 'amount';
        return [
            'sender_name' => isset($giftCardOptions[GiftcardOption::KEY_SENDER_NAME]) ?
                $giftCardOptions[GiftcardOption::KEY_SENDER_NAME]->getValue() : null,
            'sender_email' => isset($giftCardOptions[GiftcardOption::KEY_SENDER_EMAIL]) ?
                $giftCardOptions[GiftcardOption::KEY_SENDER_EMAIL]->getValue(): null,
            'recipient_name' => isset($giftCardOptions[GiftcardOption::KEY_RECIPIENT_NAME]) ?
                $giftCardOptions[GiftcardOption::KEY_RECIPIENT_NAME]->getValue() : null,
            'recipient_email' => isset($giftCardOptions[GiftcardOption::KEY_RECIPIENT_EMAIL]) ?
                $giftCardOptions[GiftcardOption::KEY_RECIPIENT_EMAIL]->getValue() : null,
            'message' => isset($giftCardOptions[GiftcardOption::KEY_MESSAGE]) ?
                $giftCardOptions[GiftcardOption::KEY_MESSAGE]->getValue() : null,
            $amountKey => isset($giftCardOptions[GiftcardOption::KEY_AMOUNT]) ? [
                'value' => floatval($giftCardOptions[GiftcardOption::KEY_AMOUNT]->getValue()),
                'currency' => $currency
            ] : null,
        ];
    }

    /**
     * Validates if the gift card has custom amount
     *
     * @param ItemInterface $wishlistGiftCardItem
     * @return bool
     */
    public function hasCustomAmount(ItemInterface $wishlistGiftCardItem): Bool
    {
        $giftCardOptions = $wishlistGiftCardItem->getOptionsByCode();
        if (isset($giftCardOptions['info_buyRequest'])) {
            $buyRequestValue = $this->serializer->unserialize($giftCardOptions['info_buyRequest']->getData()['value']);
            $amountType = $buyRequestValue['giftcard_amount'];
            return $amountType === 'custom';
        }
        return false;
    }
}
