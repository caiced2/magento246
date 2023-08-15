<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ScheduledImportExport\Model\Scheduled\Operation;

use Magento\ScheduledImportExport\Model\Scheduled\Operation;

/**
 * Scheduled operation interface
 *
 * @api
 */
interface OperationInterface
{
    /**
     * Run operation through cron
     *
     * @param Operation $operation
     * @return bool
     */
    public function runSchedule(Operation $operation);

    /**
     * Initialize operation model from scheduled operation
     *
     * @param Operation $operation
     * @return object operation instance
     */
    public function initialize(Operation $operation);

    /**
     * Log debug data to file.
     *
     * @param mixed $debugData
     * @return object
     */
    public function addLogComment($debugData);

    /**
     * Return human readable debug trace.
     *
     * @return array
     */
    public function getFormatedLogTrace();
}
