<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\RmaGraphQl\Model\Formatter;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\EnumLookup;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Rma\Api\Data\RmaInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Rma formatter
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Rma
{
    /**
     * @var string
     */
    private $rmaStatusEnum = 'ReturnStatus';

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var EnumLookup
     */
    private $enumLookup;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var Uid
     */
    private $idEncoder;

    /**
     * @var Comment
     */
    private $commentFormatter;

    /**
     * @var RmaItem
     */
    private $itemFormatter;

    /**
     * @var Shipping
     */
    private $shippingFormatter;

    /**
     * @var ShippingCarriers
     */
    private $shippingCarriersFormatter;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param RmaItem $itemFormatter
     * @param EnumLookup $enumLookup
     * @param CustomerRepositoryInterface $customerRepository
     * @param Uid $idEncoder
     * @param Comment $commentFormatter
     * @param Shipping $shippingFormatter
     * @param ShippingCarriers $shippingCarriers
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        RmaItem $itemFormatter,
        EnumLookup $enumLookup,
        CustomerRepositoryInterface $customerRepository,
        Uid $idEncoder,
        Comment $commentFormatter,
        Shipping $shippingFormatter,
        ShippingCarriers $shippingCarriers
    ) {
        $this->orderRepository = $orderRepository;
        $this->itemFormatter = $itemFormatter;
        $this->enumLookup = $enumLookup;
        $this->customerRepository = $customerRepository;
        $this->idEncoder = $idEncoder;
        $this->commentFormatter = $commentFormatter;
        $this->shippingFormatter = $shippingFormatter;
        $this->shippingCarriersFormatter = $shippingCarriers;
    }

    /**
     * Format RMA according to the GraphQL schema
     *
     * @param RmaInterface $rma
     * @return array
     * @throws GraphQlNoSuchEntityException
     * @throws LocalizedException
     * @throws RuntimeException
     */
    public function format(RmaInterface $rma): array
    {
        $order = $this->orderRepository->get($rma->getOrderId());

        try {
            $customer = $this->customerRepository->getById($rma->getCustomerId());
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()));
        }

        $comments = [];
        foreach ($rma->getComments() as $comment) {
            $comments[] = $this->commentFormatter->format($comment, $order);
        }

        $returnItems = [];
        foreach ($rma->getItems() as $item) {
            $returnItems[] = $this->itemFormatter->format($item);
        }

        return [
            'uid' => $this->idEncoder->encode((string)$rma->getEntityId()),
            'number' => $rma->getIncrementId(),
            'created_at' => $rma->getDateRequested(),
            'customer' => [
                'email' => $rma->getCustomerCustomEmail() ?? $customer->getEmail(),
                'firstname' => $customer->getFirstname(),
                'lastname' => $customer->getLastname()
            ],
            'status' => $this->enumLookup->getEnumValueFromField($this->rmaStatusEnum, $rma->getStatus()),
            'shipping' => $this->shippingFormatter->format($rma),
            'comments' => $comments,
            'items' => $returnItems,
            'available_shipping_carriers' => $this->shippingCarriersFormatter->getAvailableShippingCarriers($rma),
            'model' => $rma
        ];
    }
}
