<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GoogleTagManager\Model\Banner;

use Magento\Banner\Block\Widget\Banner;

/**
 * @api
 * @since 100.0.2
 */
class Collector
{
    /**
     * @var string[]
     */
    protected $bannerIds = [];

    /**
     * Add banner block
     *
     * @param Banner $banner
     * @return $this
     */
    public function addBannerBlock(Banner $banner)
    {
        $bannerIds = $banner->getBannerIds();
        if (empty($bannerIds)) {
            return $this;
        }
        $bannerIds = explode(',', $bannerIds);
        $this->bannerIds = array_merge($this->bannerIds, $bannerIds);
        $this->bannerIds = array_unique($this->bannerIds);
        return $this;
    }

    /**
     * Get banner ids
     *
     * @return string[]
     */
    public function getBannerIds()
    {
        return $this->bannerIds;
    }
}
