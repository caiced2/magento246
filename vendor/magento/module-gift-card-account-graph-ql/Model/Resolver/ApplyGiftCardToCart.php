<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccountGraphQl\Model\Resolver;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GiftCardAccount\Api\Exception\TooManyAttemptsException;
use Magento\GiftCardAccount\Api\GiftCardAccountManagementInterface;
use Magento\GiftCardAccount\Api\Data\GiftCardAccountInterfaceFactory;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magento\GiftCardAccount\Model\Giftcardaccount as GiftCardAccount;

/**
 * @inheritdoc
 */
class ApplyGiftCardToCart implements ResolverInterface
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
     * @var GiftCardAccountInterfaceFactory
     */
    private $giftCardAccountFactory;

    /**
     * @param GetCartForUser $getCartForUser
     * @param GiftCardAccountManagementInterface $giftCardAccountManagement
     * @param GiftCardAccountInterfaceFactory $giftCardAccountFactory
     */
    public function __construct(
        GetCartForUser $getCartForUser,
        GiftCardAccountManagementInterface $giftCardAccountManagement,
        GiftCardAccountInterfaceFactory $giftCardAccountFactory
    ) {
        $this->getCartForUser = $getCartForUser;
        $this->giftCardAccountManagement = $giftCardAccountManagement;
        $this->giftCardAccountFactory = $giftCardAccountFactory;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($args['input']['cart_id']) || empty($args['input']['cart_id'])) {
            throw new GraphQlInputException(__('Required parameter "%1" is missing', 'cart_id'));
        }
        $maskedCartId = $args['input']['cart_id'];

        if (!isset($args['input']['gift_card_code']) || empty($args['input']['gift_card_code'])) {
            throw new GraphQlInputException(__('Required parameter "%1" is missing', 'gift_card_code'));
        }
        $giftCardCode = $args['input']['gift_card_code'];

        $currentUserId = $context->getUserId();
        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
        $cart = $this->getCartForUser->execute($maskedCartId, $currentUserId, $storeId);
        $cartId = $cart->getId();

        $data = [
            GiftCardAccount::GIFT_CARDS => [$giftCardCode]
        ];

        /** @var GiftCardAccount $giftCardAccount */
        $giftCardAccount = $this->giftCardAccountFactory->create(['data' => $data]);

        try {
            $this->giftCardAccountManagement->saveByQuoteId($cartId, $giftCardAccount);
        } catch (TooManyAttemptsException $e) {
            throw new GraphQlInputException(__($e->getMessage()), $e);
        } catch (CouldNotSaveException $e) {
            throw new GraphQlInputException(__($e->getMessage()), $e);
        }

        return [
            'cart' => [
                'model' => $cart,
            ],
        ];
    }
}
