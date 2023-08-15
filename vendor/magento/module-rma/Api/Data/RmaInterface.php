<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Api\Data;

/**
 * Interface RmaInterface
 * @api
 * @since 100.0.2
 */
interface RmaInterface extends \Magento\Framework\Api\CustomAttributesDataInterface
{
    /**
     * Get entity_id
     *
     * @return string
     */
    public function getIncrementId();

    /**
     * Set entity_id
     *
     * @param string $incrementId
     * @return \Magento\Rma\Api\Data\RmaInterface
     */
    public function setIncrementId($incrementId);

    /**
     * Get entity_id
     *
     * @return int
     */
    public function getEntityId();

    /**
     * Set entity_id
     *
     * @param int $entityId
     * @return \Magento\Rma\Api\Data\RmaInterface
     */
    public function setEntityId($entityId);

    /**
     * Get order_id
     *
     * @return int
     */
    public function getOrderId();

    /**
     * Set order_id
     *
     * @param int $orderId
     * @return \Magento\Rma\Api\Data\RmaInterface
     */
    public function setOrderId($orderId);

    /**
     * Get order_increment_id
     *
     * @return string
     */
    public function getOrderIncrementId();

    /**
     * Set order_increment_id
     *
     * @param string $incrementId
     * @return \Magento\Rma\Api\Data\RmaInterface
     */
    public function setOrderIncrementId($incrementId);

    /**
     * Get store_id
     *
     * @return int
     */
    public function getStoreId();

    /**
     * Set store_id
     *
     * @param int $storeId
     * @return \Magento\Rma\Api\Data\RmaInterface
     */
    public function setStoreId($storeId);

    /**
     * Get customer_id
     *
     * @return int
     */
    public function getCustomerId();

    /**
     * Set customer_id
     *
     * @param int $customerId
     * @return \Magento\Rma\Api\Data\RmaInterface
     */
    public function setCustomerId($customerId);

    /**
     * Get date_requested
     *
     * @return string
     */
    public function getDateRequested();

    /**
     * Set date_requested
     *
     * @param string $dateRequested
     * @return \Magento\Rma\Api\Data\RmaInterface
     */
    public function setDateRequested($dateRequested);

    /**
     * Get customer_custom_email
     *
     * @return string
     */
    public function getCustomerCustomEmail();

    /**
     * Set customer_custom_email
     *
     * @param string $customerCustomEmail
     * @return \Magento\Rma\Api\Data\RmaInterface
     */
    public function setCustomerCustomEmail($customerCustomEmail);

    /**
     * Get items
     *
     * @return \Magento\Rma\Api\Data\ItemInterface[]
     */
    public function getItems();

    /**
     * Set items
     *
     * @param \Magento\Rma\Api\Data\ItemInterface[] $items
     * @return \Magento\Rma\Api\Data\RmaInterface
     */
    public function setItems(array $items = null);

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
     * @return \Magento\Rma\Api\Data\RmaInterface
     */
    public function setStatus($status);

    /**
     * Get comments list
     *
     * @return \Magento\Rma\Api\Data\CommentInterface[]
     */
    public function getComments();

    /**
     * Set comments list
     *
     * @param \Magento\Rma\Api\Data\CommentInterface[] $comments
     * @return \Magento\Rma\Api\Data\RmaInterface
     */
    public function setComments(array $comments = null);

    /**
     * Get tracks list
     *
     * @return \Magento\Rma\Api\Data\TrackInterface[]
     */
    public function getTracks();

    /**
     * Set tracks list
     *
     * @param \Magento\Rma\Api\Data\TrackInterface[] $tracks
     * @return \Magento\Rma\Api\Data\RmaInterface
     */
    public function setTracks(array $tracks = null);

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return \Magento\Rma\Api\Data\RmaExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param \Magento\Rma\Api\Data\RmaExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(\Magento\Rma\Api\Data\RmaExtensionInterface $extensionAttributes);
}
