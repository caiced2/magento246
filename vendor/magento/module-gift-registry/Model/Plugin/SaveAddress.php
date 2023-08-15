<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Model\Plugin;

class SaveAddress
{
    /**
     * @var \Magento\GiftRegistry\Model\EntityFactory
     */
    protected $entityFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @param \Magento\GiftRegistry\Model\EntityFactory $entityFactory
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        \Magento\GiftRegistry\Model\EntityFactory $entityFactory,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->entityFactory = $entityFactory;
        $this->customerSession = $customerSession;
    }

    /**
     * @param \Magento\Checkout\Api\ShippingInformationManagementInterface $subject
     * @param int $cartId
     * @param \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSaveAddressInformation(
        \Magento\Checkout\Api\ShippingInformationManagementInterface $subject,
        $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
    ) {
        $shippingAddress = $addressInformation->getShippingAddress();
        if ($shippingAddress->getExtensionAttributes()
            && $shippingAddress->getExtensionAttributes()->getGiftRegistryId()
        ) {
            $giftRegistry = $this->entityFactory->create()->loadByEntityItem(
                $shippingAddress->getExtensionAttributes()->getGiftRegistryId()
            );
            if ($giftRegistry->getId()) {
                $shippingAddress->setCustomerAddressId($this->customerSession->getCustomerId());
                $shippingAddress->importCustomerAddressData($giftRegistry->exportAddressData());
                $shippingAddress->setGiftregistryItemId($giftRegistry->getId());
            }
        }
    }
}
