<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogEvent\Model;

use Magento\Framework\Filesystem;
use Magento\Catalog\Model\Category;
use Magento\CatalogEvent\Model\ResourceModel\Event as ResourceEvent;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Registry;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\MediaStorage\Model\File\Uploader;
use Magento\Framework\Data\Collection\AbstractDb as DbCollection;
use Magento\Framework\UrlInterface;
use Magento\Catalog\Model\Product;

/**
 * Catalog Event model
 *
 * @api
 * @method int getCategoryId()
 * @method Event setCategoryId(int $value)
 * @method string getDateStart()
 * @method Event setDateStart(string $value)
 * @method string getDateEnd()
 * @method Event setDateEnd(string $value)
 * @method int getDisplayState()
 * @method int getSortOrder()
 * @method Event setSortOrder(int $value)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @api
 * @since 100.0.2
 */
class Event extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const DISPLAY_CATEGORY_PAGE = 1;

    const DISPLAY_PRODUCT_PAGE = 2;

    const STATUS_UPCOMING = 'upcoming';

    const STATUS_OPEN = 'open';

    const STATUS_CLOSED = 'closed';

    const CACHE_TAG = 'catalog_event';

    const IMAGE_PATH = 'enterprise/catalogevent';

    /**
     * @var null|Store
     */
    protected $_store = null;

    /**
     * Model cache tag for clear cache in after save and after delete
     *
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * Is model deleteable
     *
     * @var bool
     */
    protected $_isDeleteable = true;

    /**
     * Is model readonly
     *
     * @var bool
     */
    protected $_isReadonly = false;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;

    /**
     * Filesystem facade
     *
     * @var Filesystem
     */
    protected $_filesystem;

    /**
     * Store manager
     *
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Status mapper
     *
     * @var array
     * @since 100.1.0
     */
    public static $statusMapper = [
        \Magento\CatalogEvent\Model\Event::STATUS_OPEN => 0,
        \Magento\CatalogEvent\Model\Event::STATUS_UPCOMING => 1,
        \Magento\CatalogEvent\Model\Event::STATUS_CLOSED => 2
    ];

    /**
     * Construct
     *
     * @param Context $context
     * @param Registry $registry
     * @param TimezoneInterface $localeDate
     * @param Filesystem $filesystem
     * @param StoreManagerInterface $storeManager
     * @param ResourceEvent $resource
     * @param DbCollection $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        TimezoneInterface $localeDate,
        Filesystem $filesystem,
        StoreManagerInterface $storeManager,
        ResourceEvent $resource = null,
        DbCollection $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->_storeManager = $storeManager;
        $this->_filesystem = $filesystem;
        $this->_localeDate = $localeDate;
    }

    /**
     * Initialize model
     *
     * @return void
     * @codeCoverageIgnore
     */
    protected function _construct()
    {
        $this->_init(\Magento\CatalogEvent\Model\ResourceModel\Event::class);
    }

    /**
     * Apply event status
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        $this->_initDisplayStateArray();
        parent::_afterLoad();
        return $this;
    }

    /**
     * Initialize display state as array
     *
     * @return $this
     */
    protected function _initDisplayStateArray()
    {
        $state = [];
        if ($this->canDisplayCategoryPage()) {
            $state[] = self::DISPLAY_CATEGORY_PAGE;
        }
        if ($this->canDisplayProductPage()) {
            $state[] = self::DISPLAY_PRODUCT_PAGE;
        }
        $this->setDisplayStateArray($state);
        return $this;
    }

    /**
     * Set store id
     *
     * @param int $storeId
     * @return $this
     * @codeCoverageIgnore
     */
    public function setStoreId($storeId = null)
    {
        $this->_store = $this->_storeManager->getStore($storeId);
        return $this;
    }

    /**
     * Retrieve store
     *
     * @return Store
     */
    public function getStore()
    {
        if ($this->_store === null) {
            $this->setStoreId();
        }

        return $this->_store;
    }

    /**
     * Set event image
     *
     * @param string|null|Uploader $value
     * @return $this
     */
    public function setImage($value)
    {
        //in the current version should be used instance of \Magento\MediaStorage\Model\File\Uploader
        if ($value instanceof \Magento\Framework\File\Uploader) {
            $value->save(
                $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath(self::IMAGE_PATH)
            );
            $value = $value->getUploadedFileName();
        }

        $this->setData('image', $value);
        return $this;
    }

    /**
     * Retrieve image url
     *
     * @return string|false
     */
    public function getImageUrl()
    {
        if ($this->getImage()) {
            return $this->_storeManager->getStore()->getBaseUrl(
                UrlInterface::URL_TYPE_MEDIA
            ) . self::IMAGE_PATH . '/' . $this->getImage();
        }

        return false;
    }

    /**
     * Retrieve store id
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->getStore()->getId();
    }

    /**
     * Set display state of catalog event
     *
     * @param null|int|int[] $state
     * @return $this
     */
    public function setDisplayState($state)
    {
        $value = 0;
        if (is_array($state)) {
            foreach ($state as $_state) {
                $value ^= $_state;
            }
            $this->setData('display_state', $value);
        } else {
            $value = empty($state) ? $value : $state;
            $this->setData('display_state', $value);
        }
        return $this;
    }

    /**
     * Check display state for page type
     *
     * @param int $state
     * @return bool
     */
    public function canDisplay($state)
    {
        return ((int)$this->getDisplayState() & $state) == $state;
    }

    /**
     * Check display state for product view page
     *
     * @return bool
     */
    public function canDisplayProductPage()
    {
        return $this->canDisplay(self::DISPLAY_PRODUCT_PAGE);
    }

    /**
     * Check display state for category view page
     *
     * @return bool
     */
    public function canDisplayCategoryPage()
    {
        return $this->canDisplay(self::DISPLAY_CATEGORY_PAGE);
    }

    /**
     * Apply event status by date
     *
     * @return $this
     */
    public function applyStatusByDates()
    {
        if ($this->getDateStart() && $this->getDateEnd()) {
            $timeStart = (new \DateTime($this->getDateStart()))->getTimestamp();
            // Date already in gmt, no conversion
            $timeEnd = (new \DateTime($this->getDateEnd()))->getTimestamp();
            // Date already in gmt, no conversion
            $timeNow = gmdate('U');
            if ($timeStart <= $timeNow && $timeEnd >= $timeNow) {
                $this->setStatus(self::STATUS_OPEN);
            } elseif ($timeNow > $timeEnd) {
                $this->setStatus(self::STATUS_CLOSED);
            } else {
                $this->setStatus(self::STATUS_UPCOMING);
            }
        } else {
            $this->setStatus(self::STATUS_UPCOMING);
        }
        return $this;
    }

    /**
     * Retrieve category ids with events
     *
     * @param int|string|Store $storeId
     * @return array
     */
    public function getCategoryIdsWithEvent($storeId = null)
    {
        return $this->_getResource()->getCategoryIdsWithEvent($storeId);
    }

    /**
     * Before save. Validation of data, and applying status, if needed.
     *
     * @return $this
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        parent::beforeSave();
        $dateChanged = false;
        $fieldTitles = ['date_start' => __('Start Date'), 'date_end' => __('End Date')];
        foreach (['date_start', 'date_end'] as $dateType) {
            $date = $this->getData($dateType);
            if (empty($date)) {
                // Date fields is required.
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('%1 is required.', $fieldTitles[$dateType])
                );
            }
            if ($date != $this->getOrigData($dateType)) {
                $dateChanged = true;
            }
        }
        if ($dateChanged) {
            $this->applyStatusByDates();
        }

        return $this;
    }

    /**
     * Validates data for event
     *
     * @return true|array - returns true if validation passed successfully. Array with error
     * description otherwise
     */
    public function validate()
    {
        $dateStartUnixTime = strtotime($this->getData('date_start'));
        $dateEndUnixTime = strtotime($this->getData('date_end'));
        $dateIsOk = $dateEndUnixTime > $dateStartUnixTime;
        if ($dateIsOk) {
            return true;
        } else {
            return [__('Please make sure the end date follows the start date.')];
        }
    }

    /**
     * Checks if object can be deleted
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function isDeleteable()
    {
        return $this->_isDeleteable;
    }

    /**
     * Sets flag for object if it can be deleted or not
     *
     * @param bool $value
     * @return $this
     * @codeCoverageIgnore
     */
    public function setIsDeleteable($value)
    {
        $this->_isDeleteable = (bool)$value;
        return $this;
    }

    /**
     * Checks model is read only
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function isReadonly()
    {
        return $this->_isReadonly;
    }

    /**
     * Set is read only flag
     *
     * @param bool $value
     * @return $this
     * @codeCoverageIgnore
     */
    public function setIsReadonly($value)
    {
        $this->_isReadonly = (bool)$value;
        return $this;
    }

    /**
     * Get status column value
     * Set status column if it wasn't set
     *
     * @return string
     */
    public function getStatus()
    {
        if (!$this->hasData('status')) {
            $this->applyStatusByDates();
        }
        $statusMapper = array_flip(self::$statusMapper);
        return $statusMapper[$this->getData('status')];
    }

    /**
     * Set status column
     *
     * @param string $status
     *
     * @return $this
     * @since 100.1.0
     */
    public function setStatus($status)
    {
        $this->setData('status', self::$statusMapper[$status]);
        return $this;
    }

    /**
     * Converts passed start time value to scope timezone and sets it to object.
     *
     * @param string $value date time in store's time zone
     * @param null|string|bool|int|Store $store
     * @return $this
     */
    public function setStoreDateStart($value, $store = null)
    {
        $date = $this->_localeDate->scopeDate($store, $value, true);
        $this->setData('date_start', $date->format('Y-m-d H:i:s'));
        return $this;
    }

    /**
     * Converts passed end time value to scope timezone and sets it to object.
     *
     * @param string $value date time in store's time zone
     * @param null|string|bool|int|Store $store
     * @return $this
     */
    public function setStoreDateEnd($value, $store = null)
    {
        $date = $this->_localeDate->scopeDate($store, $value, true);
        $this->setData('date_end', $date->format('Y-m-d H:i:s'));
        return $this;
    }

    /**
     * Gets start time from object, converts it from UTC time zone
     * to store's time zone. Result is formatted by internal format
     * and in time zone of current store or passed through parameter.
     *
     * @param null|string|bool|int|Store $store
     * @return string
     */
    public function getStoreDateStart($store = null)
    {
        if ($this->getData('date_start')) {
            $date = $this->_localeDate->scopeDate($store, $this->getData('date_start'), true);
            return $date->format('Y-m-d H:i:s');
        }

        return $this->getData('date_start');
    }

    /**
     * Gets end time from object, converts it from UTC time zone
     * to store's time zone. Result is formatted by internal format
     * and in time zone of current store or passed through parameter.
     *
     * @param null|string|bool|int|Store $store
     * @return string
     */
    public function getStoreDateEnd($store = null)
    {
        if ($this->getData('date_end')) {
            $date = $this->_localeDate->scopeDate($store, $this->getData('date_end'), true);
            return $date->format('Y-m-d H:i:s');
        }

        return $this->getData('date_end');
    }

    /**
     * Get identities
     *
     * @return array
     */
    public function getIdentities()
    {
        return [
            self::CACHE_TAG . '_' . $this->getId(),
            \Magento\Catalog\Model\Category::CACHE_TAG . '_' . $this->getCategoryId(),
            Product::CACHE_PRODUCT_CATEGORY_TAG . '_' . $this->getCategoryId(),
        ];
    }
}
