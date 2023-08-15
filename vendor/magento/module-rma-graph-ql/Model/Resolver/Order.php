<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\RmaGraphQl\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Rma\Api\Data\RmaInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\SalesGraphQl\Model\Formatter\Order as OrderFormatter;

/**
 * Customer order resolver
 */
class Order implements ResolverInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var OrderFormatter
     */
    private $orderFormatter;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderFormatter $orderFormatter
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        OrderFormatter $orderFormatter
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderFormatter = $orderFormatter;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['model']) && !$value['model'] instanceof RmaInterface) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        /** @var $rma RmaInterface */
        $rma = $value['model'];

        $order = $this->orderRepository->get($rma->getOrderId());

        $result = $this->orderFormatter->format($order);
        $result['model'] = $order;

        return $result;
    }
}
