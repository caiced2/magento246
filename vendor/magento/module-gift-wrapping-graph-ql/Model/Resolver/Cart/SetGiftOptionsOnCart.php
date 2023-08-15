<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrappingGraphQl\Model\Resolver\Cart;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GiftMessage\Helper\Message as GiftMessageHelper;
use Magento\GiftMessage\Model\Message;
use Magento\GiftMessage\Model\MessageFactory;
use Magento\GiftMessage\Model\ResourceModel\Message as MessageResource;
use Magento\GiftWrapping\Api\Data\WrappingInterface;
use Magento\GiftWrapping\Api\WrappingRepositoryInterface;
use Magento\GiftWrapping\Helper\Data as GiftWrappingHelper;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\GuestCartRepositoryInterface;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Class set gift information for cart
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SetGiftOptionsOnCart implements ResolverInterface
{
    private const GIFT_WRAPPING_STATUS_DISABLE = 0;

    /**
     * @var GuestCartRepositoryInterface
     */
    private $guestCartRepository;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var MessageFactory
     */
    private $messageFactory;

    /**
     * @var MessageResource
     */
    private $messageResource;

    /**
     * @var GiftMessageHelper
     */
    private $giftMessageHelper;

    /**
     * @var WrappingRepositoryInterface
     */
    private $wrappingRepository;

    /**
     * @var GiftWrappingHelper
     */
    private $giftWrappingData;

    /**
     * @var GetCartForUser
     */
    private $getCartForUser;

    /**
     * @param GuestCartRepositoryInterface $guestCartRepository
     * @param CartRepositoryInterface      $cartRepository
     * @param MessageFactory               $messageFactory
     * @param MessageResource              $messageResource
     * @param GiftMessageHelper            $giftMessageHelper
     * @param WrappingRepositoryInterface  $wrappingRepository
     * @param GiftWrappingHelper           $giftWrappingData
     * @param GetCartForUser               $getCartForUser
     */
    public function __construct(
        GuestCartRepositoryInterface $guestCartRepository,
        CartRepositoryInterface $cartRepository,
        MessageFactory $messageFactory,
        MessageResource $messageResource,
        GiftMessageHelper $giftMessageHelper,
        WrappingRepositoryInterface $wrappingRepository,
        GiftWrappingHelper $giftWrappingData,
        GetCartForUser $getCartForUser
    ) {
        $this->guestCartRepository = $guestCartRepository;
        $this->quoteRepository = $cartRepository;
        $this->messageFactory = $messageFactory;
        $this->messageResource = $messageResource;
        $this->giftMessageHelper = $giftMessageHelper;
        $this->wrappingRepository = $wrappingRepository;
        $this->giftWrappingData = $giftWrappingData;
        $this->getCartForUser = $getCartForUser;
    }

    /**
     * Set gift information for cart
     *
     * @param Field            $field
     * @param ContextInterface $context
     * @param ResolveInfo      $info
     * @param array|null       $value
     * @param array|null       $args
     *
     * @return array|Value|mixed
     * @throws GraphQlInputException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (empty($args['input']['cart_id'])) {
            throw new GraphQlInputException(__('You must specify an cartId'));
        }

        $cartId = $args['input']['cart_id'];
        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();

        $cart = $this->getCartForUser->execute($cartId, $context->getUserId(), $storeId);

        /** @var StoreInterface $store */
        $store = (int)$context->getExtensionAttributes()->getStore()->getStoreId();

        if (isset($args['input']['gift_message'])) {
            $this->saveGiftMessage($args['input']['gift_message'], $cart);
        }

        if (array_key_exists('gift_wrapping_id', $args['input']) &&
            $this->giftWrappingData->isGiftWrappingAvailableForOrder()
        ) {
            $this->validateGiftWrappingId($args['input']['gift_wrapping_id']);
            try {
                if ($args['input']['gift_wrapping_id'] === null) {
                    $cart->setGwId($args['input']['gift_wrapping_id']);
                } else {
                    // phpcs:ignore Magento2.Functions.DiscouragedFunction
                    $wrappingId = (int)base64_decode($args['input']['gift_wrapping_id'], true);
                    /** @var $cartGiftWrapping WrappingInterface */
                    $cartGiftWrapping = $this->wrappingRepository->get($wrappingId, $store);

                    if ((int)$cartGiftWrapping->getStatus() === self::GIFT_WRAPPING_STATUS_DISABLE) {
                        throw new GraphQlInputException(__('Can\'t set gift wrapping for cart.'));
                    }
                    $cart->setGwId($cartGiftWrapping->getWrappingId());
                }
            } catch (LocalizedException $e) {
                throw new GraphQlInputException(__('Can\'t load gift wrapping for cart.'));
            }
        }

        if (isset($args['input']['gift_receipt_included']) && $this->giftWrappingData->allowGiftReceipt($store)) {
            $cart->setGwAllowGiftReceipt($args['input']['gift_receipt_included']);
        }

        if (isset($args['input']['printed_card_included']) && $this->giftWrappingData->allowPrintedCard($store)) {
            $cart->setGwAddCard($args['input']['printed_card_included']);
        }

        $this->quoteRepository->save($cart);

        return [
            'cart' => [
                'model' => $cart,
            ],
        ];
    }

    /**
     * Save gift message for cart
     *
     * @param array $giftMessage
     * @param CartInterface $cart
     *
     * @throws GraphQlInputException
     */
    private function saveGiftMessage(array $giftMessage, CartInterface $cart)
    {
        if ($this->giftMessageHelper->isMessagesAllowed('order', $cart)) {
            /** @var  $giftMessageModel Message */
            $giftMessageModel= $this->messageFactory->create();
            $giftMessageModel->setSender($giftMessage['from']);
            $giftMessageModel->setRecipient($giftMessage['to']);
            $giftMessageModel->setMessage($giftMessage['message']);

            try {
                $this->messageResource->save($giftMessageModel);
            } catch (LocalizedException $e) {
                throw new GraphQlInputException(__('Can\'t save gift message for cart.'));
            }

            $cart->setGiftMessageId($giftMessageModel->getId());
        }
    }

    /**
     * Validate Gift wrapping ID
     *
     * @param string|null $giftWrappingId
     * @throws GraphQlInputException
     */
    private function validateGiftWrappingId($giftWrappingId): void
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        if (($giftWrappingId!= null) && base64_encode(base64_decode($giftWrappingId)) !== $giftWrappingId) {
            throw new GraphQlInputException(__('Invalid gift wrapping id for cart.'));
        }
    }
}
