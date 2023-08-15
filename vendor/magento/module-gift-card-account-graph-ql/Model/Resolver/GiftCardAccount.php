<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccountGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GiftCardAccountGraphQl\Model\DataProvider\GiftCardAccount as GiftCardAccountProvider;
use Magento\Store\Model\Store;

/**
 * Resolver for giftCardAccount query
 */
class GiftCardAccount implements ResolverInterface
{
    /**
     * @var GiftCardAccountProvider
     */
    private $giftCardAccountProvider;

    /**
     * @param GiftCardAccountProvider $giftCardAccountProvider
     */
    public function __construct(
        GiftCardAccountProvider $giftCardAccountProvider
    ) {
        $this->giftCardAccountProvider = $giftCardAccountProvider;
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
        if (empty($args['input']['gift_card_code'])) {
            throw new GraphQlInputException(__("Required parameter '%1' is missing.", 'gift_card_code'));
        }
        $giftCardCode = $args['input']['gift_card_code'];
        /** @var Store $store */
        $store = $context->getExtensionAttributes()->getStore();
        return $this->giftCardAccountProvider->getByCode($giftCardCode, $store);
    }
}
