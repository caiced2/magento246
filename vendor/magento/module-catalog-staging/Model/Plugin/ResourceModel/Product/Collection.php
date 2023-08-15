<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Model\Plugin\ResourceModel\Product;

use Magento\Framework\EntityManager\MetadataPool;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Staging\Model\VersionManager;

/**
 * Class Collection
 */
class Collection
{
    /**
     * @var \Magento\Framework\EntityManager\EntityMetadata
     */
    protected $metadata;

    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @param MetadataPool $metadataPool
     * @param VersionManager $versionManager
     */
    public function __construct(MetadataPool $metadataPool, VersionManager $versionManager)
    {
        $this->metadata = $metadataPool->getMetadata(ProductInterface::class);
        $this->versionManager = $versionManager;
    }

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $subject
     * @param string $alias
     * @param string $attribute
     * @param string $bind
     * @param string $filter
     * @param string $joinType
     * @param null $storeId
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeJoinAttribute(
        \Magento\Catalog\Model\ResourceModel\Product\Collection $subject,
        $alias,
        $attribute,
        $bind,
        $filter = null,
        $joinType = 'inner',
        $storeId = null
    ) {
        if (is_string($attribute) && is_string($bind)) {
            $attrArr = explode('/', $attribute);
            if (ProductAttributeInterface::ENTITY_TYPE_CODE == $attrArr[0] && $bind == 'entity_id') {
                $bind = $this->metadata->getLinkField();
            }
        }
        return [$alias, $attribute, $bind, $filter, $joinType, $storeId];
    }
}
