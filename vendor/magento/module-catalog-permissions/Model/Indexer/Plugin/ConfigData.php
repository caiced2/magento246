<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Model\Indexer\Plugin;

use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\Framework\App\CacheInterface;

class ConfigData
{
    /**
     * @var CacheInterface
     */
    protected $coreCache;

    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry
     */
    protected $indexerRegistry;

    /**
     * @var \Magento\CatalogPermissions\App\ConfigInterface
     */
    protected $appConfig;

    /**
     * @var \Magento\Config\Model\Config\Loader
     */
    protected $configLoader;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param CacheInterface $coreCache
     * @param ConfigInterface $appConfig
     * @param \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry
     * @param \Magento\Config\Model\Config\Loader $configLoader
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        CacheInterface $coreCache,
        ConfigInterface $appConfig,
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        \Magento\Config\Model\Config\Loader $configLoader,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->indexerRegistry = $indexerRegistry;
        $this->appConfig = $appConfig;
        $this->coreCache = $coreCache;
        $this->configLoader = $configLoader;
        $this->storeManager = $storeManager;
    }

    /**
     * Return formatted config data for current section
     *
     * @param \Magento\Config\Model\Config $config
     * @return array
     */
    protected function getConfig(\Magento\Config\Model\Config $config)
    {
        $scope = 'default';
        $scopeId = 0;
        if ($config->getStore()) {
            $scope = 'stores';
            $store = $this->storeManager->getStore($config->getStore());
            $scopeId = (int)$store->getId();
        } elseif ($config->getWebsite()) {
            $scope = 'websites';
            $website = $this->storeManager->getWebsite($config->getWebsite());
            $scopeId = (int)$website->getId();
        }
        return $this->configLoader->getConfigByPath(
            $config->getSection() . '/magento_catalogpermissions',
            $scope,
            $scopeId,
            false
        );
    }

    /**
     *  Invalidation indexer after configuration of permission was changed
     *
     * @param \Magento\Config\Model\Config $subject
     * @param \Closure $proceed
     *
     * @return \Magento\Config\Model\Config
     */
    public function aroundSave(\Magento\Config\Model\Config $subject, \Closure $proceed)
    {
        $oldConfig = $this->getConfig($subject);
        $result = $proceed();
        $newConfig = $this->getConfig($subject);
        if ($this->checkForValidating($oldConfig, $newConfig) && $this->appConfig->isEnabled()) {
            $this->coreCache->clean(
                [
                    \Magento\Catalog\Model\Category::CACHE_TAG,
                    \Magento\Framework\App\Cache\Type\Block::CACHE_TAG,
                    \Magento\Framework\App\Cache\Type\Layout::CACHE_TAG
                ]
            );
            $this->indexerRegistry->get(\Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID)->invalidate();
            $this->indexerRegistry->get(\Magento\CatalogPermissions\Model\Indexer\Product::INDEXER_ID)->invalidate();
        }

        return $result;
    }

    /**
     * Check for validating
     *
     * @param array $oldConfig
     * @param array $newConfig
     * @return bool
     */
    protected function checkForValidating(array $oldConfig, array $newConfig)
    {
        $needInvalidating = false;
        foreach ($oldConfig as $key => $value) {
            if ($newConfig[$key] != $value) {
                $needInvalidating = true;
                break;
            }
        }
        return $needInvalidating;
    }
}
