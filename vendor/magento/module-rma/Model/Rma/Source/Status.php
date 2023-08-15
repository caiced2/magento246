<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Model\Rma\Source;

/**
 * RMA Item status attribute model
 */
class Status extends \Magento\Rma\Model\Rma\Source\AbstractSource
{
    /**
     * Status constants
     */
    const STATE_PENDING = 'pending';

    const STATE_AUTHORIZED = 'authorized';

    const STATE_PARTIAL_AUTHORIZED = 'partially_authorized';

    const STATE_RECEIVED = 'received';

    const STATE_RECEIVED_ON_ITEM = 'received_on_item';

    const STATE_APPROVED = 'approved';

    const STATE_APPROVED_ON_ITEM = 'approved_on_item';

    const STATE_REJECTED = 'rejected';

    const STATE_REJECTED_ON_ITEM = 'rejected_on_item';

    const STATE_DENIED = 'denied';

    const STATE_CLOSED = 'closed';

    const STATE_PROCESSED_CLOSED = 'processed_closed';

    /**
     * Rma item attribute status factory
     *
     * @var \Magento\Rma\Model\Item\Attribute\Source\StatusFactory
     */
    protected $_statusFactory;

    /**
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $attrOptionCollectionFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\OptionFactory $attrOptionFactory
     * @param \Magento\Rma\Model\Item\Attribute\Source\StatusFactory $statusFactory
     */
    public function __construct(
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $attrOptionCollectionFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\OptionFactory $attrOptionFactory,
        \Magento\Rma\Model\Item\Attribute\Source\StatusFactory $statusFactory
    ) {
        $this->_statusFactory = $statusFactory;
        parent::__construct($attrOptionCollectionFactory, $attrOptionFactory);
    }

    /**
     * Get state label based on the code
     *
     * @param string $state
     * @return \Magento\Framework\Phrase|string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getItemLabel($state)
    {
        switch ($state) {
            case self::STATE_PENDING:
                return __('Pending');
            case self::STATE_AUTHORIZED:
                return __('Authorized');
            case self::STATE_PARTIAL_AUTHORIZED:
                return __('Partially Authorized');
            case self::STATE_RECEIVED:
                return __('Return Received');
            case self::STATE_RECEIVED_ON_ITEM:
                return __('Return Partially Received');
            case self::STATE_APPROVED:
                return __('Approved');
            case self::STATE_APPROVED_ON_ITEM:
                return __('Partially Approved');
            case self::STATE_REJECTED:
                return __('Rejected');
            case self::STATE_REJECTED_ON_ITEM:
                return __('Partially Rejected');
            case self::STATE_DENIED:
                return __('Denied');
            case self::STATE_CLOSED:
                return __('Closed');
            case self::STATE_PROCESSED_CLOSED:
                return __('Processed and Closed');
            default:
                return $state;
        }
    }

    /**
     * Get RMA status by array of items status
     *
     * First function creates correspondence between RMA Item statuses and numbers
     * I.e. pending <=> 0, authorized <=> 1, and so on
     * Then it converts array with unique item statuses to "bitmask number"
     * according to mentioned before numbers as a bits
     * For Example if all item statuses are "pending", "authorized", "rejected",
     * then "bitmask number" = 2^0 + 2^1 + 2^5 = 35
     * Then function builds correspondence between these numbers and RMA's statuses
     * and returns it
     *
     * @param array $itemStatusArray Array of RMA items status
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getStatusByItems($itemStatusArray)
    {
        if (!is_array($itemStatusArray) || empty($itemStatusArray)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('This is the wrong RMA item status.'));
        }

        $itemStatusArray = array_unique($itemStatusArray);

        /** @var $itemStatusModel \Magento\Rma\Model\Item\Attribute\Source\Status */
        $itemStatusModel = $this->_statusFactory->create();

        foreach ($itemStatusArray as $status) {
            if (!$itemStatusModel->checkStatus($status)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('This is the wrong RMA item status.'));
            }
        }

        $itemStatusToBits = [
            \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_PENDING => 1,
            \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_AUTHORIZED => 2,
            \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_DENIED => 4,
            \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_RECEIVED => 8,
            \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_APPROVED => 16,
            \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_REJECTED => 32,
        ];
        $rmaBitMaskStatus = 0;
        foreach ($itemStatusArray as $status) {
            $rmaBitMaskStatus += $itemStatusToBits[$status];
        }

        if ($rmaBitMaskStatus == 1) {
            return self::STATE_PENDING;
        } elseif ($rmaBitMaskStatus == 2) {
            return self::STATE_AUTHORIZED;
        } elseif ($rmaBitMaskStatus == 4) {
            return self::STATE_CLOSED;
        } elseif ($rmaBitMaskStatus == 5) {
            return self::STATE_PENDING;
        } elseif ($rmaBitMaskStatus > 2 && $rmaBitMaskStatus < 8) {
            return self::STATE_PARTIAL_AUTHORIZED;
        } elseif ($rmaBitMaskStatus == 8) {
            return self::STATE_RECEIVED;
        } elseif ($rmaBitMaskStatus >= 9 && $rmaBitMaskStatus <= 15) {
            return self::STATE_RECEIVED_ON_ITEM;
        } elseif ($rmaBitMaskStatus == 16) {
            return self::STATE_PROCESSED_CLOSED;
        } elseif ($rmaBitMaskStatus == 20) {
            return self::STATE_PROCESSED_CLOSED;
        } elseif ($rmaBitMaskStatus >= 17 && $rmaBitMaskStatus <= 31) {
            return self::STATE_APPROVED_ON_ITEM;
        } elseif ($rmaBitMaskStatus == 32) {
            return self::STATE_CLOSED;
        } elseif ($rmaBitMaskStatus == 36) {
            return self::STATE_CLOSED;
        } elseif ($rmaBitMaskStatus >= 33 && $rmaBitMaskStatus <= 47) {
            return self::STATE_REJECTED_ON_ITEM;
        } elseif ($rmaBitMaskStatus == 48) {
            return self::STATE_PROCESSED_CLOSED;
        } elseif ($rmaBitMaskStatus == 52) {
            return self::STATE_PROCESSED_CLOSED;
        } elseif ($rmaBitMaskStatus > 48) {
            return self::STATE_APPROVED_ON_ITEM;
        }
    }

    /**
     * Get available states keys for entities
     *
     * @return string[]
     */
    protected function _getAvailableValues()
    {
        return [
            self::STATE_PENDING,
            self::STATE_AUTHORIZED,
            self::STATE_PARTIAL_AUTHORIZED,
            self::STATE_RECEIVED,
            self::STATE_RECEIVED_ON_ITEM,
            self::STATE_APPROVED_ON_ITEM,
            self::STATE_REJECTED_ON_ITEM,
            self::STATE_CLOSED,
            self::STATE_PROCESSED_CLOSED
        ];
    }

    /**
     * Get button disabled status
     *
     * @param string $status
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getButtonDisabledStatus($status)
    {
        if (in_array(
            $status,
            [
                self::STATE_PARTIAL_AUTHORIZED,
                self::STATE_RECEIVED,
                self::STATE_RECEIVED_ON_ITEM,
                self::STATE_APPROVED_ON_ITEM,
                self::STATE_REJECTED_ON_ITEM,
                self::STATE_CLOSED,
                self::STATE_PROCESSED_CLOSED
            ]
        )
        ) {
            return true;
        }
        return false;
    }
}
