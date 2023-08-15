<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MultipleWishlistGraphQl\Model\Resolver;

use Exception;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\MultipleWishlist\Helper\Data as MultipleWishlistHelper;
use Magento\Wishlist\Model\ResourceModel\Wishlist as WishlistResourceModel;
use Magento\Wishlist\Model\ResourceModel\Wishlist\Collection as WishlistCollection;
use Magento\Wishlist\Model\ResourceModel\Wishlist\CollectionFactory as WishlistCollectionFactory;
use Magento\Wishlist\Model\Wishlist;
use Magento\Wishlist\Model\Wishlist\Config as WishlistConfig;
use Magento\Wishlist\Model\WishlistFactory;
use Magento\WishlistGraphQl\Mapper\WishlistDataMapper;

/**
 * Delete the customer wishlist
 */
class DeleteWishlistResolver implements ResolverInterface
{
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
     * @var WishlistCollectionFactory
     */
    private $wishlistCollectionFactory;

    /**
     * @var WishlistDataMapper
     */
    private $wishlistDataMapper;

    /**
     * @param WishlistConfig $wishlistConfig
     * @param WishlistResourceModel $wishlistResource
     * @param WishlistFactory $wishlistFactory
     * @param MultipleWishlistHelper $multipleWishlistHelper
     * @param WishlistCollectionFactory $wishlistCollectionFactory
     * @param WishlistDataMapper $wishlistDataMapper
     */
    public function __construct(
        WishlistConfig $wishlistConfig,
        WishlistResourceModel $wishlistResource,
        WishlistFactory $wishlistFactory,
        MultipleWishlistHelper $multipleWishlistHelper,
        WishlistCollectionFactory $wishlistCollectionFactory,
        WishlistDataMapper $wishlistDataMapper
    ) {
        $this->wishlistConfig = $wishlistConfig;
        $this->wishlistResource = $wishlistResource;
        $this->wishlistFactory = $wishlistFactory;
        $this->multipleWishlistHelper = $multipleWishlistHelper;
        $this->wishlistCollectionFactory = $wishlistCollectionFactory;
        $this->wishlistDataMapper = $wishlistDataMapper;
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

        if (!$this->multipleWishlistHelper->isMultipleEnabled()) {
            throw new GraphQlInputException(__('The multiple wishlist configuration is currently disabled.'));
        }

        $customerId = $context->getUserId();

        if (null === $customerId || 0 === $customerId) {
            throw new GraphQlAuthorizationException(
                __('The current user cannot perform operations on wishlist')
            );
        }

        $wishlist = $this->wishlistFactory->create();
        $this->wishlistResource->load($wishlist, $args['wishlistId']);
        $this->validateWishlist($wishlist, $customerId);

        if ($this->multipleWishlistHelper->isWishlistDefault($wishlist)) {
            throw new GraphQlInputException(__('The default wish list can\'t be deleted.'));
        }

        try {
            $status = $this->wishlistResource->delete($wishlist);
            /** @var WishlistCollection $collection */
        } catch (Exception $exception) {
            throw new GraphQlInputException(__('We can\'t delete the wish list right now.'));
        }
        $collection = $this->wishlistCollectionFactory->create();
        $collection->filterByCustomerId($customerId);
        $wishlists = [];

        /** @var Wishlist $wishList */
        foreach ($collection->getItems() as $wishList) {
            array_push($wishlists, $this->wishlistDataMapper->map($wishList));
        }

        return [
            'status'=> $status,
            'wishlists' => $wishlists
        ];
    }

    /**
     * Validate wishlist based on customer
     *
     * @param Wishlist $wishlist
     * @param int $customerId
     * @return void
     * @throws GraphQlAuthorizationException
     * @throws GraphQlInputException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function validateWishlist(Wishlist $wishlist, int $customerId): void
    {
        if (null === $wishlist->getId()) {
            throw new GraphQlInputException(__('The wishlist was not found.'));
        }

        if ((int) $wishlist->getCustomerId() !== $customerId) {
            throw new GraphQlAuthorizationException(
                __('The wish list is not assigned to your account and can\'t be edited.')
            );
        }
    }
}
