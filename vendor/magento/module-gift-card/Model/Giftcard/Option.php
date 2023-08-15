<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftCard\Model\Giftcard;

use Magento\GiftCard\Api\Data\GiftCardOptionInterface;

/**
 * Gift Card Option Model
 * @codeCoverageIgnore
 */
class Option extends \Magento\Framework\Model\AbstractExtensibleModel implements GiftCardOptionInterface
{
    /**#@+
     * Constants
     */
    const KEY_AMOUNT = 'giftcard_amount';
    const KEY_SENDER_NAME = 'giftcard_sender_name';
    const KEY_RECIPIENT_NAME = 'giftcard_recipient_name';
    const KEY_SENDER_EMAIL = 'giftcard_sender_email';
    const KEY_RECIPIENT_EMAIL = 'giftcard_recipient_email';
    const KEY_MESSAGE = 'giftcard_message';
    const KEY_CUSTOM_GIFTCARD_AMOUNT = 'custom_giftcard_amount';
    /**#@-*/

    /**
     * {@inheritdoc}
     */
    public function getGiftcardAmount()
    {
        return $this->getData(self::KEY_AMOUNT);
    }

    /**
     * {@inheritdoc}
     */
    public function setGiftcardAmount($value)
    {
        return $this->setData(self::KEY_AMOUNT, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomGiftcardAmount()
    {
        return $this->getData(self::KEY_CUSTOM_GIFTCARD_AMOUNT);
    }

    /**
     * {@inheritdoc}
     */
    public function setCustomGiftcardAmount($value)
    {
        return $this->setData(self::KEY_CUSTOM_GIFTCARD_AMOUNT, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getGiftcardSenderName()
    {
        return $this->getData(self::KEY_SENDER_NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function setGiftcardSenderName($value)
    {
        return $this->setData(self::KEY_SENDER_NAME, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getGiftcardRecipientName()
    {
        return $this->getData(self::KEY_RECIPIENT_NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function setGiftcardRecipientName($value)
    {
        return $this->setData(self::KEY_RECIPIENT_NAME, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getGiftcardSenderEmail()
    {
        return $this->getData(self::KEY_SENDER_EMAIL);
    }

    /**
     * {@inheritdoc}
     */
    public function setGiftcardSenderEmail($value)
    {
        return $this->setData(self::KEY_SENDER_EMAIL, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getGiftcardRecipientEmail()
    {
        return $this->getData(self::KEY_RECIPIENT_EMAIL);
    }

    /**
     * {@inheritdoc}
     */
    public function setGiftcardRecipientEmail($value)
    {
        return $this->setData(self::KEY_RECIPIENT_EMAIL, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getGiftcardMessage()
    {
        return $this->getData(self::KEY_MESSAGE);
    }

    /**
     * {@inheritdoc}
     */
    public function setGiftcardMessage($value)
    {
        return $this->setData(self::KEY_MESSAGE, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * {@inheritdoc}
     */
    public function setExtensionAttributes(
        \Magento\GiftCard\Api\Data\GiftCardOptionExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
