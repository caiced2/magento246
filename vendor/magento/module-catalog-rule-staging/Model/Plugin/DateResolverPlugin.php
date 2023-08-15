<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogRuleStaging\Model\Plugin;

use Magento\CatalogRule\Model\Rule;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Api\UpdateRepositoryInterface;

/**
 * Resolve start and end date of catalog rule.
 */
class DateResolverPlugin
{
    /**
     * @param UpdateRepositoryInterface
     */
    private $updateRepository;

    /**
     * @var TimezoneInterface
     */
    private $localeDate;

    /**
     * @param UpdateRepositoryInterface $updateRepository
     * @param TimezoneInterface $localeDate
     */
    public function __construct(
        UpdateRepositoryInterface $updateRepository,
        TimezoneInterface $localeDate
    ) {
        $this->updateRepository = $updateRepository;
        $this->localeDate = $localeDate;
    }

    /**
     * Provide update start date to the rule model.
     *
     * @param Rule $subject
     * @return void
     */
    public function beforeGetFromDate(Rule $subject)
    {
        $date = $this->resolveDate($subject)->getStartTime();
        if ($date) {
            $date = $this->localeDate->date(new \DateTime($date));
            $date = $date->format('Y-m-d H:i:s');
        }
        $subject->setData('from_date', $date);
    }

    /**
     * Provide update end date to the rule model.
     *
     * @param Rule $subject
     * @return void
     */
    public function beforeGetToDate(Rule $subject)
    {
        $date = $this->resolveDate($subject)->getEndTime();
        if ($date) {
            $date = $this->localeDate->date(new \DateTime($date));
            $date = $date->format('Y-m-d H:i:s');
        }
        $subject->setData('to_date', $date);
    }

    /**
     * Resolve date using update id.
     *
     * @param Rule $subject
     * @return UpdateInterface
     */
    private function resolveDate(Rule $subject): UpdateInterface
    {
        $campaignId = $subject->getData('campaign_id');
        $versionId = $campaignId === null ? $subject->getData('created_in') : $campaignId;
        return $this->updateRepository->get($versionId);
    }
}
