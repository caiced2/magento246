<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\ResourceModel\Report\Environment;

/**
 * Class OsEnvironment
 */
class OsEnvironment extends AbstractEnvironment
{
    /**
     * Label for report
     */
    const OS_INFORMATION = 'OS Information';

    /**#@+
     * Keys of array phpinfo
     */
    const KEY_GENERAL = 'General';
    const KEY_SYSTEM = 'System';
    /**#@-*/

    /**
     * Get information about OS
     *
     * @return array
     */
    public function getOsInformation()
    {
        $data = [];

        if ($this->checkPhpInfo() && isset($this->phpInfoCollection[self::KEY_GENERAL][self::KEY_SYSTEM])) {
            $data = [self::OS_INFORMATION, $this->phpInfoCollection[self::KEY_GENERAL][self::KEY_SYSTEM]];
        }

        return $data;
    }
}
