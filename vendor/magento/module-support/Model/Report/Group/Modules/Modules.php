<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Modules;

use Magento\Store\Model\ScopeInterface;

/**
 * Class retrieves information about modules
 */
class Modules
{
    /**
     * @var \Magento\Framework\Module\FullModuleList
     */
    protected $fullModuleList;

    /**
     * @var \Magento\Framework\Module\ModuleList
     */
    protected $enabledModuleList;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $config;

    /**
     * @var \Magento\Store\Model\StoreManager
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Module\Dir
     */
    protected $moduleDir;

    /**
     * @var array
     * @deprecated 101.0.0
     */
    protected $modulesOutputData = [];

    /**
     * @var array
     */
    protected $allModules;

    /**
     * @var array
     */
    protected $enabledModules;

    /**
     * @param \Magento\Framework\Module\FullModuleList $fullModuleList
     * @param \Magento\Framework\Module\ModuleList $moduleList
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Store\Model\StoreManager $storeManager
     * @param \Magento\Framework\Module\Dir $moduleDir
     */
    public function __construct(
        \Magento\Framework\Module\FullModuleList $fullModuleList,
        \Magento\Framework\Module\ModuleList $moduleList,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Store\Model\StoreManager $storeManager,
        \Magento\Framework\Module\Dir $moduleDir
    ) {
        $this->fullModuleList = $fullModuleList;
        $this->enabledModuleList = $moduleList;
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->moduleDir = $moduleDir;
    }

    /**
     * Check if specified module is enabled
     *
     * @param string $moduleName
     * @return bool
     */
    public function isModuleEnabled($moduleName)
    {
        if (null === $this->enabledModules) {
            $this->loadEnabledModules();
        }

        return in_array($moduleName, $this->enabledModules);
    }

    /**
     * Load enabled modules
     *
     * @return void
     */
    protected function loadEnabledModules()
    {
        $this->enabledModules = $this->enabledModuleList->getNames();
    }

    /**
     * Get all modules list
     *
     * @return array
     */
    public function getFullModulesList()
    {
        if (null === $this->allModules) {
            $this->loadAllModules();
        }

        return $this->allModules;
    }

    /**
     * Load all modules
     *
     * @return void
     */
    protected function loadAllModules()
    {
        $this->allModules = [];
        $modules = $this->fullModuleList->getAll();

        foreach ($modules as $moduleName => $moduleInfo) {
            $this->allModules[$moduleName] = $moduleInfo['setup_version'];
        }
    }

    /**
     * Get module path
     *
     * @param string $moduleName
     * @return string
     */
    public function getModulePath($moduleName)
    {
        //return 'app/code/'. implode('/', explode('_', $moduleName)) . '/';
        return $this->moduleDir->getDir($moduleName);
    }

    /**
     * Check if specified module is custom
     *
     * @param string $moduleName
     * @return bool
     */
    public function isCustomModule($moduleName)
    {
        return substr($moduleName, 0, 8) != 'Magento_';
    }

    /**
     * Check configuration flag Disable Output
     *
     * @param string $moduleName
     * @param string $scopeType
     * @param string|null $scopeCode
     * @return string
     * @deprecated 101.0.0
     */
    protected function checkOutputEnabled(
        $moduleName,
        $scopeType = \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
        $scopeCode = null
    ) {
        $path = 'advanced/modules_disable_output/' . $moduleName;
        return $this->config->isSetFlag($path, $scopeType, $scopeCode) ? 'Disable' : 'Enable';
    }

    /**
     * Get information about configuration flag from section Disable Modules Output
     *
     * @param string $moduleName
     * @return array
     * @deprecated 101.0.0 Magento does not support disabling/enabling modules output from the Admin Panel since 2.2.0
     * version. Module output can still be enabled/disabled in configuration files. However, this functionality should
     * not be used in future development. Module design should explicitly state dependencies to avoid requiring output
     * disabling. This functionality will temporarily be kept in Magento core, as there are unresolved modularity
     * issues that will be addressed in future releases.
     */
    public function getOutputFlagInfo($moduleName)
    {
        if (!array_key_exists($moduleName, $this->modulesOutputData)) {
            $this->generateOutputFlagInfo($moduleName);
        }

        return $this->modulesOutputData[$moduleName];
    }

    /**
     * Generate information about configuration flag from section Disable Modules Output
     *
     * @param string $moduleName
     * @return void
     * @deprecated 101.0.0
     */
    protected function generateOutputFlagInfo($moduleName)
    {
        $this->modulesOutputData[$moduleName][] = '{[Default Config] = '
            . $this->checkOutputEnabled($moduleName) . '}';

        $websites = $this->storeManager->getWebsites();
        /** @var \Magento\Store\Model\Website $website */
        foreach ($websites as $website) {
            $scopePath = '[' . $website->getName() . '] = ';
            $flag = $this->checkOutputEnabled(
                $moduleName,
                ScopeInterface::SCOPE_WEBSITES,
                $website->getId()
            );
            $this->modulesOutputData[$moduleName][] = '{' . $scopePath . $flag . '}';

            $stores = $website->getStores();
            /** @var \Magento\Store\Model\Store $store */
            foreach ($stores as $store) {
                $scopePath = '[' . $website->getName() . '] => [' . $store->getName() . '] = ';
                $flag = $this->checkOutputEnabled(
                    $moduleName,
                    ScopeInterface::SCOPE_STORES,
                    $store->getId()
                );
                $this->modulesOutputData[$moduleName][] = '{' . $scopePath . $flag . '}';
            }
        }
    }
}
