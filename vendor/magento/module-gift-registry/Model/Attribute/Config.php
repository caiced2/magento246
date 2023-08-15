<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Model\Attribute;

/**
 * Gift registry attributes config model
 */
class Config implements ConfigInterface
{
    /**
     * Modules configuration model
     *
     * @var \Magento\GiftRegistry\Model\Config\Data
     */
    protected $_dataContainer;

    /**
     * @param \Magento\GiftRegistry\Model\Config\Data $dataContainer
     */
    public function __construct(\Magento\GiftRegistry\Model\Config\Data $dataContainer)
    {
        $this->_dataContainer = $dataContainer;
    }

    /**
     * Return array of default options
     *
     * @return array
     * @codeCoverageIgnore
     */
    protected function _getDefaultOption()
    {
        return [['value' => '', 'label' => __('-- Please select --')]];
    }

    /**
     * Return array of attribute types for using as options
     *
     * @return array
     */
    public function getAttributeTypesOptions()
    {
        $options = array_merge(
            $this->_getDefaultOption(),
            [
                ['label' => __('Custom Types'), 'value' => $this->getAttributeCustomTypesOptions()],
                ['label' => __('Static Types'), 'value' => $this->getAttributeStaticTypesOptions()]
            ]
        );
        return $options;
    }

    /**
     * Return array of attribute groups for using as options
     *
     * @return array
     */
    public function getAttributeGroupsOptions()
    {
        $options = $this->_getDefaultOption();
        $groups = $this->getAttributeGroups();

        if (is_array($groups)) {
            foreach ($groups as $code => $group) {
                if ($group['visible']) {
                    $options[] = ['value' => $code, 'label' => __($group['label'])];
                }
            }
        }
        return $options;
    }

    /**
     * Return array of attribute groups
     *
     * @return array|mixed
     * @codeCoverageIgnore
     */
    public function getAttributeGroups()
    {
        return $this->_dataContainer->get('attribute_groups');
    }

    /**
     * Return array of static attribute types for using as options
     *
     * @return array
     */
    public function getStaticTypes()
    {
        $staticTypes = [];

        foreach (['registry', 'registrant'] as $section) {
            $sectionArray = $this->_dataContainer->get($section);
            $staticTypes = array_merge($staticTypes, $sectionArray['static_attributes']);
        }

        return $staticTypes;
    }

    /**
     * Return array of codes of static attribute types
     *
     * @return array
     * @codeCoverageIgnore
     */
    public function getStaticTypesCodes()
    {
        return array_keys($this->getStaticTypes());
    }

    /**
     * Check if attribute is in registrant group
     *
     * @param string $attribute
     * @return bool
     */
    public function isRegistrantAttribute($attribute)
    {
        foreach ($this->getStaticTypes() as $code => $data) {
            if ($attribute == $code && $data['group'] == 'registrant') {
                return true;
            }
        }
        return false;
    }

    /**
     * Return code of static date attribute type
     *
     * @return null|string
     */
    public function getStaticDateType()
    {
        foreach ($this->getStaticTypes() as $code => $type) {
            if (isset($type['type']) && $type['type'] == 'date') {
                return $code;
            }
        }
        return null;
    }

    /**
     * Return code of static region attribute type
     *
     * @return null|string
     */
    public function getStaticRegionType()
    {
        foreach ($this->getStaticTypes() as $code => $type) {
            if (isset($type['type']) && $type['type'] == 'region') {
                return $code;
            }
        }
        return null;
    }

    /**
     * Return array of custom attribute types for using as options
     *
     * @return array
     */
    public function getAttributeCustomTypesOptions()
    {
        $types = $this->_dataContainer->get('attribute_types');
        $options = [];

        foreach ($types as $code => $type) {
            $options[] = ['value' => $code, 'label' => __($type['label'])];
        }
        return $options;
    }

    /**
     * Return array of static attribute types for using as options
     *
     * @return array
     */
    public function getAttributeStaticTypesOptions()
    {
        $options = [];
        foreach ($this->getStaticTypes() as $code => $type) {
            if ($type['visible'] !== 'true') {
                continue;
            }
            $valueParts = [$type['type'], $code];
            if (!empty($type['group'])) {
                $valueParts[] = $type['group'];
            }

            $options[] = ['value' => implode(':', $valueParts), 'label' => __($type['label'])];
        }
        return $options;
    }
}
