<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\DeferredTotalCalculating\Setup;

use Magento\Framework\Config\Data\ConfigData;
use Magento\Framework\Config\File\ConfigFilePool;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Setup\Option\SelectConfigOption;
use Magento\Framework\Setup\ConfigOptionsListInterface;

class ConfigOptionsList implements ConfigOptionsListInterface
{
    /**
     * Input key for the options
     */
    private const INPUT_KEY_DEFERRED_TOTAL_CALCULATING_FRONTNAME = 'deferred-total-calculating';

    /**
     * Path to the values in the deployment config
     */
    public const CONFIG_PATH_DEFERRED_TOTAL_CALCULATING_FRONTNAME = 'checkout/deferred_total_calculating';

    /**
     * Default value
     */
    private const DEFAULT_DEFERRED_TOTAL_CALCULATING = 0;

    /**
     * The available configuration values
     *
     * @var array
     */
    private $selectOptions = [0, 1];

    /**
     * Config variations for deferred total option.
     *
     * @return SelectConfigOption[]
     */
    public function getOptions()
    {
        return [
            new SelectConfigOption(
                self::INPUT_KEY_DEFERRED_TOTAL_CALCULATING_FRONTNAME,
                SelectConfigOption::FRONTEND_WIZARD_SELECT,
                $this->selectOptions,
                self::CONFIG_PATH_DEFERRED_TOTAL_CALCULATING_FRONTNAME,
                'Enable deferred total calculating? 1 - Yes, 0 - No',
                self::DEFAULT_DEFERRED_TOTAL_CALCULATING
            ),
        ];
    }

    /**
     * Config creator for deferred total option.
     *
     * @param array $data
     * @param DeploymentConfig $deploymentConfig
     * @return ConfigData[]
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function createConfig(array $data, DeploymentConfig $deploymentConfig)
    {
        $configData = new ConfigData(ConfigFilePool::APP_ENV);

        if (!$this->isDataEmpty($data, self::INPUT_KEY_DEFERRED_TOTAL_CALCULATING_FRONTNAME)) {
            $configData->set(
                self::CONFIG_PATH_DEFERRED_TOTAL_CALCULATING_FRONTNAME,
                (int)$data[self::INPUT_KEY_DEFERRED_TOTAL_CALCULATING_FRONTNAME]
            );
        }

        return [$configData];
    }

    /**
     * Validator for deferred total option.
     *
     * @param array $options
     * @param DeploymentConfig $deploymentConfig
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validate(array $options, DeploymentConfig $deploymentConfig)
    {
        $errors = [];

        if (
            !$this->isDataEmpty($options, self::INPUT_KEY_DEFERRED_TOTAL_CALCULATING_FRONTNAME) &&
            !in_array(
                $options[self::INPUT_KEY_DEFERRED_TOTAL_CALCULATING_FRONTNAME],
                $this->selectOptions
            )
        ) {
            $errors[] = 'You can use only 1 or 0 for ' . self::INPUT_KEY_DEFERRED_TOTAL_CALCULATING_FRONTNAME .
                ' option';
        }

        return $errors;
    }

    /**
     * Check if data ($data) with key ($key) is empty
     *
     * @param array $data
     * @param string $key
     * @return bool
     */
    private function isDataEmpty(array $data, $key)
    {
        if (isset($data[$key]) && $data[$key] !== '') {
            return false;
        }

        return true;
    }
}
