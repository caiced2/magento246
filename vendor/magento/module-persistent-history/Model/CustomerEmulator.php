<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PersistentHistory\Model;

use \Magento\Framework\Exception\NoSuchEntityException;

/**
 * Emulates customer
 */
class CustomerEmulator
{
    /**
     * Persistent session
     *
     * @var \Magento\Persistent\Helper\Session
     */
    protected $_persistentSession = null;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;

    /**
     * Wishlist data
     *
     * @var \Magento\Wishlist\Helper\Data
     */
    protected $_wishlistData = null;

    /**
     * Persistent data
     *
     * @var \Magento\PersistentHistory\Helper\Data
     */
    protected $_ePersistentData = null;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * Customer repository
     *
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $addressRepository;

    /**
     * @var \Magento\Catalog\Helper\Product\Compare
     */
    private $compareProductHelper;

    /**
     * Constructor
     *
     * @param \Magento\Persistent\Helper\Session $persistentSession
     * @param \Magento\Wishlist\Helper\Data $wishlistData
     * @param \Magento\PersistentHistory\Helper\Data $ePersistentData
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     */
    public function __construct(
        \Magento\Persistent\Helper\Session $persistentSession,
        \Magento\Wishlist\Helper\Data $wishlistData,
        \Magento\PersistentHistory\Helper\Data $ePersistentData,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
    ) {
        $this->_persistentSession = $persistentSession;
        $this->_wishlistData = $wishlistData;
        $this->_ePersistentData = $ePersistentData;
        $this->_coreRegistry = $coreRegistry;
        $this->_customerFactory = $customerFactory;
        $this->_customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->addressRepository = $addressRepository;
    }

    /**
     * Emulate customer
     *
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function emulate()
    {
        /** TODO DataObject should be initialized instead of CustomerModel after refactoring of segment_customer */
        $customerId = $this->_persistentSession->getSession()->getCustomerId();
        /** @var \Magento\Customer\Model\Customer $customer */
        $customer = $this->_customerFactory->create()->load($customerId);
        if ($defaultShipping = $customer->getDefaultShipping()) {
            $address = $this->getCustomerAddressById((int) $defaultShipping);

            if ($address !== null) {
                $this->_customerSession->setDefaultTaxShippingAddress(
                    [
                        'country_id' => $address->getCountryId(),
                        'region_id' => $address->getRegion()
                            ? $address->getRegionId()
                            : null,
                        'postcode' => $address->getPostcode(),
                    ]
                );
            }
        }

        if ($defaultBilling = $customer->getDefaultBilling()) {
            $address = $this->getCustomerAddressById((int) $defaultBilling);

            if ($address !== null) {
                $this->_customerSession->setDefaultTaxBillingAddress([
                    'country_id' => $address->getCountryId(),
                    'region_id' => $address->getRegion() ? $address->getRegionId() : null,
                    'postcode' => $address->getPostcode(),
                ]);
            }
        }
        $this->_customerSession->setCustomerId($customerId)
            ->setCustomerGroupId($customer->getGroupId())
            ->setIsCustomerEmulated(true);

        // apply persistent data to segments
        if ($this->_ePersistentData->isCustomerAndSegmentsPersist()) {
            $this->_coreRegistry->register('segment_customer', $customer, true);
        }

        if ($this->_ePersistentData->isWishlistPersist()) {
            /** @var \Magento\Customer\Api\Data\CustomerInterface $customerDataObject */
            $customerDataObject = $this->customerRepository->getById($customerId);
            $this->_wishlistData->setCustomer($customerDataObject);
        }

        if ($this->_ePersistentData->isCompareProductsPersist()
            || $this->_ePersistentData->isComparedProductsPersist()
        ) {
            $this->getCompareProductHelper()->setCustomerId($customerId);
        }
    }

    /**
     * Returns compare product helper
     *
     * @return \Magento\Catalog\Helper\Product\Compare
     * @deprecated 100.1.0
     */
    private function getCompareProductHelper()
    {
        if (null === $this->compareProductHelper) {
            $this->compareProductHelper = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Catalog\Helper\Product\Compare::class);
        }
        return $this->compareProductHelper;
    }

    /**
     * Returns customer address by id
     *
     * @param int $addressId
     * @return \Magento\Customer\Api\Data\AddressInterface|null
     */
    private function getCustomerAddressById(int $addressId)
    {
        try {
            return $this->addressRepository->getById($addressId);
        } catch (NoSuchEntityException $exception) {
            return null;
        }
    }
}
