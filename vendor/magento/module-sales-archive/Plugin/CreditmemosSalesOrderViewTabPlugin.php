<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Magento\SalesArchive\Plugin;

use Magento\Framework\AuthorizationInterface;
use Magento\Sales\Block\Adminhtml\Order\View\Tab\Creditmemos;

/**
 * Order Credit Memos to set the tab
 */
class CreditmemosSalesOrderViewTabPlugin
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
     * Verify the user authorization to see the Creditmemos tab
     *
     * @param Creditmemos $creditmemos
     * @param bool $result
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCanShowTab(Creditmemos $creditmemos, bool $result):bool
    {
        return $result && $this->authorization->isAllowed('Magento_SalesArchive::creditmemos');
    }
}
