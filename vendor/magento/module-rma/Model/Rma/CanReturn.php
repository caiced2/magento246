<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Model\Rma;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Model\ValidatorInterface;

/**
 * Class for check can we save or update RMA.
 */
class CanReturn implements ValidatorInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var OrderItemRepositoryInterface
     */
    private $orderItemRepository;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderItemRepositoryInterface $orderItemRepository
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        OrderItemRepositoryInterface $orderItemRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderItemRepository = $orderItemRepository;
    }

    /**
     * Check can we create or save RMA.
     *
     * @param mixed $rmaDataObject
     * @return bool
     */
    public function validate($rmaDataObject): bool
    {
        $canReturn = true;

        try {
            $this->orderRepository->get($rmaDataObject->getOrderId());
            foreach ($rmaDataObject->getItems() as $item) {
                $orderItem = $this->orderItemRepository->get($item->getOrderItemId());
                if ($orderItem->getOrderId() != $rmaDataObject->getOrderId()
                    || $orderItem->getQtyOrdered() < $item->getQtyRequested()) {
                    $canReturn = false;
                }
            }
        } catch (NoSuchEntityException $e) {
            $canReturn = false;
        }

        return $canReturn;
    }
}
