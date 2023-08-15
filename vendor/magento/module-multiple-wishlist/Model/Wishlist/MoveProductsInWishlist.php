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
use Magento\Framework\Exception\LocalizedException;
use Magento\MultipleWishlist\Model\ItemManager;
use Magento\Wishlist\Model\ItemFactory as WishlistItemFactory;
use Magento\Wishlist\Model\ResourceModel\Wishlist\Collection as WishlistCollection;
use Magento\Wishlist\Model\ResourceModel\Wishlist\CollectionFactory as WishlistCollectionFactory;
use Magento\Wishlist\Model\Wishlist;
use Magento\Wishlist\Model\Wishlist\Data\Error;
use Magento\Wishlist\Model\Wishlist\Data\WishlistItem as WishlistItemData;
use Magento\Wishlist\Model\Wishlist\Data\WishlistOutput;

/**
 * Moving product items to another wishlist
 */
class MoveProductsInWishlist
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
     * @var WishlistCollectionFactory
     */
    private $collectionFactory;

    /**
     * @param WishlistItemFactory $wishlistItemFactory
     * @param ItemManager $itemManager
     * @param WishlistCollectionFactory $collectionFactory
     */
    public function __construct(
        WishlistItemFactory $wishlistItemFactory,
        ItemManager $itemManager,
        WishlistCollectionFactory $collectionFactory
    ) {
        $this->wishlistItemFactory = $wishlistItemFactory;
        $this->itemManager = $itemManager;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Moving products to a new wishlist
     *
     * @param Wishlist $wishlist
     * @param array $wishlistItems
     * @param int $customerId
     *
     * @return WishlistOutput
     */
    public function execute(Wishlist $wishlist, array $wishlistItems, int $customerId): WishlistOutput
    {
        $collection = $this->collectionFactory->create();
        $collection->filterByCustomerId($customerId);

        foreach ($wishlistItems as $wishlistItem) {
            $this->moveItemInWishlist($wishlist, $wishlistItem, $collection);
        }

        return $this->prepareOutput($wishlist);
    }

    /**
     * Move product item to another wishlist
     *
     * @param Wishlist $wishlist
     * @param WishlistItemData $wishlistItemData
     * @param WishlistCollection $collection
     *
     * @return void
     */
    private function moveItemInWishlist(
        Wishlist $wishlist,
        WishlistItemData $wishlistItemData,
        WishlistCollection $collection
    ): void {
        $productName = '';
        try {
            $item = $this->wishlistItemFactory->create();
            $item->loadWithOptions($wishlistItemData->getId());
            $productName = $item->getProduct()->getName();
            if ((int) $wishlistItemData->getQuantity() <= 0) {
                throw new \InvalidArgumentException(__(
                    'The quantity %quantity is invalid.',
                    ['quantity' => $wishlistItemData->getQuantity()]
                )->render());
            }
            if ((int) $wishlistItemData->getQuantity() <= $item->getQty()) {
                $this->itemManager->move(
                    $item,
                    $wishlist,
                    $collection,
                    $wishlistItemData->getQuantity() ?: $item->getQty()
                );
            } else {
                $this->addError(
                    __(
                        'The maximum quantity that can be moved for "%sku" is %quantity.',
                        ['sku' => $item->getProduct()->getSku(), 'quantity' => $item->getQty()]
                    )->render()
                );
            }
        } catch (InvalidArgumentException $exception) {
            $this->addError($exception->getMessage());
        } catch (DomainException $exception) {
            if ($exception->getCode() == 1) {
                $errorMessage = __('"%product" is already present in %wishlist.', [
                    'product' => $productName,
                    'wishlist' => $wishlist->getName()
                ]);
            } else {
                $errorMessage = __('We cannot move %product.', [
                    'product' => $productName
                ]);
            }
            $this->addError($errorMessage->render());
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
