<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\ResourceModel\Report\Environment;

/**
 * Class PhpEnvironment
 */
class PhpEnvironment extends AbstractEnvironment
{
    /**#@+
     * Labels for report
     */
    const PHP_VERSION = 'PHP Version';
    const PHP_LOADED_INI = 'PHP Loaded Config File';
    const PHP_ADDITIONAL_INI = 'PHP Additional .ini files parsed';
    const PHP_CONFIGURATION = 'PHP Configuration';
    const PHP_LOADED_MODULES = 'PHP Loaded Modules';
    /**#@-*/

    /**#@+
     * Keys of array phpinfo
     */
    const KEY_GENERAL = 'General';
    const KEY_LOADED_INI = 'Loaded Configuration File';
    const KEY_ADDITIONAL_INI = 'Additional .ini files parsed';
    const KEY_CORE = 'Core';
    const KEY_PHP_CORE = 'PHP Core';
    /**#@-*/

    /**
     * Get php version
     *
     * @return array
     */
    public function getVersion()
    {
        return [self::PHP_VERSION, PHP_VERSION];
    }

    /**
     * Get information about loaded configuration file
     *
     * @return array
     */
    public function getLoadedConfFile()
    {
        $data = [];

        if ($this->checkPhpInfo() && isset($this->phpInfoCollection[self::KEY_GENERAL][self::KEY_LOADED_INI])) {
            $data = [self::PHP_LOADED_INI, $this->phpInfoCollection[self::KEY_GENERAL][self::KEY_LOADED_INI]];
        }

        return $data;
    }

    /**
     * Get information about additional loaded configuration files
     *
     * @return array
     */
    public function getAdditionalIniFile()
    {
        $data = [];

        if ($this->checkPhpInfo()
            && isset($this->phpInfoCollection[self::KEY_GENERAL][self::KEY_ADDITIONAL_INI])
        ) {
            $data = [
                self::PHP_ADDITIONAL_INI,
                $this->phpInfoCollection[self::KEY_GENERAL][self::KEY_ADDITIONAL_INI]
            ];
        }

        return $data;
    }

    /**
     * Get information about configuration settings
     *
     * @return array
     */
    public function getImportantConfigSettings()
    {
        $importantConfig = [
            'memory_limit',
            'register_globals',
            'safe_mode',
            'upload_max_filesize',
            'post_max_size',
            'allow_url_fopen',
            'default_charset',
            'error_log',
            'error_reporting',
            'extension_dir',
            'file_uploads',
            'upload_tmp_dir',
            'log_errors',
            'magic_quotes_gpc',
            'max_execution_time',
            'max_file_uploads',
            'max_input_time',
            'max_input_vars',
        ];

        if ($this->checkPhpInfo()) {
            $data = $this->getConfigurationFromPhpInfo($importantConfig);
        } else {
            $data = $this->getConfigurationFromIniFile($importantConfig);
        }

        return $data;
    }

    /**
     * Get configuration information by means of phpinfo() function
     *
     * @param array $importantConfig
     * @return array
     */
    protected function getConfigurationFromPhpInfo(array $importantConfig)
    {
        $data = [];
        $coreEntry = isset($this->phpInfoCollection[self::KEY_CORE])
            ? $this->phpInfoCollection[self::KEY_CORE]
            : (isset($this->phpInfoCollection[self::KEY_PHP_CORE])
                    ? $this->phpInfoCollection[self::KEY_PHP_CORE] : null
            );
        if ($coreEntry !== null) {
            $configuration = '';
            foreach ($coreEntry as $key => $info) {
                if (in_array($key, $importantConfig)) {
                    $configuration .= $key . ' => Local = "' . $info['local']
                        . '", Master = "' . $info['master'] . '"' . "\n";
                }
            }
            $data = [self::PHP_CONFIGURATION, trim($configuration)];
        }

        return $data;
    }

