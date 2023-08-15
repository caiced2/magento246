<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogRuleStaging\Test\Fixture;

use Magento\CatalogRule\Model\ResourceModel\Rule as ResourceModel;
use Magento\CatalogRule\Model\RuleFactory;
use Magento\CatalogRule\Test\Fixture\Data\ActionsSerializer;
use Magento\CatalogRule\Test\Fixture\Data\ConditionsSerializer;
use Magento\CatalogRuleStaging\Api\CatalogRuleStagingInterface;
use Magento\Framework\DataObject;
use Magento\Staging\Model\VersionManager;
use Magento\TestFramework\Fixture\DataFixtureInterface;

class StagedRule implements DataFixtureInterface
{
    private const DEFAULT_DATA = [
        'update_id' => null,
        'rule_id' => null,
    ];

    /**
     * @var ResourceModel
     */
    private ResourceModel $resourceModel;

    /**
     * @var RuleFactory
     */
    private RuleFactory $ruleFactory;

    /**
     * @var CatalogRuleStagingInterface
     */
    private CatalogRuleStagingInterface $catalogRuleStaging;

    /**
     * @var ConditionsSerializer
     */
    private ConditionsSerializer $conditionsSerializer;

    /**
     * @var ActionsSerializer
     */
    private ActionsSerializer $actionsSerializer;

    /**
     * @var VersionManager
     */
    private VersionManager $versionManager;

    /**
     * @param ResourceModel $resourceModel
     * @param RuleFactory $ruleFactory
     * @param CatalogRuleStagingInterface $catalogRuleStaging
     * @param ConditionsSerializer $conditionsSerializer
     * @param ActionsSerializer $actionsSerializer
     * @param VersionManager $versionManager
     */
    public function __construct(
        ResourceModel $resourceModel,
        RuleFactory $ruleFactory,
        CatalogRuleStagingInterface $catalogRuleStaging,
        ConditionsSerializer $conditionsSerializer,
        ActionsSerializer $actionsSerializer,
        VersionManager $versionManager
    ) {
        $this->resourceModel = $resourceModel;
        $this->ruleFactory = $ruleFactory;
        $this->catalogRuleStaging = $catalogRuleStaging;
        $this->conditionsSerializer = $conditionsSerializer;
        $this->actionsSerializer = $actionsSerializer;
        $this->versionManager = $versionManager;
    }

    /**
     * @inheritdoc
     */
    public function apply(array $data = []): ?DataObject
    {
        $data = array_merge(self::DEFAULT_DATA, $data);
        $model = $this->ruleFactory->create();
        $updateId = $data['update_id'];
        $entityId = $data['rule_id'];
        $this->resourceModel->load($model, $entityId);
        if (isset($data['conditions'])) {
            $data['conditions_serialized'] = $this->conditionsSerializer->serialize($data['conditions']);
        }
        if (isset($data['actions'])) {
            $data['actions_serialized'] = $this->actionsSerializer->serialize($data['actions']);
        }
        unset($data['rule_id'], $data['updated_id'], $data['actions'], $data['conditions']);
        $model->addData($data);
        $currentVersionId = $this->versionManager->getCurrentVersion()->getId();
        try {
            $this->versionManager->setCurrentVersionId($updateId);
            $this->catalogRuleStaging->schedule($model, $updateId);
        } finally {
            $this->versionManager->setCurrentVersionId($currentVersionId);
        }
        return $model;
    }
}
