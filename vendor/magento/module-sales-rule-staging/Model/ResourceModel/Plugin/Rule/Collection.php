<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesRuleStaging\Model\ResourceModel\Plugin\Rule;

use Magento\Quote\Model\Quote\Address;
use Magento\SalesRule\Model\ResourceModel\Rule\Collection as RuleCollection;
use Magento\Staging\Model\VersionManager as VersionManager;

class Collection
{
    /**
     * @var VersionManager
     */
    protected $versionManager;

    /**
     * @param VersionManager $versionManager
     */
    public function __construct(
        VersionManager $versionManager
    ) {
        $this->versionManager = $versionManager;
    }

    /**
     * Filter collection by specified website, customer group, coupon code, date, address.
     *
     * @param RuleCollection $subject
     * @param int $websiteId
     * @param int $customerGroupId
     * @param string $couponCode
     * @param string|null $now
     * @param Address|null $address
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @return void
     */
    public function beforeSetValidationFilter(
        RuleCollection $subject,
        $websiteId,
        $customerGroupId,
        $couponCode = '',
        $now = null,
        Address $address = null
    ) {
        // if we have an address, set a hint about needing any additional filtering
        if ($address != null) {
            $previewMode = $this->versionManager->isPreviewVersion();
            $address->setData('skip_validation_filter', $previewMode);
        }
        return;
    }
}
