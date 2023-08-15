<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrappingGraphQl\Plugin\Mutation;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ArgumentsProcessorInterface;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GiftWrapping\Api\Data\WrappingInterface;
use Magento\GiftWrapping\Api\WrappingRepositoryInterface;
use Magento\GiftWrapping\Helper\Data as GiftWrappingHelper;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\QuoteGraphQl\Model\Resolver\UpdateCartItems;

class SetGiftWrappingId
{
    private const GIFT_WRAPPING_STATUS_DISABLE = 0;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var GiftWrappingHelper
     */
    private $giftWrappingData;

    /**
     * @var WrappingRepositoryInterface
     */
    private $wrappingRepository;

    /**
     * @var ArgumentsProcessorInterface
     */
    private $argsSelection;

    /**
     * @param CartRepositoryInterface      $cartRepository
     * @param GiftWrappingHelper           $giftWrappingData
     * @param WrappingRepositoryInterface  $wrappingRepository
     * @param ArgumentsProcessorInterface  $argsSelection
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        GiftWrappingHelper $giftWrappingData,
        WrappingRepositoryInterface  $wrappingRepository,
        ArgumentsProcessorInterface $argsSelection
    ) {
        $this->cartRepository = $cartRepository;
        $this->giftWrappingData = $giftWrappingData;
        $this->wrappingRepository = $wrappingRepository;
        $this->argsSelection = $argsSelection;
    }

    /**
     * Set gift wrapping id for quote
     *
     * @param UpdateCartItems   $updateCartItems
     * @param array             $result
     * @param Field             $field
     * @param ContextInterface  $context
     * @param ResolveInfo       $info
     * @param array|null        $value
     * @param array|null        $args
     *
     * @return array
     *
     * @throws GraphQlInputException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterResolve(
        UpdateCartItems $updateCartItems,
        array $result,
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!(($result['cart']['model'] ?? null) instanceof CartInterface)) {
            throw new GraphQlInputException(__('"model" value should be specified'));
        }
        $cartModel = $result['cart']['model'];
        $processedArgs = $this->argsSelection->process($info->fieldName, $args);
        $cartItems = $processedArgs['input']['cart_items'];

        if (array_key_exists('gift_wrapping_id', $processedArgs['input']['cart_items'][0])) {
            $this->inputValidatorForAddingGiftWrappingId($processedArgs, $context);
        }

        foreach ($cartItems as $item) {
            $itemId = (int)$item['cart_item_id'];
            if (array_key_exists('gift_wrapping_id', $item)) {
                $cartItem = $cartModel->getItemById($itemId);
                if ($item['gift_wrapping_id'] === null) {
                    $cartItem->setGwId($item['gift_wrapping_id']);
                } else {
                    if ($this->validateGiftWrapping($item)) {
                        // phpcs:ignore Magento2.Functions.DiscouragedFunction
                        $wrappingId = (int)base64_decode($item['gift_wrapping_id']);
                        $cartItem->setGwId($wrappingId);
                    }
                }
                $this->cartRepository->save($cartModel);
            }
        }
        return $result;
    }

    /**
     * Input validator to check for exceptions on adding gift wrapping id
     *
     * @param array $args
     * @param ContextInterface $context
     *
     * @throws GraphQlInputException
     */
    private function inputValidatorForAddingGiftWrappingId(array $args, ContextInterface $context)
    {
        if ($args['input']['cart_items'][0]['gift_wrapping_id'] != null) {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $wrappingId = (int)base64_decode($args['input']['cart_items'][0]['gift_wrapping_id']);
            $store = (int)$context->getExtensionAttributes()->getStore()->getStoreId();

            if ($this->giftWrappingData->isGiftWrappingAvailableForItems()) {
                try {
                    /** @var $cartGiftWrapping WrappingInterface */
                    $cartGiftWrapping = $this->wrappingRepository->get($wrappingId, $store);

                    if ((int)$cartGiftWrapping->getStatus() === self::GIFT_WRAPPING_STATUS_DISABLE) {
                        throw new GraphQlInputException(__('Can\'t load gift wrapping for cart item.'));
                    }
                } catch (LocalizedException $e) {
                    throw new GraphQlInputException(__('Can\'t load gift wrapping for cart item.'));
                }
            } else {
                throw new GraphQlInputException(__('Can\'t load gift wrapping for cart item.'));
            }
        }
    }

    /**
     * Gift Wrapping validation
     *
     * @param array $item
     *
     * @return bool
     *
     * @throws GraphQlInputException
     */
    private function validateGiftWrapping(array $item)
    {
        if (!isset($item['gift_wrapping_id'])) {
            return false;
        }
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        if (base64_encode(base64_decode($item['gift_wrapping_id'])) !== $item['gift_wrapping_id']) {
            throw new GraphQlInputException(__('Invalid gift wrapping id for cart.'));
        }
        return true;
    }
}
