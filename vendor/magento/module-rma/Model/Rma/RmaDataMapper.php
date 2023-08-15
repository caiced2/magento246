<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Rma\Model\Rma;

class RmaDataMapper
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTimeFactory
     */
    private $dateTimeFactory;

    /**
     * @var \Magento\Rma\Model\ResourceModel\Item\CollectionFactory
     */
    private $itemCollectionFactory;

    /**
     * @param \Magento\Framework\Stdlib\DateTime\DateTimeFactory $dateTimeFactory
     * @param \Magento\Rma\Model\ResourceModel\Item\CollectionFactory $itemCollectionFactory
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\DateTimeFactory $dateTimeFactory,
        \Magento\Rma\Model\ResourceModel\Item\CollectionFactory $itemCollectionFactory
    ) {
        $this->dateTimeFactory = $dateTimeFactory;
        $this->itemCollectionFactory = $itemCollectionFactory;
    }

    /**
     * Filter RMA save request
     *
     * @param array $saveRequest
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function filterRmaSaveRequest(array $saveRequest)
    {
        if (!isset($saveRequest['items'])) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('We can\'t save this RMA. No items were specified.')
            );
        }
        $requiredKeys = ['qty_authorized', 'qty_approved', 'qty_returned', 'qty_requested'];
        $items = [];
        foreach ($saveRequest['items'] as $key => $itemData) {
            $intersection = array_intersect($requiredKeys, array_keys($itemData));
            if (empty($intersection)) {
                continue;
            }
            if (strpos($key, 'new') !== false) {
                //This is a new item.
                $itemData['entity_id'] = null;
            } else {
                $itemData['entity_id'] = $key;
            }
            $items[$key] = $itemData;
        }
        $saveRequest['items'] = $items;

        return $saveRequest;
    }

    /**
     * Prepare RMA instance data from save request
     *
     * @param array $saveRequest
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    public function prepareNewRmaInstanceData(array $saveRequest, \Magento\Sales\Model\Order $order)
    {
        /** @var $dateModel \Magento\Framework\Stdlib\DateTime\DateTime */
        $dateModel = $this->dateTimeFactory->create();
        $rmaData = [
            'status' => \Magento\Rma\Model\Rma\Source\Status::STATE_PENDING,
            'date_requested' => $dateModel->gmtDate(),
            'order_id' => $order->getId(),
            'order_increment_id' => $order->getIncrementId(),
            'store_id' => $order->getStoreId(),
            'customer_id' => $order->getCustomerId(),
            'order_date' => $order->getCreatedAt(),
            'customer_name' => $order->getCustomerName(),
            'customer_custom_email' => !empty($saveRequest['contact_email']) ? $saveRequest['contact_email'] : '',
        ];
        return $rmaData;
    }

    /**
     * Combine item statuses from POST request items and original RMA items
     *
     * @param array $requestedItems
     * @param int $rmaId
     * @return array
     */
    public function combineItemStatuses(array $requestedItems, $rmaId)
    {
        $statuses = [];
        foreach ($requestedItems as $requestedItem) {
            if (isset($requestedItem['status'])) {
                $statuses[] = $requestedItem['status'];
            }
        }

        /** @todo verify this code is needed as we always have all items in request */
        /** @var $rmaCollection \Magento\Rma\Model\ResourceModel\Item\Collection */
        $itemCollection = $this->itemCollectionFactory->create();
        $itemCollection->addAttributeToFilter('rma_entity_id', $rmaId);
        foreach ($itemCollection as $rmaItem) {
            if (!isset($requestedItems[$rmaItem->getId()])) {
                $statuses[] = $rmaItem->getStatus();
            }
        }
        return $statuses;
    }
}
