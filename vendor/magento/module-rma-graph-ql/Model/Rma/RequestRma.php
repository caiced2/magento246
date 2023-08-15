<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\RmaGraphQl\Model\Rma;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Rma\Api\Data\RmaInterface;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Request RMA executor
 */
class RequestRma
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var RmaRepositoryInterface
     */
    private $rmaRepository;

    /**
     * @var Builder
     */
    private $builder;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param RmaRepositoryInterface $rmaRepository
     * @param Builder $builder
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        RmaRepositoryInterface $rmaRepository,
        Builder $builder
    ) {
        $this->rmaRepository = $rmaRepository;
        $this->orderRepository = $orderRepository;
        $this->builder = $builder;
    }

    /**
     * Execute RMA request
     *
     * @param int $orderId
     * @param int $customerId
     * @param array $rmaData
     * @return RmaInterface
     * @throws GraphQlAuthorizationException
     * @throws GraphQlInputException
     * @throws GraphQlNoSuchEntityException
     */
    public function execute(int $orderId, int $customerId, array $rmaData)
    {
        try {
            $order = $this->orderRepository->get($orderId);
        } catch (LocalizedException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()));
        }

        if ((int)$order->getCustomerId() !== $customerId) {
            throw new GraphQlAuthorizationException(__('Something went wrong while processing the request.'));
        }

        try {
            $rma = $this->builder->build($order, $rmaData);
            $rma = $this->rmaRepository->save($rma);
        } catch (CouldNotSaveException $e) {
            throw new GraphQlNoSuchEntityException(__('Something went wrong while processing the request.'));
        }

        return $rma;
    }
}
