<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\BannerStaging\Plugin;

use Magento\Banner\Model\ResourceModel\Catalogrule\Collection;
use Magento\CatalogRule\Api\Data\RuleInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Staging\Model\VersionManager;

/**
 * Plugin to show a staging preview of catalog rule related banners
 */
class CatalogRuleBannerCollection
{
    /**
     * @var MetadataPool
     */
    private MetadataPool $metadataPool;

    /**
     * @var VersionManager
     */
    private VersionManager $versionManager;

    /**
     * @param MetadataPool $metadataPool
     * @param VersionManager $versionManager
     */
    public function __construct(
        MetadataPool $metadataPool,
        VersionManager $versionManager
    ) {
        $this->metadataPool = $metadataPool;
        $this->versionManager = $versionManager;
    }

    /**
     * Add website id and customer group id filter to the collection
     *
     * Uses original tables instead of index table "catalogrule_group_website" because the index table contains records
     * for current catalog rule only
     *
     * @param Collection $collection
     * @param callable $process
     * @param int $websiteId
     * @param int $customerGroupId
     * @return Collection
     * @throws \Exception
     */
    public function aroundAddWebsiteCustomerGroupFilter(
        Collection $collection,
        callable $process,
        $websiteId,
        $customerGroupId
    ): Collection {
        if ($this->versionManager->isPreviewVersion()) {
            $metadata = $this->metadataPool->getMetadata(RuleInterface::class);
            $linkField = $metadata->getLinkField();
            $connection = $collection->getConnection();
            $collection->getSelect()
                ->join(
                    ['catalogrule_website' => $collection->getTable('catalogrule_website')],
                    "catalogrule_website.$linkField = catalogrule.$linkField AND "
                    . $connection->quoteInto('catalogrule_website.website_id = ?', $websiteId),
                    []
                )
                ->join(
                    ['catalogrule_customer_group' => $collection->getTable('catalogrule_customer_group')],
                    "catalogrule_customer_group.$linkField = catalogrule.$linkField AND "
                    . $connection->quoteInto('catalogrule_customer_group.customer_group_id = ?', $customerGroupId),
                    []
                );
        } else {
            $process($websiteId, $customerGroupId);
        }

        return $collection;
    }
}
