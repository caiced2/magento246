<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Modules;

/**
 * Disabled Modules List report
 */
class DisabledModulesSection extends AbstractModuleSection
{
    /**
     * Report title
     */
    const REPORT_TITLE = 'Disabled Modules List';

    /**
     * Get disabled modules list
     *
     * @return array
     */
    protected function getModulesList()
    {
        $data = [];
        $this->loadFullModulesList();

        foreach ($this->modulesList as $moduleName => $setupVersion) {
            if (!$this->modules->isModuleEnabled($moduleName)) {
                $data[] = $this->generateModuleInfo($moduleName, $setupVersion);
            }
        }

        return $data;
    }
}
