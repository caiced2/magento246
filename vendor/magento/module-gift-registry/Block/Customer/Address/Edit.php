<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Block\Customer\Address;

use Magento\Customer\Model\Context;
use Magento\Framework\App\ObjectManager;

/**
 * GiftRegistry shipping Address block
 *
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class Edit extends \Magento\GiftRegistry\Block\Customer\Edit\AbstractEdit
{
    /**
     * Contains logged in customer
     *
     * @var \Magento\Customer\Model\Customer
     */
    protected $_customer;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

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
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param array $data
     * @param \Magento\Customer\Helper\Address|null $addressHelper
     *
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
        \Magento\Framework\App\Http\Context $httpContext,
        array $data = [],
        \Magento\Customer\Helper\Address $addressHelper = null
    ) {
        $data['addressHelper'] = $addressHelper ?? ObjectManager::getInstance()
                ->get(\Magento\Customer\Helper\Address::class);
        $data['directoryHelper'] = $directoryHelper;
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
        $this->httpContext = $httpContext;
    }

    /**
     * Getter for entity object.
     *
     * @return \Magento\GiftRegistry\Model\Entity
     */
    public function getEntity()
    {
        return $this->_registry->registry('magento_giftregistry_entity');
    }

    /**
     * Getter for address object
     *
     * @return \Magento\Customer\Model\Address
     * @codeCoverageIgnore
     */
    public function getAddress()
    {
        return $this->_registry->registry('magento_giftregistry_address');
    }

    /**
     * Check customer has address
     *
     * @return bool
     */
    public function customerHasAddresses()
    {
        return count($this->getCustomer()->getAddresses());
    }

    /**
     * Return html select input element for Address (None/<address1>/<address2>/New/)
     *
     * @param string $domId
     * @return string
     */
    public function getAddressHtmlSelect($domId = 'address_type_or_id')
    {
        if ($this->isCustomerLoggedIn()) {
            $options = [
                [
                    'value' => \Magento\GiftRegistry\Helper\Data::ADDRESS_NONE,
                    'label' => $this->escapeHtmlAttr(__('None'))
                ]
            ];
            foreach ($this->getCustomer()->getAddresses() as $address) {
                $options[] = ['value' => $address->getId(), 'label' => $address->format('oneline')];
            }
            $options[] = [
                'value' => \Magento\GiftRegistry\Helper\Data::ADDRESS_NEW,
                'label' => $this->escapeHtmlAttr(__('New Address')),
            ];

            $select = $this->getLayout()->createBlock(
                \Magento\Framework\View\Element\Html\Select::class
            )->setName(
                'address_type_or_id'
            )->setId(
                $domId
            )->setClass(
                'address-select'
            )->setOptions(
                $options
            );

            return $select->getHtml();
        }
        return '';
    }

    /**
     * Get logged in customer
     *
     * @return \Magento\Customer\Model\Customer
     */
    public function getCustomer()
    {
        if (empty($this->_customer)) {
            $this->_customer = $this->customerSession->getCustomer();
        }
        return $this->_customer;
    }

    /**
     * Checking customer login status
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function isCustomerLoggedIn()
    {
        return $this->httpContext->getValue(Context::CONTEXT_AUTH);
    }
}
