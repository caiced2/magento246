<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\RmaGraphQl\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Rma\Api\Data\RmaInterface;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\Rma\Helper\Data;

/**
 * Validator for RMA fields
 */
class Validator
{
    /**
     * @var RmaRepositoryInterface
     */
    private $rmaRepository;

    /**
     * @var Data
     */
    private $rmaData;

    /**
     * @param RmaRepositoryInterface $rmaRepository
     * @param Data $rmaData
     */
    public function __construct(
        RmaRepositoryInterface $rmaRepository,
        Data $rmaData
    ) {
        $this->rmaRepository = $rmaRepository;
        $this->rmaData = $rmaData;
    }

    /**
     * Validate input string
     *
     * @param string $string
     * @param string $errorMessage
     * @return string
     * @throws GraphQlInputException
     */
    public function validateString(string $string, string $errorMessage): string
    {
        $string = trim(strip_tags($string));
        if (empty($string)) {
            throw new GraphQlInputException(__($errorMessage));
        }
        return $string;
    }

    /**
     * Validate RMA
     *
     * @param int $rmaId
     * @param int $customerId
     * @param bool $validateShippingLabel
     * @return RmaInterface
     * @throws GraphQlInputException
     */
    public function validateRma(int $rmaId, int $customerId, bool $validateShippingLabel = false): RmaInterface
    {
        try {
            $rma = $this->rmaRepository->get($rmaId);
        } catch (NoSuchEntityException $e) {
            throw new GraphQlInputException(__('You selected the wrong RMA.'));
        }

        if (!$rma->getId() || (int)$rma->getCustomerId() !== $customerId) {
            throw new GraphQlInputException(__('You selected the wrong RMA.'));
        }

        if ($validateShippingLabel && !$rma->isAvailableForPrintLabel()) {
            throw new GraphQlInputException(__('Shipping Labels are not allowed.'));
        }

        return $rma;
    }

    /**
     * Validate requested quantity
     *
     * @param array $rmaItems
     * @param int $orderId
     * @throws GraphQlInputException
     * @throws LocalizedException
     */
    public function validateRequestedQty(array $rmaItems, int $orderId): void
    {
        $orderItems = $this->rmaData->getOrderItems($orderId)->getItems();

        foreach ($rmaItems as $rmaItem) {
            $orderItemId = $rmaItem->getOrderItemId();

            if (!isset($orderItems[$orderItemId])) {
                throw new GraphQlInputException(__('You cannot return'));
            }

            $orderItem = $orderItems[$orderItemId];
            $availableQty = $orderItem->getAvailableQty();
            if (!$orderItem->getIsQtyDecimal()) {
                $availableQty = (int)$availableQty;
            }

            if ($rmaItem->getQtyRequested() < 1) {
                throw new GraphQlInputException(__('You cannot return less than 1 product.'));
            }

            if ($rmaItem->getQtyRequested() > $availableQty) {
                throw new GraphQlInputException(
                    __(
                        'A quantity of %1 is greater than you can return.',
                        $orderItem->getName()
                    )
                );
            }
        }
    }
}
