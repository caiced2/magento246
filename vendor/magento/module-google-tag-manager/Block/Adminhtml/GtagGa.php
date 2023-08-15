<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GoogleTagManager\Block\Adminhtml;

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
class GtagGa extends \Magento\GoogleTagManager\Block\GtagGa
{
    /**
     * @var Session
     */
    private $backendSession;

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
        parent::__construct(
            $context,
            $googleGtagConfig,
            $cookieHelper,
            $serializer,
            $searchCriteriaBuilder,
            $orderRepository,
            $data
        );
    }

    /**
     * Render GA tracking scripts
     *
     * @return string
     */
    public function _toHtml()
    {
        if (!$this->getOrderId()) {
            return '';
        }
        return parent::_toHtml();
    }

    /**
     * Get order ID for the recently created creditmemo
     *
     * @return string
     */
    public function getOrderId()
    {
        return $this->backendSession->getData('googleanalytics_creditmemo_order');
    }

    /**
     * Get store currency code for page tracking javascript code
     *
     * @return string
     */
    public function getStoreCurrencyCode(): string
    {
        $storeId = $this->backendSession->getData('googleanalytics_creditmemo_store_id');
        return $this->_storeManager->getStore($storeId)->getBaseCurrencyCode();
    }
}
