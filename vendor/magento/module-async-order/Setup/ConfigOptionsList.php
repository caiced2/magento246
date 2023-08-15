<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AsyncOrder\Setup;

use Magento\Framework\Config\Data\ConfigData;
use Magento\Framework\Config\File\ConfigFilePool;
use Magento\Framework\Setup\ConfigOptionsListInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Setup\Option\SelectConfigOption;

/**
 * Provides setup option for async order.
 */
class ConfigOptionsList implements ConfigOptionsListInterface
{
    /**
     * Input key for the options
     */
    private const INPUT_KEY_ASYNC_ORDER_FRONTNAME = 'checkout-async';

    /**
     * Path to the values in the deployment config
     */
    private const CONFIG_PATH_ASYNC_ORDER_FRONTNAME = 'checkout/async';

    /**
     * Default value
     */
    private const DEFAULT_ASYNC_ORDER = 0;

    /**
     * The available configuration values
     *
     * @var array
     */
    private $selectOptions = [0, 1];

    /**
     * @inheritdoc
     */
    public function getOptions()
    {
        return [
            new SelectConfigOption(
                self::INPUT_KEY_ASYNC_ORDER_FRONTNAME,
                SelectConfigOption::FRONTEND_WIZARD_SELECT,
                $this->selectOptions,
                self::CONFIG_PATH_ASYNC_ORDER_FRONTNAME,
                'Enable async order processing? 1 - Yes, 0 - No',
                self::DEFAULT_ASYNC_ORDER
            ),
        ];
    }

    /**
     * @inheritdoc
     */
    public function createConfig(array $data, DeploymentConfig $deploymentConfig)
    {
        $configData = new ConfigData(ConfigFilePool::APP_ENV);

        if (!$this->isDataEmpty($data, self::INPUT_KEY_ASYNC_ORDER_FRONTNAME)) {
            $configData->set(
                self::CONFIG_PATH_ASYNC_ORDER_FRONTNAME,
                (int)$data[self::INPUT_KEY_ASYNC_ORDER_FRONTNAME]
            );
        }

        return [$configData];
    }

    /**
     * @inheritdoc
     */
    public function validate(array $options, DeploymentConfig $deploymentConfig)
    {
        $errors = [];

        if (!$this->isDataEmpty($options, self::INPUT_KEY_ASYNC_ORDER_FRONTNAME) &&
            !in_array(
                $options[self::INPUT_KEY_ASYNC_ORDER_FRONTNAME],
                $this->selectOptions
            )
        ) {
            $errors[] = 'You can use only 1 or 0 for ' . self::INPUT_KEY_ASYNC_ORDER_FRONTNAME . ' option';
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
