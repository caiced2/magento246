<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AdvancedCatalog\Setup;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\Config\Data\ConfigDataFactory;
use Magento\Framework\Config\File\ConfigFilePool;
use Magento\Framework\Setup\ConfigOptionsListInterface;
use Magento\Framework\Stdlib\ArrayManager;

/**
 * Deployment configuration options to configure the "indexer" connection.
 */
class ConfigOptionsList implements ConfigOptionsListInterface
{
    /**
     * Path to the values in the deployment config
     */
    const CONFIG_PATH_DB_CONNECTION_INDEXER = 'db/connection/indexer';

    /**
     * @var array
     */
    private $inputKeyToConfigOptionMap = [
        ConfigOptionsListConstants::INPUT_KEY_DB_HOST => ConfigOptionsListConstants::KEY_HOST,
        ConfigOptionsListConstants::INPUT_KEY_DB_NAME => ConfigOptionsListConstants::KEY_NAME,
        ConfigOptionsListConstants::INPUT_KEY_DB_USER => ConfigOptionsListConstants::KEY_USER,
        ConfigOptionsListConstants::INPUT_KEY_DB_PASSWORD => ConfigOptionsListConstants::KEY_PASSWORD,
        ConfigOptionsListConstants::INPUT_KEY_DB_MODEL => ConfigOptionsListConstants::KEY_MODEL,
        ConfigOptionsListConstants::INPUT_KEY_DB_ENGINE => ConfigOptionsListConstants::KEY_ENGINE,
        ConfigOptionsListConstants::INPUT_KEY_DB_INIT_STATEMENTS => ConfigOptionsListConstants::KEY_INIT_STATEMENTS,
    ];

    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @var ConfigDataFactory
     */
    private $configDataFactory;

    /**
     * @inheritDoc
     */
    public function __construct(
        ArrayManager $arrayManager = null,
        ConfigDataFactory $configDataFactory = null
    ) {
        $this->arrayManager = $arrayManager ?? ObjectManager::getInstance()->get(ArrayManager::class);
        $this->configDataFactory = $configDataFactory ?? ObjectManager::getInstance()->get(ConfigDataFactory::class);
    }

    /**
     * @inheritDoc
     */
    public function getOptions()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function createConfig(array $options, DeploymentConfig $deploymentConfig)
    {
        $configData = $this->configDataFactory->create(ConfigFilePool::APP_ENV);

        foreach ($this->inputKeyToConfigOptionMap as $key => $value) {
            if (isset($options[$key])) {
                $configData->set(
                    self::CONFIG_PATH_DB_CONNECTION_INDEXER . '/' . $value,
                    $options[$key]
                );
            }
        }

        if ($this->validateConfigData($configData->getData())) {
            $configData->set(
                self::CONFIG_PATH_DB_CONNECTION_INDEXER . '/' . ConfigOptionsListConstants::KEY_ACTIVE,
                '1'
            );
            /** forcing non-persistent connection for temporary tables */
            $configData->set(self::CONFIG_PATH_DB_CONNECTION_INDEXER . '/persistent', null);
        }

        return [$configData];
    }

    /**
     * @inheritDoc
     */
    public function validate(array $options, DeploymentConfig $deploymentConfig)
    {
        return [];
    }

    /**
     * Checks the difference between configuration data and input key mapper.
     *
     * @param array $data
     * @return bool
     */
    private function validateConfigData(array $data): bool
    {
        $configData = $this->arrayManager->get(self::CONFIG_PATH_DB_CONNECTION_INDEXER, $data, []);

        return !array_diff(
            array_values($this->inputKeyToConfigOptionMap),
            array_keys($configData)
        );
    }
}
