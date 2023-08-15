<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Magento\Rma\Setup\Patch\Data;

use Magento\Framework\DB\Query\Generator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Clean Up Data Removes unused data
 */
class WishlistDataCleanUp implements DataPatchInterface
{
    /**
     * Batch size for query
     */
    private const BATCH_SIZE = 1000;

    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @var Generator
     */
    private $queryGenerator;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * RemoveData constructor.
     * @param Json $json
     * @param Generator $queryGenerator
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param LoggerInterface $logger
     */
    public function __construct(
        Json $json,
        Generator $queryGenerator,
        ModuleDataSetupInterface $moduleDataSetup,
        LoggerInterface $logger
    ) {
        $this->json = $json;
        $this->queryGenerator = $queryGenerator;
        $this->moduleDataSetup = $moduleDataSetup;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function apply()
    {
        try {
            $this->cleanMagentoRmaItemEntityTable();
        } catch (\Throwable $e) {
            $this->logger->warning(
                'Rma module WishlistDataCleanUp patch experienced an error and could not be completed.'
                . ' Please submit a support ticket or email us at security@magento.com.'
            );

            return $this;
        }

        return $this;
    }

    /**
     * Remove login data from magento_rma_item_entity table.
     *
     * @throws LocalizedException
     */
    private function cleanMagentoRmaItemEntityTable()
    {
        $tableName = $this->moduleDataSetup->getTable('magento_rma_item_entity');
        $select = $this->moduleDataSetup
            ->getConnection()
            ->select()
            ->from(
                $tableName,
                ['entity_id', 'product_options']
            )
            ->where(
                'product_options LIKE ?',
                '%login%'
            );
        $iterator = $this->queryGenerator->generate('entity_id', $select, self::BATCH_SIZE);
        $rowErrorFlag = false;
        foreach ($iterator as $selectByRange) {
            $entityRows = $this->moduleDataSetup->getConnection()->fetchAll($selectByRange);
            foreach ($entityRows as $entityRow) {
                try {
                    $rowValue = $this->json->unserialize($entityRow['product_options']);
                    if (is_array($rowValue)
                        && array_key_exists('info_buyRequest', $rowValue)
                        && array_key_exists('login', $rowValue['info_buyRequest'])
                    ) {
                        unset($rowValue['info_buyRequest']['login']);
                    }
                    $rowValue = $this->json->serialize($rowValue);
                    $this->moduleDataSetup->getConnection()->update(
                        $tableName,
                        ['product_options' => $rowValue],
                        ['entity_id = ?' => $entityRow['entity_id']]
                    );
                } catch (\Throwable $e) {
                    $rowErrorFlag = true;
                    continue;
                }
            }
        }
        if ($rowErrorFlag) {
            $this->logger->warning(
                'Data clean up could not be completed due to unexpected data format in the table "'
                . $tableName
                . '". Please submit a support ticket or email us at security@magento.com.'
            );
        }
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [
            ConvertSerializedToJson::class
        ];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
