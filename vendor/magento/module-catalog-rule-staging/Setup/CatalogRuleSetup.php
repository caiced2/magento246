<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogRuleStaging\Setup;

use Magento\Staging\Setup\AbstractStagingSetup;

/**
 * Setup class to migration staging update for catalog rules.
 *
 * @codeCoverageIgnore
 */
class CatalogRuleSetup extends AbstractStagingSetup
{
    /**
     * Factory class for @see \Magento\CatalogRule\Model\Rule
     *
     * @var \Magento\CatalogRule\Model\RuleFactory
     */
    private $ruleFactory;
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \Magento\Staging\Model\VersionManagerFactory
     */
    private $versionManagerFactory;

    /**
     * @var \Magento\Framework\App\State
     */
    private $state;

    /**
     * @param \Magento\Staging\Api\UpdateRepositoryInterface $updateRepository
     * @param \Magento\Staging\Api\Data\UpdateInterfaceFactory $updateFactory
     * @param \Magento\CatalogRule\Model\RuleFactory $ruleFactory
     * @param \Magento\Framework\App\State $state
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Staging\Model\VersionManagerFactory $versionManagerFactory
     */
    public function __construct(
        \Magento\Staging\Api\UpdateRepositoryInterface $updateRepository,
        \Magento\Staging\Api\Data\UpdateInterfaceFactory $updateFactory,
        \Magento\CatalogRule\Model\RuleFactory $ruleFactory,
        \Magento\Framework\App\State $state,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Staging\Model\VersionManagerFactory $versionManagerFactory
    ) {
        parent::__construct($updateRepository, $updateFactory);
        
        $this->ruleFactory = $ruleFactory;
        $this->logger = $logger;
        $this->versionManagerFactory = $versionManagerFactory;
        $this->state = $state;
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
        $versionManager = $this->versionManagerFactory->create();
        $catalogRuleEntityTable = $setup->getTable('catalogrule');

        $select = $setup->getConnection()->select()->from(
            $catalogRuleEntityTable,
            ['row_id', 'rule_id', 'name', 'from_date', 'to_date']
        );
        $rules = $setup->getConnection()->fetchAll($select);

        foreach ($rules as $rule) {
            try {
                // Set current update version
                $versionManager->setCurrentVersionId(
                    $this->createUpdateForEntity($rule)->getId()
                );

                /** @var \Magento\CatalogRule\Model\Rule $ruleModel */
                $ruleModel = $this->ruleFactory->create();
                $ruleModel->load($rule['rule_id']);
                $ruleModel->unsRowId();
                $ruleModel->setIsActive(1);

                // Set is_active = false for rule entity
                $setup->getConnection()->update(
                    $catalogRuleEntityTable,
                    ['is_active' => 0],
                    ['row_id = ?' => $rule['row_id']]
                );

                // Save staging update for rule
                $ruleModel->save();
            } catch (\Magento\Framework\Exception\ValidatorException $exception) {
                // Set is_active = false for rule with dates in past
                $setup->getConnection()->update(
                    $catalogRuleEntityTable,
                    ['is_active' => 0],
                    ['row_id = ?' => $rule['row_id']]
                );
                continue;
            } catch (\Exception $exception) {
                $this->logger->critical($exception);
            }
        }
    }
}
