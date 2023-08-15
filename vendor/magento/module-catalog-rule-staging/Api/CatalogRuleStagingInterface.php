<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogRuleStaging\Api;

/**
 * Interface CatalogRuleStagingInterface
 * @api
 * @since 100.1.0
 */
interface CatalogRuleStagingInterface
{
    /**
     * @param \Magento\CatalogRule\Api\Data\RuleInterface $catalogRule
     * @param string $version
     * @param array $arguments
     * @return bool
     * @since 100.1.0
     */
    public function schedule(\Magento\CatalogRule\Api\Data\RuleInterface $catalogRule, $version, $arguments = []);

    /**
     * @param \Magento\CatalogRule\Api\Data\RuleInterface $catalogRule
     * @param string $version
     * @return bool
     * @since 100.1.0
     */
    public function unschedule(\Magento\CatalogRule\Api\Data\RuleInterface $catalogRule, $version);
}
