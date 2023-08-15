<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Configuration;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;

/**
 * Abstract scoped configurations sections model
 */
abstract class AbstractScopedConfigurationSection extends AbstractConfigurationSection
{
    const SCOPE_DEFAULT = '[Default]';

    /**
     * Core configuration
     *
     * @var ScopeConfigInterface
     */
    protected $config;

    /**
     * Store management
     *
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Available store views
     *
     * @var null|\Magento\Store\Model\Website[]
     */
    protected $storeViews;

    /**
     * Available websites
     *
     * @var null|\Magento\Store\Api\Data\WebsiteInterface[]
     */
    protected $websites;

    /**
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $config
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        LoggerInterface $logger,
        ScopeConfigInterface $config,
        StoreManagerInterface $storeManager
    ) {
        $this->config = $config;
        $this->storeManager = $storeManager;
        parent::__construct($logger);
    }

    /**
     * Return item config
     *
     * @param mixed $value
     * @param array $configInfo
     * @param string $scopeName
     * @return array
     */
    abstract public function getConfigDataItem($value, array $configInfo, $scopeName);

    /**
     * Get store views
     *
     * @return \Magento\Store\Model\Store[]
     */
    protected function getStoreViews()
    {
        if ($this->storeViews === null) {
            $this->storeViews = $this->storeManager->getStores();
        }
        return $this->storeViews;
    }

    /**
     * Get available websites
     *
     * @return \Magento\Store\Model\Website[]
     */
    protected function getWebsites()
    {
        if ($this->websites === null) {
            $this->websites = $this->storeManager->getWebsites();
        }
        return $this->websites;
    }

    /**
     * Preparing config parameters for output
     *
     * @param array $configPaths
     * @return array
     */
    protected function prepareConfigValues(array $configPaths)
    {
        if (!$configPaths) {
            return [];
        }

        $configData = [];
        foreach ($configPaths as $configPath => $configInfo) {
            $defaultValue = $this->config->getValue($configPath);
            $configData[] = $this->getConfigDataItem($defaultValue, $configInfo, self::SCOPE_DEFAULT);

            /** @var Store $store */
            foreach ($this->getStoreViews() as $store) {
                $storeValue = $this->config->getValue($configPath, ScopeInterface::SCOPE_STORES, $store->getId());
                if ($defaultValue === $storeValue) {
                    continue;
                }
                $scopeName = $this->createScopePath($store);
                $configData[] = $this->getConfigDataItem($storeValue, $configInfo, $scopeName);
            }
        }
        return $configData;
    }

    /**
     * Creating user-friendly path to scope
     *
     * @param Store $store
     * @return string
     */
    protected function createScopePath(Store $store)
    {
        return '[' . $store->getWebsite()->getName() . '] -> ['
        . $store->getGroup()->getName() . '] -> [' . $store->getName() . ']';
    }
}
