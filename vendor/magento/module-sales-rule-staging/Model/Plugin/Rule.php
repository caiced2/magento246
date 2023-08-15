<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesRuleStaging\Model\Plugin;

use Magento\SalesRule\Model\Rule as SalesRule;
use Magento\Staging\Model\VersionManager;

/**
 * Class Rule
 *
 * Plugin to update date attributes with current campaign version
 * and update hint property for sales rule filter table
 */
class Rule
{
    /**
     * Start date for rule applying
     * @var string
     */
    private static $startDateAttribute = 'from_date';

    /**
     * End date for rule applying
     * @var string
     */
    private static $endDateAttribute = 'to_date';

    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * Rule constructor.
     * @param VersionManager $versionManager
     */
    public function __construct(VersionManager $versionManager)
    {
        $this->versionManager = $versionManager;
    }

    /**
     * Adds a hint about whether to process this update for the sales rule filter table
     * Update date attributes related to current version of rule
     *
     * @param SalesRule $subject
     * @return void
     */
    public function beforeBeforeSave(SalesRule $subject)
    {
        // determine if this is an update for the current time
        $isCurrent = $this->isCurrentUpdate($subject);

        // set a hint about needing to save this update in the filter table:
        // skip the "save filter" if this update is not for the present time
        $subject->setData('skip_save_filter', !$isCurrent);

        // update datetime attributes
        $version = $this->versionManager->getCurrentVersion();

        // only update if current version is linked to rule AND current version is valid
        if ($version->getId() == $subject->getData('created_in') && $version->getStartTime() !== null) {
            $subject->setData(
                self::$startDateAttribute,
                $version->getStartTime()
            );
            $subject->setData(self::$endDateAttribute, $version->getEndTime());
        }
    }

    /**
     * Returns whether this version of the sales rule is for the present time
     *
     * @param SalesRule $salesRule
     * @return bool
     */
    protected function isCurrentUpdate(SalesRule $salesRule)
    {
        $presentTime = strtotime("now");
        $createdIn = $salesRule->getCreatedIn();
        $updatedIn = $salesRule->getUpdatedIn();

        if ($createdIn == null) {
            $createdIn = 0;
        }
        if ($updatedIn == null) {
            $updatedIn = PHP_INT_MAX;
        }

        return $createdIn <= $presentTime && $presentTime < $updatedIn;
    }
}
