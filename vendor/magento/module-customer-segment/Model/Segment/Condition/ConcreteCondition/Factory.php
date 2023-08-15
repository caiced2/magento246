<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Model\Segment\Condition\ConcreteCondition;

/**
 * For the given Customer Segment rule condition, creates the class that will be used to supply the contents used to
 * determine which cart sales rules should be further evaluated.
 */
class Factory
{
    const CONCRETE_CONDITION_CLASS = \Magento\CustomerSegment\Model\Segment\Condition\ConcreteCondition\Segment::class;

    /**
     * Object Manager
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Construct
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param \Magento\CustomerSegment\Model\Segment\Condition\Segment $segmentCondition
     * @return \Magento\AdvancedRule\Model\Condition\FilterableConditionInterface
     */
    public function create($segmentCondition)
    {
        $operator = $segmentCondition->getOperator();
        $values = $segmentCondition->getValue();  // can be a list of segment ids separated by a comma: '1, 2'

        $concreteCondition = $this->objectManager->create(
            self::CONCRETE_CONDITION_CLASS,
            [
                'data' => [
                    'operator' => $operator,
                    'values' => $values,
                ]
            ]
        );
        return $concreteCondition;
    }
}
