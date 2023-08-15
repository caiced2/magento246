<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccountGraphQl\Model\Resolver;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GiftCardAccount\Api\Exception\TooManyAttemptsException;
use Magento\GiftCardAccount\Api\GiftCardRedeemerInterface;
use Magento\GiftCardAccountGraphQl\Model\DataProvider\GiftCardAccount as GiftCardAccountProvider;
use Magento\Store\Model\Store;

/**
 * Resolver for redeem gift card mutation
 */
class RedeemGiftCard implements ResolverInterface
{
    /**
     * @var GiftCardRedeemerInterface
     */
    private $giftCardRedeemer;

    /**
     * @var GiftCardAccountProvider
     */
    private $giftCardAccountProvider;

    /**
     * @param GiftCardRedeemerInterface $giftCardRedeemer
     * @param GiftCardAccountProvider $gifCardAccountProvider
     */
    public function __construct(
        GiftCardRedeemerInterface $giftCardRedeemer,
        GiftCardAccountProvider $gifCardAccountProvider
    ) {
        $this->giftCardRedeemer = $giftCardRedeemer;
        $this->giftCardAccountProvider = $gifCardAccountProvider;
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
        $customerId = $context->getUserId();
        if (empty($customerId)) {
            throw new GraphQlAuthorizationException(__('Cannot find the customer to update balance'));
        }

        $giftCardCode = $args['input']['gift_card_code'] ?? '';
        if (empty($giftCardCode)) {
            throw new GraphQlInputException(__("Required parameter '%1' is missing", 'gift_card_code'));
        }

        try {
            $this->giftCardRedeemer->redeem($giftCardCode, $customerId);
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        } catch (CouldNotSaveException|TooManyAttemptsException $e) {
            throw new GraphQlInputException(__($e->getMessage()), $e);
        }

        /** @var Store $store */
        $store = $context->getExtensionAttributes()->getStore();
        return $this->giftCardAccountProvider->getByCode($giftCardCode, $store);
    }
}
