<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VisualMerchandiser\Model\Sorting;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class Factory
 *
 * @api
 */
class Factory
{
    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->_objectManager = $objectManager;
    }

    /**
     * @param string $className
     * @param array $data
     * @return SortInterface
     * @throws LocalizedException
     */
    public function create($className, array $data = [])
    {
        $instance = $this->_objectManager->create('\Magento\VisualMerchandiser\Model\Sorting\\'.$className, $data);

        if (!$instance instanceof SortInterface) {
            throw new LocalizedException(
                __('%1 doesn\'t implement SortInterface', $className)
            );
        }
        return $instance;
    }
}
