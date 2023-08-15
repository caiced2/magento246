<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AsyncOrder\Model;

use Magento\Framework\App\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\RuntimeException;

/**
 * Class that overrides global config async grid indexing setting in relation to async checkout.
 */
class AsyncGlobalConfig implements ScopeConfigInterface
{
    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * @var ScopeConfigInterface
     */
    private $globalConfig;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param DeploymentConfig $deploymentConfig
     * @param ScopeConfigInterface $globalConfig
     * @param Config $config
     */
    public function __construct(
        DeploymentConfig $deploymentConfig,
        ScopeConfigInterface $globalConfig,
        Config $config
    ) {
        $this->deploymentConfig = $deploymentConfig;
        $this->globalConfig = $globalConfig;
        $this->config = $config;
    }

    /**
     * Override global config dev/grid/async_indexing (Async Grid) setting in relation to async checkout.
     *
     * +----------------------+---------------------+------------------------+----------------+
     * |       Async Grid     |  Async Checkout     |  Order Place Refresh   |  Cron Refresh  |
     * +----------------------------------+---------+------------------------+----------------+
     * |          Yes         |        Yes          |          Yes           |        No      |
     * |          Yes         |         No          |           No           |       Yes      |
     * |          No          |        Yes          |          Yes           |        No      |
     * |          No          |         No          |          Yes           |        No      |
     * +----------------------+---------------------+------------------------+----------------+
     *
     * @param string $path
     * @param string $scope
     * @param null|string $scopeCode
     * @return bool
     * @throws FileSystemException
     * @throws RuntimeException
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getValue($path, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null): bool
    {
        $asyncGrid = $this->globalConfig->getValue($path, $scope, $scopeCode);
        $asyncCheckout = $this->deploymentConfig->get(OrderManagement::ASYNC_ORDER_OPTION_PATH);

        return $asyncGrid == true && $asyncCheckout == false;
    }

    /**
     * Proxy method.
     *
     * @param string $path
     * @param string $scopeType
     * @param null|string $scopeCode
     * @return bool
     */
    public function isSetFlag($path, $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null): bool
    {
        return $this->config->isSetFlag($path, $scopeType, $scopeCode);
    }
}
