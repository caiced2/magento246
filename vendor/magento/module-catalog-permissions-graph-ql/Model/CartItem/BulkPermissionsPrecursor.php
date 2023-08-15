<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissionsGraphQl\Model\CartItem;

use Magento\CatalogPermissionsGraphQl\Model\CartItem\DataProvider\Processor\BulkPreloader;
use Magento\CatalogPermissionsGraphQl\Model\CartItem\DataProvider\Processor\PermissionsProcessor;
use Magento\CatalogPermissionsGraphQl\Model\Customer\GroupProcessor;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Quote\Model\Cart\Data\CartItem;
use Magento\Quote\Model\Cart\Data\Error;
use Magento\QuoteGraphQl\Model\CartItem\DataProvider\Processor\ItemDataProcessorInterface;
use Magento\QuoteGraphQl\Model\CartItem\PrecursorInterface;

/**
 * Preloads product permissions using bulk preloader.
 */
class BulkPermissionsPrecursor implements PrecursorInterface
{
    /** Error code(s) */
    private const ERROR_CODE_PERMISSION_DENIED = 'PERMISSION_DENIED';

    /**
     * @var array
     */
    private $errors = [];

    /**
     * @var BulkPreloader
     */
    private $bulkPermissionsLoader;

    /**
     * @var GroupProcessor
     */
    private $groupProcessor;

    /**
     * @var PermissionsProcessor
     */
    private $permissionsProcessor;

    /**
     * @param BulkPreloader $bulkPreloader
     * @param GroupProcessor $groupProcessor
     * @param PermissionsProcessor $permissionsProcessor
     */
    public function __construct(
        BulkPreloader $bulkPreloader,
        GroupProcessor $groupProcessor,
        PermissionsProcessor $permissionsProcessor
    ) {
        $this->bulkPermissionsLoader = $bulkPreloader;
        $this->groupProcessor = $groupProcessor;
        $this->permissionsProcessor = $permissionsProcessor;
    }

    /**
     * @inheritdoc
     */
    public function process(array $cartItemData, ContextInterface $context): array
    {
        $skus = \array_map(
            function ($cartItem) {
                return $cartItem['sku'];
            },
            $cartItemData
        );
        $this->bulkPermissionsLoader->loadBySkus(
            $skus,
            (int)$this->groupProcessor->getCustomerGroup($context),
            (int)$context->getExtensionAttributes()->getStore()->getId()
        );
        foreach ($cartItemData as $key => $cartItem) {
            if (!$this->itemIsAllowedToCart($cartItem, $context)) {
                $this->errors[] = $this->createError(
                    __('You cannot add "%1" to the cart.', $cartItem['sku'])->render(),
                    $key
                );
                unset($cartItemData[$key]);
            }
        }
        return $cartItemData;
    }

    /**
     * Create cart item error object.
     *
     * @param string $message
     * @param int $cartItemPosition
     * @return Error
     */
    private function createError(string $message, int $cartItemPosition = 0): Error
    {
        return new Error(
            $message,
            self::ERROR_CODE_PERMISSION_DENIED,
            $cartItemPosition
        );
    }

    /**
     * Check if the item can be added to cart.
     *
     * @param array $cartItemData
     * @param ContextInterface $context
     * @return bool
     */
    private function itemIsAllowedToCart(array $cartItemData, ContextInterface $context): bool
    {
        $cartItemData = $this->permissionsProcessor->process($cartItemData, $context);
        if (isset($cartItemData['grant_checkout']) && $cartItemData['grant_checkout'] === false) {
            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
