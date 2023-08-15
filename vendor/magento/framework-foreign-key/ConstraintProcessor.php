<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Framework\ForeignKey;

use Magento\Framework\Model\ResourceModel\Db\TransactionManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

/**
 * @deprecated split database solution is deprecated and will be removed
 */
class ConstraintProcessor
{
    /**
     * @var StrategyInterface[]
     */
    protected $strategies = [];

    /**
     * @param StrategyInterface[] $strategies
     */
    public function __construct(array $strategies)
    {
        $this->strategies = $strategies;
    }

    /**
     * Process constraints
     *
     * @param TransactionManagerInterface $transactionManager
     * @param ConstraintInterface $constraint
     * @param array $involvedData
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return void
     */
    public function resolve(
        TransactionManagerInterface $transactionManager,
        ConstraintInterface $constraint,
        $involvedData
    ) {
        $strategyCode = $constraint->getStrategy();
        if (!isset($this->strategies[$strategyCode])) {
            throw new LocalizedException(
                new Phrase('The "%1" strategy code is unknown. Verify the code and try again.', [$strategyCode])
            );
        }
        $strategy = $this->strategies[$strategyCode];
        $constraintConnection = $constraint->getConnection();
        $constraintTableName = $constraint->getTableName();
        $values = $this->getInvolvedData($involvedData, $constraint->getReferenceField());
        $constraintCondition = $constraint->getCondition($values);
        $subConstraints = $constraint->getSubConstraints();

        $constraintConnection = $transactionManager->start($constraintConnection);
        if (!empty($subConstraints)) {
            $lockedData = $strategy->lockAffectedData(
                $constraintConnection,
                $constraintTableName,
                $constraintCondition,
                $constraint->getSubConstraintsAffectedFields()
            );

            if (empty($lockedData)) {
                return;
            }

            foreach ($subConstraints as $item) {
                $this->resolve($transactionManager, $item, $lockedData);
            }
        }
        $strategy->process($constraintConnection, $constraint, $constraintCondition);
    }

    /**
     * Validate that data that is about to be saved does not violates given constraint
     *
     * @param ConstraintInterface $constraint
     * @param array $data
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return void
     */
    public function validate(ConstraintInterface $constraint, array $data)
    {
        $referenceFieldName = $constraint->getReferenceField();
        $value = isset($data[$constraint->getFieldName()]) ? $data[$constraint->getFieldName()] : null;
        if ($value == null) {
            // skip validation for NULL values
            return;
        }
        $connection = $constraint->getReferenceConnection();
        $select = $connection->select()
            ->from($constraint->getReferenceTableName())
            ->columns([$referenceFieldName])
            ->where($referenceFieldName . ' = ?', $value);
        $result = $connection->fetchAssoc($select);
        if (empty($result)) {
            throw new LocalizedException(
                new Phrase(
                    "The row couldn't be updated because a foreign key constraint failed. "
                    . "Verify the constraint and try again."
                )
            );
        }
    }

    /**
     * Get involved data
     *
     * @param array $involvedData
     * @param string $referenceField
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getInvolvedData(array $involvedData, $referenceField)
    {
        $output = [];
        foreach ($involvedData as $item) {
            if (isset($item[$referenceField])) {
                $output[] = $item[$referenceField];
            } else {
                throw new LocalizedException(
                    new Phrase(
                        'The "%1" field name is unknown. Verify the field name and try again.',
                        [$referenceField]
                    )
                );
            }
        }
        return $output;
    }
}
