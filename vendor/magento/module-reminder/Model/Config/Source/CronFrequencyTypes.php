<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Reminder\Model\Config\Source;

class CronFrequencyTypes
{
    const CRON_MINUTELY = 'I';

    const CRON_HOURLY = 'H';

    const CRON_DAILY = 'D';

    /**
     * Return array of cron frequency types
     *
     * @return array
     */
    public function getCronFrequencyTypes()
    {
        return [
            self::CRON_MINUTELY => __('Minute Intervals'),
            self::CRON_HOURLY => __('Hourly'),
            self::CRON_DAILY => __('Daily')
        ];
    }
}
