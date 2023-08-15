<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Framework\ForeignKey;

/**
 * Interface \Magento\Framework\ForeignKey\ConstraintInterface
 *
 * @api
 * @deprecated split database solution is deprecated and will be removed
 */
interface ConstraintInterface
{
    /**
     * Get constraint write connection object
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function getConnection();

    /**
     * Get reference constraint write connection object
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function getReferenceConnection();

    /**
     * Get constraint strategy type
     *
     * @return string
     */
    public function getStrategy();

    /**
     * Get entity table name
     *
     * @return string
     */
    public function getTableName();

    /**
     * Get entity affected field name
     *
     * @return string
     */
    public function getFieldName();

    /**
     * Get reference table name
     *
     * @return string
     */
    public function getReferenceTableName();

    /**
     * Get reference affected field name
     *
     * @return string
     */
    public function getReferenceField();

    /**
     * Return the constraints list
     *
     * @return ConstraintInterface[]
     */
    public function getSubConstraints();

    /**
     *  Return the list of table field names that are affected by child constraints
     *
     * @return string[]
     */
    public function getSubConstraintsAffectedFields();

    /**
     * Get constraint condition
     *
     * @param array $values
     * @return string
     */
    public function getCondition(array $values);
}
