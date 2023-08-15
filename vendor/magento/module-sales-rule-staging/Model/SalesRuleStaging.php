<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SalesRuleStaging\Model;

use Magento\SalesRuleStaging\Api\SalesRuleStagingInterface;
use Magento\Framework\EntityManager\EntityManager;
use Magento\SalesRule\Api\Data\RuleInterface;
use Magento\SalesRule\Model\Converter\ToModel;
use Magento\Framework\Exception\ValidatorException;
use Magento\Staging\Model\ResourceModel\Db\CampaignValidator;

/**
 * Class SalesRuleStaging
 */
class SalesRuleStaging implements SalesRuleStagingInterface
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var ToModel
     */
    private $toModelConverter;

    /**
     * @var CampaignValidator
     */
    private $campaignValidator;

    /**
     * SalesRuleStaging constructor.
     *
     * @param EntityManager $entityManager
     * @param CampaignValidator $campaignValidator
     * @param ToModel $toModelConverter
     */
    public function __construct(
        EntityManager $entityManager,
        CampaignValidator $campaignValidator,
        ToModel $toModelConverter
    ) {
        $this->entityManager = $entityManager;
        $this->toModelConverter = $toModelConverter;
        $this->campaignValidator = $campaignValidator;
    }

    /**
     * @param RuleInterface $salesRule
     * @param string $version
     * @param array $arguments
     * @return bool
     * @throws \Exception
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function schedule(\Magento\SalesRule\Api\Data\RuleInterface $salesRule, $version, $arguments = [])
    {
        $arguments['created_in'] = $version;
        $previous = isset($arguments['origin_in']) ? $arguments['origin_in'] : null;
        $rule = $this->toModelConverter->toModel($salesRule);
        if (!$this->campaignValidator->canBeScheduled($rule, $version, $previous)) {
            throw new ValidatorException(
                __('Future Update already exists in this time range. Set a different range and try again.')
            );
        }
        return (bool)$this->entityManager->save($rule, $arguments);
    }

    /**
     * @param \Magento\SalesRule\Api\Data\RuleInterface $salesRule
     * @param string $version
     * @return bool
     */
    public function unschedule(\Magento\SalesRule\Api\Data\RuleInterface $salesRule, $version)
    {
        $rule = $this->toModelConverter->toModel($salesRule);
        return $this->entityManager->delete(
            $rule,
            [
                'created_in' => $version
            ]
        );
    }
}
