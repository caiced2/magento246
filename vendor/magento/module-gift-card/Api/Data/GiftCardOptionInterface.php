<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftCard\Api\Data;

/**
 * Interface GiftCardOptionInterface
 * @api
 * @since 100.0.2
 */
interface GiftCardOptionInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**
     * Get gift card amount.
     *
     * @return string
     */
    public function getGiftcardAmount();

    /**
     * Set gift card amount.
     *
     * @param string $value
     * @return $this
     */
    public function setGiftcardAmount($value);

    /**
     * Get gift card open amount value.
     *
     * @return float|null
     */
    public function getCustomGiftcardAmount();

    /**
     * Set gift card open amount value.
     *
     * @param float|null $value
     * @return $this
     */
    public function setCustomGiftcardAmount($value);

    /**
     * Get gift card sender name.
     *
     * @return string
     */
    public function getGiftcardSenderName();

    /**
     * Set gift card sender name.
     *
     * @param string $value
     * @return $this
     */
    public function setGiftcardSenderName($value);

    /**
     * Get gift card recipient name.
     *
     * @return string
     */
    public function getGiftcardRecipientName();

    /**
     * Set gift card recipient name.
     *
     * @param string $value
     * @return $this
     */
    public function setGiftcardRecipientName($value);

    /**
     * Get gift card sender email.
     *
     * @return string
     */
    public function getGiftcardSenderEmail();

    /**
     * Set gift card sender email.
     *
     * @param string $value
     * @return $this
     */
    public function setGiftcardSenderEmail($value);

    /**
     * Get gift card recipient email.
     *
     * @return string
     */
    public function getGiftcardRecipientEmail();

    /**
     * Set gift card recipient email.
     *
     * @param string $value
     * @return $this
     */
    public function setGiftcardRecipientEmail($value);

    /**
     * Get giftcard message.
     *
     * @return string|null
     */
    public function getGiftcardMessage();

    /**
     * Set giftcard message.
     *
     * @param string|null $value
     * @return $this
     */
    public function setGiftcardMessage($value);

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return \Magento\GiftCard\Api\Data\GiftCardOptionExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param \Magento\GiftCard\Api\Data\GiftCardOptionExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Magento\GiftCard\Api\Data\GiftCardOptionExtensionInterface $extensionAttributes
    );
}
