<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Catalog\Api\Data\ProductCustomOptionInterface;
use Magento\Catalog\Api\Data\ProductCustomOptionInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;

/** @var \Magento\TestFramework\ObjectManager $objectManager */
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(ProductRepositoryInterface::class);

/** @var $product \Magento\Catalog\Model\Product */
$product = $objectManager->create(\Magento\Catalog\Model\Product::class);
$product->isObjectNew(true);
$product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
    ->setId(1)
    ->setAttributeSetId(4)
    ->setWebsiteIds([1])
    ->setName('Simple Product')
    ->setSku('simple')
    ->setPrice(10)
    ->setWeight(1)
    ->setShortDescription("Short description")
    ->setTaxClassId(0)
    ->setDescription('Description with <b>html tag</b>')
    ->setMetaTitle('meta title')
    ->setMetaKeyword('meta keyword')
    ->setMetaDescription('meta description')
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    ->setStockData(
        [
            'use_config_manage_stock'   => 1,
            'qty'                       => 100,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 1,
        ]
    )->setCanSaveCustomOptions(true)
    ->setHasOptions(true);

$eavAttributeValues = [
    'special_price' => 3.82,
    'special_from_date' => date('Y-m-d', strtotime('+1 day')),
    'special_to_date' => date('Y-m-d', strtotime('+3 day'))
];

foreach ($eavAttributeValues as $attributeCode => $attributeValue) {
    $product->setCustomAttribute($attributeCode, $attributeValue);
}

/** @var ProductCustomOptionInterfaceFactory $customOptionFactory */
$customOptionFactory = $objectManager->create(ProductCustomOptionInterfaceFactory::class);

/** @var ProductCustomOptionInterface $customOption */
$customOption = $customOptionFactory->create(
    [
        'data' => [
            'record_id' => 0,
            'sort_order' => 1,
            'is_require' => 1,
            'sku' => 'test-option-title-1',
            'max_characters' => 50,
            'title' => 'Test option title 1',
            'type' => 'area',
            'price' => 10,
            'price_type' => 'fixed',
        ]
    ]
);
$customOption->setProductSku($product->getSku());

$product->setOptions([$customOption]);
$productRepository->save($product);
