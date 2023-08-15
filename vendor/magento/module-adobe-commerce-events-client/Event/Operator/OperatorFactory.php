<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Operator;

/**
 * Factory for creating operator objects
 */
class OperatorFactory
{
    /**
     * @var OperatorInterface[]
     */
    private array $operators;

    /**
     * @param array $operators
     */
    public function __construct(array $operators)
    {
        $this->operators = $operators;
    }

    /**
     * Creates operator object based on the given name.
     *
     * Throws an exception in the case when an operator with a given name is not registered.
     *
     * @param string $operatorName
     * @return OperatorInterface
     * @throws OperatorException
     */
    public function create(string $operatorName): OperatorInterface
    {
        if (!isset($this->operators[$operatorName])) {
            throw new OperatorException(__('Operator %1 is not registered', $operatorName));
        }

        return $this->operators[$operatorName];
    }

    /**
     * Returns an array of valid operator names.
     *
     * @return array
     */
    public function getOperatorNames(): array
    {
        return array_keys($this->operators);
    }
}
