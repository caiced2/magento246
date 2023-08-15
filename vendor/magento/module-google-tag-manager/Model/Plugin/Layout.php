<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GoogleTagManager\Model\Plugin;

use Magento\Banner\Block\Widget\Banner;
use Magento\Framework\View\LayoutInterface;
use Magento\GoogleTagManager\Helper\Data;

class Layout
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * After create block pluging
     *
     * @param LayoutInterface $subject
     * @param LayoutInterface $result
     * @return Banner|mixed
     */
    public function afterCreateBlock(LayoutInterface $subject, $result)
    {
        if (!$this->helper->isTagManagerAvailable()) {
            return $result;
        }

        if ($result instanceof Banner) {
            /** @var \Magento\GoogleTagManager\Block\ListJson $jsonBlock */
            $jsonBlock = $subject->getBlock('banner_impression');
            if (is_object($jsonBlock)) {
                $jsonBlock->appendBannerBlock($result);
            }
        }
        return $result;
    }
}
