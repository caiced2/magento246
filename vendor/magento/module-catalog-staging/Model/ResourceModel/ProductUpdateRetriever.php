<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStaging\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Staging\Model\VersionManager;

class ProductUpdateRetriever
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @param ResourceConnection $resource
     */
    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    /**
     * Retrieve update for product.
     *
     * @param string $sku
     * @param string|null $startTime
     * @param string|null $endTime
     * @return int|null
     */
    public function retrieveUpdateId(string $sku, ?string $startTime, ?string $endTime): ?int
    {
        if (!$startTime && !$endTime) {
            return VersionManager::MIN_VERSION;
        }

        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from(
                ['s' => $this->resource->getTableName('staging_update')],
                ['id']
            )->join(
                ['e' => $this->resource->getTableName('catalog_product_entity')],
                'e.created_in = s.id',
                []
            )
            ->where('e.sku = ?', $sku)
            ->where('s.start_time = ?', $startTime)
            ->limit(1)
            ->setPart('disable_staging_preview', true);
        if ($endTime) {
            $select->join(
                ['r' => $this->resource->getTableName('staging_update')],
                'r.id = s.rollback_id',
                []
            );
            $select->where('r.start_time = ?', $endTime);
        } else {
            $select->where('s.rollback_id IS NULL');
        }
        $updateId = $connection->fetchOne($select);

        return $updateId ? (int) $updateId : null;
    }
}
