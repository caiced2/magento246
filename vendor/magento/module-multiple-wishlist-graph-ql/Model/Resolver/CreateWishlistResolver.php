<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MultipleWishlistGraphQl\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\Enum\DataMapperInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\MultipleWishlist\Helper\Data as MultipleWishlistConfig;
use Magento\MultipleWishlist\Model\WishlistEditor;
use Magento\Wishlist\Model\Wishlist\Config as WishlistConfig;
use Magento\WishlistGraphQl\Mapper\WishlistDataMapper;

/**
 * Create a new customer wishlist
 */
class CreateWishlistResolver implements ResolverInterface
{
    /**
     * @var DataMapperInterface
     */
    private $enumDataMapper;

    /**
     * @var WishlistConfig
     */
    private $wishlistConfig;

    /**
     * @var WishlistEditor
     */
    private $wishlistEditor;

    /**
     * @var WishlistDataMapper
     */
    private $wishlistDataMapper;

    /**
     * @var MultipleWishlistConfig
     */
    private $multipleWishlistConfig;

    /**
     * @param WishlistConfig $wishlistConfig
     * @param WishlistEditor $wishlistEditor
     * @param DataMapperInterface $enumDataMapper
     * @param MultipleWishlistConfig $multipleWishlistConfig
     * @param WishlistDataMapper $wishlistDataMapper
     */
    public function __construct(
        WishlistConfig $wishlistConfig,
        WishlistEditor $wishlistEditor,
        DataMapperInterface $enumDataMapper,
        MultipleWishlistConfig $multipleWishlistConfig,
        WishlistDataMapper $wishlistDataMapper
    ) {
        $this->wishlistConfig = $wishlistConfig;
        $this->wishlistEditor = $wishlistEditor;
        $this->enumDataMapper = $enumDataMapper;
        $this->multipleWishlistConfig = $multipleWishlistConfig;
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

        if (!$this->multipleWishlistConfig->isMultipleEnabled()) {
            throw new GraphQlInputException(__('The multiple wishlist configuration is currently disabled.'));
        }

        $customerId = $context->getUserId();

        /* Guest checking */
        if (null === $customerId || 0 === $customerId) {
            throw new GraphQlAuthorizationException(__('The current user cannot perform operations on wishlist'));
        }

        $name = $args['input']['name'];
        $visibility = $this->getVisibility($args['input']['visibility']);

        try {
            $wishlist = $this->wishlistEditor->edit($customerId, $name, $visibility);
        } catch (LocalizedException $exception) {
            throw new GraphQlInputException(__($exception->getMessage()));
        }

        return ['wishlist' => $this->wishlistDataMapper->map($wishlist)];
    }

    /**
     * Get wishlist visibility
     *
     * @param string|null $visibility
     *
     * @return int|null
     */
    private function getVisibility(?string $visibility): ?int
    {
        if ($visibility === null) {
            return null;
        }

        $visibilityEnums = $this->enumDataMapper->getMappedEnums('WishlistVisibilityEnum');

        return array_search(strtolower($visibility), $visibilityEnums) ?? null;
    }

    /**
     * Get wishlist mapped visibility
     *
     * @param int $visibility
     *
     * @return string|null
     */
    private function getMappedVisibility(int $visibility): ?string
    {
        if ($visibility === null) {
            return null;
        }

        $visibilityEnums = $this->enumDataMapper->getMappedEnums('WishlistVisibilityEnum');

        return isset($visibilityEnums[$visibility]) ? strtoupper($visibilityEnums[$visibility]) : null;
    }
}
