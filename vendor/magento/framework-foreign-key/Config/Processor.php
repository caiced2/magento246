<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\ForeignKey\Config;

use Magento\Framework\Exception\InputException;

/**
 * @deprecated split database solution is deprecated and will be removed
 */
class Processor
{
    /**
     * Process constraint configuration. Group constraints by table name and reference table name
     *
     * @param array $xmlConstraints
     * @param array $databaseConstraints
     * @param array $databaseTables
     * @return array
     * @throws InputException
     */
    public function process(array $xmlConstraints, array $databaseConstraints, array $databaseTables)
    {
        $xmlConstraints = $this->processTablePrefixes($xmlConstraints, $databaseTables);
        $constraints = $this->mergeConstraints($xmlConstraints, $databaseConstraints);
        $constraints = $this->addTableAffectedFieldsToConstraint($constraints);
        $groupedByTable = $this->groupConstraintsByTable($constraints);
        $groupedByReference = $this->groupConstraintsByReferenceTable($constraints);
        $config = [
            'constraints_by_reference_table' => $groupedByReference,
            'constraints_by_table' => $groupedByTable,
        ];
        return $config;
    }

    /**
     * @param array $xmlConstraints
     * @param array $databaseConstraints
     * @return array
     */
    protected function mergeConstraints(array $xmlConstraints, array $databaseConstraints)
    {
        // skip XML constraint if it has corresponding native foreign key
        $constraints = array_diff_key($xmlConstraints, $databaseConstraints);
        foreach ($xmlConstraints as $xmlConstraint) {
            $referenceTableName = $xmlConstraint['reference_table_name'];
            $constraints = array_merge(
                $constraints,
                $this->getAffectedDatabaseConstraints($referenceTableName, $databaseConstraints)
            );
        }
        return $constraints;
    }

    /**
     * @param string $referenceTableName
     * @param array $databaseConstraints
     * @return array
     */
    protected function getAffectedDatabaseConstraints($referenceTableName, array $databaseConstraints)
    {
        $constraints = [];
        foreach ($databaseConstraints as $databaseConstraintId => $databaseConstraint) {
            if ($databaseConstraint['table_name'] != $referenceTableName) {
                continue;
            }
            $constraints[$databaseConstraintId] = $databaseConstraint;
            $constraints = array_merge(
                $constraints,
                $this->getAffectedDatabaseConstraints(
                    $databaseConstraint['reference_table_name'],
                    $databaseConstraints
                )
            );
        }
        return $constraints;
    }

    /**
     * Add prefixes to all table names referenced by constraints
     *
     * @param array $xmlConstraints
     * @param array $databaseTables
     * @return array
     * @throws InputException
     */
    protected function processTablePrefixes(array $xmlConstraints, array $databaseTables)
    {
        $output = [];
        foreach ($xmlConstraints as $constraint) {
            if (!isset($databaseTables[$constraint['reference_table_name']])
                || !isset($databaseTables[$constraint['table_name']])) {
                throw new InputException(
                    new \Magento\Framework\Phrase(
                        'Constraint "%1" references table that does not exist.',
                        [$constraint['name']]
                    )
                );
            }
            $constraint['reference_connection'] = $databaseTables[$constraint['reference_table_name']]['connection'];
            $constraint['reference_table_name'] = $databaseTables[$constraint['reference_table_name']]['prefixed_name'];
            $constraint['table_name'] = $databaseTables[$constraint['table_name']]['prefixed_name'];

            $constraintId = sha1(
                $constraint['table_name']
                . $constraint['reference_table_name']
                . $constraint['field_name']
                . $constraint['reference_field_name']
            );
            $output[$constraintId] = $constraint;
        }
        return $output;
    }

    /**
     * Add information about affected table fields to constraint
     *
     * @param array $constraints
     * @return array
     */
    protected function addTableAffectedFieldsToConstraint(array $constraints)
    {
        $output = [];
        foreach ($constraints as $constraint) {
            $constraint['table_affected_fields'] = $this->getTableAffectedFields(
                $constraint['table_name'],
                $constraints
            );
            $output[] = $constraint;
        }
        return $output;
    }

    /**
     * Retrieve the list of fields in the given table that are referenced by other tables
     *
     * @param string $tableName
     * @param array $constraints
     * @return array
     */
    protected function getTableAffectedFields($tableName, array $constraints)
    {
        $tableAffectedFields = [];
        foreach ($constraints as $constraint) {
            if ($constraint['reference_table_name'] == $tableName) {
                $tableAffectedFields[] = $constraint['reference_field_name'];
            }
        }
        return (!empty($tableAffectedFields)) ? $tableAffectedFields : ['*'];
    }

    /**
     * Group constraints by table name
     *
     * @param array $constraints
     * @return array
     */
    protected function groupConstraintsByTable(array $constraints)
    {
        $output = [];
        foreach ($constraints as $constraint) {
            if (!isset($output[$constraint['table_name']])) {
                $output[$constraint['table_name']] = [];
            }
            $output[$constraint['table_name']][] = $constraint;
        }
        return $output;
    }

    /**
     * Group constraints by reference table name
     *
     * @param array $constraints
     * @return array
     */
    protected function groupConstraintsByReferenceTable(array $constraints)
    {
        $output = [];
        foreach ($constraints as $constraint) {
            if (!isset($output[$constraint['reference_table_name']])) {
                $output[$constraint['reference_table_name']] = [];
            }
            $output[$constraint['reference_table_name']][] = $constraint;
        }
        return $output;
    }
}
