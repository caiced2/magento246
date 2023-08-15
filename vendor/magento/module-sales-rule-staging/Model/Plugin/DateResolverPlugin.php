<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesRuleStaging\Model\Plugin;

use Magento\SalesRule\Model\Rule as RuleModel;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\VersionManager;

/**
 * Provides date update for basic SalesRule fields `from_date` and `to_date`.
 */
class DateResolverPlugin
{
    /**
     * @var string
     */
    private static $startDateAttribute = 'from_date';

    /**
     * End date attribute
     * @var string
     */
    private static $endDataAttribute = 'to_date';

    /**
     * @var \Magento\Staging\Api\UpdateRepositoryInterface
     */
    protected $updateRepository;

    /**
     * @param \Magento\Staging\Api\UpdateRepositoryInterface $updateRepository
     */
    public function __construct(
        UpdateRepositoryInterface $updateRepository
    ) {
        $this->updateRepository = $updateRepository;
    }

    /**
     * Provide update start date to the rule model.
     *
     * @param RuleModel $subject
     * @return void
     * @throws \Exception
     */
    public function beforeGetFromDate(RuleModel $subject)
    {
        // for update end date if different than created_in
        $resolvedTime = new \DateTime($this->resolveDate($subject)->getStartTime() ?? 'now');
        if ($resolvedTime != $subject->getData(self::$startDateAttribute)) {
            $subject->setData(self::$startDateAttribute, $resolvedTime->format('Y-m-d'));
        }
    }

    /**
     * Provide update end date to the rule model.
     *
     * @param RuleModel $subject
     * @return void
     * @throws \Exception
     */
    public function beforeGetToDate(RuleModel $subject)
    {
        $resolvedTime = $this->resolveDate($subject)->getEndTime();
        $toDate = $subject->getData(self::$endDataAttribute);
        $maxDate = date('Y-m-d', VersionManager::MAX_VERSION);

        if (!empty($resolvedTime)) {
            $resolvedTime = new \DateTime($resolvedTime);
        }

        if ($toDate instanceof \DateTime) {
            $subject->setData(self::$endDataAttribute, $toDate->format('Y-m-d'));
        }

        if (!empty($resolvedTime) && $resolvedTime->format('Y-m-d') != $toDate) {
            $subject->setData(self::$endDataAttribute, $resolvedTime->format('Y-m-d'));
        }

        if (!empty($resolvedTime) && $resolvedTime->format('Y-m-d') > $maxDate) {
            $subject->setData(self::$endDataAttribute, $maxDate);
        }
    }

    /**
     * Resolve date using update id.
     *
     * @param RuleModel $subject
     * @return \Magento\Staging\Api\Data\UpdateInterface
     */
    protected function resolveDate(RuleModel $subject)
    {
        $campaignId = $subject->getData('campaign_id');
        $versionId = $campaignId === null ? $subject->getData('created_in') : $campaignId;
        return $this->updateRepository->get($versionId);
    }
}
