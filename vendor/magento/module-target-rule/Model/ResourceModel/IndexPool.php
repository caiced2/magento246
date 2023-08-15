<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Model\ResourceModel;

use Magento\Framework\ObjectManagerInterface;
use Magento\TargetRule\Model\ResourceModel\Index\IndexInterface;

/**
 * TargetRule Product Index Pool
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class IndexPool
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var array
     */
    private $types;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param array $types
     */
    public function __construct(ObjectManagerInterface $objectManager, array $types = [])
    {
        $this->objectManager = $objectManager;
        $this->types = $types;
    }

    /**
     * Get index shared object
     *
     * @param string $type
     * @throws \LogicException
     * @return IndexInterface
     */
    public function get($type)
    {
        if (!isset($this->types[$type])) {
            throw new \LogicException(
                'The "' . $type . '" Catalog Product List Type needs to be defined. Verify the type and try again.'
            );
        }

        /** @var IndexInterface $index */
        $index = $this->objectManager->get($this->types[$type]);

        if (!$index instanceof IndexInterface) {
            throw new \LogicException(
                $this->types[$type] . ' doesn\'t implement \Magento\TargetRule\Model\ResourceModel\Index\IndexInterface'
            );
        }

        return $index;
    }
}
