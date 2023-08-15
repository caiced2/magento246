<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Configuration;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Psr\Log\LoggerInterface;

/**
 * Environment section model
 */
class EnvironmentSection extends AbstractConfigurationSection
{
    const TAB = '    ';
    const HIDDEN_VALUE = '****';

    /**
     * Deployment configuration object
     *
     * @var DeploymentConfig
     */
    protected $deploymentConfig;

    /**
     * Field keys that must be hidden
     *
     * @var array
     */
    protected $sensitiveFields = ['username', 'password', 'key'];

    /**
     * @param LoggerInterface $logger
     * @param DeploymentConfig $deploymentConfig
     */
    public function __construct(
        LoggerInterface $logger,
        DeploymentConfig $deploymentConfig
    ) {
        $this->deploymentConfig = $deploymentConfig;
        parent::__construct($logger);
    }

    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        $deploymentConfigMap = [
            ConfigOptionsListConstants::CONFIG_PATH_BACKEND,
            ConfigOptionsListConstants::CONFIG_PATH_INSTALL,
            ConfigOptionsListConstants::CONFIG_PATH_CRYPT,
            ConfigOptionsListConstants::CONFIG_PATH_SESSION,
            ConfigOptionsListConstants::CONFIG_PATH_DB,
            ConfigOptionsListConstants::CONFIG_PATH_RESOURCE,
            ConfigOptionsListConstants::CONFIG_PATH_X_FRAME_OPT,
            ConfigOptionsListConstants::CONFIG_PATH_CACHE_TYPES,
        ];
        $data = [];

        foreach ($deploymentConfigMap as $path) {
            $data[$path] = $this->deploymentConfig->get($path, []);
        }

        $data = $this->generateRecursive($data);
        return [
            $this->getReportTitle() => [
                'headers' => [(string)__('Path'), (string)__('Value')],
                'data' => $data,
                'count' => count($data),
            ]
        ];
    }

    /**
     * Generating and formatting data
     *
     * @param array $data
     * @param array $preparedData
     * @param int $depth
     * @return array
     */
    protected function generateRecursive($data, $preparedData = [], $depth = 0)
    {
        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                if (in_array($key, $this->sensitiveFields)) {
                    $value = self::HIDDEN_VALUE;
                }

                $preparedData[] = [str_repeat(self::TAB, $depth) . $this->wrapKey($key), $value];
            }

            if (is_array($value)) {
                $preparedData[] = [str_repeat(self::TAB, $depth) . $this->wrapKey($key), ''];
                $preparedData = $this->generateRecursive($value, $preparedData, $depth + 1);
            }
        }
        return $preparedData;
    }

    /**
     * Wrapping key for output
     *
     * @param string $key
     * @return string
     */
    protected function wrapKey($key)
    {
        return '<' . $key . '>';
    }

    /**
     * {@inheritdoc}
     */
    public function getReportTitle()
    {
        return (string)__('Data from app/etc/env.php');
    }
}
