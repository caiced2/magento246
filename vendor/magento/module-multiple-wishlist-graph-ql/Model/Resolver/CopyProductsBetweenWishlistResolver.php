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
use Magento\MultipleWishlist\Helper\Data as MultipleWishlistConfig;
use Magento\MultipleWishlist\Model\Wishlist\CopyProductsInWishlist;
use Magento\Wishlist\Model\ResourceModel\Wishlist as WishlistResourceModel;
use Magento\Wishlist\Model\Wishlist\Config as WishlistConfig;
use Magento\Wishlist\Model\Wishlist\Data\Error;
use Magento\Wishlist\Model\Wishlist\Data\WishlistItemFactory;
use Magento\Wishlist\Model\WishlistFactory;
use Magento\WishlistGraphQl\Mapper\WishlistDataMapper;

/**
 * Copy wishlist items resolver
 */
class CopyProductsBetweenWishlistResolver implements ResolverInterface
{
    /**
     * @var CopyProductsInWishlist
     */
    private $copyProductsInWishlist;

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
     * @var MultipleWishlistConfig
     */
    private $multipleWishlistConfig;

    /**
     * @param WishlistResourceModel $wishlistResource
     * @param WishlistFactory $wishlistFactory
     * @param WishlistConfig $wishlistConfig
     * @param CopyProductsInWishlist $copyProductsInWishlist
     * @param WishlistDataMapper $wishlistDataMapper
     * @param MultipleWishlistConfig $multipleWishlistConfig
     */
    public function __construct(
        WishlistResourceModel $wishlistResource,
        WishlistFactory $wishlistFactory,
        WishlistConfig $wishlistConfig,
        CopyProductsInWishlist $copyProductsInWishlist,
        WishlistDataMapper $wishlistDataMapper,
        MultipleWishlistConfig $multipleWishlistConfig
    ) {
        $this->wishlistResource = $wishlistResource;
        $this->wishlistFactory = $wishlistFactory;
        $this->wishlistConfig = $wishlistConfig;
        $this->copyProductsInWishlist = $copyProductsInWishlist;
        $this->wishlistDataMapper = $wishlistDataMapper;
        $this->multipleWishlistConfig = $multipleWishlistConfig;
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
        if (!$this->wishlistConfig->isEnabled()) {
            throw new GraphQlInputException(__('The wishlist configuration is currently disabled.'));
        }

        if (!$this->multipleWishlistConfig->isMultipleEnabled()) {
            throw new GraphQlInputException(__('The multiple wishlist configuration is currently disabled.'));
        }

        $customerId = $context->getUserId();

        if (null === $customerId || 0 === $customerId) {
            throw new GraphQlAuthorizationException(__('The current user cannot perform operations on wishlist'));
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

        $items = [];
        $wishlistItemsData = $args['wishlistItems'];

        foreach ($wishlistItemsData as $wishlistItemData) {
            $items[] = (new WishlistItemFactory())->create($wishlistItemData);
        }

        $wishlistOutput = $this->copyProductsInWishlist->execute($destinationWishlist, $items);

        if (count($wishlistOutput->getErrors()) !== count($items)) {
            $this->wishlistResource->save($destinationWishlist);
        }

        return [
            'destination_wishlist' => $this->wishlistDataMapper->map($wishlistOutput->getWishlist()),
            'source_wishlist' => $this->wishlistDataMapper->map($sourceWishlist),
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
