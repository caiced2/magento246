<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MultipleWishlist\Model\Wishlist;

use DomainException;
use Exception;
use InvalidArgumentException;
use Magento\MultipleWishlist\Model\ItemManager;
use Magento\Wishlist\Model\ItemFactory as WishlistItemFactory;
use Magento\Wishlist\Model\Wishlist;
use Magento\Wishlist\Model\Wishlist\Data\Error;
use Magento\Wishlist\Model\Wishlist\Data\WishlistItem as WishlistItemData;
use Magento\Wishlist\Model\Wishlist\Data\WishlistOutput;

/**
 * Copying product items to another wishlist
 */
class CopyProductsInWishlist
{
    /**#@+
     * Error message codes
     */
    private const ERROR_UNDEFINED = 'UNDEFINED';
    /**#@-*/

    /**
     * @var array
     */
    private $errors = [];

    /**
     * @var WishlistItemFactory
     */
    private $wishlistItemFactory;

    /**
     * @var ItemManager
     */
    private $itemManager;

    /**
     * @param WishlistItemFactory $wishlistItemFactory
     * @param ItemManager $itemManager
     */
    public function __construct(
        WishlistItemFactory $wishlistItemFactory,
        ItemManager $itemManager
    ) {
        $this->wishlistItemFactory = $wishlistItemFactory;
        $this->itemManager = $itemManager;
    }

    /**
     * Copying products to a new wishlist
     *
     * @param Wishlist $wishlist
     * @param array $wishlistItems
     *
     * @return WishlistOutput
     */
    public function execute(Wishlist $wishlist, array $wishlistItems): WishlistOutput
    {
        foreach ($wishlistItems as $wishlistItem) {
            $this->copyItemInWishlist($wishlist, $wishlistItem);
        }

        return $this->prepareOutput($wishlist);
    }

    /**
     * Copy product item to another wishlist
     *
     * @param Wishlist $wishlist
     * @param WishlistItemData $wishlistItemData
     *
     * @return void
     */
    private function copyItemInWishlist(Wishlist $wishlist, WishlistItemData $wishlistItemData): void
    {
        $item = $this->wishlistItemFactory->create();

        try {
            $item->loadWithOptions($wishlistItemData->getId());
            if ((int) $wishlistItemData->getQuantity() <= 0) {
                throw new \InvalidArgumentException(__(
                    'The quantity %quantity is invalid.',
                    ['quantity' => $wishlistItemData->getQuantity()]
                )->render());
            }
            if ((int) $wishlistItemData->getQuantity() <= $item->getQty()) {
                $this->itemManager->copy($item, $wishlist, $wishlistItemData->getQuantity() ?: $item->getQty());
            } else {
                $this->addError(
                    __(
                        'The maximum quantity that can be copied for "%sku" is %quantity.',
                        ['sku' => $item->getProduct()->getSku(), 'quantity' => $item->getQty()]
                    )->render()
                );
            }
        } catch (InvalidArgumentException $exception) {
            $this->addError($exception->getMessage());
        } catch (DomainException $exception) {
            $this->addError(
                __(
                    'The "%product" is already present in "%wishlist"',
                    ['product' => $item->getProduct()->getName(), 'wishlist' => $wishlist->getName()]
                )->render()
            );
        } catch (Exception $exception) {
            $this->addError($exception->getMessage());
        }
    }

    /**
     * Add wishlist line item error
     *
     * @param string $message
     * @param string|null $code
     *
     * @return void
     */
    private function addError(string $message, string $code = null): void
    {
        $this->errors[] = new Error(
            $message,
            $code ?? self::ERROR_UNDEFINED
        );
    }

    /**
     * Prepare output
     *
     * @param Wishlist $wishlist
     *
     * @return WishlistOutput
     */
    private function prepareOutput(Wishlist $wishlist): WishlistOutput
    {
        $output = new WishlistOutput($wishlist, $this->errors);
        $this->errors = [];

        return $output;
    }
}
