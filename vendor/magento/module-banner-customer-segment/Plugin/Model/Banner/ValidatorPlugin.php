<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\BannerCustomerSegment\Plugin\Model\Banner;

class ValidatorPlugin
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
     * Add customer_segment_ids to data not already
     *
     * @param \Magento\Banner\Model\Banner\Validator $subject
     * @param array $result
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @return array
     */
    public function afterPrepareSaveData(\Magento\Banner\Model\Banner\Validator $subject, $result)
    {
        if ($this->segmentHelper->isEnabled() && !isset($result['customer_segment_ids'])) {
            $result['customer_segment_ids'] = [];
        }

        return $result;
    }
}
