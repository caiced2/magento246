<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GoogleTagManager\Block\Adminhtml\Creditmemo;

use Magento\Backend\Model\Session;
use Magento\Cookie\Helper\Cookie;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\GoogleTagManager\Model\Config\TagManagerConfig;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * @api
 */
class GtagGa extends \Magento\GoogleTagManager\Block\Adminhtml\GtagGa
{
    /**
     * @var Session
     */
    private $backendSession;

    /**
     * @var TagManagerConfig
     */
    private $tagManagerConfig;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param TagManagerConfig $googleGtagConfig
     * @param \Magento\Cookie\Helper\Cookie $cookieHelper
     * @param SerializerInterface $serializer
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderRepositoryInterface $orderRepository
     * @param Session $backendSession
     * @param array $data
     */
    public function __construct(
        Context $context,
        TagManagerConfig $googleGtagConfig,
        Cookie $cookieHelper,
        SerializerInterface $serializer,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderRepositoryInterface $orderRepository,
        Session $backendSession,
        array $data = []
    ) {
        $this->backendSession = $backendSession;
        $this->serializer = $serializer;
        $this->tagManagerConfig = $googleGtagConfig;
        parent::__construct(
            $context,
            $googleGtagConfig,
            $cookieHelper,
            $serializer,
            $searchCriteriaBuilder,
            $orderRepository,
            $backendSession,
            $data
        );
    }

    /**
     * Get order ID for the recently created creditmemo
     *
     * @return string
     */
    public function getOrderId(): string
    {
        $orderId = $this->backendSession->getData('googleanalytics_creditmemo_order', true);
        if ($orderId && $this->tagManagerConfig->isGoogleAnalyticsAvailable()) {
            return $orderId;
        }
        return '';
    }

    /**
     * Get refunded amount for the recently created creditmemo
     *
     * @return string
     */
    public function getRevenue(): string
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
    public function getProducts(): array
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
        return $this->serializer->serialize($refundJson);
    }
}
