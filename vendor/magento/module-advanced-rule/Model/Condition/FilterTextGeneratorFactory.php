<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedRule\Model\Condition;

/**
 * Class FilterTextGeneratorFactory
 */
class FilterTextGeneratorFactory
{
    /**
     * Object manager
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * Construct
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->_objectManager = $objectManager;
    }

    /**
     * Creates new instances of filter text generator
     *
     * @param string $className
     * @param array $data
     * @return \Magento\AdvancedRule\Model\Condition\FilterTextGeneratorInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function create($className, $data = [])
    {
        $filterTextGenerator = $this->_objectManager->create($className, $data);
        if (!$filterTextGenerator instanceof \Magento\AdvancedRule\Model\Condition\FilterTextGeneratorInterface) {
            throw new \InvalidArgumentException(
                sprintf(
                    "%s class doesn't implement \Magento\AdvancedRule\Model\Condition\FilterTextGeneratorInterface",
                    $className
                )
            );
        }
        return $filterTextGenerator;
    }
}
