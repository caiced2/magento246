<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Model\Item;

/**
 * RMA Item Status Manager
 */
class Status extends \Magento\Framework\DataObject
{
    /**
     * Artificial "maximal" item status when whole order is closed
     */
    const STATUS_ORDER_IS_CLOSED = 'order_is_closed';

    /**
     * Artificial "minimal" item status when all allowed fields are editable
     */
    const STATUS_ALL_ARE_EDITABLE = 'all_are_editable';

    /**
     * Flag for artificial statuses
     *
     * @var bool
     */
    protected $_isSpecialStatus = false;

    /**
     * Rma item attribute source status
     *
     * @var \Magento\Rma\Model\Item\Attribute\Source\Status
     */
    protected $_sourceStatus;

    /**
     * @param \Magento\Rma\Model\Item\Attribute\Source\Status $sourceStatus
     * @param array $data
     */
    public function __construct(\Magento\Rma\Model\Item\Attribute\Source\Status $sourceStatus, array $data = [])
    {
        $this->_sourceStatus = $sourceStatus;
        parent::__construct($data);
    }

    /**
     * Get options array for display in grid, consisting only from allowed statuses
     *
     * @return array
     */
    public function getAllowedStatuses()
    {
        $statusesAllowed = [
            \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_PENDING => [
                \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_PENDING,
                \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_AUTHORIZED,
                \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_DENIED,
            ],
            \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_AUTHORIZED => [
                \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_AUTHORIZED,
                \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_RECEIVED,
            ],
            \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_RECEIVED => [
                \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_RECEIVED,
                \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_APPROVED,
                \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_REJECTED,
            ],
            \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_APPROVED => [
                \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_APPROVED,
            ],
            \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_REJECTED => [
                \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_REJECTED,
            ],
            \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_DENIED => [
                \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_DENIED,
            ],
        ];
        $boundingArray = isset($statusesAllowed[$this->getStatus()]) ? $statusesAllowed[$this->getStatus()] : [];
        return array_intersect_key($this->_sourceStatus->getAllOptionsForGrid(), array_flip($boundingArray));
    }

    /**
     * Get item status sequence - linear order on item statuses set
     *
     * @return string[]
     */
    protected function _getStatusSequence()
    {
        return [
            self::STATUS_ALL_ARE_EDITABLE,
            \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_PENDING,
            \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_AUTHORIZED,
            \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_RECEIVED,
            \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_APPROVED,
            \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_REJECTED,
            \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_DENIED,
            self::STATUS_ORDER_IS_CLOSED
        ];
    }

    /**
     * Get Border status for each attribute.
     *
     * For statuses, "less" than border status, attribute becomes uneditable
     * For statuses, "equal or greater" than border status, attribute becomes editable
     *
     * @param string  $attribute
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getBorderStatus($attribute)
    {
        switch ($attribute) {
            case 'qty_requested':
                return \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_PENDING;
                break;
            case 'qty_authorized':
                return \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_AUTHORIZED;
                break;
            case 'qty_returned':
                return \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_RECEIVED;
                break;
            case 'qty_approved':
                return \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_APPROVED;
                break;
            case 'reason':
                return \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_PENDING;
                break;
            case 'condition':
                return \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_PENDING;
                break;
            case 'resolution':
                return \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_APPROVED;
                break;
            case 'status':
                return \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_APPROVED;
                break;
            case 'action':
                return self::STATUS_ORDER_IS_CLOSED;
                break;
            default:
                return \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_PENDING;
                break;
        }
    }

    /**
     * Get whether attribute is editable
     *
     * @param string $attribute
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getAttributeIsEditable($attribute)
    {
        $typeSequence = $this->_getStatusSequence();
        $itemStateKey = array_search($this->getSequenceStatus(), $typeSequence);
        if ($itemStateKey === false) {
            return false;
        }

        if (array_search($this->getBorderStatus($attribute), $typeSequence) > $itemStateKey) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get whether editable attribute is disabled
     *
     * @param string $attribute
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getAttributeIsDisabled($attribute)
    {
        if ($this->getSequenceStatus() == self::STATUS_ALL_ARE_EDITABLE) {
            return false;
        }

        switch ($attribute) {
            case 'qty_authorized':
                $enabledStatus = \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_PENDING;
                break;
            case 'qty_returned':
                $enabledStatus = \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_AUTHORIZED;
                break;
            case 'qty_approved':
                $enabledStatus = \Magento\Rma\Model\Item\Attribute\Source\Status::STATE_RECEIVED;
                break;
            default:
                return false;
                break;
        }

        if ($enabledStatus == $this->getSequenceStatus()) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Sets "maximal" status for closed orders
     *
     * For closed orders no attributes should be editable.
     * So this method sets item status to artificial "maximum" value
     *
     * @return void
     */
    public function setOrderIsClosed()
    {
        $this->setSequenceStatus(self::STATUS_ORDER_IS_CLOSED);
        $this->_isSpecialStatus = true;
    }

    /**
     * Sets "minimal" status
     *
     * For split line functionality all fields must be editable
     *
     * @return void
     */
    public function setAllEditable()
    {
        $this->setSequenceStatus(self::STATUS_ALL_ARE_EDITABLE);
        $this->_isSpecialStatus = true;
    }

    /**
     * Sets status to object but not for self::STATUS_ORDER_IS_CLOSED status
     *
     * @param string $status
     * @return \Magento\Rma\Model\Item\Status
     */
    public function setStatus($status)
    {
        if (!$this->getSequenceStatus() || !$this->_isSpecialStatus) {
            $this->setSequenceStatus($status);
        }
        return parent::setStatus($status);
    }
}
