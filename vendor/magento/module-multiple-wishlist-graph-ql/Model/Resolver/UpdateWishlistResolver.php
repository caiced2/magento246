<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MultipleWishlistGraphQl\Model\Resolver;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\Enum\DataMapperInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\MultipleWishlist\Helper\Data as MultipleWishlistHelper;
use Magento\Wishlist\Model\ResourceModel\Wishlist as WishlistResourceModel;
use Magento\Wishlist\Model\ResourceModel\Wishlist\CollectionFactory;
use Magento\Wishlist\Model\Wishlist;
use Magento\Wishlist\Model\Wishlist\Config as WishlistConfig;
use Magento\Wishlist\Model\WishlistFactory;

/**
 * Update the customer wishlist
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UpdateWishlistResolver implements ResolverInterface
{
    /**
     * @var WishlistConfig
     */
    private $wishlistConfig;

    /**
     * @var WishlistFactory
     */
    private $wishlistFactory;

    /**
     * @var MultipleWishlistHelper
     */
    private $multipleWishlistHelper;

    /**
     * @var WishlistResourceModel
     */
    private $wishlistResource;

    /**
     * @var DataMapperInterface
     */
    private $enumDataMapper;

    /**
     * @var CollectionFactory
     */
    private $wishlistColFactory;

    /**
     * UpdateWishlistResolver constructor.
     * @param WishlistConfig $wishlistConfig
     * @param WishlistFactory $wishlistFactory
     * @param MultipleWishlistHelper $multipleWishlistHelper
     * @param WishlistResourceModel $wishlistResourceModel
     * @param DataMapperInterface $enumDataMapper
     * @param CollectionFactory $wishlistColFactory
     */
    public function __construct(
        WishlistConfig $wishlistConfig,
        WishlistFactory $wishlistFactory,
        MultipleWishlistHelper $multipleWishlistHelper,
        WishlistResourceModel $wishlistResourceModel,
        DataMapperInterface $enumDataMapper,
        CollectionFactory $wishlistColFactory
    ) {
        $this->wishlistConfig = $wishlistConfig;
        $this->wishlistResource = $wishlistResourceModel;
        $this->wishlistFactory = $wishlistFactory;
        $this->multipleWishlistHelper = $multipleWishlistHelper;
        $this->enumDataMapper = $enumDataMapper;
        $this->wishlistColFactory = $wishlistColFactory;
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
        $this->validateWishlistConfiguration();
        $wishlistId = isset($args['wishlistId']) ? $args['wishlistId'] : null;

        $customerId = $context->getUserId();

        if (null === $customerId || 0 === $customerId) {
            throw new GraphQlAuthorizationException(
                __('The current user cannot perform operations on wishlist')
            );
        }

        $wishlist = $this->wishlistFactory->create();
        $this->wishlistResource->load($wishlist, $wishlistId);
        $this->validateWishlist($wishlist, $customerId);
        $newWishlistName = isset($args['name']) ? $args['name'] : (string)$wishlist->getName();
        try {
            $this->checkForExistingWishlist($wishlist, $customerId, $newWishlistName);
            $visibility = isset($args['visibility'])
                ? $this->getVisibility($args['visibility'])
                : $wishlist->getVisibility();
            $wishlist->setName($newWishlistName ?? $wishlist->getName());
            $wishlist->setVisibility($visibility);
            $this->wishlistResource->save($wishlist);
        } catch (LocalizedException $exception) {
            throw new GraphQlInputException(__($exception->getMessage()));
        } catch (Exception $exception) {
            throw new GraphQlInputException(__('We can\'t update the wish list right now.'));
        }

        return [
            'uid' => $wishlist->getId(),
            'visibility' => $this->getMappedVisibility((int) $wishlist->getVisibility()),
            'name' => $wishlist->getName(),
        ];
    }

    /**
     * Validate configuration
     *
     * @throws GraphQlInputException
     */
    private function validateWishlistConfiguration(): void
    {
        if (!$this->wishlistConfig->isEnabled()) {
            throw new GraphQlInputException(__('The wishlist configuration is currently disabled.'));
        }

        if (!$this->multipleWishlistHelper->isMultipleEnabled()) {
            throw new GraphQlInputException(__('The multiple wishlist configuration is currently disabled.'));
        }
    }

    /**
     * Validate wishlist based on customer
     *
     * @param Wishlist $wishlist
     * @param int $customerId
     * @return void
     * @throws GraphQlAuthorizationException
     * @throws GraphQlInputException
     * @throws LocalizedException
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

    /**
     * Check for wishlist under the customer with same name
     *
     * @param Wishlist $wishlist
     * @param int $customerId
     * @param string $newWishlistName
     * @throws LocalizedException
     */
    private function checkForExistingWishlist(Wishlist $wishlist, int $customerId, string $newWishlistName): void
    {
        if ($newWishlistName !== null && $wishlist->getName() !== $newWishlistName) {
            $wishlistCollection = $this->wishlistColFactory->create();
            $wishlistCollection->filterByCustomerId($customerId);
            $wishlistCollection->addFieldToFilter('name', $newWishlistName);
            if ($wishlistCollection->getSize()) {
                throw new LocalizedException(
                    __('Wish list "%1" already exists.', $newWishlistName)
                );
            }
        }
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

    /**
     * Get wishlist visibility
     *
     * @param string $visibility
     *
     * @return int|null
     */
    private function getVisibility(string $visibility): ?int
    {
        if ($visibility === null) {
            return null;
        }

        $visibilityEnums = $this->enumDataMapper->getMappedEnums('WishlistVisibilityEnum');

        return array_search(strtolower($visibility), $visibilityEnums) ?? null;
    }
}
