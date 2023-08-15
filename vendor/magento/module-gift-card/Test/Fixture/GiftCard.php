<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCard\Test\Fixture;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Framework\DataObject;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard as GiftCardProductType;
use Magento\GiftCard\Model\Giftcard as GiftCardType;

class GiftCard extends ProductFixture
{
    private const DEFAULT_DATA = [
        'type_id' => GiftCardProductType::TYPE_GIFTCARD,
        'name' => 'Gift Card Product%uniqid%',
        'sku' => 'gift-card-product%uniqid%',
        'weight' => null,
        'giftcard_type' => GiftCardType::TYPE_VIRTUAL,
        'giftcard_amounts' => '',
        'allow_open_amount' => '1',
        'open_amount_min' => '',
        'open_amount_max' => '',
        'is_redeemable' => '1',
        'lifetime' => '0',
        'allow_message' => '1',
        'email_template' => 'Default',
        'gift_message_available' => '1',
    ];

    /**
     * @inheritdoc
     */
    public function apply(array $data = []): ?DataObject
    {
        return parent::apply($this->prepareData($data));
    }

    /**
     * Prepare product data
     *
     * @param array $data
     * @return array
     */
    private function prepareData(array $data): array
    {
        $data = array_merge(self::DEFAULT_DATA, $data);

        return $data;
    }
}
