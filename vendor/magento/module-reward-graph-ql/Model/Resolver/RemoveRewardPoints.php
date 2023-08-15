<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\RewardGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magento\RewardGraphQl\Model\Config;

/**
 * Removes reward points from cart
 */
class RemoveRewardPoints implements ResolverInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var GetCartForUser
     */
    private $getCartForUser;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @param Config $config
     * @param GetCartForUser $getCartForUser
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(
        Config $config,
        GetCartForUser $getCartForUser,
        CartRepositoryInterface $cartRepository
    ) {
        $this->config = $config;
        $this->getCartForUser = $getCartForUser;
        $this->cartRepository = $cartRepository;
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
        /** @var int $websiteId */
        $websiteId = (int)$context->getExtensionAttributes()->getStore()->getWebsite()->getId();

        if ($this->config->isDisabled($websiteId)) {
            return null;
        }

        if ($context->getExtensionAttributes()->getIsCustomer() === false) {
            throw new GraphQlAuthorizationException(
                __('The current customer isn\'t authorized.')
            );
        }

        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();

        /** @var CartInterface $cart */
        $cart = $this->getCartForUser->execute($args['cartId'], $context->getUserId(), $storeId);

        if ($cart->getUseRewardPoints()) {
            //Remove Reward points from cart
            $cart->setUseRewardPoints(0);
            $cart->collectTotals();
            $this->cartRepository->save($cart);
        }

        return [
            'cart' => [
                'model' => $cart
            ]
        ];
    }
}
