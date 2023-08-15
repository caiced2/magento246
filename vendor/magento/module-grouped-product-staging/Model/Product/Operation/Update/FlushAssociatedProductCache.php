<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GroupedProductStaging\Model\Product\Operation\Update;

use Magento\CatalogStaging\Model\Product\Operation\Update\TemporaryUpdateProcessor;
use Magento\GroupedProduct\Model\Product\Type\Grouped;

class FlushAssociatedProductCache
{
    /**
     * @param TemporaryUpdateProcessor $temporaryUpdateProcessor
     * @param \Magento\Catalog\Model\Product $entity
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeLoadEntity(
        TemporaryUpdateProcessor $temporaryUpdateProcessor,
        $entity
    ) {
        $this->flushCache($entity);
    }

    /**
     * @param TemporaryUpdateProcessor $temporaryUpdateProcessor
     * @param \Magento\Catalog\Model\Product $entity
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeBuildEntity(
        TemporaryUpdateProcessor $temporaryUpdateProcessor,
        $entity
    ) {
        $this->flushCache($entity);
    }

    /**
     * @param \Magento\Catalog\Model\Product $entity
     * @return void
     */
    private function flushCache(\Magento\Catalog\Model\Product $entity)
    {
        if ($entity->getTypeId() === Grouped::TYPE_CODE) {
            /** @var \Magento\GroupedProduct\Model\Product\Type\Grouped $typeInstance */
            $typeInstance = $entity->getTypeInstance();
            $typeInstance->flushAssociatedProductsCache($entity);
        }
    }
}
