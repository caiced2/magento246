<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Block\Customer\Edit;

/**
 * Customer giftregistry list block
 *
 * @api
 * @since 100.0.2
 */
class Registrants extends AbstractEdit
{
    /**
     * Scope Selector 'registry/registrant'
     *
     * @var string
     */
    protected $_prefix = 'registrant';

    /**
     * Gift registry data
     *
     * @var \Magento\GiftRegistry\Helper\Data
     */
    protected $_giftRegistryData = null;

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
     * @param \Magento\GiftRegistry\Helper\Data $giftRegistryData
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
        \Magento\GiftRegistry\Helper\Data $giftRegistryData,
        array $data = []
    ) {
        $this->_giftRegistryData = $giftRegistryData;
        parent::__construct(
            $context,
            $directoryHelper,
            $jsonEncoder,
            $configCacheType,
            $regionCollectionFactory,
            $countryCollectionFactory,
            $registry,
            $customerSession,
            $attributeConfig,
            $data
        );
    }

    /**
     * Retrieve Max Recipients
     *
     * @return int
     * @codeCoverageIgnore
     */
    public function getMaxRegistrant()
    {
        return $this->_giftRegistryData->getMaxRegistrant();
    }

    /**
     * Return array of attributes groupped by group
     *
     * @return array
     * @codeCoverageIgnore
     */
    public function getGroupedRegistrantAttributes()
    {
        return $this->getGroupedAttributes();
    }

    /**
     * Return registrant collection
     *
     * @return \Magento\GiftRegistry\Model\ResourceModel\Person\Collection
     * @codeCoverageIgnore
     */
    public function getRegistrantList()
    {
        return $this->getEntity()->getRegistrantsCollection();
    }

    /**
     * Reorder attributes array  by group
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
                    $grouped[$field] = $fdata;
                    $grouped[$field]['id'] = $this->_getElementId($field);
                    $grouped[$field]['name'] = $this->_getElementName($field);
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
        $custom = $this->isAttributeStatic($code) ? '' : '[custom]';
        return $this->_prefix . '[<%- data._index_ %>]' . $custom . '[' . $code . ']';
    }

    /**
     * Prepare html element id
     *
     * @param string $code
     * @return string
     */
    protected function _getElementId($code)
    {
        $custom = $this->isAttributeStatic($code) ? '' : 'custom:';
        return $this->_prefix . ':' . $custom . $code . '<%- data._index_ %>';
    }

    /**
     * Get current registrant info , formatted in php array of JSON data
     *
     * @param int $entityId id of the giftregistry entity
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getRegistrantPresets($entityId)
    {
        $data = [];
        $registrantCollection = $this->getEntity()->getRegistrantsCollection();
        foreach ($registrantCollection->getItems() as $registrant) {
            $data[] = $registrant->unserialiseCustom()->getData();
        }
        return $data;
    }
}
