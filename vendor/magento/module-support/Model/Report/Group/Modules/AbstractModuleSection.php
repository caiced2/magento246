<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Modules;

/**
 * General class for modules report
 */
abstract class AbstractModuleSection extends \Magento\Support\Model\Report\Group\AbstractSection
{
    /**
     * Report title
     */
    const REPORT_TITLE = 'Report title';

    /**
     * @var \Magento\Framework\Module\ModuleResource
     */
    protected $resource;

    /**
     * @var Modules
     */
    protected $modules;

    /**
     * @var array
     */
    protected $modulesList;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param Modules $modules
     * @param \Magento\Framework\Module\ModuleResource $resource
     * @param array $data
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        Modules $modules,
        \Magento\Framework\Module\ModuleResource $resource,
        array $data = []
    ) {
        $this->modules = $modules;
        $this->resource = $resource;
        parent::__construct($logger, $data);
    }

    /**
     * Generate Modules report
     *
     * @return array
     */
    public function generate()
    {
        $data = $this->getModulesList();

        return [
            static::REPORT_TITLE => [
                'headers' => [
                    'Module', 'Code Pool', 'Config Version', 'DB Version', 'DB Data Version', 'Output', 'Enabled'
                ],
                'data' => $data
            ]
        ];
    }

    /**
     * Load full modules list
     *
     * @return void
     */
    protected function loadFullModulesList()
    {
        if ($this->modulesList === null) {
            $this->modulesList = $this->modules->getFullModulesList();
        }
    }

    /**
     * Generate information about a module
     *
     * @param string $moduleName
     * @param string $setupVersion
     * @return array
     */
    protected function generateModuleInfo($moduleName, $setupVersion)
    {
        $schemaVersion = $this->resource->getDbVersion($moduleName);
        $dataVersion = $this->resource->getDataVersion($moduleName);

        return [
            $moduleName . "\n" . '{' . $this->modules->getModulePath($moduleName) . '}',
            $this->modules->isCustomModule($moduleName) ? 'custom' : 'core',
            $setupVersion,
            $schemaVersion ?: 'n/a',
            $dataVersion ?: 'n/a',
            implode("\n", $this->modules->getOutputFlagInfo($moduleName)),
            $this->modules->isModuleEnabled($moduleName) ? 'Yes' : 'No'
        ];
    }

    /**
     * Get modules list
     *
     * @return array
     */
    abstract protected function getModulesList();
}
