<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VisualMerchandiser\Model\Position;

use InvalidArgumentException;
use Magento\Backend\Model\Auth\Session;
use Magento\Config\Model\Config;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

/**
 * Products positions cache model
 */
class Cache extends AbstractModel
{
    public const POSITION_CACHE_KEY = 'position_cache_key';
    public const CACHE_PREFIX = 'MERCHANDISER_POSITION_CACHE_';

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var Config
     */
    protected $backendConfig;

    /**
     * @var array|null
     */
    protected $cachedData = null;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param Config $backendConfig
     * @param AbstractResource $resource
     * @param AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Config $backendConfig,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->cache = $context->getCacheManager();
        $this->backendConfig = $backendConfig;
    }

    /**
     * Save cache data for given key
     *
     * @param string $key
     * @param array $positions
     * @param int|null $sortOrder
     * @return void
     */
    public function saveData($key, $positions, $sortOrder = null)
    {
        if (!$key) {
            return;
        }

        $lifeTime = $this->backendConfig->getConfigDataValue(
            Session::XML_PATH_SESSION_LIFETIME
        );

        if (!is_numeric($lifeTime)) {
            $lifeTime = null;
        }

        $data['positions'] = $positions;

        if ($sortOrder !== null) {
            $data['sort_order'] = $sortOrder;
        }

        $this->cachedData = null;
        $saveResult = $this->cache->save(json_encode($data), self::CACHE_PREFIX . $key, [], $lifeTime);
        if ($saveResult) {
            $this->cachedData = $data;
        }
    }

    /**
     * Get cache data for given key
     *
     * @param string $cacheKey
     * @param string $param
     * @return null|mixed
     * @throws InvalidArgumentException
     */
    private function getFromCache($cacheKey, $param)
    {
        if (!$cacheKey) {
            return false;
        }

        if ($this->cachedData == null) {
            $jsonStr = $this->cache->load(self::CACHE_PREFIX . $cacheKey);
            if (strlen($jsonStr)) {
                $this->cachedData = json_decode($jsonStr, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new InvalidArgumentException("Unable to unserialize value. Error: " . json_last_error_msg());
                }
            }
        }

        return isset($this->cachedData[$param]) ? $this->cachedData[$param] : false;
    }

    /**
     * Get positions from cache
     *
     * @param string $key
     * @return bool|array
     * @throws InvalidArgumentException
     */
    public function getPositions($key)
    {
        $positions = $this->getFromCache($key, 'positions');

        if ($positions !== false) {
            if (!is_array($positions)) {
                return false;
            }

            $positionsFiltered = [];
            foreach ($positions as $key => $value) {
                if (is_numeric($key) && is_numeric($value)) {
                    $positionsFiltered[$key] = $value;
                }
            }

            return $positionsFiltered;
        }
        return false;
    }

    /**
     * Get sort order
     *
     * @param string $key
     * @return bool|int
     * @throws InvalidArgumentException
     */
    public function getSortOrder($key)
    {
        return $this->getFromCache($key, 'sort_order');
    }

    /**
     * Prepend new positions
     *
     * @param string $key
     * @param array $data
     * @return void
     * @throws InvalidArgumentException
     */
    public function prependPositions($key, $data)
    {
        $positions = $this->getPositions($key);
        $filteredData = [];
        foreach ($data as $item) {
            if (!array_key_exists($item, $positions)) {
                $filteredData[$item] = 0;
            }
        }
        $data = $this->reorderPositions($filteredData + $positions);
        $this->saveData($key, $data);
    }

    /**
     * Reindex positions
     *
     * @param array $data
     * @return array
     */
    public function reorderPositions($data)
    {
        $positionIndex = 0;
        $finalData = [];
        foreach (array_keys($data) as $dataKey) {
            $finalData[$dataKey] = $positionIndex;
            $positionIndex++;
        }

        return $finalData;
    }
}
