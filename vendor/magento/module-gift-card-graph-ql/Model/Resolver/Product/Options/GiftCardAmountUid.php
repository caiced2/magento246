<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardGraphQl\Model\Resolver\Product\Options;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard;
use Magento\GiftCard\Model\Giftcard\Option;

/**
 * Format new option uid in base64 encode for giftcard amounts
 */
class GiftCardAmountUid implements ResolverInterface
{
    /** @var Uid */
    private $uidEncoder;

    /**
     * @param Uid $uidEncoder
     */
    public function __construct(Uid $uidEncoder)
    {
        $this->uidEncoder = $uidEncoder;
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
        if (!isset($value['value']) || empty($value['value'])) {
            throw new GraphQlInputException(
                __('Wrong format option data: "value" should not be empty.')
            );
        }

        $optionDetails = [
            Giftcard::TYPE_GIFTCARD,
            Option::KEY_AMOUNT,
            $value['value']
        ];

        $content = implode('/', $optionDetails);

        return $this->uidEncoder->encode($content);
    }
}
