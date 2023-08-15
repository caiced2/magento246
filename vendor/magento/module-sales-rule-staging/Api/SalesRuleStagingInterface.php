<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesRuleStaging\Api;

/**
 * Interface SalesRuleStagingInterface
 * @api
 * @since 100.1.0
 */
interface SalesRuleStagingInterface
{
    /**
     * @param \Magento\SalesRule\Api\Data\RuleInterface $salesRule
     * @param string $version
     * @param array $arguments
     * @return bool
     * @since 100.1.0
     */
    public function schedule(\Magento\SalesRule\Api\Data\RuleInterface $salesRule, $version, $arguments = []);

    /**
     * @param \Magento\SalesRule\Api\Data\RuleInterface $salesRule
     * @param string $version
     * @return bool
     * @since 100.1.0
     */
    public function unschedule(\Magento\SalesRule\Api\Data\RuleInterface $salesRule, $version);
}
