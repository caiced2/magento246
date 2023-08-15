<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Block\Customer\Edit;

/**
 * Customer giftregistry list block
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class AbstractEdit extends \Magento\Directory\Block\Data
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\GiftRegistry\Model\Attribute\Config
     */
    protected $attributeConfig;

    /**
     * Registry Entity object
     *
     * @var \Magento\GiftRegistry\Model\Entity
     */
    protected $_entity = null;

    /**
     * Attribute groups array
     *
     * @var array
     */
    protected $_groups = null;

    /**
     * Static types fields holder
     *
     * @var array
     */
    protected $_staticTypes = [];

    /**
     * Scope Selector 'registry/registrant'
     *
     * @var string
     */
    protected $_prefix;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_registry = null;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Directory\Helper\Data $directoryHelper
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\App\Cache\Type\Config $configCacheType
     * @param \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory
     * @param \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\GiftRegistry\Model\Attribute\Config $attributeConfig
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Directory\Helper\Data $directoryHelper,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\App\Cache\Type\Config $configCacheType,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory,
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\GiftRegistry\Model\Attribute\Config $attributeConfig,
        array $data = []
    ) {
        $this->_registry = $registry;
        $this->customerSession = $customerSession;
        $this->attributeConfig = $attributeConfig;
        parent::__construct(
            $context,
            $directoryHelper,
            $jsonEncoder,
            $configCacheType,
            $regionCollectionFactory,
            $countryCollectionFactory,
            $data
        );
    }

    /**
     * Get config
     *
     * @param string $path
     * @return string|null
     * @codeCoverageIgnore
     */
    public function getConfig($path)
    {
        return $this->_scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * Getter, return entity object , instantiated in controller
     *
     * @return \Magento\GiftRegistry\Model\Entity
     * @codeCoverageIgnore
     */
    public function getEntity()
    {
        return $this->_registry->registry('magento_giftregistry_entity');
    }

    /**
     * Getter for CustomAttributes Array
     *
     * @codeCoverageIgnore
     * @return array
     */
    public function getCustomAttributes()
    {
        return $this->getEntity()->getCustomAttributes();
    }

    /**
     * Check if attribute is required
     *
     * @param array $data
     * @return bool
     */
    public function isAttributeRequired($data)
    {
        if (isset($data['frontend']) && is_array($data['frontend']) && !empty($data['frontend']['is_required'])) {
            return true;
        }
        return false;
    }

    /**
     * Check if attribute needs region updater js object
     *
     * @param array $data
     * @return bool
     */
    public function useRegionUpdater($data)
    {
        return $data['type'] == 'country' && !empty($data['show_region']);
    }

    /**
     * Check if attribute is static
     *
     * @param string $code
     * @return bool
     */
    public function isAttributeStatic($code)
    {
        $types = $this->attributeConfig->getStaticTypesCodes();
        if (in_array($code, $types)) {
            return true;
        }
        return false;
    }

    /**
     * Return array of attribute groups for using as options
     *
     * @return mixed
     * @codeCoverageIgnore
     */
    public function getAttributeGroups()
    {
        return $this->attributeConfig->getAttributeGroups();
    }

    /**
     * Return group label
     *
     * @param string $groupId
     * @return string
     */
    public function getGroupLabel($groupId)
    {
        if ($this->_groups === null) {
            $this->_groups = $this->attributeConfig->getAttributeGroups();
        }
        if (is_array(
            $this->_groups
        ) && !empty($this->_groups[$groupId]) && is_array(
            $this->_groups[$groupId]
        ) && !empty($this->_groups[$groupId]['label'])
        ) {
            $label = $this->_groups[$groupId]['label'];
        } else {
            $label = $groupId;
        }
        return $label;
    }

    /**
     * JS Calendar html
     *
     * @param string $name - DOM name
     * @param string $id - DOM id
     * @param string $value
     * @param bool|int $formatType
     * @param string $class
     *
     * @return string
     */
    public function getCalendarDateHtml($name, $id, $value, $formatType = false, $class = '')
    {
        if ($formatType === false) {
            $formatType = \IntlDateFormatter::MEDIUM;
        }
        $date = !($value instanceof \DateTime) ? new \DateTime($value ?: 'now') : $value;
        $calendar = $this->getLayout()->createBlock(
            \Magento\GiftRegistry\Block\Customer\Date::class
        )->setId(
            $id
        )->setName(
            $name
        )->setValue(
            $this->formatDate($date, $formatType, false, $date->getTimezone())
        )->setClass(
            $class . ' product-custom-option datetime-picker input-text validate-date'
        )->setImage(
            $this->getViewFileUrl('Magento_Theme::calendar.png')
        )->setDateFormat(
            $this->_localeDate->getDateFormat($formatType)
        );
        return $calendar->getHtml();
    }

    /**
     * Select element for choosing attribute group
     *
     * @param string $options
     * @param string $name
     * @param string $id
     * @param bool $value
     * @param string $class
     * @return string
     */
    public function getSelectHtml($options, $name, $id, $value = false, $class = '')
    {
        $select = $this->getLayout()->createBlock(
            \Magento\Framework\View\Element\Html\Select::class
        )->setData(
            ['id' => $id, 'class' => 'select global-scope ' . $class]
        )->setName(
            $name
        )->setValue(
            $value
        )->setOptions(
            $options
        );
        return $select->getHtml();
    }

    /**
     * Reorder attributes array by group
     *
     * @param array $attributes
     * @return array
     */
    protected function _groupAttributes($attributes)
    {
        $grouped = [];
        if (is_array($attributes)) {
            foreach ($attributes as $field => $fdata) {
                if (is_array($fdata)) {
                    $grouped[$fdata['group']][$field] = $fdata;
                    $grouped[$fdata['group']][$field]['id'] = $this->_getElementId($field);
                    $grouped[$fdata['group']][$field]['name'] = $this->_getElementName($field);

                    if ($fdata['type'] == 'country' && !empty($fdata['show_region'])) {
                        $regionCode = $field . '_region';
                        $regionAttribute['label'] = __('State/Province');
                        $regionAttribute['default'] = __('Please select a region, state or province');
                        $regionAttribute['group'] = $fdata['group'];
                        $regionAttribute['type'] = 'region';
                        $regionAttribute['id'] = $this->_getElementId($regionCode);
                        $regionAttribute['name'] = $this->_getElementName($regionCode);
                        $grouped[$fdata['group']][$regionCode] = $regionAttribute;
                    }
                }
            }
        }
        return $grouped;
    }

    /**
     * Prepare html element name
     *
     * @param string $code
     * @return string
     */
    protected function _getElementName($code)
    {
        if (!$this->isAttributeStatic($code)) {
            return $this->_prefix . '[' . $code . ']';
        }
        return $code;
    }

    /**
     * Prepare html element id
     *
     * @param string $code
     * @return string
     * @codeCoverageIgnore
     */
    protected function _getElementId($code)
    {
        return $code;
    }

    /**
     * Get current type Id
     *
     * @return int
     * @codeCoverageIgnore
     */
    public function getTypeId()
    {
        return $this->getEntity()->getTypeId();
    }

    /**
     * Get current type label
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getTypeLabel()
    {
        return $this->getEntity()->getTypeLabel();
    }

    /**
     * Reorder data in group array for internal use
     *
     * @param array $selectOptions
     * @return array
     */
    protected function _convertGroupArray($selectOptions)
    {
        $data = [];
        if (is_array($selectOptions)) {
            $data[] = ['label' => __('Please Select'), 'value' => ''];
            foreach ($selectOptions as $option) {
                $data[] = ['label' => $option['label'], 'value' => $option['code']];
            }
        }
        return $data;
    }

    /**
     * Render input field of the specific type : text, select, date, region, country
     *
     * @param array $data
     * @param string $field
     * @param string $value
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function renderField($data, $field, $value = null)
    {
        $element = '';
        if ($field && is_array($data)) {
            $type = $data['type'];
            $name = $data['name'];
            $id = $data['id'];
            $value = $this->getEntity()->getFieldValue($id);
            $class = $this->isAttributeRequired($data) ? 'required-entry' : '';

            switch ($type) {
                case 'country':
                    $element = $this->getCountryHtmlSelect($value, $name, $id, $class);
                    break;

                case 'region':
                    $default = isset($data['default']) ? $data['default'] : '';
                    $element = $this->getRegionHtmlSelectEmpty(
                        $name,
                        $id,
                        $value,
                        $class,
                        '',
                        $default
                    );
                    $id = $this->_getElementId($id . '_text');
                    $name = $this->_getElementName($id);
                    $value = $this->getEntity()->getFieldValue($id);
                    $element .= $this->_getInputTextHtml($name, $id, $value, $class);
                    break;

                case 'date':
                    $format = isset($data['date_format']) ? $data['date_format'] : '';
                    $element = $this->getCalendarDateHtml($name, $id, $value, $format, $class);
                    break;

                case 'select':
                    $options = $this->_convertGroupArray($data['options']);
                    if (empty($value)) {
                        $value = isset($data['default']) ? $data['default'] : '';
                    }
                    $element = $this->getSelectHtml($options, $name, $id, $value, $class);
                    break;

                case 'number':
                    $element = $this->_getInputTextHtml($name, $id, $value, $class . ' validate-digits');
                    break;

                default:
                    $element = $this->_getInputTextHtml($name, $id, $value, $class);
                    break;
            }
        }
        return $element;
    }

    /**
     * Render "input text" field
     *
     * @param string $name
     * @param string $id
     * @param string $value
     * @param string $class
     * @param string $params additional params
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\ValidatorException
     */
    protected function _getInputTextHtml($name, $id, $value = '', $class = '', $params = '')
    {
        $template = $this->getLayout()->getBlock('giftregistry_edit')->getInputTypeTemplate('text');
        $this->setInputName(
            $name
        )->setInputId(
            $id
        )->setInputValue(
            $value
        )->setInputClass(
            $class
        )->setInputParams(
            $params
        );
        if ($template) {
            return $this->fetchView($template);
        }
        return '';
    }

    /**
     * Return region select html element
     *
     * @param string $name
     * @param string $id
     * @param string $value
     * @param string $class
     * @param string $params additional params
     * @param string $default
     * @return string
     */
    public function getRegionHtmlSelectEmpty($name, $id, $value = '', $class = '', $params = '', $default = '')
    {
        $template = $this->getLayout()->getBlock('giftregistry_edit')->getInputTypeTemplate('region');
        $this->setSelectRegionName(
            $name
        )->setSelectRegionId(
            $id
        )->setSelectRegionValue(
            $value
        )->setSelectRegionClass(
            $class
        )->setSelectRegionParams(
            $params
        )->setSelectRegionDefault(
            $default
        );
        if ($template) {
            return $this->fetchView($template);
        }
        return '';
    }

    /**
     * Return "create giftregistry" form url
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getAddGiftRegistryUrl()
    {
        return $this->getUrl('magento_giftregistry/index/addselect');
    }

    /**
     * Return "create giftregistry" form url
     *
     * @return string
     * @deprecated 101.1.3 Action does not exist
     * @codeCoverageIgnore
     */
    public function getSaveActionUrl()
    {
        //phpcs:ignore Magento2.Functions.DiscouragedFunction
        trigger_error('Method is deprecated', E_USER_DEPRECATED);
        return '';
    }

    /**
     * Return array of attributes groupped by group
     *
     * @return array
     */
    public function getGroupedAttributes()
    {
        $attributes = $this->getCustomAttributes();
        if (!empty($attributes[$this->_prefix])) {
            return $this->_groupAttributes($attributes[$this->_prefix]);
        }
        return [];
    }
}
