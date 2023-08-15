<?php
/**
 * Item data interface
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Api\Data;

/**
 * Interface CategoryInterface
 * @api
 * @since 100.0.2
 */
interface ItemInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**
     * Get id
     *
     * @return int
     */
    public function getEntityId();

    /**
     * Set id
     *
     * @param int $id
     * @return \Magento\Rma\Api\Data\ItemInterface
     */
    public function setEntityId($id);

    /**
     * Get RMA id
     *
     * @return int
     */
    public function getRmaEntityId();

    /**
     * Set RMA id
     *
     * @param int $id
     * @return \Magento\Rma\Api\Data\ItemInterface
     */
    public function setRmaEntityId($id);

    /**
     * Get order_item_id
     *
     * @return int
     */
    public function getOrderItemId();

    /**
     * Set order_item_id
     *
     * @param int $id
     * @return \Magento\Rma\Api\Data\ItemInterface
     */
    public function setOrderItemId($id);

    /**
     * Get qty_requested
     *
     * @return int
     */
    public function getQtyRequested();

    /**
     * Set qty_requested
     *
     * @param int $qtyRequested
     * @return \Magento\Rma\Api\Data\ItemInterface
     */
    public function setQtyRequested($qtyRequested);

    /**
     * Get qty_authorized
     *
     * @return int
     */
    public function getQtyAuthorized();

    /**
     * Set qty_authorized
     *
     * @param int $qtyAuthorized
     * @return \Magento\Rma\Api\Data\ItemInterface
     */
    public function setQtyAuthorized($qtyAuthorized);

    /**
     * Get qty_approved
     *
     * @return int
     */
    public function getQtyApproved();

    /**
     * Set qty_approved
     *
     * @param int $qtyApproved
     * @return \Magento\Rma\Api\Data\ItemInterface
     */
    public function setQtyApproved($qtyApproved);

    /**
     * Get qty_returned
     *
     * @return int
     */
    public function getQtyReturned();

    /**
     * Set qty_returned
     *
     * @param int $qtyReturned
     * @return \Magento\Rma\Api\Data\ItemInterface
     */
    public function setQtyReturned($qtyReturned);

    /**
     * Get reason
     *
     * @return string
     */
    public function getReason();

    /**
     * Set reason
     *
     * @param string $reason
     * @return \Magento\Rma\Api\Data\ItemInterface
     */
    public function setReason($reason);

    /**
     * Get condition
     *
     * @return string
     */
    public function getCondition();

    /**
     * Set condition
     *
     * @param string $condition
     * @return \Magento\Rma\Api\Data\ItemInterface
     */
    public function setCondition($condition);

    /**
     * Get resolution
     *
     * @return string
     */
    public function getResolution();

    /**
     * Set resolution
     *
     * @param string $resolution
     * @return \Magento\Rma\Api\Data\ItemInterface
     */
    public function setResolution($resolution);

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus();

    /**
     * Set status
     *
     * @param string $status
     * @return \Magento\Rma\Api\Data\ItemInterface
     */
    public function setStatus($status);

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return \Magento\Rma\Api\Data\ItemExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param \Magento\Rma\Api\Data\ItemExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(\Magento\Rma\Api\Data\ItemExtensionInterface $extensionAttributes);
}
