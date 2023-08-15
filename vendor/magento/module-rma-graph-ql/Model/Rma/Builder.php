<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\RmaGraphQl\Model\Rma;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Validator\EmailAddress;
use Magento\Rma\Api\Data\RmaInterface;
use Magento\Rma\Helper\Data;
use Magento\Rma\Model\Rma\Source\Status;
use Magento\Rma\Model\RmaFactory;
use Magento\RmaGraphQl\Model\Rma\Item\Builder as ItemBuilder;
use Magento\RmaGraphQl\Model\Validator;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Rma Builder
 */
class Builder
{
    /**
     * @var RmaFactory
     */
    private $rmaFactory;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var ItemBuilder
     */
    private $itemBuilder;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @param RmaFactory $rmaFactory
     * @param DateTime $dateTime
     * @param Data $helper
     * @param ItemBuilder $itemBuilder
     * @param Validator $validator
     */
    public function __construct(
        RmaFactory $rmaFactory,
        DateTime $dateTime,
        Data $helper,
        ItemBuilder $itemBuilder,
        Validator $validator
    ) {
        $this->rmaFactory = $rmaFactory;
        $this->dateTime = $dateTime;
        $this->helper = $helper;
        $this->itemBuilder = $itemBuilder;
        $this->validator = $validator;
    }

    /**
     * Build RMA object
     *
     * @param OrderInterface $order
     * @param array $rmaData
     * @return RmaInterface
     * @throws GraphQlInputException
     * @throws GraphQlNoSuchEntityException
     */
    public function build(OrderInterface $order, array $rmaData): RmaInterface
    {
        $rma = $this->rmaFactory->create();
        $rma->setData(
            [
                'status' => Status::STATE_PENDING,
                'date_requested' => $this->dateTime->gmtDate(),
                'order_id' => $order->getId(),
                'order_increment_id' => $order->getIncrementId(),
                'store_id' => $order->getStoreId(),
                'customer_id' => $order->getCustomerId(),
                'order_date' => $order->getCreatedAt(),
                'customer_name' => $order->getCustomerName()
            ]
        );

        if (isset($rmaData['contact_email'])) {
            $rma = $this->setEmail($rmaData['contact_email'], $rma);
        }

        $items = [];
        foreach ($rmaData['items'] as $item) {
            $items[] = $this->itemBuilder->build($item);
        }

        $this->validator->validateRequestedQty($items, (int)$order->getId());

        return $rma->setItems($items);
    }

    /**
     * Add customer custom email to RMA
     *
     * @param string $email
     * @param RmaInterface $rma
     * @return RmaInterface
     * @throws GraphQlInputException
     */
    private function setEmail(string $email, RmaInterface $rma): RmaInterface
    {
        $validator = new EmailAddress();
        if (!$validator->isValid($email)) {
            throw new GraphQlInputException(
                __('You entered an invalid email address: "%1".', $this->helper->getContactEmailLabel())
            );
        }

        return $rma->setCustomerCustomEmail($email);
    }
}
