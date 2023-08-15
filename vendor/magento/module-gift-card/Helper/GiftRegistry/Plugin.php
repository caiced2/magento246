<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Helper\GiftRegistry;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\GiftRegistry\Helper\Data as DataHelper;
use Magento\Quote\Model\Quote\Item;
use Magento\Catalog\Model\Product;
use Magento\GiftCard\Model\Catalog\Product\Type\Giftcard as ProductType;

/**
 * Plugin for Magento\GiftRegistry\Helper\Data
 */
class Plugin
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(ProductRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * Perform specific checks for gift cards
     *
     * @param DataHelper $subject
     * @param bool $result
     * @param Item|Product $item
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCanAddToGiftRegistry(DataHelper $subject, $result, $item)
    {
        // skip following calculations for virtual product or cart item
        if (!$result) {
            return false;
        }

        $productType = $item instanceof Item ? $item->getProductType() : $item->getTypeId();

        if ($productType == ProductType::TYPE_GIFTCARD) {
            $product = $item instanceof Item ? $this->productRepository->getById($item->getProductId()) : $item;

            return $product->getTypeInstance()->isTypePhysical($product);
        }

        return true;
    }
}
