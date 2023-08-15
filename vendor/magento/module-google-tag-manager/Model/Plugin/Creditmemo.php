<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GoogleTagManager\Model\Plugin;

use Magento\Backend\Model\Session;
use Magento\GoogleTagManager\Helper\Data;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;

/**
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class Creditmemo
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var Session
     */
    protected $backendSession;

    /**
     * @param Data $helper
     * @param Session $backendSession
     */
    public function __construct(
        Data $helper,
        Session $backendSession
    ) {
        $this->helper = $helper;
        $this->backendSession = $backendSession;
    }

    /**
     * After save plugin
     *
     * @param CreditmemoRepositoryInterface $subject
     * @param CreditmemoInterface $result
     * @return CreditmemoInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(CreditmemoRepositoryInterface $subject, $result)
    {
        if (!$this->helper->isTagManagerAvailable()) {
            return $result;
        }

        $order = $result->getOrder();
        $this->backendSession->setData('googleanalytics_creditmemo_order', $order->getIncrementId());
        $this->backendSession->setData('googleanalytics_creditmemo_store_id', $result->getStoreId());
        if (abs((float)$result->getBaseGrandTotal() - (float)$order->getBaseGrandTotal()) > 0.009) {
            $this->backendSession->setData('googleanalytics_creditmemo_revenue', $result->getBaseGrandTotal());
        }
        $products = [];

        /** @var \Magento\Sales\Model\Order\Creditmemo\Item $item */
        foreach ($result->getItemsCollection() as $item) {
            $qty = $item->getQty();
            if ($qty < 1) {
                continue;
            }
            $products[]= [
                'id' => $item->getSku(),
                'quantity' => $qty,
            ];
        }
        $this->backendSession->setData('googleanalytics_creditmemo_products', $products);

        return $result;
    }
}
