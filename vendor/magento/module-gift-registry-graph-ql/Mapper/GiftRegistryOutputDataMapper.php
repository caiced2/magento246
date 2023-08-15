<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistryGraphQl\Mapper;

use Magento\GiftRegistry\Model\Entity as GiftRegistry;
use Magento\GiftRegistry\Model\ResourceModel\Entity as GiftRegistryResourceModel;

/**
 * Prepares the gift registry output as associative array
 */
class GiftRegistryOutputDataMapper
{
    /**
     * @var GiftRegistryResourceModel
     */
    private $entityResourceModel;

    /**
     * @param GiftRegistryResourceModel $entityResourceModel
     */
    public function __construct(GiftRegistryResourceModel $entityResourceModel)
    {
        $this->entityResourceModel = $entityResourceModel;
    }

    /**
     * Mapping gift registry data
     *
     * @param GiftRegistry $giftRegistry
     *
     * @return array
     */
    public function map(GiftRegistry $giftRegistry): array
    {
        $this->entityResourceModel->load($giftRegistry, $giftRegistry->getEntityId());

        return [
            'uid' => $giftRegistry->getUrlKey(),
            'event_name' => $giftRegistry->getTitle(),
            'message' => $giftRegistry->getMessage(),
            'model' => $giftRegistry
        ];
    }
}
