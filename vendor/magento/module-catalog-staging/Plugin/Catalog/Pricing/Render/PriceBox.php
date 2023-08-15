<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Plugin\Catalog\Pricing\Render;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Pricing\Render\PriceBox as PriceBoxSubject;
use Magento\Framework\EntityManager\MetadataPool;

/**
 * Plugin for PriceBox class
 */
class PriceBox
{
    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @param MetadataPool $metadataPool
     */
    public function __construct(MetadataPool $metadataPool)
    {
        $this->metadataPool = $metadataPool;
    }

    /**
     * @param PriceBoxSubject $subject
     * @param string $result
     * @return string
     * @throws \Exception
     */
    public function afterGetCacheKey(PriceBoxSubject $subject, $result)
    {
        /** @var Product $saleableItem */
        $saleableItem = $subject->getSaleableItem();
        $entityMetadata = $this->metadataPool->getMetadata(ProductInterface::class);
        $linkField = $entityMetadata->getLinkField();
        $linkValue = $saleableItem->getData($linkField);

        return "{$result}-{$linkValue}";
    }
}
