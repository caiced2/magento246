<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Model\Product;

use Magento\Staging\Model\Entity\RetrieverInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;

class Retriever implements RetrieverInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        ProductRepositoryInterface $productRepository
    ) {
        $this->productRepository = $productRepository;
    }

    /**
     * @inheritDoc
     */
    public function getEntity($entityId)
    {
        return $this->productRepository->getById($entityId);
    }

    /**
     * Retrieve product in edit mode
     *
     * @param string $entityId
     * @param string $editMode
     * @return \Magento\Catalog\Api\Data\ProductInterface
     */
    public function getProduct($entityId, $editMode)
    {
        return $this->productRepository->getById($entityId, $editMode);
    }
}
