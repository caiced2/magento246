<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\BannerCustomerSegment\Observer;

use Magento\Banner\Model\Banner;
use Magento\Framework\Event\ObserverInterface;

class AddCustomerSegmentFilterToCollection implements ObserverInterface
{
    /**
     * @var \Magento\CustomerSegment\Helper\Data
     */
    private $segmentHelper;

    /**
     * @var \Magento\BannerCustomerSegment\Model\ResourceModel\BannerSegmentLink
     */
    private $bannerSegmentLink;

    /**
     * @var \Magento\CustomerSegment\Model\Customer
     */
    private $segmentCustomer;

    /**
     * @param \Magento\CustomerSegment\Helper\Data $segmentHelper
     * @param \Magento\BannerCustomerSegment\Model\ResourceModel\BannerSegmentLink $bannerSegmentLink
     * @param \Magento\CustomerSegment\Model\Customer $segmentCustomer
     */
    public function __construct(
        \Magento\CustomerSegment\Helper\Data $segmentHelper,
        \Magento\BannerCustomerSegment\Model\ResourceModel\BannerSegmentLink $bannerSegmentLink,
        \Magento\CustomerSegment\Model\Customer $segmentCustomer
    ) {
        $this->segmentHelper = $segmentHelper;
        $this->bannerSegmentLink = $bannerSegmentLink;
        $this->segmentCustomer = $segmentCustomer;
    }

    /**
     * Apply customer segment filter to a collection, passed as an event argument
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->segmentHelper->isEnabled()) {
            return;
        }
        /** @var \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection */
        $collection = $observer->getEvent()->getCollection();
        $segmentIds = $this->segmentCustomer->getCurrentCustomerSegmentIds();
        $this->bannerSegmentLink->addBannerSegmentFilter($collection->getSelect(), $segmentIds);
    }
}
