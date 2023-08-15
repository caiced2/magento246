<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Plugin\Helper\Product\Edit\Action;

/**
 * Plugin for \Magento\Catalog\Helper\Product\Edit\Action\Attribute
 */
class Attribute
{
    /**
     * Excluded attributes for catalog mass update grid.
     *
     * @var array
     */
    private $excludedAttributes = ['special_from_date', 'special_to_date'];

    /**
     * Added excluded attributes for catalog mass update grid.
     *
     * @param \Magento\Catalog\Helper\Product\Edit\Action\Attribute $subject
     * @param array $result
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetExcludedAttributes(
        \Magento\Catalog\Helper\Product\Edit\Action\Attribute $subject,
        array $result
    ): array {
        return array_merge($result, $this->excludedAttributes);
    }
}
