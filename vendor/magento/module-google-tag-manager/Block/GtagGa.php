<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GoogleTagManager\Block;

use Magento\Cookie\Helper\Cookie;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\GoogleTagManager\Model\Config\TagManagerConfig;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Google Analytics4 Block
 *
 * @api
 */
class GtagGa extends \Magento\GoogleGtag\Block\Ga
{
    /**
     * @var TagManagerConfig
     */
    private $tagManagerConfig;

    /**
     * @var Cookie
     */
    private $cookieHelper;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param Context $context
     * @param TagManagerConfig $googleGtagConfig
     * @param Cookie $cookieHelper
     * @param SerializerInterface $serializer
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderRepositoryInterface $orderRepository
     * @param array $data
     */
    public function __construct(
        Context $context,
        TagManagerConfig $googleGtagConfig,
        Cookie $cookieHelper,
        SerializerInterface $serializer,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderRepositoryInterface $orderRepository,
        array $data = []
    ) {
        $this->tagManagerConfig = $googleGtagConfig;
        $this->cookieHelper = $cookieHelper;
        $this->serializer = $serializer;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
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
        if (!$this->tagManagerConfig->isGoogleAnalyticsAvailable()) {
            return '';
        }
        return parent::_toHtml();
    }

    /**
     * Get store currency code for page tracking javascript code
     *
     * @return string
     */
    public function getStoreCurrencyCode(): string
    {
        return $this->_storeManager->getStore()->getBaseCurrencyCode();
    }

    /**
     * Return information about order and items
     *
     * @return array
     */
    public function getOrdersDataArray(): array
    {
        $result = [];
        $orderIds = $this->getOrderIds();
        if (empty($orderIds) || !is_array($orderIds)) {
            return $result;
        }
        $this->searchCriteriaBuilder->addFilter(
            'entity_id',
            $orderIds,
            'in'
        );
        $collection = $this->orderRepository->getList($this->searchCriteriaBuilder->create());

        /** @var \Magento\Sales\Model\Order $order */
        foreach ($collection->getItems() as $order) {
            $orderData = [
                'id' => $order->getIncrementId(),
                'revenue' => $order->getBaseGrandTotal(),
                'tax' => $order->getBaseTaxAmount(),
                'shipping' => $order->getBaseShippingAmount(),
                'coupon' => (string)$order->getCouponCode()
            ];

            $products = [];
            /** @var \Magento\Sales\Model\Order\Item $item*/
            foreach ($order->getAllVisibleItems() as $item) {
                $products[] = [
                    'id' => $item->getSku(),
                    'name' => $item->getName(),
                    'price' => $item->getBasePrice(),
                    'quantity' => $item->getQtyOrdered(),
                ];
            }

            $result[] = [
                'ecommerce' => [
                    'purchase' => [
                        'actionField' => $orderData,
                        'products' => $products
                    ],
                    'currencyCode' => $this->getStoreCurrencyCode()
                ],
                'event' => 'purchase'
            ];
        }
        return $result;
    }

    /**
     * Check if user not allow to save cookie
     *
     * @return bool
     */
    public function isUserNotAllowSaveCookie(): bool
    {
        return $this->cookieHelper->isUserNotAllowSaveCookie();
    }

    /**
     * Return required data for google tag manager
     *
     * @return string
     */
    public function getTagManagerData()
    {
        $tagManagerData = [
            'isCookieRestrictionModeEnabled' => $this->isCookieRestrictionModeEnabled(),
            'currentWebsite' => $this->getCurrentWebsiteId(),
            'cookieName' => Cookie::IS_USER_ALLOWED_SAVE_COOKIE,
            'gtmAccountId' => $this->tagManagerConfig->getContainerId(),
            'storeCurrencyCode' => $this->getStoreCurrencyCode(),
            'ordersData' => $this->getOrdersDataArray()
        ];
        return $this->serializer->serialize($tagManagerData) ?? '[]';
    }
}
