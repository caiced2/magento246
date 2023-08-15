<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Model\GiftRegistry;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResourceModel;
use Magento\Framework\Exception\LocalizedException;
use Magento\GiftRegistry\Model\Entity as GiftRegistry;

/**
 * Assign the address to git registry
 */
class AddAddressToGiftRegistry
{
    /**
     * @var CustomerResourceModel
     */
    private $customerResourceModel;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var AddressFactory
     */
    private $addressFactory;

    /**
     * @param CustomerResourceModel $customerResourceModel
     * @param CustomerFactory $customerFactory
     * @param AddressFactory $addressFactory
     */
    public function __construct(
        CustomerResourceModel $customerResourceModel,
        CustomerFactory $customerFactory,
        AddressFactory $addressFactory
    ) {
        $this->customerResourceModel = $customerResourceModel;
        $this->customerFactory = $customerFactory;
        $this->addressFactory = $addressFactory;
    }

    /**
     * Adding the address to gift registry
     *
     * @param GiftRegistry $giftRegistry
     * @param array $address
     *
     * @return GiftRegistry
     *
     * @throws LocalizedException
     */
    public function execute(GiftRegistry $giftRegistry, array $address): GiftRegistry
    {
        if (isset($address['address_id'])) {
            $addressId = $address['address_id'];
            $customerModel = $this->customerFactory->create();
            $this->customerResourceModel->load($customerModel, $giftRegistry->getCustomerId());
            $address = $customerModel->getAddressItemById($addressId);

            if ($address === null || (int) $address->getCustomerId() !== (int) $giftRegistry->getCustomerId()) {
                throw new LocalizedException(__('The address is incorrect. Verify and try again.'));
            }
        } else {
            $addressData = $address['address_data'] ?? [];

            if (isset($addressData['country_code'])) {
                $addressData[AddressInterface::COUNTRY_ID] = $addressData['country_code'];
            }

            if (isset($addressData['region'])) {
                $addressData += $addressData['region'];
            }

            if ($giftRegistry->getId()) {
                $address = $giftRegistry->exportAddress();
                $address->addData($addressData);
            } else {
                $address = $this->addressFactory->create(['data' => $addressData]);
            }

            $errors = $address->validate();

            if (is_array($errors)) {
                throw new LocalizedException(__(implode('.', $errors)));
            }
        }

        $giftRegistry->importAddress($address);

        return $giftRegistry;
    }
}
