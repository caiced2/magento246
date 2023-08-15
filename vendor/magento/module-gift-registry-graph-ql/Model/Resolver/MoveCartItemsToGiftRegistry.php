<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistryGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GiftRegistry\Helper\Data as GiftRegistryHelper;
use Magento\GiftRegistry\Model\EntityFactory as GiftRegistryFactory;
use Magento\GiftRegistry\Model\GiftRegistry\MoveCartItemsToGiftRegistry as MoveCartItemsToGiftRegistryModel;
use Magento\GiftRegistry\Model\ResourceModel\Entity as GiftRegistryResourceModel;
use Magento\GiftRegistryGraphQl\Mapper\GiftRegistryOutputDataMapper;
use Magento\GiftRegistryGraphQl\Mapper\GiftRegistryOutputErrorMapper;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;

/**
 * The GraphQl resolver to moves all items from cart to the gift registry
 */
class MoveCartItemsToGiftRegistry implements ResolverInterface
{
    /**
     * @var GiftRegistryFactory
     */
    private $entityFactory;

    /**
     * @var GiftRegistryResourceModel
     */
    private $entityResourceModel;

    /**
     * @var GiftRegistryOutputDataMapper
     */
    private $giftRegistryOutputDataMapper;

    /**
     * @var GiftRegistryOutputErrorMapper
     */
    private $giftRegistryOutputErrorMapper;

    /**
     * @var GiftRegistryHelper
     */
    private $giftRegistryHelper;

    /**
     * @var GetCartForUser
     */
    private $getCartForUser;

    /**
     * @var MoveCartItemsToGiftRegistryModel
     */
    private $moveCartItemsToGiftRegistry;

    /**
     * @param GiftRegistryFactory $entityFactory
     * @param GiftRegistryResourceModel $entityResourceModel
     * @param GiftRegistryOutputDataMapper $giftRegistryOutputDataMapper
     * @param GiftRegistryOutputErrorMapper $giftRegistryOutputErrorMapper
     * @param GiftRegistryHelper $giftRegistryHelper
     * @param GetCartForUser $getCartForUser
     * @param MoveCartItemsToGiftRegistryModel $moveCartItemsToGiftRegistry
     */
    public function __construct(
        GiftRegistryFactory $entityFactory,
        GiftRegistryResourceModel $entityResourceModel,
        GiftRegistryOutputDataMapper $giftRegistryOutputDataMapper,
        GiftRegistryOutputErrorMapper $giftRegistryOutputErrorMapper,
        GiftRegistryHelper $giftRegistryHelper,
        GetCartForUser $getCartForUser,
        MoveCartItemsToGiftRegistryModel $moveCartItemsToGiftRegistry
    ) {
        $this->entityFactory = $entityFactory;
        $this->entityResourceModel = $entityResourceModel;
        $this->giftRegistryOutputDataMapper = $giftRegistryOutputDataMapper;
        $this->giftRegistryOutputErrorMapper = $giftRegistryOutputErrorMapper;
        $this->giftRegistryHelper = $giftRegistryHelper;
        $this->getCartForUser = $getCartForUser;
        $this->moveCartItemsToGiftRegistry = $moveCartItemsToGiftRegistry;
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
        if (!$this->giftRegistryHelper->isEnabled()) {
            throw new GraphQlInputException(__(
                'The %1 is not currently available.',
                ['gift registry']
            ));
        }
        $customerId = (int)$context->getUserId() ?? null;

        if (0 === $customerId) {
            throw new GraphQlAuthorizationException(__(
                'The current user cannot perform operations on %1',
                ['gift registry']
            ));
        }

        $giftRegistry = $this->entityFactory->create();
        $this->entityResourceModel->load(
            $giftRegistry,
            $args['giftRegistryUid'],
            'url_key'
        );

        if (!$giftRegistry->getEntityId() || $customerId !== (int)$giftRegistry->getCustomerId()) {
            throw new GraphQlInputException(__(
                'The %1 ID is incorrect. Verify the ID and try again.',
                ['gift registry']
            ));
        }

        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
        $cart = $this->getCartForUser->execute($args['cartUid'], $customerId, $storeId);

        $giftRegistryOutput = $this->moveCartItemsToGiftRegistry->execute($cart, $giftRegistry);

        return [
            'gift_registry' => $this->giftRegistryOutputDataMapper->map($giftRegistryOutput->getGiftRegistry()),
            'status' => empty($giftRegistryOutput->getErrors()),
            'user_errors' => $this->giftRegistryOutputErrorMapper->map($giftRegistryOutput->getErrors(), $giftRegistry)
        ];
    }
}
