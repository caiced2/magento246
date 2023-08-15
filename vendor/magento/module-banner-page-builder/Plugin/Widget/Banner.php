<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\BannerPageBuilder\Plugin\Widget;

use Magento\Banner\Block\Widget\Banner as WidgetBanner;

/**
 * Adds additional data to the dynamic block model
 */
class Banner
{
    /**
     * @var \Magento\Banner\Model\ResourceModel\Banner
     */
    private $bannerResource;

    /**
     * @var \Magento\Banner\Model\BannerFactory
     */
    private $bannerFactory;

    /**
     * @param \Magento\Banner\Model\ResourceModel\Banner $bannerResource
     * @param \Magento\Banner\Model\BannerFactory $bannerFactory
     */
    public function __construct(
        \Magento\Banner\Model\ResourceModel\Banner $bannerResource,
        \Magento\Banner\Model\BannerFactory $bannerFactory
    ) {
        $this->bannerFactory = $bannerFactory;
        $this->bannerResource = $bannerResource;
    }

    /**
     * @param WidgetBanner $subject
     * @param string $result
     * @return string
     */
    public function afterGetWidgetAttributes(WidgetBanner $subject, string $result) : string
    {
        /** @var \Magento\Banner\Model\Banner $banner */
        $banner = $this->bannerFactory->create();
        $this->bannerResource->load($banner, $subject->getBannerIds(), 'banner_id');

        $attributes = [
            'data-banner-name' => $banner->getName(),
            'data-banner-status' => $banner->getIsEnabled() ? __('Enabled') : __('Disabled')
        ];
        $data = [];
        foreach ($attributes as $key => $value) {
            $data[] = $key . '=' . '"' . $subject->escapeHtmlAttr($value) . '"';
        }

        return $result .    ' ' . implode(' ', $data);
    }
}
