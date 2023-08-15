<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Observer;

use Magento\Framework\Event\ObserverInterface;

class BindRelatedBannersToCatalogRule implements ObserverInterface
{
    /**
     * Banner factory
     *
     * @var \Magento\Banner\Model\ResourceModel\BannerFactory
     */
    protected $_bannerFactory = null;

    /**
     * @param \Magento\Banner\Model\ResourceModel\BannerFactory $bannerFactory
     */
    public function __construct(
        \Magento\Banner\Model\ResourceModel\BannerFactory $bannerFactory
    ) {
        $this->_bannerFactory = $bannerFactory;
    }

    /**
     * Bind specified banners to catalog rule
     *
     * @param   \Magento\Framework\Event\Observer $observer
     * @return  \Magento\Banner\Observer\BindRelatedBannersToCatalogRule
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $catalogRule = $observer->getEvent()->getRule();
        $banners = $catalogRule->getRelatedBanners();
        if (empty($banners)) {
            $banners = [];
        }
        $this->_bannerFactory->create()->bindBannersToCatalogRule($catalogRule->getId(), $banners);

        return $this;
    }
}
