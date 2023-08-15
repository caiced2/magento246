<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissions\Plugin\CatalogSearch\Model\ResourceModel\Fulltext;

use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection as DefaultCollection;
use Magento\CatalogPermissions\Model\Permission;

/**
 * Plugin modifies layered navigation filter items count according to price category permissions
 */
class Collection
{
    /**
     * Changes count of price items in filter according to category permissions
     *
     * @param DefaultCollection $subject
     * @param array $result
     * @param string $field
     * @return array
     */
    public function afterGetFacetedData(DefaultCollection $subject, array $result, string $field): array
    {
        if (!empty($result) && $field === 'price') {
            $productPrices = [];
            foreach ($subject->getItems() as $product) {
                if ((int) $product->getData('grant_catalog_product_price') === Permission::PERMISSION_DENY) {
                    $productPrices[] = (float) $product->getMinimalPrice();
                }
            }
            foreach ($result as $key => $aggregation) {
                $count = $aggregation['count'];
                if (!isset($aggregation['from']) || !isset($aggregation['to'])) {
                    continue;
                }

                if (!empty($productPrices)) {
                    $from = $aggregation['from'];
                    $to = $aggregation['to'];
                    foreach ($productPrices as $price) {
                        if ($price >= $from && $price < $to) {
                            $count--;
                        }
                    }
                }
                if ($count > 0) {
                    $result[$key]['count'] = $count;
                } else {
                    unset($result[$key]);
                }
            }
        }

        return $result;
    }
}

