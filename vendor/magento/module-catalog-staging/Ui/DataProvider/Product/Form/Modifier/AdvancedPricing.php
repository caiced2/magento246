<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AdvancedPricing as CatalogAdvancedPricing;

class AdvancedPricing extends CatalogAdvancedPricing
{
    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        $this->meta = parent::modifyMeta($meta);

        unset(
            $this->meta['advanced_pricing_modal']['children']
            ['advanced-pricing']['children']['container_special_from_date']
        );
        unset(
            $this->meta['advanced_pricing_modal']['children']
            ['advanced-pricing']['children']['container_special_to_date']
        );
        return $this->meta;
    }
}
