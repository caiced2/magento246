<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\RmaGraphQl\Model\Resolver\CustomerOrder\Item;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Rma\Helper\Data as RmaHelper;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Resolver for eligible_for_return flag
 */
class IsEligible implements ResolverInterface
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
     * @var RmaHelper
     */
    private $helper;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param StoreManagerInterface $storeManager
     * @param RmaHelper $helper
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager,
        RmaHelper $helper
    ) {
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->helper = $helper;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['model']) && !($value['model'] instanceof OrderItemInterface)) {
            throw new LocalizedException(__('"model" value should be specified'));
        }
        /** @var OrderItemInterface $order */
        $orderItem = $value['model'];

        $storeId = $this->storeManager->getStore()->getId();
        $product = $this->productRepository->getById($orderItem->getProductId());
        return $this->helper->canReturnProduct($product, $storeId);
    }
}
