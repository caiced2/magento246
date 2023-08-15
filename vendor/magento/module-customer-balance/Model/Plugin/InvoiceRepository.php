<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Model\Plugin;

use Magento\Sales\Api\Data\InvoiceExtension;
use Magento\Sales\Api\Data\InvoiceExtensionFactory;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\InvoiceSearchResultInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;

/**
 * Plugin for Invoice repository
 */
class InvoiceRepository
{
    /**
     * @var InvoiceExtensionFactory
     */
    private $extensionFactory;

    /**
     * Init plugin
     *
     * @param InvoiceExtensionFactory $invoiceExtensionFactory
     */
    public function __construct(
        InvoiceExtensionFactory $invoiceExtensionFactory
    ) {
        $this->extensionFactory = $invoiceExtensionFactory;
    }

    /**
     * Get invoice customer balance
     *
     * @param InvoiceRepositoryInterface $subject
     * @param InvoiceInterface $resultEntity
     * @return InvoiceInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(
        InvoiceRepositoryInterface $subject,
        InvoiceInterface $resultEntity
    ) {
        /** @var InvoiceExtension $extensionAttributes */
        $extensionAttributes = $resultEntity->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->extensionFactory->create();
        }

        $extensionAttributes->setBaseCustomerBalanceAmount($resultEntity->getBaseCustomerBalanceAmount());
        $extensionAttributes->setCustomerBalanceAmount($resultEntity->getCustomerBalanceAmount());
        $resultEntity->setExtensionAttributes($extensionAttributes);

        return $resultEntity;
    }

    /**
     * Add customer balance amount information to invoice list
     *
     * @param InvoiceRepositoryInterface $subject
     * @param InvoiceSearchResultInterface $resultInvoice
     * @return InvoiceSearchResultInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetList(
        InvoiceRepositoryInterface $subject,
        InvoiceSearchResultInterface $resultInvoice
    ) {
        /** @var InvoiceInterface $invoice */
        foreach ($resultInvoice->getItems() as $invoice) {
            $this->afterGet($subject, $invoice);
        }
        return $resultInvoice;
    }

    /**
     * Add customer balance amount information to invoice
     *
     * @param InvoiceRepositoryInterface $subject
     * @param InvoiceInterface $entity
     *
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

        if ($extensionAttributes->getCustomerBalanceAmount() !== null) {
            $entity->setCustomerBalanceAmount($extensionAttributes->getCustomerBalanceAmount());
        }
        if ($extensionAttributes->getBaseCustomerBalanceAmount() !== null) {
            $entity->setBaseCustomerBalanceAmount($extensionAttributes->getBaseCustomerBalanceAmount());
        }
    }
}
