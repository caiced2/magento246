<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SalesRuleStaging\Setup;

use Magento\Staging\Setup\AbstractStagingSetup;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Api\Data\UpdateInterfaceFactory;
use Magento\SalesRule\Model\RuleFactory;
use Magento\Framework\App\State;
use Psr\Log\LoggerInterface;
use Magento\Staging\Model\VersionManagerFactory;
use Magento\Staging\Model\Operation\Update\CreateEntityVersion;
use Magento\Staging\Model\Operation\Update\UpdateEntityVersion;
use Magento\Framework\App\ObjectManager;
use Magento\Staging\Model\VersionManager;

/**
 * Setup class to migration staging update for sales rules.
 *
 * @codeCoverageIgnore
 */
class SalesRuleMigration extends AbstractStagingSetup
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var VersionManagerFactory
     */
    private $versionManagerFactory;

    /**
     * @var State
     */
    private $state;

    /**
     * @var RuleFactory
     */
    private $ruleFactory;

    /**
     * @var CreateEntityVersion
     */
    private $createEntityVersion;

    /**
     * @var UpdateEntityVersion
     */
    private $updateEntityVersion;

    /**
     * @param UpdateRepositoryInterface $updateRepository
     * @param UpdateInterfaceFactory $updateFactory
     * @param RuleFactory $ruleFactory
     * @param State $state
     * @param LoggerInterface $logger
     * @param VersionManagerFactory $versionManagerFactory
     * @param CreateEntityVersion $createEntityVersion
     * @param UpdateEntityVersion $updateEntityVersion
     */
    public function __construct(
        UpdateRepositoryInterface $updateRepository,
        UpdateInterfaceFactory $updateFactory,
        RuleFactory $ruleFactory,
        State $state,
        LoggerInterface $logger,
        VersionManagerFactory $versionManagerFactory,
        ?CreateEntityVersion $createEntityVersion = null,
        ?UpdateEntityVersion $updateEntityVersion = null
    ) {
        parent::__construct($updateRepository, $updateFactory);

        $this->ruleFactory = $ruleFactory;
        $this->logger = $logger;
        $this->versionManagerFactory = $versionManagerFactory;
        $this->state = $state;
        $this->createEntityVersion = $createEntityVersion ?:
            ObjectManager::getInstance()->get(CreateEntityVersion::class);
        $this->updateEntityVersion = $updateEntityVersion ?:
            ObjectManager::getInstance()->get(UpdateEntityVersion::class);
    }

    /**
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup
     * @return void
     */
    public function migrateRules($setup)
    {
        // Emulate area for rules migration
        $this->state->emulateAreaCode(
            \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE,
            [$this, 'updateRules'],
            [$setup]
        );
    }

    /**
     * Create staging updates by rules.
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup
     * @return void
     */
    public function updateRules($setup)
    {
        $salesRuleEntityTable = $setup->getTable('salesrule');
        $select = $setup->getConnection()->select()->from(
            $salesRuleEntityTable,
            ['row_id', 'rule_id', 'name', 'from_date', 'to_date', 'is_active']
        );
        $rules = $setup->getConnection()->fetchAll($select);
        foreach ($rules as $rule) {
            if ($rule['from_date']) {
                try {
                    /** @var \Magento\SalesRule\Model\Rule $ruleModel */
                    $ruleModel = $this->ruleFactory->create();
                    $ruleModel->load($rule['rule_id']);
                    $ruleModel->unsRowId();
                    $update = $this->createUpdateForEntity($rule);
                    $updateId = $update->getId();
                    $this->updateEntityVersion->execute(
                        $ruleModel,
                        [
                            'created_in' => VersionManager::MIN_VERSION,
                            'updated_in' => $updateId,
                            'row_id' => $rule['row_id'],
                        ]
                    );
                    $ruleModel->unsRowId();
                    $this->createEntityVersion->execute(
                        $ruleModel,
                        [
                            'created_in' => $updateId,
                            'updated_in' => $update->getRollbackId(),
                        ]
                    );
                    if ($update->getRollbackId()) {
                        $ruleModel->unsRowId();
                        $this->createEntityVersion->execute(
                            $ruleModel,
                            [
                                'created_in' => $update->getRollbackId(),
                                'updated_in' => VersionManager::MAX_VERSION,
                            ]
                        );
                    }
                } catch (\Exception $exception) {
                    $this->logger->critical($exception);
                }
            }
        }
    }
}
