<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GoogleTagManager\Block\Plugin\Banner\Widget;

use Magento\GoogleTagManager\Helper\Data;
use Magento\GoogleTagManager\Model\Banner\Collector;

class Banner
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var Collector
     */
    protected $bannerCollector;

    /**
     * @param Data $helper
     * @param Collector $bannerCollector
     */
    public function __construct(
        Data $helper,
        Collector $bannerCollector
    ) {
        $this->helper = $helper;
        $this->bannerCollector = $bannerCollector;
    }

    /**
     * Before plugin
     *
     * @param \Magento\Banner\Block\Widget\Banner $subject
     * @return void
     */
    public function beforeToHtml(\Magento\Banner\Block\Widget\Banner $subject)
    {
        if ($this->helper->isTagManagerAvailable()) {
            $this->bannerCollector->addBannerBlock($subject);
        }
    }
}
