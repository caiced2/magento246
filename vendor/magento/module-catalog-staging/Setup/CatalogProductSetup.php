<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Setup;

use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Eav\Model\Config;
use Magento\Framework\App\State;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Api\Data\UpdateInterfaceFactory;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\VersionManager;
use Magento\Staging\Model\VersionManagerFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * @codeCoverageIgnore
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CatalogProductSetup
{
    /**#@+
     * Endpoint types
     */
    public const ENDPOINT_FROM = 0;
    public const ENDPOINT_TO = 1;
    /**#@-*/

    /**
     * @var Config
     */
    protected $eavConfig;

    /**
     * @var VersionManager
     */
    protected $versionManager;

    /**
     * @var UpdateInterfaceFactory
     */
    protected $updateFactory;

    /**
     * @var UpdateRepositoryInterface
     */
    protected $updateRepository;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var State
     */
    protected $state;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ModuleDataSetupInterface
     */
    protected $setup;

    /**
     * @var array
     */
    protected $metadata = [
        'special_price' => [],
        'special_from_date' => [
            'endpoint_type' => self::ENDPOINT_FROM,
            'linked_attribute_code' => 'special_to_date',
            'parent_attribute_code' => 'special_price',
        ],
        'special_to_date' => [
            'endpoint_type' => self::ENDPOINT_TO,
            'linked_attribute_code' => 'special_from_date',
            'parent_attribute_code' => 'special_price',
        ],
        'custom_design' => [],
        'custom_design_from' => [
            'endpoint_type' => self::ENDPOINT_FROM,
            'linked_attribute_code' => 'custom_design_to',
            'parent_attribute_code' => 'custom_design',
        ],
        'custom_design_to' => [
            'endpoint_type' => self::ENDPOINT_TO,
            'linked_attribute_code' => 'custom_design_from',
            'parent_attribute_code' => 'custom_design',
        ],
        'news_from_date' => [
            'endpoint_type' => self::ENDPOINT_FROM,
            'linked_attribute_code' => 'news_to_date',
        ],
        'news_to_date' => [
            'endpoint_type' => self::ENDPOINT_TO,
            'linked_attribute_code' => 'news_from_date',
        ],
    ];

    /**
     * @var array
     */
    protected $attributesMap = [];

    /**
     * @var array
     */
    protected $attributesList = [];

    /**
     * @var array
     */
    protected $originProductEntities = [];

    /**
     * @var int
     */
    protected $originVersionId;

    /**
     * @var Product
     */
    protected $productEntity;

    /**
     * @param Config $eavConfig
     * @param VersionManagerFactory $versionManagerFactory
     * @param UpdateInterfaceFactory $updateFactory
     * @param UpdateRepositoryInterface $updateRepository
     * @param ProductFactory $productFactory
     * @param StoreManagerInterface $storeManager
     * @param State $state
     * @param LoggerInterface $logger
     */
    public function __construct(
        Config $eavConfig,
        VersionManagerFactory $versionManagerFactory,
        UpdateInterfaceFactory $updateFactory,
        UpdateRepositoryInterface $updateRepository,
        ProductFactory $productFactory,
        StoreManagerInterface $storeManager,
        State $state,
        LoggerInterface $logger
    ) {
        $this->eavConfig = $eavConfig;
        $this->versionManager = $versionManagerFactory->create();
        $this->updateFactory = $updateFactory;
        $this->updateRepository = $updateRepository;
        $this->productFactory = $productFactory;
        $this->storeManager = $storeManager;
        $this->state = $state;
        $this->logger = $logger;
    }

    /**
     * Executes staging content data patch
     *
     * @param ModuleDataSetupInterface $setup
     * @return void
     */
    public function execute(ModuleDataSetupInterface $setup)
    {
        $this->setup = $setup;
        $this->setup->startSetup();
        $this->state->emulateAreaCode(
            FrontNameResolver::AREA_CODE,
            [$this, 'process']
        );
        $this->setup->endSetup();
    }

    /**
     * Initialize product attributes and process updates for staging content data patch
     *
     * @return void
     */
    public function process()
    {
        try {
            $this->initAttributesMap();
            $this->initAttributesList();

            $this->originVersionId = $this->versionManager->getVersion()->getId();

            $entityIds = array_keys($this->attributesList);
            foreach ($entityIds as $entityId) {
                $this->generateUpdates($entityId);
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->versionManager->setCurrentVersionId($this->originVersionId);
        }
    }

    /**
     * Retrieve list of eav attributes metadata
     *
     * @return array
     */
    protected function getEavAttributesMetadata()
    {
        $entityTypeId = $this->eavConfig->getEntityType(Product::ENTITY)->getId();

        $select = $this->setup->getConnection()->select()->from(
            $this->setup->getTable('eav_attribute'),
            ['attribute_id', 'attribute_code']
        );
        $select->where('attribute_code IN (?)', array_keys($this->metadata));
        $select->where('entity_type_id = ?', $entityTypeId);

        $attributes = $this->setup->getConnection()->fetchAll($select);
        return $attributes;
    }

    /**
     * Initialize attributes map
     *
     * @return void
     */
    protected function initAttributesMap()
    {
        $attributes = $this->getEavAttributesMetadata();

        foreach ($attributes as $attribute) {
            $this->attributesMap[$attribute['attribute_id']] = [
                'attribute_code' => $attribute['attribute_code'],
            ];

            // Add linked attribute Id
            $linkedAttributeCode = $this->getLinkedAttributeCode($attribute['attribute_code']);
            if ($linkedAttributeCode) {
                $linkedAttribute = array_filter($attributes, function ($value) use ($linkedAttributeCode) {
                    if ($value['attribute_code'] == $linkedAttributeCode) {
                        return true;
                    }
                });
                if (is_array($linkedAttribute) && !empty($linkedAttribute)) {
                    $linkedAttributeId = current($linkedAttribute)['attribute_id'];
                    $this->attributesMap[$attribute['attribute_id']]['linked_attribute_id'] = $linkedAttributeId;
                }
            }
        }
    }

    /**
     * Retrieve product entity datetime attributes
     *
     * @return array
     */
    protected function getDatetimeAttributes()
    {
        $select = $this->setup->getConnection()->select()->from(
            ['dt' => $this->setup->getTable('catalog_product_entity_datetime')],
            ['*']
        )->join(
            ['e' => $this->setup->getTable('catalog_product_entity')],
            'e.row_id=dt.row_id',
            ['entity_id']
        );

        $attributes = $this->setup->getConnection()->fetchAll($select);
        return $attributes;
    }

    /**
     * Initialize list of product entity datetime attributes
     *
     * @return void
     */
    protected function initAttributesList()
    {
        $attributes = $this->getDatetimeAttributes();

        $this->attributesList = [];

        foreach ($attributes as $attribute) {
            $entityId = $attribute['entity_id'];
            $storeId = $attribute['store_id'];
            $attributeId = $attribute['attribute_id'];

            if (!isset($this->attributesList[$entityId])) {
                $this->attributesList[$entityId] = [];
            }
            if (!isset($this->attributesList[$entityId][$storeId])) {
                $this->attributesList[$entityId][$storeId] = [];
            }

            $date = new \DateTime($attribute['value'], new \DateTimeZone('UTC'));
            $timestamp = $date->format('U');
            // phpcs:ignore Magento2.Performance.ForeachArrayMerge
            $this->attributesList[$entityId][$storeId][$attributeId] = array_merge(
                $attribute,
                ['timestamp' => $timestamp]
            );
        }
    }

    /**
     * Generate updates by datetime intervals
     *
     * @param int $entityId
     * @return void
     */
    protected function generateUpdates($entityId)
    {
        $currentStoreId = -1;

        $intervals = $this->getUpdateIntervals($entityId);
        if (!empty($intervals)) {
            $this->loadOriginProductEntities($entityId);
        }

        $versions = [];
        foreach ($intervals as $data) {
            $endpointsByStore = $this->getEndpointsMap($data['endpoints']);
            foreach ($endpointsByStore as $storeId => $attributes) {
                $filteredAttributes = $this->filterAttributes($storeId, $attributes);
                if (empty($filteredAttributes)) {
                    continue;
                }

                if ($currentStoreId < 0 || $currentStoreId != $storeId) {
                    $currentStoreId = $storeId;

                    $this->productEntity = clone $this->originProductEntities[$storeId];
                    $this->productEntity->unlockAttributes();
                }

                if (!isset($versions[$data['timestamp_start']])) {
                    $versionId = $this->createUpdate($data['timestamp_start'], $data['timestamp_end'])->getId();
                    $this->versionManager->setCurrentVersionId($versionId);

                    $versions[$data['timestamp_start']] = $versionId;
                }

                $this->resetProductEntity();
                $this->updateProductEntity(
                    $data['timestamp_start'],
                    $data['timestamp_end'],
                    $storeId,
                    $filteredAttributes
                );
            }
        }

        if (!empty($versions)) {
            $this->resetOriginProductEntities((int)reset($versions));
        }
    }

    /**
     * Filter attributes
     *
     * Reduce attributes with value equals to null
     *
     * @param int $storeId
     * @param array $attributes
     * @return array
     */
    protected function filterAttributes($storeId, array $attributes)
    {
        $result = array_filter($attributes, function ($attributeId) use ($storeId) {
            $attributeCode = $this->attributesMap[$attributeId]['attribute_code'];
            if ($attributeCode == 'custom_design_from'
                || $attributeCode == 'special_from_date'
            ) {
                $parentAttributeCode = $this->getParentAttributeCode($attributeCode);
                $parentAttributeValue = $this->originProductEntities[$storeId]->getData($parentAttributeCode);
                return $parentAttributeValue !== null;
            }
            return true;
        });
        return $result;
    }

    /**
     * Retrieve endpoints grouped by store ID
     *
     * @param array $endpoints
     * @return array
     */
    protected function getEndpointsMap(array $endpoints)
    {
        $result = [];
        foreach ($endpoints as $uid => $attributeId) {
            $storeId = explode('_', $uid)[0];
            if (!isset($result[$storeId])) {
                $result[$storeId] = [];
            }
            $result[$storeId][] = $attributeId;
        }
        return $result;
    }

    /**
     * Load origin product entities for required store IDs
     *
     * @param int $entityId
     * @return void
     */
    protected function loadOriginProductEntities($entityId)
    {
        $this->versionManager->setCurrentVersionId($this->originVersionId);

        $this->originProductEntities = [];

        $storeIds = array_keys($this->attributesList[$entityId]);
        foreach ($storeIds as $storeId) {
            $this->storeManager->setCurrentStore($storeId);

            /** @var Product $originProductEntity */
            $originProductEntity = $this->productFactory->create();
            $originProductEntity->setStoreId($storeId);
            $originProductEntity->load($entityId);

            $this->originProductEntities[$storeId] = $originProductEntity;
        }
    }

    /**
     * Reset origin product entities
     *
     * @param int $versionId
     * @return void
     */
    protected function resetOriginProductEntities($versionId)
    {
        $this->versionManager->setCurrentVersionId($this->originVersionId);

        /** @var Product $originProductEntity */
        foreach ($this->originProductEntities as $originProductEntity) {
            if ($versionId) {
                $originProductEntity->setData('updated_in', $versionId);
            }
            foreach (array_keys($this->metadata) as $attributeCode) {
                $originProductEntity->setData($attributeCode, false);
            }
            $originProductEntity->save();
        }
    }

    /**
     * Retrieve list of update timestamps
     *
     * @param array $attributesByStore
     * @return array
     */
    protected function getUpdateTimestamps(array $attributesByStore)
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $timestampNow = $date->getTimestamp();

        $result = [];
        foreach ($attributesByStore as $storeId => $attributes) {
            foreach ($attributes as $attributeId => $data) {
                $endpointType = $this->getEndpointType($attributeId);
                $linkedAttributeId = $this->getLinkedAttributeId($attributeId);

                // Do not process outdated attributes
                if ($endpointType == self::ENDPOINT_TO
                    && $data['timestamp'] < $timestampNow
                ) {
                    continue;
                }
                if ($endpointType == self::ENDPOINT_FROM
                    && isset($attributes[$linkedAttributeId])
                    && $attributes[$linkedAttributeId]['timestamp'] < $timestampNow
                ) {
                    continue;
                }

                $uid = $storeId . '_' . $attributeId;
                $result[$uid] = $data['timestamp'];
            }
        }

        array_walk($result, function (&$timestamp) use ($timestampNow) {
            if ($timestamp < $timestampNow) {
                $timestamp = $timestampNow + 60;
            }
        });

        asort($result);
        return $result;
    }

    /**
     * Retrieve list of update intervals
     *
     * @param int $entityId
     * @return array
     */
    protected function getUpdateIntervals($entityId)
    {
        $timestamps = $this->getUpdateTimestamps($this->attributesList[$entityId]);

        $intervals = [];
        $endpoints = [];

        $prevTimestamp = 0;
        foreach ($timestamps as $uid => $timestamp) {
            if ($prevTimestamp && $prevTimestamp < $timestamp) {
                $intervals[$prevTimestamp] = [
                    'timestamp_start' => $prevTimestamp,
                    'timestamp_end' => $timestamp - 1,
                    'endpoints' => $endpoints,
                ];
            }

            $prevTimestamp = $timestamp;

            $attributeId = explode('_', $uid)[1];
            if (self::ENDPOINT_TO == $this->getEndpointType($attributeId)) {
                $storeId = explode('_', $uid)[0];
                $linkedUid = $storeId . '_' . $this->getLinkedAttributeId($attributeId);
                unset($endpoints[$linkedUid]);
                continue;
            }

            $endpoints[$uid] = $attributeId;
        }

        if (!empty($endpoints)) {
            $intervals[$prevTimestamp] = [
                'timestamp_start' => $prevTimestamp,
                'timestamp_end' => null,
                'endpoints' => $endpoints,
            ];
        }

        return $intervals;
    }

    /**
     * Reset product entity
     *
     * @return void
     */
    protected function resetProductEntity()
    {
        $versionId = $this->versionManager->getVersion()->getId();

        $this->productEntity->setData('row_id', false);
        $this->productEntity->setData('created_in', $versionId);

        foreach (array_keys($this->metadata) as $attributeCode) {
            $this->productEntity->setData($attributeCode, false);
        }

        $optionsOriginal = $this->productEntity->getData('options');
        if (!is_array($optionsOriginal)) {
            return;
        }
        $options = [];
        foreach ($optionsOriginal as $originalOption) {
            $options[] = clone $originalOption;
        }
        $this->productEntity->setOptions($options);
    }

    /**
     * Update product entity
     *
     * @param int $timestampStart
     * @param int $timestampEnd
     * @param int $storeId
     * @param array $attributes
     * @return void
     */
    protected function updateProductEntity($timestampStart, $timestampEnd, $storeId, array $attributes = [])
    {
        $attributeCodes = array_map(function ($attributeId) {
            return $this->attributesMap[$attributeId]['attribute_code'];
        }, $attributes);

        foreach ($attributeCodes as $attributeCode) {
            // Set parent attribute value
            $parentAttributeCode = $this->getParentAttributeCode($attributeCode);
            if ($parentAttributeCode) {
                $parentAttributeValue = $this->originProductEntities[$storeId]->getData($parentAttributeCode);
                $this->productEntity->setData($parentAttributeCode, $parentAttributeValue);
            }

            // Set 'from' attribute value
            $date = new \DateTime('@' . $timestampStart, new \DateTimeZone('UTC'));
            $this->productEntity->setData($attributeCode, $date->format('Y-m-d H:i:s'));

            // Set 'to' attribute value
            $linkedAttributeCode = $this->getLinkedAttributeCode($attributeCode);
            if ($timestampEnd && $linkedAttributeCode) {
                $date = new \DateTime('@' . $timestampEnd, new \DateTimeZone('UTC'));
                $this->productEntity->setData($linkedAttributeCode, $date->format('Y-m-d H:i:s'));
            }
        }

        $this->productEntity->save();
    }

    /**
     * Retrieve attribute type
     *
     * @param int $attributeId
     * @return string
     */
    protected function getEndpointType($attributeId)
    {
        $attributeCode = $this->attributesMap[$attributeId]['attribute_code'];
        return $this->metadata[$attributeCode]['endpoint_type'];
    }

    /**
     * Retrieve linked attribute ID
     *
     * @param int $attributeId
     * @return int
     */
    protected function getLinkedAttributeId($attributeId)
    {
        return $this->attributesMap[$attributeId]['linked_attribute_id'];
    }

    /**
     * Retrieve linked attribute code
     *
     * @param string $attributeCode
     * @return string|null
     */
    protected function getLinkedAttributeCode($attributeCode)
    {
        if (isset($this->metadata[$attributeCode]['linked_attribute_code'])) {
            return $this->metadata[$attributeCode]['linked_attribute_code'];
        }
        return null;
    }

    /**
     * Retrieve parent attribute code
     *
     * @param string $attributeCode
     * @return string|null
     */
    protected function getParentAttributeCode($attributeCode)
    {
        if (isset($this->metadata[$attributeCode]['parent_attribute_code'])) {
            return $this->metadata[$attributeCode]['parent_attribute_code'];
        }
        return null;
    }

    /**
     * Create update for product entity
     *
     * @param int $timestampStart
     * @param int $timestampEnd
     * @return UpdateInterface
     */
    protected function createUpdate($timestampStart, $timestampEnd)
    {
        /** @var UpdateInterface $update */
        $update = $this->updateFactory->create();
        $update->setName($this->productEntity->getSku());

        $date = new \DateTime('@' . $timestampStart, new \DateTimeZone('UTC'));
        $update->setStartTime($date->format('Y-m-d H:i:s'));

        if ($timestampEnd) {
            $date = new \DateTime('@' . $timestampEnd, new \DateTimeZone('UTC'));
            $update->setEndTime($date->format('Y-m-d H:i:s'));
        }

        $update->setIsCampaign(false);
        $this->updateRepository->save($update);

        return $update;
    }
}
