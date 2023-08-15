<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Logs;

/**
 * Log Files section of Logs report group
 */
class LogFilesSection extends AbstractLogsSection
{
    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        $data = $this->logFilesData->getLogFilesData()[LogFilesData::LOG_FILES];

        return [
            (string)__('Log Files') => [
                'headers' => [__('File'), __('Size'), __('Log Entries'), __('Last Update')],
                'data' => $data
            ]
        ];
    }
}
