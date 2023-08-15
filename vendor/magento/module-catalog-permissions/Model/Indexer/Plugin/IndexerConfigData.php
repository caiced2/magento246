<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Model\Indexer\Plugin;

/**
 * Plugin for deleting catalog permissions indexers from list of indexers and dependency list of other indexers.
 *
 * In case if catalog permission functionality is disabled.
 */
class IndexerConfigData
{
    /**
     * @var \Magento\CatalogPermissions\App\Config
     */
    protected $config;

    /**
     * @param \Magento\CatalogPermissions\App\Config $config
     */
    public function __construct(\Magento\CatalogPermissions\App\Config $config)
    {
        $this->config = $config;
    }

    /**
     *  Unset indexer data in configuration if flat is disabled
     *
     * @param \Magento\Indexer\Model\Config\Data $subject
     * @param array|mixed|null $data
     * @param string $path
     * @param mixed $default
     *
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(
        \Magento\Indexer\Model\Config\Data $subject,
        $data,
        $path = null,
        $default = null
    ) {
        if (!$this->config->isEnabled()) {
            // Process Category indexer data
            $this->processData(\Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID, $path, $default, $data);
            // Process Product indexer data
            $this->processData(\Magento\CatalogPermissions\Model\Indexer\Product::INDEXER_ID, $path, $default, $data);
        }

        return $data;
    }

    /**
     * Delete process.
     *
     * @param int $indexerId
     * @param string $path
     * @param mixed $default
     * @param mixed $data
     * @return void
     */
    protected function processData($indexerId, $path, $default, &$data)
    {
        if (!$path && isset($data[$indexerId])) {
            unset($data[$indexerId]);
        } elseif ($path) {
            list($firstKey,) = explode('/', $path);
            if ($firstKey == $indexerId) {
                $data = $default;
            }
        }

        $this->removeDependency($indexerId, $data);
    }

    /**
     * Remove dependency.
     *
     * @param string $indexerId
     * @param array $data
     */
    private function removeDependency($indexerId, &$data)
    {
        if (!is_array($data)) {
            return;
        }
        if (isset($data['dependencies'])) {
            $key = array_search($indexerId, $data['dependencies']);
            if (false !== $key) {
                unset($data['dependencies'][$key]);
            }
        } else {
            foreach ($data as $key => $item) {
                $this->removeDependency($indexerId, $item);
            }
        }
    }
}
