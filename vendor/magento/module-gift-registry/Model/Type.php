<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Model;

use Magento\Store\Model\Store;

/**
 * Gift registry types processing model
 *
 * @method string getCode()
 * @method \Magento\GiftRegistry\Model\Type setCode(string $value)
 * @method string getMetaXml()
 * @method \Magento\GiftRegistry\Model\Type setMetaXml(string $value)
 *
 * @api
 * @since 100.0.2
 */
class Type extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var Store
     */
    protected $_store = null;

    /**
     * @var array
     */
    protected $_storeData = null;

    /**
     * @var \Magento\GiftRegistry\Model\Attribute\Config
     */
    protected $attributeConfig;

    /**
     * @var \Magento\GiftRegistry\Model\Attribute\ProcessorFactory
     */
    protected $processorFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Intialize model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\GiftRegistry\Model\ResourceModel\Type::class);
    }

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\GiftRegistry\Model\Attribute\Config $attributeConfig
     * @param \Magento\GiftRegistry\Model\Attribute\ProcessorFactory $processorFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\GiftRegistry\Model\Attribute\Config $attributeConfig,
        \Magento\GiftRegistry\Model\Attribute\ProcessorFactory $processorFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->attributeConfig = $attributeConfig;
        $this->processorFactory = $processorFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Perform actions before object save.
     *
     * @return $this
     */
    public function beforeSave()
    {
        if (!$this->hasStoreId() && !$this->getStoreId()) {
            $this->_cleanupData();
            $xmlModel = $this->processorFactory->create();
            $this->setMetaXml($xmlModel->processData($this));
        }

        parent::beforeSave();
    }

    /**
     * Perform actions after object save.
     *
     * @return $this
     */
    public function afterSave()
    {
        $this->_getResource()->saveTypeStoreData($this);
        if ($this->getStoreId()) {
            $this->_saveAttributeStoreData();
        }
    }

    /**
     * Perform actions after object load
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();
        $this->assignAttributesStoreData();
        return $this;
    }

    /**
     * Callback function for sorting attributes by sort_order param
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    protected function _sortAttributes($a, $b)
    {
        if ($a['sort_order'] != $b['sort_order']) {
            return $a['sort_order'] > $b['sort_order'] ? 1 : -1;
        }
        return 0;
    }

    /**
     * Set store id
     *
     * @param null|string|bool|int|Store $storeId
     * @return $this
     */
    public function setStoreId($storeId = null)
    {
        $this->_store = $this->storeManager->getStore($storeId);
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
     * Retrieve store id
     *
     * @return int
     * @codeCoverageIgnore
     */
    public function getStoreId()
    {
        return $this->getStore()->getId();
    }

    /**
     * Save registry type attribute data per store view
     *
     * @return $this
     */
    protected function _saveAttributeStoreData()
    {
        $groups = $this->getAttributes();
        if ($groups) {
            foreach ((array)$groups as $attributes) {
                foreach ((array)$attributes as $attribute) {
                    $this->_getResource()->saveStoreData($this, $attribute);
                    if (isset($attribute['options']) && is_array($attribute['options'])) {
                        foreach ($attribute['options'] as $option) {
                            $optionCode = $option['code'];
                            $option['code'] = $attribute['code'];
                            $this->_getResource()->saveStoreData($this, $option, $optionCode);
                        }
                    }
                }
            }
        }
        return $this;
    }

    /**
     * Clear object model from data that should be deleted
     *
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _cleanupData()
    {
        $groups = $this->getAttributes();
        if ($groups) {
            $attributesToSave = [];
            $config = $this->attributeConfig;
            foreach ((array)$groups as $group => $attributes) {
                foreach ((array)$attributes as $attribute) {
                    if ($attribute['is_deleted']) {
                        $this->_getResource()->deleteAttributeStoreData($this->getId(), $attribute['code']);
                        if (in_array($attribute['code'], $config->getStaticTypesCodes())) {
                            $this->_getResource()->deleteAttributeValues(
                                $this->getId(),
                                $attribute['code'],
                                $config->isRegistrantAttribute($attribute['code'])
                            );
                        }
                    } else {
                        if (isset($attribute['options']) && is_array($attribute['options'])) {
                            $optionsToSave = [];
                            foreach ($attribute['options'] as $option) {
                                if ($option['is_deleted']) {
                                    $this->_getResource()->deleteAttributeStoreData(
                                        $this->getId(),
                                        $attribute['code'],
                                        $option['code']
                                    );
                                } else {
                                    $optionsToSave[] = $option;
                                }
                            }
                            $attribute['options'] = $optionsToSave;
                        }
                        $attributesToSave[$group][] = $attribute;
                    }
                }
                $this->setAttributes($attributesToSave);
            }
        }
        return $this;
    }

    /**
     * Assign attributes store data
     *
     * @return $this
     */
    public function assignAttributesStoreData()
    {
        $xmlModel = $this->processorFactory->create();
        $groups = $xmlModel->processXml($this->getMetaXml());
        $storeData = [];

        if (is_array($groups)) {
            foreach ($groups as $group => $attributes) {
                if (!empty($attributes)) {
                    $storeData[$group] = $this->getAttributesStoreData($attributes);
                }
            }
        }
        $this->setAttributes($storeData);
        return $this;
    }

    /**
     * Assign attributes store data
     *
     * @param array $attributes
     * @return array
     */
    public function getAttributesStoreData($attributes)
    {
        if (is_array($attributes)) {
            foreach ($attributes as $code => $attribute) {
                $storeLabel = $this->getAttributeStoreData($code);
                if ($storeLabel) {
                    $attributes[$code]['label'] = $storeLabel;
                    $attributes[$code]['default_label'] = $attribute['label'];
                }
                if (isset($attribute['options']) && is_array($attribute['options'])) {
                    $options = [];
                    foreach ($attribute['options'] as $key => $label) {
                        $data = ['code' => $key, 'label' => $label];
                        $storeLabel = $this->getAttributeStoreData($code, $key);
                        if ($storeLabel) {
                            $data['label'] = $storeLabel;
                            $data['default_label'] = $label;
                        }
                        $options[] = $data;
                    }
                    $attributes[$code]['options'] = $options;
                }
            }
            uasort($attributes, [$this, '_sortAttributes']);
        }
        return $attributes;
    }

    /**
     * Retrieve attribute store label
     *
     * @param string $attributeCode
     * @param string $optionCode
     * @return string
     */
    public function getAttributeStoreData($attributeCode, $optionCode = '')
    {
        if ($this->_storeData === null) {
            $this->_storeData = $this->_getResource()->getAttributesStoreData($this);
        }

        if (is_array($this->_storeData)) {
            foreach ($this->_storeData as $item) {
                if ($item['attribute_code'] == $attributeCode && $item['option_code'] == $optionCode) {
                    return $item['label'];
                }
            }
        }
        return '';
    }

    /**
     * Retrieve attribute by code
     *
     * @param string $code
     * @return null|array
     */
    public function getAttributeByCode($code)
    {
        if (!$this->getId() || empty($code)) {
            return null;
        }
        $groups = $this->getAttributes();
        if ($groups) {
            foreach ($groups as $group) {
                if (isset($group[$code])) {
                    return $group[$code];
                }
            }
        }
        return null;
    }

    /**
     * Retrieve attribute label by code
     *
     * @param string $attributeCode
     * @return string
     */
    public function getAttributeLabel($attributeCode)
    {
        $attribute = $this->getAttributeByCode($attributeCode);
        if ($attribute && isset($attribute['label'])) {
            return $attribute['label'];
        }
        return '';
    }

    /**
     * Retrieve attribute option label by code
     *
     * @param string $attributeCode
     * @param string $optionCode
     * @return string
     */
    public function getOptionLabel($attributeCode, $optionCode)
    {
        $attribute = $this->getAttributeByCode($attributeCode);
        if ($attribute && isset($attribute['options']) && is_array($attribute['options'])) {
            foreach ($attribute['options'] as $option) {
                if ($option['code'] == $optionCode) {
                    return $option['label'];
                }
            }
        }
        return '';
    }

    /**
     * Retrieve listed static attributes list from type attributes list
     *
     * @return array
     */
    public function getListedAttributes()
    {
        $listedAttributes = [];
        if ($this->getAttributes()) {
            $staticCodes = $this->attributeConfig->getStaticTypesCodes();
            foreach ($this->getAttributes() as $group) {
                foreach ($group as $code => $attribute) {
                    if (in_array($code, $staticCodes) && !empty($attribute['frontend']['is_listed'])) {
                        $listedAttributes[$code] = $attribute['label'];
                    }
                }
            }
        }
        return $listedAttributes;
    }

    /**
     * Filter and load post data to object
     *
     * @param array $data
     * @return $this
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function loadPost(array $data)
    {
        $type = $data['type'];
        $this->setCode($type['code']);

        $attributes = isset($data['attributes']) ? $data['attributes'] : null;
        $this->setAttributes($attributes);

        $label = isset($type['label']) ? $type['label'] : null;
        $this->setLabel($label);

        $sortOrder = isset($type['sort_order']) ? $type['sort_order'] : null;
        $this->setSortOrder($sortOrder);

        $isListed = isset($type['is_listed']) ? $type['is_listed'] : null;
        $this->setIsListed($isListed);

        return $this;
    }
}
