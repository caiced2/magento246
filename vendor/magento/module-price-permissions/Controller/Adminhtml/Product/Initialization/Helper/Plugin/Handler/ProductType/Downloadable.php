<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PricePermissions\Controller\Adminhtml\Product\Initialization\Helper\Plugin\Handler\ProductType;

use Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper\HandlerInterface;
use Magento\Catalog\Model\Product;

/**
 */
class Downloadable implements HandlerInterface
{
    /**
     * Handle data received from Downloadable Links tab of downloadable products
     *
     * @param Product $product
     * @return void
     */
    public function handle(Product $product)
    {
        if ($product->getTypeId() != \Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE) {
            return;
        }

        $downloadableData = $product->getDownloadableData();
        if (is_array($downloadableData) && isset($downloadableData['link'])) {
            /** @var \Magento\Downloadable\Model\Product\Type $type */
            $type = $product->getTypeInstance();
            $originalLinks = $type->getLinks($product);
            foreach ($downloadableData['link'] as &$downloadableDataItem) {
                $linkId = $downloadableDataItem['link_id'];
                if (isset($originalLinks[$linkId]) && empty($downloadableDataItem['is_delete'])) {
                    $originalLink = $originalLinks[$linkId];
                    $downloadableDataItem['price'] = $originalLink->getPrice();
                } else {
                    // Set zero price for new links
                    $downloadableDataItem['price'] = 0;
                }
            }
            $product->setDownloadableData($downloadableData);
        }
    }
}
