<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Magento\SalesArchive\Plugin;

use Magento\Framework\AuthorizationInterface;
use Magento\Sales\Block\Adminhtml\Order\View\Tab\Shipments;

/**
 * Order Shipments Plugin to set the tab
 */
class ShipmentsSalesOrderViewTabPlugin
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
     * Verify the user authorization to see the Shipments tab
     *
     * @param Shipments $shipments
     * @param bool $result
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCanShowTab(Shipments $shipments, bool $result):bool
    {
        return $result && $this->authorization->isAllowed('Magento_SalesArchive::shipments');
    }
}
