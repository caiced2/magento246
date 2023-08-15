<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Model\GiftRegistry;

use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Helper\Data;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\GiftRegistry\Helper\Data as GiftRegistryHelper;
use Magento\GiftRegistry\Model\Entity as GiftRegistry;
use Magento\GiftRegistry\Model\GiftRegistry\Data\ErrorFactory;
use Magento\GiftRegistry\Model\GiftRegistry\Data\GiftRegistryOutput;
use Magento\GiftRegistry\Model\GiftRegistry\Data\GiftRegistryOutputFactory;
use Magento\GiftRegistry\Model\ResourceModel\Entity as GiftRegistryResourceModel;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;

/**
 * Move all items items from cart to the gift registry
 */
class MoveCartItemsToGiftRegistry
{
    /**#@+
     * Error message codes
     */
    private const ERROR_NOT_FOUND = 'NOT_FOUND';
    private const ERROR_UNDEFINED = 'UNDEFINED';
    private const ERROR_OUT_OF_STOCK = 'OUT_OF_STOCK';
    /**#@-*/

    /**
     * @var array
     */
    private $errors = [];

    /**
     * @var GiftRegistryResourceModel
     */
    private $entityResourceModel;

    /**
     * @var GiftRegistryHelper
     */
    private $giftRegistryHelper;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var GiftRegistryOutputFactory
     */
    private $giftRegistryOutputFactory;

    /**
     * @var ErrorFactory
     */
    private $errorFactory;

    /**
     * @param GiftRegistryResourceModel $entityResourceModel
     * @param GiftRegistryHelper $giftRegistryHelper
     * @param ProductRepositoryInterface $productRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param GiftRegistryOutputFactory $giftRegistryOutputFactory
     * @param ErrorFactory $errorFactory
     */
    public function __construct(
        GiftRegistryResourceModel $entityResourceModel,
        GiftRegistryHelper $giftRegistryHelper,
        ProductRepositoryInterface $productRepository,
        CartRepositoryInterface $quoteRepository,
        GiftRegistryOutputFactory $giftRegistryOutputFactory,
        ErrorFactory $errorFactory
    ) {
        $this->entityResourceModel = $entityResourceModel;
        $this->giftRegistryHelper = $giftRegistryHelper;
        $this->productRepository = $productRepository;
        $this->quoteRepository = $quoteRepository;
        $this->giftRegistryOutputFactory = $giftRegistryOutputFactory;
        $this->errorFactory = $errorFactory;
    }

    /**
     * Adding products to gift registry
     *
     * @param Quote $quote
     * @param GiftRegistry $giftRegistry
     * @return GiftRegistryOutput
     *
     * @throws AlreadyExistsException
     */
    public function execute(Quote $quote, GiftRegistry $giftRegistry): GiftRegistryOutput
    {
        $countOfCartItems = count($quote->getAllVisibleItems());
        foreach ($quote->getAllVisibleItems() as $item) {
            if ($this->addItemToGiftRegistry($giftRegistry, $item)) {
                $quote->removeItem($item->getId());
            }
        }
        $giftRegistryOutput = $this->prepareOutput($giftRegistry);

        if (count($giftRegistryOutput->getErrors()) < $countOfCartItems) {
            $this->entityResourceModel->save($giftRegistry);
            $this->quoteRepository->save($quote);
        }

        return $giftRegistryOutput;
    }

    /**
     * Add product item to gift registry
     *
     * @param GiftRegistry $giftRegistry
     * @param QuoteItem $item
     *
     * @return bool
     */
    private function addItemToGiftRegistry(GiftRegistry $giftRegistry, QuoteItem $item): bool
    {
        $productId = (int)$item->getProductId();

        if ($item->getHasError()) {
            $errorCode = self::ERROR_UNDEFINED;

            foreach ($item->getErrorInfos() as $errorInfo) {
                if ($errorInfo['code'] == Data::ERROR_QTY) {
                    $errorCode = self::ERROR_OUT_OF_STOCK;
                }
            }
            $this->addError(
                $item->getMessage(),
                $productId,
                $errorCode
            );
            return false;
        }

        if (!$this->giftRegistryHelper->canAddToGiftRegistry($item)) {
            $this->addError(
                __('You can\'t add virtual products, digital products or gift cards to gift registries.')->render(),
                $productId
            );
            return false;
        }

        try {
            $giftRegistryItem = $giftRegistry->addItem($item);
        } catch (NoSuchEntityException $e) {
            $this->addError(
                $e->getMessage(),
                $productId,
                self::ERROR_NOT_FOUND
            );
            return false;
        } catch (LocalizedException $e) {
            $this->addError(
                $e->getMessage(),
                $productId
            );
            return false;
        } catch (Exception $e) {
            $this->addError(
                __('We can\'t add product to the gift registry right now.')->render(),
                $productId
            );
            return false;
        }
        return true;
    }

    /**
     * Add gift registry line item error
     *
     * @param string $message
     * @param int $productId
     * @param string|null $code
     * @return void
     */
    private function addError(string $message, int $productId, string $code = null): void
    {
        $this->errors[] = $this->errorFactory->create(
            [
                'message' => $message,
                'productId' => $productId,
                'code' => $code ?? self::ERROR_UNDEFINED
            ]
        );
    }

    /**
     * Prepare output
     *
     * @param GiftRegistry $giftRegistry
     *
     * @return GiftRegistryOutput
     */
    private function prepareOutput(GiftRegistry $giftRegistry): GiftRegistryOutput
    {
        $output = $this->giftRegistryOutputFactory->create([
            'giftRegistry' => $giftRegistry,
            'errors' => $this->errors
        ]);
        $this->errors = [];

        return $output;
    }
}
