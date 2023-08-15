<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\BannerCustomerSegment\Observer;

use Magento\Banner\Model\Banner;
use Magento\Framework\Event\ObserverInterface;

class AddFieldsToBannerForm implements ObserverInterface
{
    /**
     * @var \Magento\CustomerSegment\Helper\Data
     */
    private $segmentHelper;

    /**
     * @param \Magento\CustomerSegment\Helper\Data $segmentHelper
     */
    public function __construct(
        \Magento\CustomerSegment\Helper\Data $segmentHelper
    ) {
        $this->segmentHelper = $segmentHelper;
    }

    /**
     * Add customer segment fields to the banner form, passed as an event argument
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->segmentHelper->isEnabled()) {
            return;
        }
        /* @var \Magento\Framework\Data\Form $form */
        $form = $observer->getEvent()->getForm();
        /** @var \Magento\Framework\DataObject $model */
        $model = $observer->getEvent()->getModel();
        /** @var \Magento\Backend\Block\Widget\Form\Element\Dependence $afterFormBlock */
        $afterFormBlock = $observer->getEvent()->getAfterFormBlock();
        $this->segmentHelper->addSegmentFieldsToForm($form, $model, $afterFormBlock);
    }
}
