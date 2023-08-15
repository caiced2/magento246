<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftCard\Model\ResourceModel\Indexer;

use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Indexer\DimensionalIndexerInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Sql\ColumnValueExpression;
use Magento\Catalog\Model\Indexer\Product\Price\TableMaintainer;
use Magento\Catalog\Model\ResourceModel\Product\Indexer\Price\IndexTableStructureFactory;
use Magento\Catalog\Model\ResourceModel\Product\Indexer\Price\Query\JoinAttributeProcessor;
use Magento\Catalog\Model\ResourceModel\Product\Indexer\Price\BasePriceModifier;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Customer\Model\Indexer\CustomerGroupDimensionProvider;
use Magento\Store\Model\Indexer\WebsiteDimensionProvider;

/**
 * GiftCard product price indexer resource model
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Price implements DimensionalIndexerInterface
{
    /**
     * @var IndexTableStructureFactory
     */
    private $indexTableStructureFactory;

    /**
     * @var TableMaintainer
     */
    private $tableMaintainer;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resource;

    /**
     * @var string
     */
    private $connectionName;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private $connection;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager = null;

    /**
     * @var JoinAttributeProcessor
     */
    private $joinAttributeProcessor;

    /**
     * @var \Magento\Eav\Model\Config
     */
    private $eavConfig;

    /**
     * Mapping between dimensions and field in database
     *
     * @var array
     */
    private $dimensionToFieldMapper = [
        WebsiteDimensionProvider::DIMENSION_NAME => 'pw.website_id',
        CustomerGroupDimensionProvider::DIMENSION_NAME => 'cg.customer_group_id',
    ];

    /**
     * @var BasePriceModifier
     */
    private $basePriceModifier;

    /**
     * @param BasePriceModifier $basePriceModifier
     * @param IndexTableStructureFactory $indexTableStructureFactory
     * @param TableMaintainer $tableMaintainer
     * @param MetadataPool $metadataPool
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param JoinAttributeProcessor $joinAttributeProcessor
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param string $connectionName
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        BasePriceModifier $basePriceModifier,
        IndexTableStructureFactory $indexTableStructureFactory,
        TableMaintainer $tableMaintainer,
        MetadataPool $metadataPool,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        JoinAttributeProcessor $joinAttributeProcessor,
        \Magento\Eav\Model\Config $eavConfig,
        $connectionName = 'indexer'
    ) {
        $this->indexTableStructureFactory = $indexTableStructureFactory;
        $this->tableMaintainer = $tableMaintainer;
        $this->metadataPool = $metadataPool;
        $this->resource = $resource;
        $this->eventManager = $eventManager;
        $this->joinAttributeProcessor = $joinAttributeProcessor;
        $this->eavConfig = $eavConfig;
        $this->basePriceModifier = $basePriceModifier;
        $this->connectionName = $connectionName;
    }

    /**
     * @inheritDoc
     */
    public function executeByDimensions(array $dimensions, \Traversable $entityIds)
    {
        $this->tableMaintainer->createMainTmpTable($dimensions);

        $temporaryPriceTable = $this->indexTableStructureFactory->create([
            'tableName' => $this->tableMaintainer->getMainTmpTable($dimensions),
            'entityField' => 'entity_id',
            'customerGroupField' => 'customer_group_id',
            'websiteField' => 'website_id',
            'taxClassField' => 'tax_class_id',
            'originalPriceField' => 'price',
            'finalPriceField' => 'final_price',
            'minPriceField' => 'min_price',
            'maxPriceField' => 'max_price',
            'tierPriceField' => 'tier_price',
        ]);

        $select = $this->giftCardFinalPriceSelect(
            $dimensions,
            \Magento\GiftCard\Model\Catalog\Product\Type\Giftcard::TYPE_GIFTCARD,
            iterator_to_array($entityIds)
        );
        $this->tableMaintainer->insertFromSelect($select, $temporaryPriceTable->getTableName(), [
            "entity_id",
            "customer_group_id",
            "website_id",
            "tax_class_id",
            "price",
            "final_price",
            "min_price",
            "max_price",
            "tier_price",
        ]);

        $this->basePriceModifier->modifyPrice($temporaryPriceTable, iterator_to_array($entityIds));
    }

    /**
     * Get GiftCard final price select
     *
     * @param array $dimensions
     * @param string $productType
     * @param array $entityIds
     * @return Select
     * @throws \Exception
     * @throws \LogicException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Select_Exception
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function giftCardFinalPriceSelect(array $dimensions, string $productType, array $entityIds = [])
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from(
            ['e' => $this->getTable('catalog_product_entity')],
            ['entity_id']
        )->joinInner(
            ['cg' => $this->getTable('customer_group')],
            array_key_exists(CustomerGroupDimensionProvider::DIMENSION_NAME, $dimensions)
                ? sprintf(
                    '%s = %s',
                    $this->dimensionToFieldMapper[CustomerGroupDimensionProvider::DIMENSION_NAME],
                    $dimensions[CustomerGroupDimensionProvider::DIMENSION_NAME]->getValue()
                ) : '',
            ['customer_group_id']
        )->joinInner(
            ['pw' => $this->getTable('catalog_product_website')],
            'pw.product_id = e.entity_id',
            ['pw.website_id']
        )->joinInner(
            ['cwd' => $this->getTable('catalog_product_index_website')],
            'pw.website_id = cwd.website_id',
            []
        )->joinLeft(
            // customer group website limitations
            ['cgw' => $this->getTable('customer_group_excluded_website')],
            'cg.customer_group_id = cgw.customer_group_id AND pw.website_id = cgw.website_id',
            []
        );

        $select->columns(['tax_class_id' => new ColumnValueExpression('0')])->where('e.type_id = ?', $productType);
        $this->joinAttributeProcessor->process($select, 'status', Status::STATUS_ENABLED);

        $allowOpenAmount = $this->joinAttributeProcessor->process($select, 'allow_open_amount');
        $openAmountMin = $this->joinAttributeProcessor->process($select, 'open_amount_min');

        // join giftCard amounts table
        $attrAmounts = $this->eavConfig->getAttribute(\Magento\Catalog\Model\Product::ENTITY, 'giftcard_amounts');
        $linkField = $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();
        $select->joinLeft(
            ['gca' => $this->getTable('magento_giftcard_amount')],
            "gca.{$linkField} = e.{$linkField} AND gca.attribute_id = " .
            $attrAmounts->getAttributeId() .
            ' AND (gca.website_id = pw.website_id OR gca.website_id = 0)',
            []
        );

        $amountsExpr = 'MIN(' . $connection->getCheckSql('gca.value_id IS NULL', 'NULL', 'gca.value') . ')';

        $openAmountExpr = 'MIN(' . $connection->getCheckSql(
            $allowOpenAmount . ' = 1',
            $connection->getCheckSql($openAmountMin . ' > 0', $openAmountMin, '0'),
            'NULL'
        ) . ')';

        $priceExpr = new ColumnValueExpression(
            'ROUND(' . $connection->getCheckSql(
                $openAmountExpr . ' IS NULL',
                $connection->getCheckSql($amountsExpr . ' IS NULL', '0', $amountsExpr),
                $connection->getCheckSql(
                    $amountsExpr . ' IS NULL',
                    $openAmountExpr,
                    $connection->getCheckSql($openAmountExpr . ' > ' . $amountsExpr, $amountsExpr, $openAmountExpr)
                )
            ) . ', 4)'
        );

        $select->group(
            ['e.entity_id', 'cg.customer_group_id', 'pw.website_id']
        )->columns(
            [
                'price' => new ColumnValueExpression('NULL'),
                'final_price' => $priceExpr,
                'min_price' => $priceExpr,
                'max_price' => new ColumnValueExpression('NULL'),
                'tier_price' => new ColumnValueExpression('NULL'),
            ]
        );

        if (!empty($entityIds)) {
            $select->where('e.entity_id IN(?)', $entityIds);
        }

        foreach ($dimensions as $dimension) {
            if (!isset($this->dimensionToFieldMapper[$dimension->getName()])) {
                throw new \LogicException(
                    'Provided dimension is not valid for Price indexer: ' . $dimension->getName()
                );
            }
            $select->where($this->dimensionToFieldMapper[$dimension->getName()] . ' = ?', $dimension->getValue());
        }

        // exclude websites that are limited for customer group
        $select->where('cgw.website_id IS NULL');

        /**
         * Add additional external limitation
         */
        $this->eventManager->dispatch(
            'prepare_catalog_product_index_select',
            [
                'select' => $select,
                'entity_field' => new ColumnValueExpression('e.entity_id'),
                'website_field' => new ColumnValueExpression('pw.website_id'),
                'store_field' => new ColumnValueExpression('cwd.default_store_id'),
            ]
        );

        return $select;
    }

    /**
     * Get connection
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     * @throws \DomainException
     */
    private function getConnection(): \Magento\Framework\DB\Adapter\AdapterInterface
    {
        if ($this->connection === null) {
            $this->connection = $this->resource->getConnection($this->connectionName);
        }

        return $this->connection;
    }

    /**
     * Get table
     *
     * @param string $tableName
     * @return string
     */
    private function getTable($tableName)
    {
        return $this->resource->getTableName($tableName, $this->connectionName);
    }
}
