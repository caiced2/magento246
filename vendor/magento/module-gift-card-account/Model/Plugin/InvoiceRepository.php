<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Model\Plugin;

use Magento\Sales\Api\Data\InvoiceExtension;
use Magento\Sales\Api\Data\InvoiceExtensionFactory;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\InvoiceSearchResultInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;

/**
 * Plugin for Invoice repository.
 */
class InvoiceRepository
{
    /**
     * @var InvoiceExtensionFactory
     */
    private $extensionFactory;

    /**
     * @param InvoiceExtensionFactory $extensionFactory
     */
    public function __construct(
        InvoiceExtensionFactory $extensionFactory
    ) {
        $this->extensionFactory = $extensionFactory;
    }

    /**
     * Sets gift card account data from extension attributes to Invoice models after get
     *
     * @param InvoiceRepositoryInterface $subject
     * @param InvoiceInterface $entity
     * @return InvoiceInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(
        InvoiceRepositoryInterface $subject,
        InvoiceInterface $entity
    ) {
        /** @var InvoiceExtension $extensionAttributes */
        $extensionAttributes = $entity->getExtensionAttributes();

        if ($extensionAttributes === null) {
            $extensionAttributes = $this->extensionFactory->create();
        }

        $extensionAttributes->setBaseGiftCardsAmount($entity->getBaseGiftCardsAmount());
        $extensionAttributes->setGiftCardsAmount($entity->getGiftCardsAmount());

        $entity->setExtensionAttributes($extensionAttributes);

        return $entity;
    }

    /**
     * Sets gift card account data from extension attributes to Invoice models after get list
     *
     * @param InvoiceRepositoryInterface $subject
     * @param InvoiceSearchResultInterface $entities
     * @return InvoiceSearchResultInterface
     */
    public function afterGetList(
        InvoiceRepositoryInterface $subject,
        InvoiceSearchResultInterface $entities
    ) {
        /** @var InvoiceInterface $entity */
        foreach ($entities->getItems() as $entity) {
            $this->afterGet($subject, $entity);
        }

        return $entities;
    }

    /**
     * Sets gift card account data from extension attributes to Invoice model before save
     *
     * @param InvoiceRepositoryInterface $subject
     * @param InvoiceInterface $entity
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSave(
        InvoiceRepositoryInterface $subject,
        InvoiceInterface $entity
    ) {
        $extensionAttributes = $entity->getExtensionAttributes();
        if (!$extensionAttributes) {
            return;
        }

        if ($extensionAttributes->getGiftCardsAmount() !== null) {
            $entity->setGiftCardsAmount($extensionAttributes->getGiftCardsAmount());
        }
        if ($extensionAttributes->getBaseGiftCardsAmount() !== null) {
            $entity->setBaseGiftCardsAmount($extensionAttributes->getBaseGiftCardsAmount());
        }
    }
}
