<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Class for adding Customer Segment conditions section
 */
class AddSegmentsToSalesRuleCombineObserver implements ObserverInterface
{
    /**
     * @var \Magento\CustomerSegment\Helper\Data
     */
    private $_segmentHelper;

    /**
     * @param \Magento\CustomerSegment\Helper\Data $segmentHelper
     */
    public function __construct(
        \Magento\CustomerSegment\Helper\Data $segmentHelper
    ) {
        $this->_segmentHelper = $segmentHelper;
    }

    /**
     * Add Customer Segment condition to the salesrule management
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->_segmentHelper->isEnabled()) {
            return;
        }
        $additional = $observer->getEvent()->getAdditional();
        $conditions = (array) $additional->getConditions();
        $conditions = array_merge_recursive(
            $conditions,
            [
                [
                    'label' => __('Customer Segment'),
                    'value' => \Magento\CustomerSegment\Model\Segment\Condition\Segment::class,
                ],
            ]
        );
        $additional->setConditions($conditions);
    }
}
