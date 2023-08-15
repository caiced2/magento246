<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Model\ResourceModel\Catalogrule;

use Magento\CatalogRule\Model\ResourceModel\Rule;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Banner\Model\BannerFactory;
use Magento\Framework\EntityManager\Operation\AttributeInterface;

/**
 * Class ReadHandler
 */
class ReadHandler implements AttributeInterface
{
    /**
     * @var BannerFactory
     */
    protected $bannerFactory;

    /**
     * @var MetadataPool
     */
    protected $metadataPool;

    /**
     * @param BannerFactory $bannerFactory
     * @param MetadataPool $metadataPool
     */
    public function __construct(
        \Magento\Banner\Model\BannerFactory $bannerFactory,
        MetadataPool $metadataPool
    ) {
        $this->bannerFactory = $bannerFactory;
        $this->metadataPool = $metadataPool;
    }

    /**
     * @param string $entityType
     * @param array $entityData
     * @param array $arguments
     * @return array
     * @throws \Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute($entityType, $entityData, $arguments = [])
    {
        $identifierField = $this->metadataPool->getMetadata($entityType)->getIdentifierField();
        $entityId = $entityData[$identifierField];
        $entityData['related_banners'] = $this->bannerFactory->create()->getRelatedBannersByCatalogRuleId($entityId);
        return $entityData;
    }
}
