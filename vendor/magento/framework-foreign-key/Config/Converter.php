<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\ForeignKey\Config;

use Magento\Framework\ForeignKey\StrategyInterface;
use Magento\Framework\App\ResourceConnection\ConfigInterface as ResourceConfigInterface;

use Magento\Framework\Stdlib\BooleanUtils;

/**
 * @deprecated split database solution is deprecated and will be removed
 */
class Converter implements \Magento\Framework\Config\ConverterInterface
{
    /**
     * @var BooleanUtils
     */
    private $booleanUtils;

    /**
     * Resource config
     *
     * @var ResourceConfigInterface
     */
    private $config;

    /**
     * @param ResourceConfigInterface $config
     * @param BooleanUtils $booleanUtils
     */
    public function __construct(ResourceConfigInterface $config, BooleanUtils $booleanUtils)
    {
        $this->booleanUtils = $booleanUtils;
        $this->config = $config;
    }

    /**
     * Convert dom node tree to array
     *
     * @param \DOMDocument $source
     * @return array
     * @throws \InvalidArgumentException
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function convert($source)
    {
        $constraints = [];
        /** @var \DOMNodeList $entities */
        $entities = $source->getElementsByTagName('entity');
        /** @var \DOMNode $entityNode */
        foreach ($entities as $entityNode) {
            $entityName = $entityNode->attributes->getNamedItem('name')->nodeValue;
            $entityResource = $entityNode->attributes->getNamedItem('resource')->nodeValue;

            /** @var $constraintNode \DOMNode */
            foreach ($entityNode->childNodes as $constraintNode) {
                if ($constraintNode->nodeType != XML_ELEMENT_NODE) {
                    continue;
                }
                $constraintActiveAttr = $constraintNode->attributes->getNamedItem('active');
                $isConstraintActive = ($constraintActiveAttr !== null)
                    ? $this->booleanUtils->toBoolean($constraintActiveAttr->nodeValue)
                    : true;

                if (!$isConstraintActive) {
                    // skip inactive constraints
                    continue;
                }
                $constraintName = $constraintNode->attributes->getNamedItem('name')->nodeValue;
                $constraintReference = $constraintNode->attributes->getNamedItem('referenceEntity')->nodeValue;
                $onDeleteAttr = $constraintNode->attributes->getNamedItem('onDelete');
                $onDeleteAction = ($onDeleteAttr !== null)
                    ? $onDeleteAttr->nodeValue
                    : StrategyInterface::TYPE_CASCADE;

                $entityField  = null;
                $referenceField  = null;
                /** @var $fieldNode \DOMNode */
                foreach ($constraintNode->childNodes as $fieldNode) {
                    if ($fieldNode->nodeType != XML_ELEMENT_NODE) {
                        continue;
                    }
                    $entityField = $fieldNode->attributes->getNamedItem('name')->nodeValue;
                    $referenceField = $fieldNode->attributes
                        ->getNamedItem('reference')
                        ->nodeValue;
                    break;
                }
                $constraints[] = [
                    'name' => $constraintName,
                    'active' => $isConstraintActive,
                    'delete_strategy' => $onDeleteAction,
                    'table_name' => $entityName,
                    'connection' => $this->config->getConnectionName($entityResource),
                    'reference_table_name' => $constraintReference,
                    'field_name' => $entityField,
                    'reference_field_name' => $referenceField,
                ];
            }
        }

        return $constraints;
    }
}
