<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ScheduledImportExport\Model;

class Observer
{
    /**
     * @var \Magento\ScheduledImportExport\Model\Scheduled\OperationFactory
     */
    protected $_operationFactory;

    /**
     * @param \Magento\ScheduledImportExport\Model\Scheduled\OperationFactory $operationFactory
     */
    public function __construct(
        \Magento\ScheduledImportExport\Model\Scheduled\OperationFactory $operationFactory
    ) {
        $this->_operationFactory = $operationFactory;
    }

    /**
     * Run operation in crontab
     *
     * @param \Magento\Cron\Model\Schedule|\Magento\Framework\DataObject $schedule
     * @param bool $forceRun
     * @return bool
     */
    public function processScheduledOperation($schedule, $forceRun = false)
    {
        $operation = $this->_operationFactory->create()->loadByJobCode($schedule->getJobCode());

        $result = false;
        if ($operation && ($operation->getStatus() || $forceRun)) {
            $result = $operation->run();
        }

        return $result;
    }
}
