<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Class BindRelatedBannersToSalesRule
 */
class BindRelatedBannersToSalesRule implements ObserverInterface
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
     * Bind specified banners to sales rule
     *
     * @param   \Magento\Framework\Event\Observer $observer
     * @return  \Magento\Banner\Observer\BindRelatedBannersToSalesRule
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $salesRule = $observer->getEvent()->getRule();
        $banners = $salesRule->getRelatedBanners();
        if ($banners !== null && is_array($banners)) {
            $this->_bannerFactory->create()->bindBannersToSalesRule($salesRule->getId(), $banners);
        }

        return $this;
    }
}
