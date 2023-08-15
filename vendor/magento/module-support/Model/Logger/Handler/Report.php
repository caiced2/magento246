<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Logger\Handler;

use Magento\Framework\Logger\Handler\System;

/**
 * Log handler for reports
 */
class Report extends System
{
    /**
     * @var string
     */
    protected $fileName = '/var/log/support_report.log';
}