    /**
     * Get configuration by means of ini_get_all() function
     *
     * @param array $importantConfig
     * @return array
     */
    protected function getConfigurationFromIniFile(array $importantConfig)
    {
        $data = [];
        $iniValues = $this->phpInfo->iniGetAll();
        if (!empty($iniValues) && is_array($iniValues)) {
            $configuration = '';
            foreach ($iniValues as $key => $info) {
                if (in_array($key, $importantConfig)) {
                    $configuration .= $key . ' => Local = "' . $info['local_value']
                        . '", Master = "' . $info['global_value']. '"' . "\n";
                }
            }
            $data = [self::PHP_CONFIGURATION, trim($configuration)];
        }

        return $data;
    }

    /**
     * Get information about loaded modules of php
     *
     * @return array
     */
    public function getLoadedModules()
    {
        $defaultPhpInfoCategories = [
            'General',
            'apache2handler',
            'Apache Environment',
            'PHP Core',
            'Core',
            'HTTP Headers Information',
            'Environment',
            'PHP Variables'
        ];
        if ($this->checkPhpInfo()) {
            $data = $this->getLoadedModulesFromPhpInfo($defaultPhpInfoCategories);
        } else {
            $data = $this->getLoadedModulesFromFunction();
        }

        return $data;
    }

    /**
     * Get loaded modules by means of get_loaded_extensions() function
     *
     * @return array
     */
    protected function getLoadedModulesFromFunction()
    {
        $data = [self::PHP_LOADED_MODULES, 'n/a'];
        $modules = $this->phpInfo->getLoadedExtensions();
        if (is_array($modules) && !empty($modules)) {
            $modules = array_map('strtolower', $modules);
            sort($modules);
            $modulesInfo = '';
            foreach ($modules as $module) {
                $modulesInfo .= $module .
                    ($this->phpInfo->getModuleVersion($module)
                        ? ' [' . $this->phpInfo->getModuleVersion($module) . ']'
                        : ''
                    ) . "\n";
            }
            $data = [self::PHP_LOADED_MODULES, trim($modulesInfo)];
        }

        return $data;
    }

    /**
     * Get loaded mdules by means of phpinfo() function
     *
     * @param array $defaultPhpInfoCategories
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function getLoadedModulesFromPhpInfo(array $defaultPhpInfoCategories)
    {
        $modulesInfo = '';
        foreach ($this->phpInfoCollection as $module => $info) {
            if (!in_array($module, $defaultPhpInfoCategories)) {
                // Collect additional information for required modules by Magento
                switch ($module) {
                    case 'curl':
                        if (isset($info['cURL Information'])) {
                            $module .= ' [' . $info['cURL Information'] . ']';
                        }
                        break;
                    case 'dom':
                        if (isset($info['libxml Version'])) {
                            $module .= ' [' . $info['libxml Version'] . ']';
                        }
                        break;
                    case 'gd':
                        if (isset($info['GD Version'])) {
                            $module .= ' [' . $info['GD Version'] . ']';
                        }
                        break;
                    case 'iconv':
                        if (isset($info['iconv library version'])) {
                            $module .= ' [' . $info['iconv library version'] . ']';
                        }
                        break;
                    case 'mcrypt':
                        if (isset($info['Version'])) {
                            $module .= ' [' . $info['Version'] . ']';
                        }
                        break;
                    case 'pdo_mysql':
                        if (isset($info['Client API version'])) {
                            $module .= ' [' . $info['Client API version'] . ']';
                        } elseif (isset($info['PDO Driver for MySQL, client library version'])) {
                            $module .= ' [' . $info['PDO Driver for MySQL, client library version'] . ']';
                        }
                        break;
                    case 'SimpleXML':
                        if (isset($info['Revision'])) {
                            $module .= ' [' . $info['Revision'] . ']';
                        }
                        break;
                    case 'soap':
                    case 'hash':
                    default:
                        $module .= $this->phpInfo->getModuleVersion($module)
                            ? ' [' . $this->phpInfo->getModuleVersion($module) . ']' : '';
                        break;
                }
                $modulesInfo .= $module . "\n";
            }
        }

        return [self::PHP_LOADED_MODULES, trim($modulesInfo)];
    }
}
