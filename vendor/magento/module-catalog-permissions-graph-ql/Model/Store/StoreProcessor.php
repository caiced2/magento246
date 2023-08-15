<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogPermissionsGraphQl\Model\Store;

use Magento\GraphQl\Model\Query\ContextInterface;

/**
 * Process context to extract store data
 */
class StoreProcessor
{
    /**
     * Get store id from context
     *
     * @param ContextInterface|null $context
     * @return int|null
     */
    public function getStoreId(ContextInterface $context = null) : ?int
    {
        $storeId = null;
        if ($context) {
            $storeId = (int) $context->getExtensionAttributes()->getStore()->getId();
        }

        return $storeId;
    }
}
