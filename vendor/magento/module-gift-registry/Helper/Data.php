<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftRegistry\Helper;

use Magento\Catalog\Model\Product;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filter\LocalizedToNormalized;
use Magento\Framework\Filter\NormalizedToLocalized;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\GiftRegistry\Model\Entity;
use Magento\GiftRegistry\Model\EntityFactory;
use Magento\GiftRegistry\Model\ResourceModel\GiftRegistry\Collection;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Gift Registry helper
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 * @api
 * @since 100.0.2
 */
class Data extends AbstractHelper
{
    public const XML_PATH_ENABLED = 'magento_giftregistry/general/enabled';

    public const XML_PATH_SEND_LIMIT = 'magento_giftregistry/sharing_email/send_limit';

    public const XML_PATH_MAX_REGISTRANT = 'magento_giftregistry/general/max_registrant';

    public const ADDRESS_PREFIX = 'gr_address_';

    /**
     * Option for address source selector.
     *
     * @var string
     */
    public const ADDRESS_NEW = 'new';

    /**
     * Option for address source selector.
     *
     * @var string
     */
    public const ADDRESS_NONE = 'none';

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var EntityFactory
     */
    protected $entityFactory;

    /**
     * @var TimezoneInterface
     */
    protected $_localeDate;

    /**
     * @var Escaper
     */
    protected $_escaper;

    /**
     * @var ResolverInterface
     */
    protected $_localeResolver;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param EntityFactory $entityFactory
     * @param TimezoneInterface $localeDate
     * @param Escaper $escaper
     * @param ResolverInterface $localeResolver
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        EntityFactory $entityFactory,
        TimezoneInterface $localeDate,
        Escaper $escaper,
        ResolverInterface $localeResolver,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->entityFactory = $entityFactory;
        $this->_localeDate = $localeDate;
        $this->_escaper = $escaper;
        $this->_localeResolver = $localeResolver;
        $this->_storeManager = $storeManager;
    }

    /**
     * Check whether gift registry is enabled
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function isEnabled()
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieve sharing recipients limit config data
     *
     * @return int
     * @codeCoverageIgnore
     */
    public function getRecipientsLimit()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_SEND_LIMIT,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieve address prefix
     *
     * @return int
     * @codeCoverageIgnore
     */
    public function getAddressIdPrefix()
    {
        return self::ADDRESS_PREFIX;
    }

    /**
     * Retrieve Max Recipients
     *
     * @param int $store
     * @return int
     * @codeCoverageIgnore
     */
    public function getMaxRegistrant($store = null)
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_MAX_REGISTRANT,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Validate custom attributes values
     *
     * @param array $customValues
     * @param array $attributes
     * @return array|bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function validateCustomAttributes($customValues, $attributes)
    {
        $errors = [];
        foreach ($attributes as $field => $data) {
            if (empty($customValues[$field])) {
                if (!empty($data['frontend']) && is_array(
                    $data['frontend']
                ) && !empty($data['frontend']['is_required'])
                ) {
                    $errors[] = __('Please enter the "%1".', $data['label']);
                }
            } else {
                if ($data['type'] == 'select' && is_array($data['options'])) {
                    $found = false;
                    foreach ($data['options'] as $option) {
                        if ($customValues[$field] == $option['code']) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $errors[] = __('Please enter the correct "%1".', $data['label']);
                    }
                }
            }
        }
        if (empty($errors)) {
            return true;
        }
        return $errors;
    }

    /**
     * Return list of gift registries
     *
     * @return Collection
     */
    public function getCurrentCustomerEntityOptions()
    {
        $result = [];
        $entityCollection = $this->entityFactory->create()->getCollection()->filterByCustomerId(
            $this->customerSession->getCustomerId()
        )->filterByIsActive(
            1
        );

        if (count($entityCollection)) {
            foreach ($entityCollection as $entity) {
                $result[] = new \Magento\Framework\DataObject(
                    ['value' => $entity->getId(), 'title' => $this->_escaper->escapeHtml($entity->getTitle())]
                );
            }
        }
        return $result;
    }

    /**
     * Format custom dates to internal format
     *
     * @param array|string $data
     * @param array $fieldDateFormats
     * @return array|string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function filterDatesByFormat($data, $fieldDateFormats)
    {
        if (!is_array($data)) {
            return $data;
        }
        foreach ($data as $id => $field) {
            if (!empty($data[$id])) {
                if (!is_array($field)) {
                    if (isset($fieldDateFormats[$id])) {
                        $data[$id] = $this->_filterDate($data[$id], $fieldDateFormats[$id]);
                    }
                } else {
                    foreach ($field as $id2 => $field2) {
                        if (!empty($data[$id][$id2]) && !is_array($field2) && isset($fieldDateFormats[$id2])) {
                            $data[$id][$id2] = $this->_filterDate($data[$id][$id2], $fieldDateFormats[$id2]);
                        }
                    }
                }
            }
        }
        return $data;
    }

    /**
     * Convert date in from <$formatIn> to internal format
     *
     * @param string $value
     * @param string|bool $formatIn - FORMAT_TYPE_FULL, FORMAT_TYPE_LONG, FORMAT_TYPE_MEDIUM, FORMAT_TYPE_SHORT
     * @return string
     */
    public function _filterDate($value, $formatIn = false)
    {
        if ($formatIn === false) {
            return $value;
        }

        $formatIn = $this->_localeDate->getDateFormat($formatIn);
        $filterInput = new LocalizedToNormalized(
            ['date_format' => $formatIn, 'locale' => $this->_localeResolver->getLocale()]
        );
        $filterInternal = new NormalizedToLocalized(
            ['date_format' => DateTime::DATE_INTERNAL_FORMAT]
        );
        $value = $filterInput->filter($value);

        return $filterInternal->filter($value);
    }

    /**
     * Return frontend registry link
     *
     * @param Entity $entity
     * @return string
     * @codeCoverageIgnore
     * @throws NoSuchEntityException
     */
    public function getRegistryLink($entity)
    {
        return $this->_storeManager->getStore($entity->getStoreId())
            ->getUrl(
                'giftregistry/view/index',
                ['id' => $entity->getUrlKey()]
            );
    }

    /**
     * Check if product can be added to gift registry
     *
     * @param Item|Product $item
     * @return bool
     */
    public function canAddToGiftRegistry($item)
    {
        return !$item->getIsVirtual();
    }
}
