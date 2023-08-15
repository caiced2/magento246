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
use Magento\Reward\Model\PaymentDataImporter;
use Magento\RewardGraphQl\Model\Config;

/**
 * Applies reward points to cart
 */
class ApplyRewardPoints implements ResolverInterface
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
     * @var PaymentDataImporter
     */
    private $paymentDataImporter;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @param Config $config
     * @param GetCartForUser $getCartForUser
     * @param PaymentDataImporter $paymentDataImporter
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(
        Config $config,
        GetCartForUser $getCartForUser,
        PaymentDataImporter $paymentDataImporter,
        CartRepositoryInterface $cartRepository
    ) {
        $this->config = $config;
        $this->getCartForUser = $getCartForUser;
        $this->paymentDataImporter = $paymentDataImporter;
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
        /** @var int $currentWebsiteId */
        $currentWebsiteId = (int)$context->getExtensionAttributes()->getStore()->getWebsite()->getId();

        if ($this->config->isDisabled($currentWebsiteId)) {
            return null;
        }

        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(
                __('The current customer isn\'t authorized.')
            );
        }

        /** @var CartInterface $cart */
        $cart = $this->getCartForUser->execute(
            $args['cartId'],
            $context->getUserId(),
            (int)$context->getExtensionAttributes()->getStore()->getId()
        );

        // Apply the available reward points to the cart
        $this->paymentDataImporter->import($cart, $cart->getPayment(), true);
        $cart->collectTotals();
        $this->cartRepository->save($cart);

        return [
            'cart' => [
                'model' => $cart
            ]
        ];
    }
}
