<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Modules;

/**
 * Custom Modules List report
 */
class CustomModulesSection extends AbstractModuleSection
{
    /**
     * Report title
     */
    const REPORT_TITLE = 'Custom Modules List';

    /**
     * Get custom modules list
     *
     * @return array
     */
    protected function getModulesList()
    {
        $data = [];
        $this->loadFullModulesList();

        foreach ($this->modulesList as $moduleName => $setupVersion) {
            if ($this->modules->isCustomModule($moduleName)) {
                $data[] = $this->generateModuleInfo($moduleName, $setupVersion);
            }
        }

        return $data;
    }
}
