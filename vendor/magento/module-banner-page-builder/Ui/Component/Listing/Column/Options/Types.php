<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);


namespace Magento\BannerPageBuilder\Ui\Component\Listing\Column\Options;


class Types implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Banner config
     *
     * @var \Magento\Banner\Model\Config
     */
    private $bannerConfig;

    /**
     * Types constructor.
     * @param \Magento\Banner\Model\Config $bannerConfig
     */
    public function __construct(
        \Magento\Banner\Model\Config $bannerConfig
    ) {
        $this->bannerConfig = $bannerConfig;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return $this->bannerConfig->toOptionArray(false, true);

    }
}
