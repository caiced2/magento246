<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\RmaGraphQl\Model\Resolver\CustomerOrder;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Rma\Helper\Data as RmaHelper;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\SalesGraphQl\Model\OrderItem\DataProvider as OrderItemProvider;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Resolver for a list of customer order items eligible for return.
 */
class EligibleItems implements ResolverInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var OrderItemProvider
     */
    private $orderItemProvider;

    /**
     * @var RmaHelper
     */
    private $helper;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param StoreManagerInterface $storeManager
     * @param OrderItemProvider $orderItemProvider
     * @param RmaHelper $helper
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager,
        OrderItemProvider $orderItemProvider,
        RmaHelper $helper
    ) {
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->orderItemProvider = $orderItemProvider;
        $this->helper = $helper;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['model']) && !($value['model'] instanceof OrderInterface)) {
            throw new LocalizedException(__('"model" value should be specified'));
        }
        /** @var OrderInterface $order */
        $order = $value['model'];

        $itemsList = [];
        $storeId = $this->storeManager->getStore()->getId();
        foreach ($order->getItems() as $item) {
            $product = $this->productRepository->getById($item->getProductId());
            if ($this->helper->canReturnProduct($product, $storeId)) {
                $this->orderItemProvider->addOrderItemId((int)$item->getId());
                $orderItem = $this->orderItemProvider->getOrderItemById((int)$item->getId());
                $orderItem['eligible_for_return'] = true;
                $itemsList[] = $orderItem;
            }
        }

        return $itemsList;
    }
}
