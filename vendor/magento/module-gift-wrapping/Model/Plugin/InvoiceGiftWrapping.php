<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftWrapping\Model\Plugin;

use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\InvoiceExtensionFactory;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Api\Data\InvoiceSearchResultInterface;

class InvoiceGiftWrapping
{
    /**
     * @var InvoiceExtensionFactory
     */
    private $invoiceExtensionFactory;

    /**
     * Init plugin
     *
     * @param InvoiceExtensionFactory $invoiceExtensionFactory
     */
    public function __construct(
        InvoiceExtensionFactory $invoiceExtensionFactory
    ) {
        $this->invoiceExtensionFactory = $invoiceExtensionFactory;
    }

    /**
     * Get Gift Wrapping
     *
     * @param InvoiceRepositoryInterface $subject
     * @param InvoiceInterface $invoice
     * @return InvoiceInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(
        InvoiceRepositoryInterface $subject,
        InvoiceInterface $invoice
    ) {
        /** @var \Magento\Sales\Api\Data\InvoiceExtension $extensionAttributes */
        $extensionAttributes = $invoice->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->invoiceExtensionFactory->create();
        }

        $extensionAttributes->setGwBasePrice($invoice->getGwBasePrice());
        $extensionAttributes->setGwPrice($invoice->getGwPrice());
        $extensionAttributes->setGwItemsBasePrice($invoice->getGwItemsBasePrice());
        $extensionAttributes->setGwItemsPrice($invoice->getGwItemsPrice());
        $extensionAttributes->setGwCardBasePrice($invoice->getGwCardBasePrice());
        $extensionAttributes->setGwCardPrice($invoice->getGwCardPrice());
        $extensionAttributes->setGwBaseTaxAmount($invoice->getGwBaseTaxAmount());
        $extensionAttributes->setGwTaxAmount($invoice->getGwTaxAmount());
        $extensionAttributes->setGwItemsBaseTaxAmount($invoice->getGwItemsBaseTaxAmount());
        $extensionAttributes->setGwItemsTaxAmount($invoice->getGwItemsTaxAmount());
        $extensionAttributes->setGwCardBaseTaxAmount($invoice->getGwCardBaseTaxAmount());
        $extensionAttributes->setGwCardTaxAmount($invoice->getGwCardTaxAmount());

        $invoice->setExtensionAttributes($extensionAttributes);

        return $invoice;
    }

    /**
     * @param InvoiceRepositoryInterface $subject
     * @param InvoiceSearchResultInterface $invoiceSearchResult
     * @return InvoiceSearchResultInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetList(
        InvoiceRepositoryInterface $subject,
        InvoiceSearchResultInterface $invoiceSearchResult
    ) {
        /** @var InvoiceInterface $entity */
        foreach ($invoiceSearchResult->getItems() as $invoice) {
            $this->afterGet($subject, $invoice);
        }
        return $invoiceSearchResult;
    }
}
