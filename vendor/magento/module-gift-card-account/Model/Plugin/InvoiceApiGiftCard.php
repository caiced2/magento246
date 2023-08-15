<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccount\Model\Plugin;

use Magento\Sales\Api\Data\InvoiceExtension;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\InvoiceExtensionFactory;
use Magento\Sales\Model\Order\InvoiceDocumentFactory;

/**
 * Plugin for Invoice API to set the gift card amount.
 */
class InvoiceApiGiftCard
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
     * Set Gift Card Amount after Invoice create
     *
     * @param InvoiceDocumentFactory $subject
     * @param InvoiceInterface $invoice
     * @return InvoiceInterface $invoice
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCreate(
        InvoiceDocumentFactory $subject,
        InvoiceInterface $invoice
    ): InvoiceInterface {
        /** @var InvoiceExtension $extensionAttributes */
        $extensionAttributes = $invoice->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->invoiceExtensionFactory->create();
        }
        $extensionAttributes->setGiftCardsAmount($invoice->getGiftCardsAmount());
        $extensionAttributes->setBaseGiftCardsAmount($invoice->getBaseGiftCardsAmount());
        $invoice->setExtensionAttributes($extensionAttributes);
        return $invoice;
    }
}
