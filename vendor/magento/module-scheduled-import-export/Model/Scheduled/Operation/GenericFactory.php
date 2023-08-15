<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ScheduledImportExport\Model\Scheduled\Operation;

class GenericFactory
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
     * Create array optioned object
     *
     * @param string $model
     * @param array $data
     * @throws \InvalidArgumentException
     * @return \Magento\ScheduledImportExport\Model\Scheduled\Operation\OperationInterface
     */
    public function create($model, array $data = [])
    {
        $modelInstance = $this->_objectManager->create($model, $data);
        if (false ==
            $modelInstance instanceof \Magento\ScheduledImportExport\Model\Scheduled\Operation\OperationInterface
        ) {
            throw new \InvalidArgumentException(
                $model .
                'doesn\'t implement \Magento\ScheduledImportExport\Model\Scheduled\Operation\OperationInterface'
            );
        }
        return $modelInstance;
    }
}
