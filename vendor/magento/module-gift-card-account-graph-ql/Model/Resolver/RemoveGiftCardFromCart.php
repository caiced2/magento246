<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccountGraphQl\Model\Resolver;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GiftCardAccount\Api\GiftCardAccountManagementInterface;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;

/**
 * Resolver for the RemoveGiftCardFromCart mutation
 */
class RemoveGiftCardFromCart implements ResolverInterface
{
    /**
     * @var GetCartForUser
     */
    private $getCartForUser;

    /**
     * @var GiftCardAccountManagementInterface
     */
    private $giftCardAccountManagement;

    /**
     * @param GetCartForUser $getCartForUser
     * @param GiftCardAccountManagementInterface $giftCardAccountManagement
     */
    public function __construct(
        GetCartForUser $getCartForUser,
        GiftCardAccountManagementInterface $giftCardAccountManagement
    ) {
        $this->getCartForUser = $getCartForUser;
        $this->giftCardAccountManagement = $giftCardAccountManagement;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (empty($args['input']['cart_id'])) {
            throw new GraphQlInputException(__('Required parameter "cart_id" is missing'));
        }

        if (empty($args['input']['gift_card_code'])) {
            throw new GraphQlInputException(__('Required parameter "gift_card_code" is missing'));
        }

        $maskedCartId = $args['input']['cart_id'];
        $giftCardCode = $args['input']['gift_card_code'];

        $currentUserId = $context->getUserId();
        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
        $cart = $this->getCartForUser->execute($maskedCartId, $currentUserId, $storeId);
        $cartId = $cart->getId();

        try {
            $this->giftCardAccountManagement->deleteByQuoteId($cartId, $giftCardCode);
        } catch (CouldNotDeleteException $e) {
            throw new GraphQlInputException(__($e->getMessage()), $e);
        }

        return [
            'cart' => [
                'model' => $cart,
            ],
        ];
    }
}
