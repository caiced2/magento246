<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Modules;

/**
 * All Modules List report
 */
class AllModulesSection extends AbstractModuleSection
{
    /**
     * Report title
     */
    const REPORT_TITLE = 'All Modules List';

    /**
     * Get all modules list
     *
     * @return array
     */
    protected function getModulesList()
    {
        $data = [];
        $this->loadFullModulesList();

        foreach ($this->modulesList as $moduleName => $setupVersion) {
            $data[] = $this->generateModuleInfo($moduleName, $setupVersion);
        }

        return $data;
    }
}
