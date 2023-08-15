<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Logs;

/**
 * Top Exception Messages section of Logs report group
 */
class TopExceptionMessagesSection extends AbstractLogsSection
{
    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        $data = $this->logFilesData->getLogFilesData()[LogFilesData::EXCEPTION_MESSAGES];

        return [
            (string)__('Top Exception Messages') => [
                'headers' => [__('Count'), __('Message'), __('Stack Trace'), __('Last Occurrence')],
                'data' => $data
            ]
        ];
    }
}
