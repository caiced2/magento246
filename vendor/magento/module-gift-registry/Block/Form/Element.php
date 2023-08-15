<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Block\Form;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Abstract block to render form elements
 */
class Element extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Directory\Model\Country
     */
    protected $country;

    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $region;

    /**
     * @var \Magento\Framework\App\Cache\Type\Config
     */
    protected $_configCacheType;

    /**
     * @var mixed
     */
    protected $_countryCollection;

    /**
     * @var \Magento\Directory\Model\ResourceModel\Region\Collection
     */
    protected $_regionCollection;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\App\Cache\Type\Config $configCacheType
     * @param \Magento\Directory\Model\Country $country
     * @param \Magento\Directory\Model\RegionFactory $region
     * @param array $data
     * @param SerializerInterface|null $serializer
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\Cache\Type\Config $configCacheType,
        \Magento\Directory\Model\Country $country,
        \Magento\Directory\Model\RegionFactory $region,
        array $data = [],
        SerializerInterface $serializer = null
    ) {
        $this->_configCacheType = $configCacheType;
        $this->country = $country;
        $this->region = $region;
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(SerializerInterface::class);
        parent::__construct($context, $data);
    }

    /**
     * Load country collection
     *
     * @return mixed
     */
    protected function _getCountryCollection()
    {
        if (!$this->_countryCollection) {
            $this->_countryCollection = $this->country->getResourceCollection()->loadByStore();
        }
        return $this->_countryCollection;
    }

    /**
     * Load region collection by specified country code
     *
     * @param null|string $country
     * @return \Magento\Directory\Model\ResourceModel\Region\Collection
     */
    protected function _getRegionCollection($country = null)
    {
        if (!$this->_regionCollection) {
            $this->_regionCollection = $this->region->create()->getResourceCollection()->addCountryFilter(
                $country
            )->load();
        }
        return $this->_regionCollection;
    }

    /**
     * Try to load country options from cache
     * If it is not exist load options from country collection and save to cache
     *
     * @return array
     */
    protected function _getCountryOptions()
    {
        $options = false;
        $cacheId = 'DIRECTORY_COUNTRY_SELECT_STORE_' . $this->_storeManager->getStore()->getCode();
        if ($optionsCache = $this->_configCacheType->load($cacheId)) {
            $options = $this->serializer->unserialize($optionsCache);
        }
        if ($options == false) {
            $options = $this->_getCountryCollection()->toOptionArray();
            $this->_configCacheType->save($this->serializer->serialize($options), $cacheId);
        }
        return $options;
    }

    /**
     * Get field name
     *
     * @param string $name
     * @return string
     */
    protected function _getFieldName($name)
    {
        $name = $this->getFieldNamePrefix() . $name;
        $container = $this->getFieldNameContainer();
        if ($container) {
            $name = $container . '[' . $name . ']';
        }
        return $name;
    }

    /**
     * Get field id
     *
     * @param string $id
     * @return string
     */
    protected function _getFieldId($id)
    {
        return $this->getFieldIdPrefix() . $id;
    }

    /**
     * Get field id prefix
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getFieldIdPrefix()
    {
        return $this->getData('field_id_prefix');
    }

    /**
     * Get field name prefix
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getFieldNamePrefix()
    {
        return $this->getData('field_name_prefix');
    }

    /**
     * Get field name container
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getFieldNameContainer()
    {
        return $this->getData('field_name_container');
    }

    /**
     * Create select html element
     *
     * @param string $name
     * @param string $id
     * @param array $options
     * @param mixed $value
     * @param string $class
     * @return string
     * @codeCoverageIgnore
     */
    public function getSelectHtml($name, $id, $options = [], $value = null, $class = '')
    {
        $select = $this->getLayout()->createBlock(
            \Magento\Framework\View\Element\Html\Select::class
        )->setName(
            $this->_getFieldName($name)
        )->setId(
            $this->_getFieldId($id)
        )->setClass(
            'select ' . $class
        )->setValue(
            $value
        )->setOptions(
            $options
        );
        return $select->getHtml();
    }

    /**
     * Create country select html element
     *
     * @param string $name
     * @param string $id
     * @param null|string $value
     * @param string $class
     * @return string
     * @codeCoverageIgnore
     */
    public function getCountryHtmlSelect($name, $id, $value = null, $class = '')
    {
        $options = $this->_getCountryOptions();
        return $this->getSelectHtml($name, $id, $options, $value, $class);
    }

    /**
     * Create region select html element
     *
     * @param string $name
     * @param string $id
     * @param null|int $value
     * @param null|string $country
     * @param string $class
     * @return string
     * @codeCoverageIgnore
     */
    public function getRegionHtmlSelect($name, $id, $value = null, $country = null, $class = '')
    {
        $options = $this->_getRegionCollection($country)->toOptionArray();
        return $this->getSelectHtml($name, $id, $options, $value, $class);
    }

    /**
     * Create js calendar html
     *
     * @param string $name
     * @param string $id
     * @param string $value
     * @param null|string $formatType
     * @param string $class
     * @return string
     */
    public function getCalendarDateHtml($name, $id, $value = null, $formatType = null, $class = '')
    {
        if ($formatType === null) {
            $formatType = \IntlDateFormatter::MEDIUM;
        }

        $calendar = $this->getLayout()->createBlock(
            \Magento\Framework\View\Element\Html\Date::class
        )->setName(
            $this->_getFieldName($name)
        )->setId(
            $this->_getFieldId($id)
        )->setValue(
            $value
        )->setClass(
            'datetime-picker input-text' . $class
        )->setImage(
            $this->getViewFileUrl('Magento_Theme::calendar.png')
        )->setDateFormat(
            $this->_localeDate->getDateFormat($formatType)
        );
        return $calendar->getHtml();
    }

    /**
     * Create input text html element
     *
     * @param string $name
     * @param string $id
     * @param string $value
     * @param string $class
     * @param string $style
     * @return string
     * @codeCoverageIgnore
     */
    public function getInputTextHtml($name, $id, $value = '', $class = '', $style = '')
    {
        $name = $this->_getFieldName($name);
        $id = $this->_getFieldId($id);
        $class = 'input-text ' . $class;

        return '<input class="' .
            $class .
            '" type="text" name="' .
            $name .
            '" id="' .
            $id .
            '" value="' .
            $value .
            '" style="' .
            $style .
            '"/>';
    }

    /**
     * Convert array to options array for select html element
     *
     * @param array $selectOptions
     * @param bool $withEmpty
     * @return array
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function convertArrayToOptions($selectOptions, $withEmpty = false)
    {
        $options = [];
        if ($withEmpty) {
            $options[] = ['value' => '', 'label' => __('-- Please select --')];
        }
        if (is_array($selectOptions)) {
            foreach ($selectOptions as $code => $option) {
                $options[] = ['label' => $option['label'], 'value' => $option['code']];
            }
        }
        return $options;
    }
}
