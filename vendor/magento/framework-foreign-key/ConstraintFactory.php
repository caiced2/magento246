<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\ForeignKey;

use Magento\Framework\ObjectManagerInterface;

/**
 * @deprecated split database solution is deprecated and will be removed
 */
class ConstraintFactory
{
    /**
     * @var array
     */
    private $constraints = [];

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param array $constraintData
     * @param array $constraintConfig constraints grouped by reference table name
     * @return ConstraintInterface
     */
    public function get($constraintData, array $constraintConfig)
    {
        if (isset($this->constraints[$constraintData['name']])) {
            return $this->constraints[$constraintData['name']];
        }
        $tableName = $constraintData['table_name'];
        $subConstraints = [];
        if (isset($constraintConfig[$tableName])) {
            foreach ($constraintConfig[$tableName] as $childConstraintData) {
                $subConstraints[] = $this->get($childConstraintData, $constraintConfig);
            }
        }
        $constraint =  $this->objectManager->create(
            \Magento\Framework\ForeignKey\ConstraintInterface::class,
            [
                'name' => $constraintData['name'],
                'connectionName' => $constraintData['connection'],
                'referenceConnection' => $constraintData['reference_connection'],
                'tableName' => $constraintData['table_name'],
                'referenceTableName' => $constraintData['reference_table_name'],
                'fieldName' => $constraintData['field_name'],
                'referenceFieldName' => $constraintData['reference_field_name'],
                'deleteStrategy' => $constraintData['delete_strategy'],
                'subConstraints' => $subConstraints,
                'tableAffectedFields' => $constraintData['table_affected_fields'],
            ]
        );
        $this->constraints[$constraintData['name']] = $constraint;
        return $this->constraints[$constraintData['name']];
    }
}
