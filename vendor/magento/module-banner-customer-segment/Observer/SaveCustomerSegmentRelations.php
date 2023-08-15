<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\BannerCustomerSegment\Observer;

use Magento\Banner\Model\Banner;
use Magento\Framework\Event\ObserverInterface;

class SaveCustomerSegmentRelations implements ObserverInterface
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
     * @param \Magento\CustomerSegment\Helper\Data $segmentHelper
     * @param \Magento\BannerCustomerSegment\Model\ResourceModel\BannerSegmentLink $bannerSegmentLink
     */
    public function __construct(
        \Magento\CustomerSegment\Helper\Data $segmentHelper,
        \Magento\BannerCustomerSegment\Model\ResourceModel\BannerSegmentLink $bannerSegmentLink
    ) {
        $this->segmentHelper = $segmentHelper;
        $this->bannerSegmentLink = $bannerSegmentLink;
    }

    /**
     * Store customer segment ids associated with a banner entity, passed as an event argument
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @throws \UnexpectedValueException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->segmentHelper->isEnabled()) {
            return;
        }
        /** @var Banner $banner */
        $banner = $observer->getEvent()->getBanner();
        $segmentIds = $banner->getData('customer_segment_ids') ?: [];
        if (!is_array($segmentIds)) {
            throw new \UnexpectedValueException(
                'Customer segments associated with a dynamic block'
                . ' are expected to be defined as an array of identifiers.'
            );
        }
        $segmentIds = array_map('intval', $segmentIds);
        $this->bannerSegmentLink->saveBannerSegments($banner->getId(), $segmentIds);
    }
}
