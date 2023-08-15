<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Custom handlers for models logging
 */
namespace Magento\Logging\Model\Handler;

use Magento\Framework\Model\AbstractModel;
use Magento\Logging\Model\Event\Changes;
use Magento\Logging\Model\Processor;

/**
 * Class Models
 */
class Models
{
    /**
     * Factory for event changes model
     *
     * @var \Magento\Logging\Model\Event\ChangesFactory
     */
    protected $_eventChangesFactory;

    /**
     * Construct
     *
     * @param \Magento\Logging\Model\Event\ChangesFactory $eventChangesFactory
     */
    public function __construct(\Magento\Logging\Model\Event\ChangesFactory $eventChangesFactory)
    {
        $this->_eventChangesFactory = $eventChangesFactory;
    }

    /**
     * SaveAfter handler
     *
     * @param AbstractModel $model
     * @param Processor $processor
     * @return bool|Changes false if model wasn't modified
     */
    public function modelSaveAfter($model, $processor)
    {
        $processor->collectId($model);
        /** @var Changes $changes */
        $changes = $this->_eventChangesFactory->create();
        $changes->setOriginalData($model->getOrigData())->setResultData($model->getData());
        return $changes;
    }

    /**
     * Delete after handler
     *
     * @param AbstractModel $model
     * @param Processor $processor
     * @return Changes
     */
    public function modelDeleteAfter($model, $processor)
    {
        $processor->collectId($model);
        /** @var Changes $changes */
        $changes = $this->_eventChangesFactory->create();
        $changes->setOriginalData($model->getOrigData())->setResultData(null);
        return $changes;
    }

    /**
     * MassUpdate after handler
     *
     * @param AbstractModel $model
     * @param Processor $processor
     * @return bool|Changes
     */
    public function modelMassUpdateAfter($model, $processor)
    {
        return $this->modelSaveAfter($model, $processor);
    }

    /**
     * MassDelete after handler
     *
     * @param AbstractModel $model
     * @param Processor $processor
     * @return bool|Changes
     */
    public function modelMassDeleteAfter($model, $processor)
    {
        return $this->modelSaveAfter($model, $processor);
    }

    /**
     * Load after handler
     *
     * @param AbstractModel $model
     * @param Processor $processor
     * @return bool
     */
    public function modelViewAfter($model, $processor)
    {
        if ($model->getId()) {
            $processor->collectId($model);
        }

        return true;
    }
}
