<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\CheckoutAddressSearch\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\ResourceModel\Address\CollectionFactory as AddressCollectionFactory;

/**
 * Returns collection of customer addresses for onepage checkout address search.
 */
class AddressSearch
{
    /**
     * @var AddressCollectionFactory
     */
    private $shippingAddressCollectionFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param AddressCollectionFactory $shippingAddressCollectionFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param Config $config
     */
    public function __construct(
        AddressCollectionFactory $shippingAddressCollectionFactory,
        CustomerRepositoryInterface $customerRepository,
        Config $config
    ) {
        $this->shippingAddressCollectionFactory = $shippingAddressCollectionFactory;
        $this->customerRepository = $customerRepository;
        $this->config = $config;
    }

    /**
     * Creates address collection and applies filters and customer limitations.
     *
     * @param string $pattern
     * @param int $customerId
     * @param int $pageNum
     * @return \Magento\Customer\Model\ResourceModel\Address\Collection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function search(
        string $pattern,
        int $customerId,
        int $pageNum
    ): \Magento\Customer\Model\ResourceModel\Address\Collection {
        $customer = $this->customerRepository->getById($customerId);
        $pageSize = $this->config->getPageSize();
        /** @var \Magento\Customer\Model\ResourceModel\Address\Collection $addressCollection */
        $addressCollection = $this->shippingAddressCollectionFactory->create();

        // filter only active addresses
        $addressCollection->addFilter('is_active', 1);
        $addressCollection->addAttributeToSelect('*');
        // filter addresses of the certain customer
        $addressCollection->setCustomerFilter($customer);
        // sort by lastly added address
        $addressCollection->setOrder('entity_id', 'desc');
        $addressCollection->setCurPage($pageNum);
        $addressCollection->setPageSize($pageSize);
        $addressCollection->addAttributeToFilter(
            [
                ['attribute' => 'postcode', 'like' => $pattern . '%'],
                ['attribute' => 'region', 'like' => '%' . $pattern . '%'],
                ['attribute' => 'city', 'like' => '%' . $pattern . '%'],
                ['attribute' => 'street', 'like' => '%' . $pattern . '%']
            ]
        );

        return $addressCollection;
    }
}
