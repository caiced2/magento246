<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Interface GiftcardAmountInterface: this interface is used to serialize and deserialize
 * EAV attribute giftcard_amounts
 *
 * @api
 * @since 100.0.2
 */
interface GiftcardAmountInterface extends ExtensibleDataInterface
{
    /**
     * @return int
     * @since 101.0.0
     */
    public function getAttributeId();

    /**
     * @param int $attributeId
     * @return $this
     * @since 101.0.0
     */
    public function setAttributeId($attributeId);

    /**
     * @return int
     */
    public function getWebsiteId();

    /**
     * @param int $websiteId
     * @return $this
     */
    public function setWebsiteId($websiteId);

    /**
     * @return float
     */
    public function getValue();

    /**
     * @param float $value
     * @return $this
     */
    public function setValue($value);

    /**
     * @return float
     */
    public function getWebsiteValue();

    /**
     * @param float $websiteValue
     * @return $this
     */
    public function setWebsiteValue($websiteValue);

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return \Magento\GiftCard\Api\Data\GiftcardAmountExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param \Magento\GiftCard\Api\Data\GiftcardAmountExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Magento\GiftCard\Api\Data\GiftcardAmountExtensionInterface $extensionAttributes = null
    );
}
