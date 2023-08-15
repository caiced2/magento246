<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Logs;

/**
 * Abstract Log Files section of Logs report group
 */
abstract class AbstractLogsSection extends \Magento\Support\Model\Report\Group\AbstractSection
{
    /**
     * @var \Magento\Support\Model\Report\Group\Logs\LogFilesData
     */
    protected $logFilesData;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Support\Model\Report\Group\Logs\LogFilesData $logFilesData
     * @param array $data
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Support\Model\Report\Group\Logs\LogFilesData $logFilesData,
        array $data = []
    ) {
        $this->logFilesData = $logFilesData;
        parent::__construct($logger, $data);
    }
}
