<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\ForeignKey\ObjectRelationProcessor;

use Magento\Framework\App\ResourceConnection;

/**
 * @deprecated split database solution is deprecated and will be removed
 */
class EnvironmentConfig
{
    const CACHE_ID = 'connection_config_cache';

    /**
     * @var \Magento\Framework\App\ResourceConnection\ConfigInterface
     */
    private $config;

    /**
     * @var \Magento\Framework\Config\CacheInterface
     */
    private $cache;

    /**
     * @var array
     */
    private $connectionNames;

    /**
     * @var array
     */
    private $connectionConfig;

    /**
     * @var \Magento\Framework\Json\DecoderInterface
     */
    private $jsonDecoder;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    private $jsonEncoder;

    /**
     * @param ResourceConnection\ConfigInterface $config
     * @param \Magento\Framework\Config\CacheInterface $cache
     * @param \Magento\Framework\Json\DecoderInterface $jsonDecoder
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param array $connectionNames
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection\ConfigInterface $config,
        \Magento\Framework\Config\CacheInterface $cache,
        \Magento\Framework\Json\DecoderInterface $jsonDecoder,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        array $connectionNames = []
    ) {
        $this->config = $config;
        $this->cache = $cache;
        $this->jsonDecoder = $jsonDecoder;
        $this->jsonEncoder = $jsonEncoder;
        $this->connectionNames = $connectionNames;
    }

    /**
     * Returns true if any of connections is not default, or value for $connectionName, if specified
     *
     * @param null|string $connectionName
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function isScalable($connectionName = null)
    {
        if (empty($this->connectionNames)) {
            return false;
        }

        if (null === $this->connectionConfig) {
            $this->loadConnectionsConfig();
        }

        if (null !== $connectionName) {
            return $this->isScalableForConnection($connectionName);
        }

        foreach ($this->connectionConfig as $name => $isDefault) {
            if ($this->isScalableForConnection($name)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if connection default or not, returns true if connection not default
     *
     * @param string $connectionName
     * @return bool
     */
    private function isScalableForConnection($connectionName)
    {
        if (isset($this->connectionConfig[$connectionName])) {
            return $this->connectionConfig[$connectionName] === false;
        }
        return false;
    }

    /**
     * Loads configuration for all connections and updates cache.
     *
     * @return void
     */
    private function loadConnectionsConfig()
    {
        $this->connectionConfig = $this->cache->load(self::CACHE_ID);
        if (false === $this->connectionConfig) {
            $this->connectionConfig = [];
            foreach ($this->connectionNames as $connectionName) {
                $this->connectionConfig[$connectionName] =
                    (bool)($this->config->getConnectionName($connectionName) == ResourceConnection::DEFAULT_CONNECTION);
            }
            $this->cache->save(
                $this->jsonEncoder->encode($this->connectionConfig),
                self::CACHE_ID,
                [\Magento\Framework\App\Cache\Type\Config::CACHE_TAG]
            );
        } else {
            $this->connectionConfig = $this->jsonDecoder->decode($this->connectionConfig);
        }
    }
}
