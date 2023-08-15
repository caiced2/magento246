<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GoogleTagManager\Block\Adminhtml\Creditmemo;

/**
 * @api
 * @since 100.0.2
 */
class Ga extends \Magento\GoogleTagManager\Block\Ga
{
    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $backendSession;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $salesOrderCollection
     * @param \Magento\GoogleTagManager\Helper\Data $googleAnalyticsData
     * @param \Magento\Cookie\Helper\Cookie $cookieHelper
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Backend\Model\Session $backendSession
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $salesOrderCollection,
        \Magento\GoogleTagManager\Helper\Data $googleAnalyticsData,
        \Magento\Cookie\Helper\Cookie $cookieHelper,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Backend\Model\Session $backendSession,
        array $data = []
    ) {
        $this->backendSession = $backendSession;
        parent::__construct(
            $context,
            $salesOrderCollection,
            $googleAnalyticsData,
            $cookieHelper,
            $jsonHelper,
            $data
        );
    }

    /**
     * Get order ID for the recently created creditmemo
     *
     * @return string
     */
    public function getOrderId()
    {
        $orderId = $this->backendSession->getData('googleanalytics_creditmemo_order', true);
        if ($orderId && $this->_googleAnalyticsData->isGoogleAnalyticsAvailable()) {
            return $orderId;
        }
        return '';
    }

    /**
     * Get refunded amount for the recently created creditmemo
     *
     * @return string
     */
    public function getRevenue()
    {
        $revenue = $this->backendSession->getData('googleanalytics_creditmemo_revenue', true);
        if ($revenue) {
            return $revenue;
        }
        return '';
    }

    /**
     * Get refunded products
     *
     * @return array
     */
    public function getProducts()
    {
        $products = $this->backendSession->getData('googleanalytics_creditmemo_products', true);
        if ($products) {
            return $products;
        }
        return [];
    }

    /**
     * Build json for dataLayer.push action
     *
     * @return string|null
     */
    public function getRefundJson()
    {
        $orderId = $this->getOrderId();
        if (!$orderId) {
            return null;
        }
        $refundJson = new \StdClass();
        $refundJson->event = 'refund';
        $refundJson->ecommerce = new \StdClass();
        $refundJson->ecommerce->refund = new \StdClass();
        $refundJson->ecommerce->refund->actionField  = new \StdClass();
        $refundJson->ecommerce->refund->actionField->id = $orderId;
        $revenue = $this->getRevenue();
        if ($revenue) {
            $refundJson->ecommerce->refund->actionField->revenue = $revenue;
        }
        $refundJson->ecommerce->refund->products = $this->getProducts();
        return json_encode($refundJson);
    }
}
