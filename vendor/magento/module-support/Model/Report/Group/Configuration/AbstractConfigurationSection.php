<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Configuration;

use Magento\Support\Model\Report\Group\AbstractSection;

/**
 * Abstract configuration sections model
 */
abstract class AbstractConfigurationSection extends AbstractSection
{
    const FLAG_YES = 'Yes';
    const FLAG_NO = 'No';

    /**
     * Report title to be shown on layout
     *
     * @return string
     */
    abstract public function getReportTitle();

    /**
     * Casting value to flag string
     *
     * @param mixed $value
     * @return string
     */
    public function toFlag($value)
    {
        return $value ? self::FLAG_YES : self::FLAG_NO;
    }
}
