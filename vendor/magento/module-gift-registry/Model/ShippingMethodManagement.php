<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftRegistry\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\GiftRegistry\Api\ShippingMethodManagementInterface;

/**
 * Shipping method read service.
 */
class ShippingMethodManagement implements ShippingMethodManagementInterface
{
    /**
     * Entity factory.
     *
     * @var EntityFactory
     */
    protected $entityFactory;

    /**
     * Shipping address management.
     *
     * @var \Magento\Quote\Api\ShippingMethodManagementInterface
     */
    protected $shippingMethodManagement;

    /**
     * Shipping method data factory.
     *
     * @var \Magento\Quote\Api\Data\EstimateAddressInterfaceFactory
     */
    protected $estimatedAddressFactory;

    /**
     * @param EntityFactory $entityFactory
     * @param \Magento\Quote\Api\ShippingMethodManagementInterface $shippingMethodManagement
     * @param \Magento\Quote\Api\Data\EstimateAddressInterfaceFactory $estimatedAddressFactory
     */
    public function __construct(
        EntityFactory $entityFactory,
        \Magento\Quote\Api\ShippingMethodManagementInterface $shippingMethodManagement,
        \Magento\Quote\Api\Data\EstimateAddressInterfaceFactory $estimatedAddressFactory
    ) {
        $this->entityFactory = $entityFactory;
        $this->shippingMethodManagement = $shippingMethodManagement;
        $this->estimatedAddressFactory = $estimatedAddressFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function estimateByRegistryId($cartId, $registryId)
    {
        $giftRegistry =  $this->entityFactory->create();
        $giftRegistry->loadByEntityItem($registryId);

        if (!$giftRegistry->getId()) {
            throw new NoSuchEntityException(__('Unknown gift registry identifier'));
        }
        
        $address = $giftRegistry->exportAddress();
        /** @var \Magento\Quote\Api\Data\EstimateAddressInterface $estimatedAddress */
        $estimatedAddress = $this->estimatedAddressFactory->create();
        $estimatedAddress->setCountryId($address->getCountryId());
        $estimatedAddress->setPostcode($address->getPostcode());
        $estimatedAddress->setRegion($address->getRegion());
        $estimatedAddress->setRegionId($address->getRegionId());
        return $this->shippingMethodManagement->estimateByAddress($cartId, $estimatedAddress);
    }
}
