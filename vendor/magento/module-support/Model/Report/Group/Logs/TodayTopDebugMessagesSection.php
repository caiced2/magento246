<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Logs;

/**
 * Today's Top Debug Messages section of Logs report group
 */
class TodayTopDebugMessagesSection extends AbstractLogsSection
{
    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        $data = $this->logFilesData->getLogFilesData()[LogFilesData::CURRENT_DEBUG_MESSAGES];

        return [
            (string)__('Today\'s Top Debug Messages') => [
                'headers' => [__('Count'), __('Message'), __('Last Occurrence')],
                'data' => $data
            ]
        ];
    }
}
