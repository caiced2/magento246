<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogRuleStaging\Model\Plugin;

class ConditionFieldsetIdResolver
{
    /**
     * @var \Magento\Framework\EntityManager\MetadataPool
     */
    protected $metadataPool;

    /**
     * @param \Magento\Framework\EntityManager\MetadataPool $metadataPool
     */
    public function __construct(\Magento\Framework\EntityManager\MetadataPool $metadataPool)
    {
        $this->metadataPool = $metadataPool;
    }

    /**
     * @param \Magento\CatalogRule\Model\Rule $subject
     * @param \Closure $proceed
     * @param string $formName
     * @return string
     * @throws \Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetConditionsFieldSetId(
        \Magento\CatalogRule\Model\Rule $subject,
        \Closure $proceed,
        $formName = ''
    ) {
        $metadata = $this->metadataPool->getMetadata(\Magento\CatalogRule\Api\Data\RuleInterface::class);
        return $formName . 'rule_conditions_fieldset_' . $subject->getData($metadata->getLinkField());
    }
}
