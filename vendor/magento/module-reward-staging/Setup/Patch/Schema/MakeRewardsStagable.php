<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\RewardStaging\Setup\Patch\Schema;

use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\SalesRule\Api\Data\RuleInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class MakeRewardsStagable implements
    SchemaPatchInterface,
    PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param MetadataPool $metadataPool
     */
    public function __construct(ModuleDataSetupInterface $moduleDataSetup, MetadataPool $metadataPool)
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->metadataPool = $metadataPool;
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->dropForeignKey(
            $this->moduleDataSetup->getTable('magento_reward_salesrule'),
            $this->moduleDataSetup->getConnection()->getForeignKeyName(
                $this->moduleDataSetup->getTable('magento_reward_salesrule'),
                'rule_id',
                $this->moduleDataSetup->getTable('sequence_salesrule'),
                'sequence_value'
            )
        );

        $select = $this->moduleDataSetup->getConnection()->select()
            ->from(['rules' => $this->moduleDataSetup->getTable('salesrule')])
            ->reset(Select::COLUMNS)
            ->columns(['rules.rule_id', 'rules.row_id'])
            ->join(
                ['reward' => $this->moduleDataSetup->getTable('magento_reward_salesrule')],
                '`reward`.`rule_id` = `rules`.`rule_id`',
                'reward.points_delta'
            )
            ->setPart('disable_staging_preview', true);
        $deltas = $this->moduleDataSetup->getConnection()->fetchAll($select);

        $this->moduleDataSetup->getConnection()->delete($this->moduleDataSetup->getTable('magento_reward_salesrule'));

        $data = [];
        foreach ($deltas as $delta) {
            $data[] = [$delta['row_id'], $delta['points_delta']];
        }
        if (count($data)) {
            $this->moduleDataSetup->getConnection()->insertArray(
                $this->moduleDataSetup->getTable('magento_reward_salesrule'),
                ['rule_id', 'points_delta'],
                $data
            );
        }

        $metadata = $this->metadataPool->getMetadata(RuleInterface::class);
        $this->moduleDataSetup->getConnection()->addForeignKey(
            $this->moduleDataSetup->getConnection()->getForeignKeyName(
                $this->moduleDataSetup->getTable('magento_reward_salesrule'),
                'rule_id',
                $metadata->getEntityTable(),
                $metadata->getLinkField()
            ),
            $this->moduleDataSetup->getTable('magento_reward_salesrule'),
            'rule_id',
            $this->moduleDataSetup->getTable($metadata->getEntityTable()),
            $metadata->getLinkField()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [

        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '2.0.0';
    }
}
