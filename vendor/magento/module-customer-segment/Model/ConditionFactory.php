<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Conditions factory
 */
namespace Magento\CustomerSegment\Model;

class ConditionFactory
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->_objectManager = $objectManager;
    }

    /**
     * Create new condition object
     *
     * @param string $className
     * @param array $data
     * @throws \InvalidArgumentException
     * @return \Magento\Rule\Model\Condition\ConditionInterface
     */
    public function create($className, array $data = [])
    {
        $classNamePrefix = 'Magento\CustomerSegment\Model\Segment\Condition\\';
        if (false === strpos($className, $classNamePrefix)) {
            $className = $classNamePrefix . $className;
        }
        $condition = $this->_objectManager->create($className, $data);
        if (false == $condition instanceof \Magento\Rule\Model\Condition\AbstractCondition) {
            throw new \InvalidArgumentException(
                $className . ' doesn\'t extends \Magento\Rule\Model\Condition\AbstractCondition'
            );
        }
        return $condition;
    }
}
