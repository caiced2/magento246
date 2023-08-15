<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Magento\SalesArchive\Plugin;

use Magento\Framework\AuthorizationInterface;
use Magento\Sales\Block\Adminhtml\Order\View\Tab\Invoices;

/**
 * Order Invoices Plugin to set the tab
 */
class InvoicesSalesOrderViewTabPlugin
{
    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * @param AuthorizationInterface $authorization
     */
    public function __construct(
        AuthorizationInterface $authorization
    ) {
        $this->authorization = $authorization;
    }

    /**
     * Verify the user authorization to see the Invoices tab
     *
     * @param Invoices $invoices
     * @param bool $result
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @return bool
     */
    public function afterCanShowTab(Invoices $invoices, bool $result):bool
    {
        return $result && $this->authorization->isAllowed('Magento_SalesArchive::invoices');
    }
}
