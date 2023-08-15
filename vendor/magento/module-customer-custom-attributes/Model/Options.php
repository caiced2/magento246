<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Model;

use Magento\Customer\Model\ResourceModel\Attribute\Collection;
use Magento\Customer\Model\ResourceModel\Address\Attribute\Collection as AddressCollection;
use Magento\Customer\Helper\Address as AddressHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Escaper;

/**
 * Options provider for prefix and suffix customer attributes
 */
class Options extends \Magento\Customer\Model\Options
{
    /**
     * @var \Magento\Customer\Model\ResourceModel\Attribute\Collection
     */
    private $attributeCollection;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Address\Attribute\Collection
     */
    private $addressAttributeCollection;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param \Magento\Customer\Model\ResourceModel\Attribute\Collection $attributeCollection
     * @param \Magento\Customer\Model\ResourceModel\Address\Attribute\Collection $addressAttributeCollection
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Helper\Address $addressHelper
     * @param \Magento\Framework\Escaper $escaper
     */
    public function __construct(
        Collection $attributeCollection,
        AddressCollection $addressAttributeCollection,
        StoreManagerInterface $storeManager,
        AddressHelper $addressHelper,
        Escaper $escaper
    ) {
        parent::__construct($addressHelper, $escaper);
        $this->attributeCollection = $attributeCollection;
        $this->addressAttributeCollection = $addressAttributeCollection;
        $this->storeManager = $storeManager;
    }

    /**
     * Get name prefix attribute options for specified entity type
     *
     * @param int|null $store
     * @param string $entityType
     *
     * @return array|bool
     */
    public function getNamePrefixOptions($store = null, string $entityType = 'customer_address')
    {
        return $this->prepareNamePrefixSuffixOptions(
            $this->addressHelper->getConfig('prefix_options', $store),
            !$this->isAttributeRequired($entityType, 'prefix', $store)
        );
    }

    /**
     * Get name suffix attribute options for specified entity type
     *
     * @param int|null $store
     * @param string $entityType
     *
     * @return array|bool
     */
    public function getNameSuffixOptions($store = null, string $entityType = 'customer_address')
    {
        return $this->prepareNamePrefixSuffixOptions(
            $this->addressHelper->getConfig('suffix_options', $store),
            !$this->isAttributeRequired($entityType, 'suffix', $store)
        );
    }

    /**
     * Check if entity attribute is required for specified store
     *
     * @param string $entityType
     * @param string $attributeCode
     * @param int|null $store
     *
     * @return bool|mixed
     */
    private function isAttributeRequired(string $entityType, string $attributeCode, $store)
    {
        $store = $this->storeManager->getStore($store);
        $websiteId = $store->getWebsiteId();
        $collection = $this->addressAttributeCollection;

        if ($entityType === 'customer') {
            $collection = $this->attributeCollection;
        }

        $collection = clone $collection;
        /** @var \Magento\Eav\Model\Entity\Attribute $attribute */
        $attribute = $collection->clear()
            ->setCodeFilter($attributeCode)
            ->setWebsite($websiteId)
            ->getFirstItem();

        if (!$attribute) {
            return false;
        }

        return $attribute->getScopeIsRequired() !== null
            ? $attribute->getScopeIsRequired()
            : $attribute->getIsRequired();
    }

    /**
     * Prepare array of name prefix/suffix options
     *
     * @param string|null $options
     * @param bool $isOptional
     *
     * @return array|bool
     */
    private function prepareNamePrefixSuffixOptions($options, bool $isOptional = false)
    {
        if (!$options) {
            return false;
        }
        $options = trim($options);
        if (empty($options)) {
            return false;
        }
        $result = [];
        $options = explode(';', $options);
        foreach ($options as $value) {
            $value = $this->escaper->escapeHtml(trim($value));
            $result[$value] = $value;
        }
        if ($isOptional && trim(current($options))) {
            $result = array_merge([' ' => ' '], $result);
        }

        return $result;
    }
}
