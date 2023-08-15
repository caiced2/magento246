<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Rma\Api\Data;

/**
 * Interface CommentInterface
 * @api
 * @since 100.0.2
 */
interface CommentInterface extends \Magento\Framework\Api\CustomAttributesDataInterface
{
    /**
     * Returns comment
     *
     * @return string
     */
    public function getComment();

    /**
     * Set comment
     *
     * @param string $comment
     * @return \Magento\Rma\Api\Data\CommentInterface
     */
    public function setComment($comment);

    /**
     * Return Rma Id
     *
     * @return int
     */
    public function getRmaEntityId();

    /**
     * Set Rma Id
     *
     * @param int $rmaId
     * @return \Magento\Rma\Api\Data\CommentInterface
     */
    public function setRmaEntityId($rmaId);

    /**
     * Returns created_at
     *
     * @return string
     */
    public function getCreatedAt();

    /**
     * Set created_at
     *
     * @param string $createdAt
     * @return \Magento\Rma\Api\Data\CommentInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * Returns entity_id
     *
     * @return int
     */
    public function getEntityId();

    /**
     * Set entity_id
     *
     * @param int $entityId
     * @return \Magento\Rma\Api\Data\CommentInterface
     */
    public function setEntityId($entityId);

    /**
     * Returns is_customer_notified
     *
     * @return bool
     */
    public function isCustomerNotified();

    /**
     * Set is_customer_notified
     *
     * @param bool $isCustomerNotified
     * @return \Magento\Rma\Api\Data\CommentInterface
     */
    public function setIsCustomerNotified($isCustomerNotified);

    /**
     * Returns is_visible_on_front
     *
     * @return bool
     */
    public function isVisibleOnFront();

    /**
     * Set is_visible_on_front
     *
     * @param bool $isVisibleOnFront
     * @return \Magento\Rma\Api\Data\CommentInterface
     */
    public function setIsVisibleOnFront($isVisibleOnFront);

    /**
     * Returns status
     *
     * @return string
     */
    public function getStatus();

    /**
     * Set status
     *
     * @param string $status
     * @return \Magento\Rma\Api\Data\CommentInterface
     */
    public function setStatus($status);

    /**
     * Returns is_admin
     *
     * @return bool
     */
    public function isAdmin();

    /**
     * Set is_admin
     *
     * @param bool $isAdmin
     * @return \Magento\Rma\Api\Data\CommentInterface
     */
    public function setIsAdmin($isAdmin);

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return \Magento\Rma\Api\Data\CommentExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param \Magento\Rma\Api\Data\CommentExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(\Magento\Rma\Api\Data\CommentExtensionInterface $extensionAttributes);
}
