<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Rma\Api\Data;

/**
 * Interface TrackInterface
 * @api
 * @since 100.0.2
 */
interface TrackInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**
     * Returns entity id
     *
     * @return int
     */
    public function getEntityId();

    /**
     * Set entity id
     *
     * @param int $entityId
     * @return \Magento\Rma\Api\Data\TrackInterface
     */
    public function setEntityId($entityId);

    /**
     * Returns rma entity id
     *
     * @return int
     */
    public function getRmaEntityId();

    /**
     * Set rma entity id
     *
     * @param int $entityId
     * @return \Magento\Rma\Api\Data\TrackInterface
     */
    public function setRmaEntityId($entityId);

    /**
     * Returns track number
     *
     * @return string
     */
    public function getTrackNumber();

    /**
     * Set track number
     *
     * @param string $trackNumber
     * @return \Magento\Rma\Api\Data\TrackInterface
     */
    public function setTrackNumber($trackNumber);

    /**
     * Returns carrier title
     *
     * @return string
     */
    public function getCarrierTitle();

    /**
     * Set carrier title
     *
     * @param string $carrierTitle
     * @return \Magento\Rma\Api\Data\TrackInterface
     */
    public function setCarrierTitle($carrierTitle);

    /**
     * Returns carrier code
     *
     * @return string
     */
    public function getCarrierCode();

    /**
     * Set carrier code
     *
     * @param string $carrierCode
     * @return \Magento\Rma\Api\Data\TrackInterface
     */
    public function setCarrierCode($carrierCode);

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return \Magento\Rma\Api\Data\TrackExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param \Magento\Rma\Api\Data\TrackExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(\Magento\Rma\Api\Data\TrackExtensionInterface $extensionAttributes);
}
