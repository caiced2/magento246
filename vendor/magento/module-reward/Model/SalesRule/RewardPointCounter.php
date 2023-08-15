<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Model\SalesRule;

use Magento\SalesRule\Api\RuleRepositoryInterface;

/**
 * Service to calculate the sum of Reward points deltas for Sales Rules
 */
class RewardPointCounter
{
    /**
     * @var RuleRepositoryInterface
     */
    private $ruleRepository;

    /**
     * @param RuleRepositoryInterface $ruleRepository
     */
    public function __construct(
        RuleRepositoryInterface $ruleRepository
    ) {
        $this->ruleRepository = $ruleRepository;
    }

    /**
     * Calculate and return the sum of Reward points deltas for Sales Rules
     *
     * It retrieves Reward points delta for each Sales Rule, sums them and return the sum.
     * Reward points delta stands for "Add Reward Points" field in Sales Rules which is added by Reward module.
     * This field allows a customer to gain Reward Points on Order placement.
     *
     * @param array $ruleIds
     * @return int
     */
    public function getPointsForRules(array $ruleIds)
    {
        $points = 0;
        foreach ($ruleIds as $ruleId) {
            if ($ruleId) {
                try {
                    $rule = $this->ruleRepository->getById($ruleId);
                } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
                    //sales rule does not exist (was deleted), move on
                    continue;
                }
                if ($rule->getExtensionAttributes()) {
                    $points += (int)$rule->getExtensionAttributes()->getRewardPointsDelta();
                }
            }
        }
        return $points;
    }
}
