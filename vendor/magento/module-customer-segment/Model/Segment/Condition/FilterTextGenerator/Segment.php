<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Model\Segment\Condition\FilterTextGenerator;

use Magento\CustomerSegment\Model\Segment\Condition\ConcreteCondition\Segment as FilterableSegment;
use Magento\AdvancedRule\Model\Condition\FilterTextGeneratorInterface;

/**
 * For the current quote, generates the filter text strings for sales rules that reference Customer Segment ids.
 * These strings will be used to quickly determine which of these rules should be further evaluated.
 */
class Segment implements FilterTextGeneratorInterface
{
    /**
     * @var \Magento\CustomerSegment\Helper\Data
     */
    private $segmentHelper;

    /**
     * @var \Magento\CustomerSegment\Model\Customer
     */
    private $segmentCustomer;

    /**
     * @param \Magento\CustomerSegment\Helper\Data $segmentHelper
     * @param \Magento\CustomerSegment\Model\Customer $segmentCustomer
     */
    public function __construct(
        \Magento\CustomerSegment\Helper\Data $segmentHelper,
        \Magento\CustomerSegment\Model\Customer $segmentCustomer
    ) {
        $this->segmentHelper = $segmentHelper;
        $this->segmentCustomer = $segmentCustomer;
    }

    /**
     * @param \Magento\Framework\DataObject $quoteAddress
     * @return string[]
     */
    public function generateFilterText(\Magento\Framework\DataObject $quoteAddress)
    {
        $filterText = [];
        if ($quoteAddress instanceof \Magento\Quote\Model\Quote\Address) {
            $websiteId = $quoteAddress->getQuote()->getStore()->getWebsiteId();
            $customerId = $quoteAddress->getCustomerId();
            $customerSegmentIds = $this->segmentHelper->isEnabled()
                ? $this->segmentCustomer->getCustomerSegmentIdsForWebsite($customerId, $websiteId)
                : [];

            foreach ($customerSegmentIds as $customerSegmentId) {
                $text = FilterableSegment::FILTER_TEXT_PREFIX . $customerSegmentId;
                if (!in_array($text, $filterText)) {
                    $filterText[] = $text;
                }
            }
        }
        return $filterText;
    }

    /**
     * Returns the Customer Segment ids that are valid for the customer
     *
     * @return string[]
     *
     * @deprecated 101.0.0 This method works incorrectly in admin panel
     */
    protected function getCustomerSegmentIds()
    {
        if (!$this->segmentHelper->isEnabled()) {
            // if the CustomerSegment module is currently disabled, then we will not match on any segment ids
            return [];
        }

        $segmentIds = $this->segmentCustomer->getCurrentCustomerSegmentIds();
        return $segmentIds;
    }
}
