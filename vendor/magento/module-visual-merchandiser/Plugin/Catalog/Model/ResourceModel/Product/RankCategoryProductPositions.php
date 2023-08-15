<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VisualMerchandiser\Plugin\Catalog\Model\ResourceModel\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Category as CategoryResourceModel;
use Magento\Catalog\Model\ResourceModel\Product\CategoryLink as CategoryProductLink;
use Magento\Catalog\Model\Category\Product\PositionResolver as CategoryProductPositionResolver;
use Magento\Framework\App\ResourceConnection;

/**
 * Rank category product positions on product save.
 */
class RankCategoryProductPositions
{
    /**
     * @var CategoryResourceModel
     */
    private $categoryResourceModel;

    /**
     * @var CategoryProductPositionResolver
     */
    private $positionResolver;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param CategoryResourceModel $categoryResourceModel
     * @param CategoryProductPositionResolver $positionResolver
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        CategoryResourceModel $categoryResourceModel,
        CategoryProductPositionResolver $positionResolver,
        ResourceConnection $resourceConnection
    ) {
        $this->categoryResourceModel = $categoryResourceModel;
        $this->positionResolver = $positionResolver;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Rank category product positions.
     *
     * @param CategoryProductLink $categoryProductLink
     * @param callable $proceed
     * @param ProductInterface $product
     * @param array $insertLinks
     * @param bool $insert
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundUpdateCategoryLinks(
        CategoryProductLink $categoryProductLink,
        callable $proceed,
        ProductInterface $product,
        array $insertLinks,
        $insert = false
    ): array {
        foreach ($insertLinks as $link) {
            $this->resourceConnection->getConnection()->insertOnDuplicate(
                $this->categoryResourceModel->getCategoryProductTable(),
                $this->getCategoryProductPosition((int) $product->getId(), (int) $link['category_id']),
                ['position']
            );
        }

        return array_column($insertLinks, 'category_id');
    }

    /**
     * Retrieve and prepare category product positions array.
     *
     * @param int $productId
     * @param int $categoryId
     * @return array
     */
    private function getCategoryProductPosition(int $productId, int $categoryId): array
    {
        $lastPosition = $this->positionResolver->getMinPosition((int) $categoryId);
        $categoryProductPosition[] = [
            'category_id' => (int) $categoryId,
            'product_id' => $productId,
            'position' => --$lastPosition,
        ];

        return $categoryProductPosition;
    }
}
