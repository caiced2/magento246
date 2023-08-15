<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Framework\ForeignKey;

use Magento\Framework\ForeignKey\Config\Data;

/**
 * @deprecated split database solution is deprecated and will be removed
 */
class Config implements ConfigInterface
{
    /**
     * @var \Magento\Framework\ForeignKey\ConstraintFactory
     */
    protected $constraintFactory;

    /**
     * @var Data
     */
    private $dataContainer;

    /**
     * @param Data $dataContainer
     * @param ConstraintFactory $constraintFactory
     */
    public function __construct(
        Data $dataContainer,
        \Magento\Framework\ForeignKey\ConstraintFactory $constraintFactory
    ) {
        $this->dataContainer = $dataContainer;
        $this->constraintFactory = $constraintFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getConstraintsByReferenceTableName($referenceTableName)
    {
        $constraints = [];
        $constraintConfig = $this->dataContainer->get('constraints_by_reference_table');
        if (isset($constraintConfig[$referenceTableName])) {
            foreach ($constraintConfig[$referenceTableName] as $constraintData) {
                $constraints[] = $this->constraintFactory->get($constraintData, $constraintConfig);
            }
        }
        return $constraints;
    }

    /**
     * {@inheritdoc}
     */
    public function getConstraintsByTableName($tableName)
    {
        $constraints = [];
        $constraintConfig = $this->dataContainer->get('constraints_by_reference_table');
        $constraintsByTable = $this->dataContainer->get('constraints_by_table');
        if (isset($constraintsByTable[$tableName])) {
            foreach ($constraintsByTable[$tableName] as $constraintData) {
                $constraints[] = $this->constraintFactory->get($constraintData, $constraintConfig);
            }
        }
        return $constraints;
    }
}
