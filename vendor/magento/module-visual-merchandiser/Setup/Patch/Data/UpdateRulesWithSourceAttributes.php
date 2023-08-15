<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VisualMerchandiser\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\Api\Search\SearchCriteriaFactory;
use Magento\Framework\DB\DataConverter\SerializedToJson;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\VisualMerchandiser\Model\ResourceModel\Rules as RulesResourceModel;
use Magento\VisualMerchandiser\Model\ResourceModel\Rules\Collection as RulesCollection;
use Magento\VisualMerchandiser\Model\ResourceModel\Rules\CollectionFactory as RulesCollectionFactory;
use Magento\VisualMerchandiser\Model\ResourceModel\RulesFactory as RulesResourceModelFactory;
use Magento\VisualMerchandiser\Model\Rules;

/**
 * Update stored data of the existing rules.
 */
class UpdateRulesWithSourceAttributes implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var RulesCollectionFactory
     */
    private $rulesCollectionFactory;

    /**
     * @var SearchCriteriaFactory
     */
    private $searchCriteriaFactory;

    /**
     * @var RulesResourceModelFactory
     */
    private $rulesResourceModelFactory;

    /**
     * @var SerializedToJson
     */
    private $serializedToJson;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param AttributeRepositoryInterface $attributeRepository
     * @param RulesCollectionFactory $rulesCollectionFactory
     * @param SearchCriteriaFactory $searchCriteriaFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        AttributeRepositoryInterface $attributeRepository,
        RulesCollectionFactory $rulesCollectionFactory,
        SearchCriteriaFactory $searchCriteriaFactory,
        RulesResourceModelFactory $rulesResourceModelFactory,
        SerializedToJson $serializedToJson
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->attributeRepository = $attributeRepository;
        $this->rulesCollectionFactory = $rulesCollectionFactory;
        $this->searchCriteriaFactory = $searchCriteriaFactory;
        $this->rulesResourceModelFactory = $rulesResourceModelFactory;
        $this->serializedToJson = $serializedToJson;
    }

    /**
     * {@inheritdoc}
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\DB\DataConverter\DataConversionException
     */
    public function apply()
    {
        $searchCriteria = $this->searchCriteriaFactory->create();
        $searchResult = $this->attributeRepository->getList(Product::ENTITY, $searchCriteria);
        $sourceAttributes = [];

        /** @var AbstractAttribute $attribute */
        foreach ($searchResult->getItems() as $attribute) {
            $this->convertAdditionalDataField($attribute);
            if ($attribute->usesSource()) {
                $sourceAttributes[$attribute->getName()] = $attribute;
            }
        }

        /** @var RulesCollection $rulesCollection */
        $rulesCollection = $this->rulesCollectionFactory->create();
        /** @var RulesResourceModel $rulesResourceModel */
        $rulesResourceModel = $this->rulesResourceModelFactory->create();
        /** @var Rules $rules */
        foreach ($rulesCollection->getItems() as $rules) {
            $conditions = $rules->getConditions();
            if (is_array($conditions)) {
                array_walk(
                    $conditions,
                    function (&$rule) use ($sourceAttributes) {
                        if (isset($sourceAttributes[$rule['attribute']]) && $rule['operator'] === 'like') {
                            $rule['operator'] = 'eq';
                        }
                    }
                );
                $rules->setData('conditions_serialized', json_encode($conditions));
                $rulesResourceModel->save($rules);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [
            CreateAutosortAttribute::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * Convert field data to JSON format.
     *
     * Data in this column of the database will be changed by the patch in the Swatch module.
     * This module is ahead in the existing installation sequence of modules.
     *
     * @param AbstractAttribute $attribute
     * @throws \Magento\Framework\DB\DataConverter\DataConversionException
     */
    private function convertAdditionalDataField(AbstractAttribute $attribute)
    {
        if ($attribute->getData('additional_data')) {
            $attribute->setData(
                'additional_data',
                $this->serializedToJson->convert($attribute->getData('additional_data'))
            );
        }
    }
}
