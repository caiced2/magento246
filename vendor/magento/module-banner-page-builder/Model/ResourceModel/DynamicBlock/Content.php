<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\BannerPageBuilder\Model\ResourceModel\DynamicBlock;

/**
 * Pulls dynamic block content from the database for use on the stage
 */
class Content
{
    /**
     * @var \Magento\Banner\Model\ResourceModel\Banner
     */
    private $bannerResource;

    /**
     * @param \Magento\Banner\Model\ResourceModel\Banner $bannerResource
     */
    public function __construct(
        \Magento\Banner\Model\ResourceModel\Banner $bannerResource
    ) {
        $this->bannerResource = $bannerResource;
    }

    /**
     * Retrieves the content of the block regardless of current store view or customer segment
     * @param int $blockId
     * @return string
     */
    public function getById(int $blockId): string
    {
        $connection =  $this->bannerResource->getConnection();
        $select = $connection->select()->from(
            $this->bannerResource->getTable('magento_banner_content'),
            ['banner_content']
        )->where(
            'banner_id=?',
            $blockId
        );
        return $connection->fetchOne($select) ?? '';
    }
}
