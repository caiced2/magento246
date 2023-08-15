<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SalesRuleStaging\Model;

use Magento\SalesRule\Model\Converter\ToDataModel;
use Magento\Framework\Exception\ValidatorException;
use Magento\Staging\Model\ResourceModel\Db\CampaignValidator;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\App\ObjectManager;

/**
 * Class SalesRuleStagingAdapter
 * @deprecated 100.1.0
 */
class SalesRuleStagingAdapter
{
    /**
     * @var SalesRuleStaging
     */
    private $salesRuleStaging;

    /**
     * @var ToDataModel
     */
    private $toDataModel;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var CampaignValidator
     */
    private $campaignValidator;

    /**
     * SalesRuleStagingAdapter constructor.
     *
     * @param SalesRuleStaging $salesRuleStaging
     * @param ToDataModel $toDataModel
     * @param EntityManager|null $entityManager
     * @param CampaignValidator|null $campaignValidator
     */
    public function __construct(
        SalesRuleStaging $salesRuleStaging,
        ToDataModel $toDataModel,
        EntityManager $entityManager = null,
        CampaignValidator $campaignValidator = null
    ) {
        $this->salesRuleStaging = $salesRuleStaging;
        $this->toDataModel = $toDataModel;
        $this->entityManager = $entityManager ?: ObjectManager::getInstance()->get(EntityManager::class);
        $this->campaignValidator = $campaignValidator ?: ObjectManager::getInstance()->get(CampaignValidator::class);
    }

    /**
     * Schedule update for sales rule
     *
     * @param \Magento\SalesRule\Model\Rule $salesRule
     * @param string $version
     * @param array $arguments
     * @return bool
     * @throws \Exception
     * @throws ValidatorException
     */
    public function schedule(\Magento\SalesRule\Model\Rule $salesRule, $version, $arguments = [])
    {
        $arguments['created_in'] = $version;
        $previous = isset($arguments['origin_in']) ? $arguments['origin_in'] : null;
        if (!$this->campaignValidator->canBeScheduled($salesRule, $version, $previous)) {
            throw new ValidatorException(
                __(
                    'Future Update in this time range already exists. '
                    .'Select a different range to add a new Future Update.'
                )
            );
        }
        return (bool)$this->entityManager->save($salesRule, $arguments);
    }

    /**
     * Unschedule sales rule
     *
     * @param \Magento\SalesRule\Model\Rule $salesRule
     * @param string $version
     * @return bool
     */
    public function unschedule(\Magento\SalesRule\Model\Rule $salesRule, $version)
    {
        $dataObject = $this->toDataModel->toDataModel($salesRule);
        return $this->salesRuleStaging->unschedule($dataObject, $version);
    }
}
