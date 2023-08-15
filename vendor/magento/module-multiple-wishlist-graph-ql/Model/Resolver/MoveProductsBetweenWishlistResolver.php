<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MultipleWishlistGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\MultipleWishlist\Helper\Data as MultipleWishlistHelper;
use Magento\MultipleWishlist\Model\Wishlist\MoveProductsInWishlist;
use Magento\Wishlist\Model\ResourceModel\Wishlist as WishlistResourceModel;
use Magento\Wishlist\Model\Wishlist\Config as WishlistConfig;
use Magento\Wishlist\Model\Wishlist\Data\Error;
use Magento\Wishlist\Model\Wishlist\Data\WishlistItemFactory;
use Magento\Wishlist\Model\WishlistFactory;
use Magento\WishlistGraphQl\Mapper\WishlistDataMapper;

/**
 * Move wishlist items resolver
 */
class MoveProductsBetweenWishlistResolver implements ResolverInterface
{
    /**
     * @var MoveProductsInWishlist
     */
    private $moveProductsInWishlist;

    /**
     * @var WishlistDataMapper
     */
    private $wishlistDataMapper;

    /**
     * @var WishlistConfig
     */
    private $wishlistConfig;

    /**
     * @var WishlistResourceModel
     */
    private $wishlistResource;

    /**
     * @var WishlistFactory
     */
    private $wishlistFactory;
    /**
     * @var MultipleWishlistHelper
     */
    private $multipleWishlistHelper;

    /**
     * @param WishlistResourceModel $wishlistResource
     * @param WishlistFactory $wishlistFactory
     * @param WishlistConfig $wishlistConfig
     * @param MoveProductsInWishlist $moveProductsInWishlist
     * @param WishlistDataMapper $wishlistDataMapper
     * @param MultipleWishlistHelper $multipleWishlistHelper
     */
    public function __construct(
        WishlistResourceModel $wishlistResource,
        WishlistFactory $wishlistFactory,
        WishlistConfig $wishlistConfig,
        MoveProductsInWishlist $moveProductsInWishlist,
        WishlistDataMapper $wishlistDataMapper,
        MultipleWishlistHelper $multipleWishlistHelper
    ) {
        $this->wishlistResource = $wishlistResource;
        $this->wishlistFactory = $wishlistFactory;
        $this->wishlistConfig = $wishlistConfig;
        $this->moveProductsInWishlist = $moveProductsInWishlist;
        $this->wishlistDataMapper = $wishlistDataMapper;
        $this->multipleWishlistHelper = $multipleWishlistHelper;
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
        if (!$this->multipleWishlistHelper->isMultipleEnabled()) {
            throw new GraphQlInputException(__('The multiple wishlist configuration is currently disabled.'));
        }

        if (!$this->wishlistConfig->isEnabled()) {
            throw new GraphQlInputException(__('The wishlist configuration is currently disabled.'));
        }

        $customerId = $context->getUserId();

        /* Guest checking */
        if (null === $customerId || 0 === $customerId) {
            throw new GraphQlAuthorizationException(__('The current user cannot perform operations on wishlist'));
        }

        $sourceWishlist = $this->wishlistFactory->create();
        $this->wishlistResource->load($sourceWishlist, $args['sourceWishlistUid']);

        if (null === $sourceWishlist->getId()) {
            throw new GraphQlInputException(__('The source wishlist was not found.'));
        }

        if ((int) $sourceWishlist->getCustomerId() !== $customerId) {
            throw new GraphQlAuthorizationException(
                __('The wish list is not assigned to your account and can\'t be edited.')
            );
        }

        $destinationWishlist = $this->wishlistFactory->create();
        $this->wishlistResource->load($destinationWishlist, $args['destinationWishlistUid']);

        if (null === $destinationWishlist->getId()) {
            throw new GraphQlInputException(__('The destination wishlist was not found.'));
        }

        if ((int) $destinationWishlist->getCustomerId() !== $customerId) {
            throw new GraphQlAuthorizationException(
                __('The wish list is not assigned to your account and can\'t be edited.')
            );
        }

        $wishlistItems = [];
        $wishlistItemsData = $args['wishlistItems'];

        foreach ($wishlistItemsData as $wishlistItemData) {
            $wishlistItems[] = (new WishlistItemFactory())->create($wishlistItemData);
        }

        $wishlistOutput = $this->moveProductsInWishlist->execute(
            $destinationWishlist,
            $wishlistItems,
            (int) $customerId
        );

        if (count($wishlistOutput->getErrors()) !== count($wishlistItems)) {
            $this->wishlistResource->save($destinationWishlist);
        }

        return [
            'source_wishlist' => $this->wishlistDataMapper->map($sourceWishlist),
            'destination_wishlist' => $this->wishlistDataMapper->map($wishlistOutput->getWishlist()),
            'user_errors' => \array_map(
                function (Error $error) {
                    return [
                        'code' => $error->getCode(),
                        'message' => $error->getMessage(),
                    ];
                },
                $wishlistOutput->getErrors()
            )
        ];
    }
}
